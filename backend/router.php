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
    // SEO endpoints built from Admin → Site Settings → SEO
    // Your own robots.txt / sitemap.xml (frontend/public/) always win; generated ones are only a fallback
    if (($path === '/robots.txt' || $path === '/sitemap.xml') && !is_file($dist . $path)) {
        require_once __DIR__ . '/core/bootstrap.php';
        $xml = $path === '/sitemap.xml';
        header('Content-Type: ' . ($xml ? 'application/xml' : 'text/plain') . '; charset=utf-8');
        header('Cache-Control: public, max-age=3600');
        echo $xml ? seo_sitemap_xml() : seo_robots_txt();
        return true;
    }
    $file = realpath($dist . $path);
    $root = realpath($dist);
    if ($file === false || !str_starts_with($file, $root) || !is_file($file)) {
        $file = $root . '/index.html'; // SPA fallback
    }
    // HTML page: write the SEO tags into <head> on the server
    if (str_ends_with($file, '.html')) {
        $html = (string) file_get_contents($file);
        try {
            require_once __DIR__ . '/core/bootstrap.php';
            $html = seo_inject($html);
        } catch (Throwable $e) {
            error_log('[seo] ' . $e->getMessage()); // still serve the page if the database is unreachable
        }
        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: no-cache');
        echo $html;
        return true;
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
// Image missing on disk (fresh Render deploy/restart)? Rebuild it from the database.
if (str_starts_with($path, '/uploads/') && !is_file($file) && getenv('DATABASE_URL')
    && preg_match('#^/uploads/[A-Za-z0-9._-]+$#', $path)) {
    require_once __DIR__ . '/core/bootstrap.php';
    try {
        $restored = media_blob_restore(substr($path, strlen('/uploads/')));
    } catch (Throwable $e) {
        error_log('[uploads] ' . $e->getMessage());
        $restored = null;
    }
    if ($restored === null) {
        http_response_code(404);
        echo 'Not found';
        return true;
    }
    $file = $restored;
    $mime = (string) (DB::value('SELECT mime FROM media WHERE file_name = ?', [basename($file)]) ?: 'application/octet-stream');
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($file));
    header('Cache-Control: public, max-age=2592000');
    header('Access-Control-Allow-Origin: *');
    header('X-Content-Type-Options: nosniff');
    readfile($file);
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
