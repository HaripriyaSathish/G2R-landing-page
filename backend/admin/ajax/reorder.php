<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/core/bootstrap.php';
require_admin(true);
verify_csrf();

$type = (string) ($_POST['type'] ?? '');
$ids  = array_values(array_filter(array_map('intval', explode(',', (string) ($_POST['ids'] ?? '')))));
$table = ['sections' => 'sections', 'items' => 'section_items'][$type] ?? null;
if (!$table || !$ids) {
    json_response(['ok' => false, 'error' => 'Invalid request'], 400);
}
$pdo = DB::pdo();
$pdo->beginTransaction();
$stmt = $pdo->prepare("UPDATE {$table} SET sort_order = ? WHERE id = ?");
foreach ($ids as $i => $id) {
    $stmt->execute([($i + 1) * 10, $id]);
}
$pdo->commit();
json_response(['ok' => true]);
