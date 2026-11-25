<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/core/bootstrap.php';
require_admin(true);

$action = (string) ($_GET['action'] ?? $_POST['action'] ?? 'list');
if ($action !== 'list') {
    verify_csrf();
}

function media_json(array $m): array
{
    return [
        'id' => (int) $m['id'],
        'url' => media_url((int) $m['id'], false),
        'name' => $m['original_name'],
        'alt' => $m['alt_text'],
        'mime' => $m['mime'],
        'size' => (int) $m['size'],
        'size_h' => human_size((int) $m['size']),
        'width' => $m['width'] ? (int) $m['width'] : null,
        'height' => $m['height'] ? (int) $m['height'] : null,
        'created' => $m['created_at'],
    ];
}

try {
    switch ($action) {
        case 'list':
            $q = trim((string) ($_GET['q'] ?? ''));
            $rows = $q === ''
                ? DB::all('SELECT * FROM media ORDER BY id DESC')
                : DB::all('SELECT * FROM media WHERE original_name ' . DB::like() . ' ? OR alt_text ' . DB::like() . ' ? ORDER BY id DESC', ["%{$q}%", "%{$q}%"]);
            json_response(['ok' => true, 'items' => array_map('media_json', $rows)]);

        case 'upload':
            $files = $_FILES['files'] ?? null;
            if (!$files) {
                json_response(['ok' => false, 'error' => 'No files received. The file may exceed the server upload limit (' . ini_get('upload_max_filesize') . ').'], 400);
            }
            $items = [];
            $errors = [];
            $count = is_array($files['name']) ? count($files['name']) : 1;
            for ($i = 0; $i < $count; $i++) {
                $name = is_array($files['name']) ? $files['name'][$i] : $files['name'];
                $tmp  = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
                $err  = is_array($files['error']) ? $files['error'][$i] : $files['error'];
                if ($err !== UPLOAD_ERR_OK) {
                    $errors[] = $name . ': upload failed' . (in_array($err, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) ? ' (file too large)' : '');
                    continue;
                }
                try {
                    $items[] = media_json(media_store($tmp, $name, true));
                } catch (Throwable $e) {
                    $errors[] = $name . ': ' . $e->getMessage();
                }
            }
            json_response(['ok' => true, 'items' => $items, 'errors' => $errors]);

        case 'update':
            $id = (int) ($_POST['id'] ?? 0);
            DB::run('UPDATE media SET alt_text = ? WHERE id = ?', [mb_substr(trim((string) ($_POST['alt'] ?? '')), 0, 250), $id]);
            json_response(['ok' => true, 'message' => 'Image details saved']);

        case 'delete':
            $id = (int) ($_POST['id'] ?? 0);
            $m = DB::one('SELECT * FROM media WHERE id = ?', [$id]);
            if (!$m) {
                json_response(['ok' => false, 'error' => 'Image not found'], 404);
            }
            DB::run('DELETE FROM media WHERE id = ?', [$id]);
            $path = UPLOAD_PATH . '/' . basename($m['file_name']);
            if (is_file($path)) {
                @unlink($path);
            }
            json_response(['ok' => true, 'message' => 'Image deleted']);

        default:
            json_response(['ok' => false, 'error' => 'Unknown action'], 400);
    }
} catch (Throwable $e) {
    json_response(['ok' => false, 'error' => $e->getMessage()], 500);
}
