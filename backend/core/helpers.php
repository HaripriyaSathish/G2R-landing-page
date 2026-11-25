<?php
declare(strict_types=1);

/* ---------- Output ---------- */

function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function json_response($data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/* ---------- URLs ---------- */

/** Root URL of the backend (no trailing slash), e.g. http://localhost:8000 */
function base_url(string $path = ''): string
{
    $configured = rtrim((string) config('app_url', ''), '/');
    if ($configured === '') {
        $https  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
        $scheme = $https ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
        $configured = $scheme . '://' . $host . base_path();
    }
    return $configured . ($path !== '' ? '/' . ltrim($path, '/') : '');
}

/** URL path prefix where the backend folder is mounted ('' or '/backend' etc.) */
function base_path(): string
{
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    $dir = preg_replace('#/(admin|api)(/.*)?$#', '', $dir);
    return rtrim((string) $dir, '/');
}

function admin_url(string $path = ''): string
{
    return base_path() . '/admin/' . ltrim($path, '/');
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/* ---------- Settings ---------- */

function settings_all(bool $refresh = false): array
{
    static $cache = null;
    if ($cache === null || $refresh) {
        $cache = [];
        foreach (DB::all('SELECT skey, svalue FROM settings') as $row) {
            $cache[$row['skey']] = $row['svalue'];
        }
    }
    return $cache;
}

function setting(string $key, $default = '')
{
    $all = settings_all();
    return array_key_exists($key, $all) && $all[$key] !== null ? $all[$key] : $default;
}

function save_setting(string $key, $value): void
{
    DB::upsert('settings', 'skey', 'svalue', $key, is_array($value) ? json_encode($value) : (string) $value);
}

/* ---------- Media ---------- */

function media_find(?int $id): ?array
{
    static $cache = null;
    if (!$id) {
        return null;
    }
    if ($cache === null) {
        // One query for the whole (small) media table instead of one per image
        $cache = [];
        foreach (DB::all('SELECT * FROM media') as $m) {
            $cache[(int) $m['id']] = $m;
        }
    }
    if (!array_key_exists($id, $cache)) {
        $cache[$id] = DB::one('SELECT * FROM media WHERE id = ?', [$id]);
    }
    return $cache[$id];
}

function media_url(?int $id, bool $absolute = true): string
{
    $m = media_find($id);
    if (!$m) {
        return '';
    }
    $rel = 'uploads/' . rawurlencode($m['file_name']);
    return $absolute ? base_url($rel) : base_path() . '/' . $rel;
}

/**
 * Store an uploaded or local file in the media library.
 * @return array media row
 */
function media_store(string $tmpPath, string $originalName, bool $isUpload = true, string $alt = ''): array
{
    if (!is_file($tmpPath)) {
        throw new RuntimeException('File not found.');
    }
    $size = filesize($tmpPath);
    if ($size > config('upload.max_size')) {
        throw new RuntimeException('File is larger than ' . round(config('upload.max_size') / 1048576) . ' MB.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = (string) $finfo->file($tmpPath);
    $ext   = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    // Some finfo builds don't know avif/svg; fall back on extension for those two only.
    if ($ext === 'avif' && in_array($mime, ['application/octet-stream', 'image/heif', 'image/heic'], true)) {
        $mime = 'image/avif';
    }
    if ($ext === 'svg' && in_array($mime, ['text/plain', 'text/xml', 'application/xml', 'image/svg'], true)) {
        $mime = 'image/svg+xml';
    }
    $allowed = config('upload.allowed');
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Unsupported file type (' . $mime . '). Allowed: JPG, PNG, WEBP, GIF, AVIF, SVG.');
    }
    if ($mime === 'image/svg+xml') {
        $svg = (string) file_get_contents($tmpPath);
        if (preg_match('/<script|on\w+\s*=|javascript:/i', $svg)) {
            throw new RuntimeException('SVG contains scripts and was rejected.');
        }
    }

    $base = preg_replace('/[^a-z0-9]+/', '-', strtolower(pathinfo($originalName, PATHINFO_FILENAME)));
    $base = trim(substr((string) $base, 0, 60), '-') ?: 'image';
    $fileName = $base . '-' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];

    if (!is_dir(UPLOAD_PATH)) {
        mkdir(UPLOAD_PATH, 0775, true);
    }
    $dest = UPLOAD_PATH . '/' . $fileName;
    $ok = $isUpload ? move_uploaded_file($tmpPath, $dest) : copy($tmpPath, $dest);
    if (!$ok) {
        throw new RuntimeException('Could not save the file.');
    }

    $w = $h = null;
    $dim = @getimagesize($dest);
    if ($dim) {
        [$w, $h] = $dim;
    }
    if ($alt === '') {
        $alt = ucwords(trim(preg_replace('/[-_]+/', ' ', pathinfo($originalName, PATHINFO_FILENAME))));
    }

    $id = DB::insert(
        'INSERT INTO media (file_name, original_name, mime, size, width, height, alt_text) VALUES (?,?,?,?,?,?,?)',
        [$fileName, $originalName, $mime, $size, $w, $h, mb_substr($alt, 0, 250)]
    );
    return DB::one('SELECT * FROM media WHERE id = ?', [$id]);
}

function human_size(int $bytes): string
{
    if ($bytes >= 1048576) {
        return round($bytes / 1048576, 1) . ' MB';
    }
    return max(1, (int) round($bytes / 1024)) . ' KB';
}

/* ---------- Session / Auth / CSRF ---------- */

function start_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_name('g2r_admin');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        ]);
        session_start();
    }
}

function current_admin(): ?array
{
    start_session();
    if (empty($_SESSION['admin_id'])) {
        return null;
    }
    static $admin = null;
    if ($admin === null) {
        $admin = DB::one('SELECT id, username, name, email, last_login FROM admins WHERE id = ?', [$_SESSION['admin_id']]);
    }
    return $admin;
}

function require_admin(bool $ajax = false): array
{
    $admin = current_admin();
    if (!$admin) {
        if ($ajax) {
            json_response(['ok' => false, 'error' => 'Session expired. Please log in again.'], 401);
        }
        redirect(admin_url('login.php'));
    }
    return $admin;
}

function csrf_token(): string
{
    start_session();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $sent = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'json')) {
            json_response(['ok' => false, 'error' => 'Security token expired. Refresh the page and try again.'], 419);
        }
        flash('error', 'Security token expired. Please try again.');
        redirect($_SERVER['HTTP_REFERER'] ?? admin_url('index.php'));
    }
}

function flash(string $type, ?string $message = null)
{
    start_session();
    if ($message !== null) {
        $_SESSION['flash'][$type] = $message;
        return null;
    }
    $msg = $_SESSION['flash'][$type] ?? null;
    unset($_SESSION['flash'][$type]);
    return $msg;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function client_ip(): string
{
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 64);
}

/* ---------- CORS ---------- */

function send_cors_headers(): void
{
    $origin  = $_SERVER['HTTP_ORIGIN'] ?? '';
    $allowed = config('cors_origins', []);
    $extra   = array_filter(array_map('trim', explode(',', (string) setting('cors_origins_extra', ''))));
    if ($origin !== '' && in_array($origin, array_merge($allowed, $extra), true)) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Vary: Origin');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
        header('Access-Control-Max-Age: 86400');
    }
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}
