<?php
declare(strict_types=1);

/**
 * Tiny PDO wrapper. Works with MySQL (local) and PostgreSQL (Neon / Render).
 * If the DATABASE_URL environment variable is set (postgresql://user:pass@host/db?sslmode=require)
 * it is used; otherwise the MySQL settings in config/config.php are used.
 */
final class DB
{
    private static ?PDO $pdo = null;
    private static string $driver = 'mysql';

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $opts = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $url = self::databaseUrl();
            if ($url !== '') {
                self::$driver = 'pgsql';
                // Client-side prepares: one network round trip per query and safe with Neon's pooler
                $opts[PDO::ATTR_EMULATE_PREPARES] = true;
                [$dsn, $user, $pass] = self::pgsqlDsn($url);
                try {
                    self::$pdo = new PDO($dsn, $user, $pass, $opts);
                } catch (PDOException $e) {
                    // Old libpq (no SNI, e.g. XAMPP on Windows): Neon asks for the endpoint id explicitly
                    if (!preg_match('/endpoint id is not specified|sni/i', $e->getMessage())) {
                        throw $e;
                    }
                    [$dsn, $user, $pass] = self::pgsqlDsn($url, true);
                    self::$pdo = new PDO($dsn, $user, $pass, $opts);
                }
            } else {
                $c = config('db');
                $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $c['host'], $c['port'], $c['name'], $c['charset']);
                self::$driver = 'mysql';
                self::$pdo = new PDO($dsn, $c['user'], $c['pass'], $opts);
            }
        }
        return self::$pdo;
    }

    public static function databaseUrl(): string
    {
        $raw = trim((string) (getenv('DATABASE_URL') ?: ($_SERVER['DATABASE_URL'] ?? '')));
        // Tolerate common pasting mistakes: quotes, spaces, or Neon's "psql 'postgresql://...'" snippet
        if (preg_match('#postgres(?:ql)?://[^\s\'"]+#i', $raw, $m)) {
            return $m[0];
        }
        return trim($raw, " \t\n\r\0\x0B'\"");
    }

    /** postgresql://user:pass@host:port/db?sslmode=require → [dsn, user, pass] */
    public static function pgsqlDsn(string $url, bool $neonEndpointOption = false): array
    {
        $p = parse_url($url);
        if (!$p || empty($p['host'])) {
            throw new RuntimeException('DATABASE_URL is not a valid postgresql:// URL.');
        }
        parse_str($p['query'] ?? '', $q);
        $parts = [
            'host=' . $p['host'],
            'port=' . ($p['port'] ?? 5432),
            'dbname=' . ltrim($p['path'] ?? '/postgres', '/'),
            'sslmode=' . ($q['sslmode'] ?? 'require'),
        ];
        // Only for older libpq builds without SNI: Neon then needs the endpoint id passed explicitly.
        // Modern libpq (Render/Docker) sends SNI itself and Neon rejects a second, different value.
        if ($neonEndpointOption && str_ends_with($p['host'], '.neon.tech')) {
            $endpoint = preg_replace('/-pooler$/', '', explode('.', $p['host'])[0]);
            $parts[] = "options='endpoint=" . $endpoint . "'";
        }
        return ['pgsql:' . implode(';', $parts), rawurldecode($p['user'] ?? ''), rawurldecode($p['pass'] ?? '')];
    }

    public static function driver(): string
    {
        self::pdo();
        return self::$driver;
    }

    public static function isPg(): bool
    {
        return self::driver() === 'pgsql';
    }

    /** Case-insensitive LIKE operator for the current driver */
    public static function like(): string
    {
        return self::isPg() ? 'ILIKE' : 'LIKE';
    }

    public static function run(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function all(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    public static function one(string $sql, array $params = []): ?array
    {
        $row = self::run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public static function value(string $sql, array $params = [])
    {
        $v = self::run($sql, $params)->fetchColumn();
        return $v === false ? null : $v;
    }

    /** INSERT and return the new id (RETURNING on Postgres, safe behind Neon's pooler) */
    public static function insert(string $sql, array $params = []): int
    {
        if (self::isPg()) {
            return (int) self::run($sql . ' RETURNING id', $params)->fetchColumn();
        }
        self::run($sql, $params);
        return (int) self::pdo()->lastInsertId();
    }

    public static function lastId(): int
    {
        return (int) self::pdo()->lastInsertId();
    }

    /** Insert or update a settings-style key/value row */
    public static function upsert(string $table, string $keyCol, string $valCol, string $key, ?string $value): void
    {
        $sql = self::isPg()
            ? "INSERT INTO {$table} ({$keyCol}, {$valCol}) VALUES (?, ?) ON CONFLICT ({$keyCol}) DO UPDATE SET {$valCol} = EXCLUDED.{$valCol}, updated_at = CURRENT_TIMESTAMP"
            : "INSERT INTO {$table} ({$keyCol}, {$valCol}) VALUES (?, ?) ON DUPLICATE KEY UPDATE {$valCol} = VALUES({$valCol})";
        self::run($sql, [$key, $value]);
    }
}
