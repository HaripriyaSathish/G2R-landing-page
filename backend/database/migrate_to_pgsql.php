<?php
/**
 * Copy every table from the local MySQL database into PostgreSQL (Neon).
 *
 *   MySQL is only READ (SELECT). Nothing in MySQL is changed.
 *
 * Usage (PowerShell, from the project root):
 *   $env:DATABASE_URL = "postgresql://user:pass@host/db?sslmode=require"
 *   php -d extension=pdo_pgsql backend/database/migrate_to_pgsql.php           # stops if Postgres already has data
 *   php -d extension=pdo_pgsql backend/database/migrate_to_pgsql.php --fresh   # wipes the Postgres tables first
 *   php -d extension=pdo_pgsql backend/database/migrate_to_pgsql.php --images  # only (re)copy image files from backend/uploads
 *
 * Image files are copied too (into the media_files table), so they survive Render redeploys.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("Run from the command line.\n");
}

require dirname(__DIR__) . '/core/bootstrap.php';

$url = DB::databaseUrl();
if ($url === '') {
    exit("Set the DATABASE_URL environment variable to the Neon connection string first.\n");
}
if (!extension_loaded('pdo_pgsql')) {
    exit("pdo_pgsql is not loaded. Run with:  php -d extension=pdo_pgsql " . implode(' ', $argv) . "\n");
}
$fresh = in_array('--fresh', $argv, true);
$imagesOnly = in_array('--images', $argv, true);

// Source: local MySQL from config/config.php (read-only use)
$c = config('db');
$my = new PDO(
    sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $c['host'], $c['port'], $c['name'], $c['charset']),
    $c['user'], $c['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

// Target: PostgreSQL
[$dsn, $user, $pass] = DB::pgsqlDsn($url);
$pg = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);

$tables = ['admins', 'settings', 'media', 'sections', 'section_items', 'enquiries'];

echo "Creating schema...\n";
$pg->exec((string) file_get_contents(__DIR__ . '/schema.pgsql.sql'));

// Keep timestamps in the same local time as MySQL / PHP
$tz = config('timezone', 'UTC');
$dbName = (string) $pg->query('SELECT current_database()')->fetchColumn();
$pg->exec('ALTER DATABASE "' . str_replace('"', '""', $dbName) . '" SET timezone TO ' . $pg->quote($tz));

if (!$imagesOnly) {
$existing = 0;
foreach ($tables as $t) {
    $existing += (int) $pg->query("SELECT COUNT(*) FROM {$t}")->fetchColumn();
}
if ($existing > 0 && !$fresh) {
    exit("PostgreSQL already has {$existing} rows. Re-run with --fresh to replace them with the MySQL data.\n");
}

$pg->beginTransaction();
try {
    $pg->exec('SET LOCAL timezone TO ' . $pg->quote($tz));
    if ($fresh) {
        $pg->exec('TRUNCATE media_files, ' . implode(', ', $tables) . ' RESTART IDENTITY CASCADE');
    }
    foreach ($tables as $t) {
        $pgCols = $pg->query("SELECT column_name FROM information_schema.columns WHERE table_schema = 'public' AND table_name = " . $pg->quote($t))->fetchAll(PDO::FETCH_COLUMN);
        $rows = $my->query("SELECT * FROM `{$t}`")->fetchAll();
        if (!$rows) {
            echo str_pad($t, 15) . " 0 rows\n";
            continue;
        }
        $cols = array_values(array_intersect(array_keys($rows[0]), $pgCols));
        $sql = sprintf('INSERT INTO %s (%s) VALUES (%s)', $t, implode(', ', $cols), implode(', ', array_fill(0, count($cols), '?')));
        $stmt = $pg->prepare($sql);
        foreach ($rows as $r) {
            $stmt->execute(array_map(static fn ($col) => $r[$col], $cols));
        }
        if (in_array('id', $cols, true)) {
            $pg->exec("SELECT setval(pg_get_serial_sequence('{$t}', 'id'), GREATEST((SELECT COALESCE(MAX(id), 0) FROM {$t}), 1), (SELECT COUNT(*) > 0 FROM {$t}))");
        }
        echo str_pad($t, 15) . ' ' . count($rows) . " rows\n";
    }
    $pg->commit();
} catch (Throwable $e) {
    $pg->rollBack();
    exit('Failed, nothing was written: ' . $e->getMessage() . "\n");
}

// Verify counts match
echo "\nVerify (MySQL -> PostgreSQL):\n";
$ok = true;
foreach ($tables as $t) {
    $a = (int) $my->query("SELECT COUNT(*) FROM `{$t}`")->fetchColumn();
    $b = (int) $pg->query("SELECT COUNT(*) FROM {$t}")->fetchColumn();
    $ok = $ok && $a === $b;
    echo str_pad($t, 15) . " {$a} -> {$b}" . ($a === $b ? '' : '  MISMATCH') . "\n";
}
echo $ok ? "\nDone. All data copied.\n" : "\nCounts differ, check above.\n";
} // end data copy

// Image files: backend/uploads/* -> media_files (bytes in Postgres, survive Render redeploys)
echo "\nCopying image files into PostgreSQL...\n";
$save = $pg->prepare("INSERT INTO media_files (media_id, data) VALUES (?, decode(?, 'base64'))
                      ON CONFLICT (media_id) DO UPDATE SET data = EXCLUDED.data");
$copied = 0;
$bytes = 0;
$missing = [];
foreach ($pg->query('SELECT id, file_name FROM media ORDER BY id')->fetchAll() as $m) {
    $path = UPLOAD_PATH . '/' . basename($m['file_name']);
    if (!is_file($path)) {
        $missing[] = $m['file_name'];
        continue;
    }
    $data = (string) file_get_contents($path);
    $save->execute([(int) $m['id'], base64_encode($data)]);
    $copied++;
    $bytes += strlen($data);
    echo "  #{$m['id']} {$m['file_name']} (" . round(strlen($data) / 1024) . " KB)\n";
}
$inDb = (int) $pg->query('SELECT COUNT(*) FROM media_files')->fetchColumn();
$total = (int) $pg->query('SELECT COUNT(*) FROM media')->fetchColumn();
echo "\nImages copied: {$copied} (" . round($bytes / 1048576, 1) . " MB). In database: {$inDb} of {$total} media rows.\n";
if ($missing) {
    echo 'Missing on disk (not copied): ' . implode(', ', $missing) . "\n";
}
