<?php
/**
 * GET /api/site.php  -> all site settings + active sections & items (JSON)
 * Optional: ?section=hero  -> one section only
 */
declare(strict_types=1);
require dirname(__DIR__) . '/core/bootstrap.php';

send_cors_headers();
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['ok' => false, 'error' => 'Method not allowed'], 405);
}

try {
    $payload = Content::sitePayload();
    if (!empty($_GET['section'])) {
        $slug = (string) $_GET['section'];
        $payload['sections'] = array_values(array_filter($payload['sections'], static fn ($s) => $s['slug'] === $slug));
    }
    header('Cache-Control: no-cache');
    json_response(['ok' => true] + $payload);
} catch (Throwable $e) {
    error_log('[api/site] ' . $e->getMessage());
    json_response(['ok' => false, 'error' => 'Unable to load content.'], 500);
}
