<?php
/**
 * Router for PHP's built-in dev server:
 *   php -S localhost:8000 -t backend backend/router.php
 * (Apache/XAMPP uses the .htaccess files instead.)
 */
$path = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');

// Never serve internal folders
if (preg_match('#^/(config|core|database)(/|$)#', $path) || str_contains($path, '..')) {
    http_response_code(404);
    echo 'Not found';
    return true;
}
// Never execute scripts from uploads
if (str_starts_with($path, '/uploads/') && preg_match('/\.(php\d?|phtml|phar)$/i', $path)) {
    http_response_code(404);
    return true;
}
// Single-service mode (Render): serve the built React site for everything that is not
// /api, /admin or /uploads. FRONTEND_DIST defaults to ../frontend/dist when it exists.
$dist = getenv('FRONTEND_DIST') ?: dirname(__DIR__) . '/frontend/dist';
$isBackend = (bool) preg_match('#^/(api|admin|uploads)(/|$)#', $path);
if (!$isBackend && is_file($dist . '/index.html') && getenv('SERVE_FRONTEND') !== '0') {
    $file = realpath($dist . $path);
    $root = realpath($dist);
    if ($file === false || !str_starts_with($file, $root) || !is_file($file)) {
        $file = $root . '/index.html'; // SPA fallback
    }
    $types = [
        'html' => 'text/html; charset=utf-8', 'js' => 'text/javascript; charset=utf-8', 'css' => 'text/css; charset=utf-8',
        'json' => 'application/json', 'svg' => 'image/svg+xml', 'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
        'webp' => 'image/webp', 'avif' => 'image/avif', 'gif' => 'image/gif', 'ico' => 'image/x-icon', 'woff2' => 'font/woff2',
        'woff' => 'font/woff', 'ttf' => 'font/ttf', 'txt' => 'text/plain; charset=utf-8', 'xml' => 'application/xml', 'webmanifest' => 'application/manifest+json',
    ];
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
    header(str_starts_with($path, '/assets/') ? 'Cache-Control: public, max-age=31536000, immutable' : 'Cache-Control: no-cache');
    header('Content-Length: ' . filesize($file));
    readfile($file);
    return true;
}
if ($path === '/' || $path === '') {
    header('Location: /admin/');
    return true;
}
if ($path === '/admin') {
    header('Location: /admin/');
    return true;
}
$file = __DIR__ . $path;
if (is_dir($file) && is_file($file . '/index.php')) {
    $_SERVER['SCRIPT_NAME'] = rtrim($path, '/') . '/index.php';
    require $file . '/index.php';
    return true;
}
if (str_starts_with($path, '/uploads/') && is_file($file)) {
    header('Cache-Control: public, max-age=2592000');
    header('Access-Control-Allow-Origin: *');
    if (str_ends_with(strtolower($file), '.avif')) {
        header('Content-Type: image/avif');
        readfile($file);
        return true;
    }
}
return false; // let the built-in server handle the file
