<?php
/**
 * GET /api/health.php — deployment check. Shows whether the database connects.
 * Never prints the password: credentials in the URL and error text are masked.
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$mask = static fn (string $s): string => preg_replace('#(://[^:/@\s]+:)[^@\s]+@#', '$1****@', preg_replace('/npg_[A-Za-z0-9]+/', 'npg_****', $s));

$url = trim((string) (getenv('DATABASE_URL') ?: ''));
$out = [
    'ok'                => false,
    'php'               => PHP_VERSION,
    'pdo_drivers'       => class_exists('PDO') ? PDO::getAvailableDrivers() : [],
    'database_url_set'  => $url !== '',
    'database_url'      => $url !== '' ? $mask($url) : null,
    'looks_quoted'      => $url !== '' && (str_starts_with($url, '"') || str_starts_with($url, "'")),
    'driver'            => null,
    'error'             => null,
];

try {
    require dirname(__DIR__) . '/core/bootstrap.php';
    $out['driver'] = DB::driver();
    $out['tables'] = [
        'settings' => (int) DB::value('SELECT COUNT(*) FROM settings'),
        'sections' => (int) DB::value('SELECT COUNT(*) FROM sections'),
        'media'    => (int) DB::value('SELECT COUNT(*) FROM media'),
    ];
    if (DB::isPg()) {
        $out['tables']['media_files'] = (int) DB::value('SELECT COUNT(*) FROM media_files');
    }
    $out['ok'] = true;
} catch (Throwable $e) {
    $out['error'] = get_class($e) . ': ' . $mask($e->getMessage());
    error_log('[health] ' . $out['error']);
}

http_response_code($out['ok'] ? 200 : 500);
echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
