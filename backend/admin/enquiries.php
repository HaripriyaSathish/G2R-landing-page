<?php
declare(strict_types=1);
require __DIR__ . '/partials/layout.php';
require_admin();

$statuses = ['new' => 'New', 'read' => 'Read', 'replied' => 'Replied', 'archived' => 'Archived'];

if (is_post()) {
    verify_csrf();
    $ids = array_filter(array_map('intval', (array) ($_POST['ids'] ?? [])));
    $act = (string) ($_POST['bulk'] ?? '');
    if ($ids) {
        $in = implode(',', array_fill(0, count($ids), '?'));
        if ($act === 'delete') {
            DB::run("DELETE FROM enquiries WHERE id IN ($in)", $ids);
            flash('success', count($ids) . ' enquiry(s) deleted.');
        } elseif (isset($statuses[$act])) {
            DB::run("UPDATE enquiries SET status = ? WHERE id IN ($in)", array_merge([$act], $ids));
            flash('success', count($ids) . ' enquiry(s) marked as ' . strtolower($statuses[$act]) . '.');
        }
    } else {
        flash('error', 'Select at least one enquiry.');
    }
    redirect($_SERVER['REQUEST_URI']);
}

$status = (string) ($_GET['status'] ?? '');
$q      = trim((string) ($_GET['q'] ?? ''));
$where  = [];
$params = [];
if (isset($statuses[$status])) {
    $where[] = 'status = ?';
    $params[] = $status;
}
if ($q !== '') {
    $lk = DB::like();
    $where[] = "(name $lk ? OR email $lk ? OR phone $lk ? OR message $lk ? OR enquiry_type $lk ?)";
    array_push($params, "%$q%", "%$q%", "%$q%", "%$q%", "%$q%");
}
$sqlWhere = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// CSV export
if (($_GET['export'] ?? '') === 'csv') {
    $rows = DB::all("SELECT id, created_at, name, email, phone, enquiry_type, message, status, mail_status FROM enquiries $sqlWhere ORDER BY id DESC", $params);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="enquiries-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['ID', 'Date', 'Name', 'Email', 'Phone', 'Enquiry type', 'Message', 'Status', 'Email status']);
    foreach ($rows as $r) {
        // prevent CSV formula injection
        fputcsv($out, array_map(fn ($v) => preg_match('/^[=+\-@]/', (string) $v) ? "'" . $v : $v, $r));
    }
    exit;
}

$page    = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;
$total   = (int) DB::value("SELECT COUNT(*) FROM enquiries $sqlWhere", $params);
$pages   = max(1, (int) ceil($total / $perPage));
$offset  = ($page - 1) * $perPage;
$rows    = DB::all("SELECT * FROM enquiries $sqlWhere ORDER BY id DESC LIMIT $perPage OFFSET $offset", $params);
$counts  = array_column(DB::all('SELECT status, COUNT(*) c FROM enquiries GROUP BY status'), 'c', 'status');
$qs = fn (array $extra) => '?' . http_build_query(array_filter(array_merge(['status' => $status, 'q' => $q], $extra), fn ($v) => $v !== '' && $v !== null));

admin_header('Enquiries', 'enquiries');
?>
<div class="tabs">
  <a class="tab <?= $status === '' ? 'active' : '' ?>" href="<?= e($qs(['status' => ''])) ?>"><i data-lucide="inbox"></i> All <span class="badge"><?= array_sum($counts) ?></span></a>
  <?php foreach ($statuses as $k => $label): ?>
    <a class="tab <?= $status === $k ? 'active' : '' ?>" href="<?= e($qs(['status' => $k])) ?>"><?= e($label) ?> <span class="badge <?= $k ?>"><?= (int) ($counts[$k] ?? 0) ?></span></a>
  <?php endforeach; ?>
</div>

<form class="card" method="post" id="bulkForm">
  <?= csrf_field() ?>
  <div class="card-head">
    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
      <select name="bulk" style="width:auto"><option value="">Bulk action…</option><?php foreach ($statuses as $k => $l): ?><option value="<?= $k ?>">Mark as <?= e(strtolower($l)) ?></option><?php endforeach; ?><option value="delete">Delete</option></select>
      <button class="btn btn-sm btn-outline" type="submit" data-no-loading>Apply</button>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <div class="input-icon"><i data-lucide="search"></i><input type="search" form="searchForm" name="q" value="<?= e($q) ?>" placeholder="Search name, email, phone…"></div>
      <a class="btn btn-sm btn-outline" href="<?= e($qs(['export' => 'csv'])) ?>"><i data-lucide="download"></i> Export CSV</a>
    </div>
  </div>
  <?php if (!$rows): ?>
    <div class="empty"><i data-lucide="mail-open"></i><p><?= $q || $status ? 'No enquiries match your filter.' : 'No enquiries yet.' ?></p></div>
  <?php else: ?>
  <div class="table-wrap"><table class="table">
    <tr><th style="width:36px"><input type="checkbox" id="checkAll"></th><th>From</th><th>Enquiry type</th><th class="hide-sm">Message</th><th>Status</th><th class="hide-sm">Email</th><th>Received</th></tr>
    <?php foreach ($rows as $r): ?>
      <tr class="<?= $r['status'] === 'new' ? 'unread' : '' ?>">
        <td><input type="checkbox" name="ids[]" value="<?= (int) $r['id'] ?>"></td>
        <td><a href="enquiry.php?id=<?= (int) $r['id'] ?>"><div class="cell-title"><?= e($r['name']) ?></div><div class="cell-sub"><?= e($r['email']) ?><?= $r['phone'] ? ' · ' . e($r['phone']) : '' ?></div></a></td>
        <td><?= e($r['enquiry_type'] ?: '—') ?></td>
        <td class="hide-sm cell-sub" style="max-width:280px"><?= e(mb_strimwidth((string) $r['message'], 0, 90, '…')) ?></td>
        <td><span class="badge <?= e($r['status']) ?>"><?= e(ucfirst($r['status'])) ?></span></td>
        <td class="hide-sm"><span class="badge <?= e($r['mail_status']) ?>" title="<?= e($r['mail_error'] ?? '') ?>"><?= e(ucfirst($r['mail_status'])) ?></span></td>
        <td class="cell-sub"><?= e(date('d M Y, h:i A', strtotime($r['created_at']))) ?></td>
      </tr>
    <?php endforeach; ?>
  </table></div>
  <?php if ($pages > 1): ?>
    <div class="form-actions" style="justify-content:space-between"><span class="muted">Page <?= $page ?> of <?= $pages ?> · <?= $total ?> results</span>
      <div><?php if ($page > 1): ?><a class="btn btn-sm btn-outline" href="<?= e($qs(['page' => $page - 1])) ?>">← Prev</a><?php endif; ?>
      <?php if ($page < $pages): ?><a class="btn btn-sm btn-outline" href="<?= e($qs(['page' => $page + 1])) ?>">Next →</a><?php endif; ?></div></div>
  <?php endif; ?>
  <?php endif; ?>
</form>
<form id="searchForm" method="get"><input type="hidden" name="status" value="<?= e($status) ?>"></form>
<?php admin_footer(); ?>
<script>
const ca=document.getElementById('checkAll'); if(ca) ca.onchange=()=>document.querySelectorAll('input[name="ids[]"]').forEach(c=>c.checked=ca.checked);
document.getElementById('bulkForm').addEventListener('submit',async e=>{const s=e.target.bulk.value;if(!s){e.preventDefault();toast('Choose a bulk action','error');return;}
 if(s==='delete'&&!e.target.dataset.ok){e.preventDefault();if(await confirmBox('Delete selected enquiries?','They will be permanently removed.')){e.target.dataset.ok=1;e.target.submit();}}});
</script>
