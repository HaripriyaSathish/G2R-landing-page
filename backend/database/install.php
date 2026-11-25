<?php
/**
 * One-time installer (CLI only).
 *
 *   php backend/database/install.php --images="C:\path\to\G2R IMAGES"  [--fresh]
 *
 * --fresh   drops and recreates the database (all content is lost!)
 * --images  folder whose images are imported into the media library
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$opts   = getopt('', ['fresh', 'images::']);
$fresh  = isset($opts['fresh']);
$imgDir = $opts['images'] ?? (dirname(__DIR__) . '/database/seed-images');

$config = require dirname(__DIR__) . '/config/config.php';
$db     = $config['db'];

// 1. Create database & tables (connect without dbname first)
$pdo = new PDO("mysql:host={$db['host']};port={$db['port']};charset=utf8mb4", $db['user'], $db['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
if ($fresh) {
    $pdo->exec("DROP DATABASE IF EXISTS `{$db['name']}`");
    echo "Dropped database {$db['name']}\n";
}
$sql = file_get_contents(__DIR__ . '/schema.sql');
$sql = str_replace('g2r_landing', $db['name'], $sql);
foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
    $pdo->exec($stmt);
}
echo "Schema ready\n";

require dirname(__DIR__) . '/core/bootstrap.php';
$seed = require __DIR__ . '/seed.php';

// 2. Admin user
if (!DB::value('SELECT COUNT(*) FROM admins')) {
    DB::run('INSERT INTO admins (username, password_hash, name, email) VALUES (?,?,?,?)', [
        'admin', password_hash('admin123', PASSWORD_DEFAULT), 'Administrator', 'g2rglobalsolutions@gmail.com',
    ]);
    echo "Admin created (admin / admin123)\n";
}

// 3. Media import
$mediaMap = [];
foreach (DB::all('SELECT id, original_name FROM media') as $m) {
    $mediaMap[$m['original_name']] = (int) $m['id'];
}
if (is_dir($imgDir)) {
    $count = 0;
    foreach (scandir($imgDir) as $file) {
        $path = $imgDir . DIRECTORY_SEPARATOR . $file;
        if (!is_file($path) || isset($mediaMap[$file])) {
            continue;
        }
        try {
            $row = media_store($path, $file, false);
            $mediaMap[$file] = (int) $row['id'];
            $count++;
        } catch (Throwable $e) {
            echo "  ! skipped {$file}: {$e->getMessage()}\n";
        }
    }
    echo "Imported {$count} images into media library\n";
} else {
    echo "Image folder not found ({$imgDir}) - skipping media import\n";
}

$resolve = function ($value) use (&$resolve, $mediaMap) {
    if (is_array($value)) {
        return array_map($resolve, $value);
    }
    if (is_string($value) && str_starts_with($value, '@img:')) {
        $name = substr($value, 5);
        return $mediaMap[$name] ?? null;
    }
    return $value;
};

// 4. Settings (only missing keys)
foreach ($seed['settings'] as $k => $v) {
    $exists = DB::value('SELECT COUNT(*) FROM settings WHERE skey = ?', [$k]);
    if (!$exists) {
        $v = $resolve($v);
        save_setting($k, $v === null ? '' : $v);
    }
}
echo "Settings ready\n";

// 5. Sections + items (only missing sections)
$order = 0;
foreach ($seed['sections'] as $slug => $sec) {
    $order += 10;
    $def = Content::def($slug);
    if (DB::value('SELECT COUNT(*) FROM sections WHERE slug = ?', [$slug])) {
        continue;
    }
    DB::run('INSERT INTO sections (slug, name, sort_order, is_active, data) VALUES (?,?,?,?,?)', [
        $slug, $def['name'], $order, 1, json_encode($resolve($sec['data']), JSON_UNESCAPED_UNICODE),
    ]);
    foreach ($sec['items'] as $grp => $items) {
        $i = 0;
        foreach ($items as $item) {
            $active = $item['_active'] ?? 1;
            unset($item['_active']);
            DB::run('INSERT INTO section_items (section_slug, grp, sort_order, is_active, data) VALUES (?,?,?,?,?)', [
                $slug, $grp, ++$i, $active, json_encode($resolve($item), JSON_UNESCAPED_UNICODE),
            ]);
        }
    }
    echo "  + section {$slug}\n";
}
echo "Done.\n";
