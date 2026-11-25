<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/core/bootstrap.php';
require_admin(true);
verify_csrf();

$type  = (string) ($_POST['type'] ?? '');
$id    = (int) ($_POST['id'] ?? 0);
$value = !empty($_POST['value']) ? 1 : 0;
$table = ['section' => 'sections', 'item' => 'section_items'][$type] ?? null;
if (!$table || !$id) {
    json_response(['ok' => false, 'error' => 'Invalid request'], 400);
}
DB::run("UPDATE {$table} SET is_active = ? WHERE id = ?", [$value, $id]);
json_response(['ok' => true, 'message' => $value ? 'Now visible on website' : 'Hidden from website']);
