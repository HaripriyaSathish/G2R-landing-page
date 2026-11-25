<?php
declare(strict_types=1);
require __DIR__ . '/partials/layout.php';
require_admin();

$id = (int) ($_GET['id'] ?? 0);
$r  = DB::one('SELECT * FROM enquiries WHERE id = ?', [$id]);
if (!$r) {
    flash('error', 'Enquiry not found.');
    redirect(admin_url('enquiries.php'));
}

if (is_post()) {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? 'save');
    if ($action === 'delete') {
        DB::run('DELETE FROM enquiries WHERE id = ?', [$id]);
        flash('success', 'Enquiry deleted.');
        redirect(admin_url('enquiries.php'));
    }
    if ($action === 'resend') {
        try {
            $html = email_layout('Website enquiry #' . $id, '<p><strong>' . e($r['name']) . '</strong> (' . e($r['email']) . ', ' . e($r['phone'] ?: '-') . ')</p><p>Type: ' . e($r['enquiry_type'] ?: '-') . '</p><div style="padding:14px;background:#F6F7FC;border-radius:8px">' . nl2br(e($r['message'])) . '</div>');
            Mailer::fromSettings()->send(setting('smtp_notify_to') ?: setting('contact_email'), 'Enquiry #' . $id . ' - ' . $r['name'], $html, $r['email'], $r['name']);
            DB::run("UPDATE enquiries SET mail_status = 'sent', mail_error = NULL WHERE id = ?", [$id]);
            flash('success', 'Notification email sent.');
        } catch (Throwable $e) {
            DB::run("UPDATE enquiries SET mail_status = 'failed', mail_error = ? WHERE id = ?", [mb_substr($e->getMessage(), 0, 490), $id]);
            flash('error', 'Email failed: ' . $e->getMessage());
        }
        redirect(admin_url('enquiry.php?id=' . $id));
    }
    $status = in_array($_POST['status'] ?? '', ['new', 'read', 'replied', 'archived'], true) ? $_POST['status'] : $r['status'];
    DB::run('UPDATE enquiries SET status = ?, notes = ? WHERE id = ?', [$status, trim((string) ($_POST['notes'] ?? '')), $id]);
    flash('success', 'Enquiry updated.');
    redirect(admin_url('enquiry.php?id=' . $id));
}

if ($r['status'] === 'new') {
    DB::run("UPDATE enquiries SET status = 'read' WHERE id = ?", [$id]);
    $r['status'] = 'read';
}
$prev = DB::value('SELECT id FROM enquiries WHERE id > ? ORDER BY id ASC LIMIT 1', [$id]);
$next = DB::value('SELECT id FROM enquiries WHERE id < ? ORDER BY id DESC LIMIT 1', [$id]);
$replySubject = rawurlencode('Re: Your enquiry with ' . setting('site_name', 'us'));
$phoneDigits = preg_replace('/\D+/', '', (string) $r['phone']);

admin_header('Enquiry #' . $id, 'enquiries', ['breadcrumb' => ['Enquiries' => admin_url('enquiries.php')]]);
?>
<div class="grid grid-2">
  <div class="card">
    <div class="card-head">
      <div><h2><i data-lucide="user"></i> <?= e($r['name']) ?></h2><p>Received <?= e(date('l, d M Y \a\t h:i A', strtotime($r['created_at']))) ?></p></div>
      <div style="display:flex;gap:6px">
        <?php if ($prev): ?><a class="icon-btn sm" href="?id=<?= (int) $prev ?>" title="Newer"><i data-lucide="chevron-left"></i></a><?php endif; ?>
        <?php if ($next): ?><a class="icon-btn sm" href="?id=<?= (int) $next ?>" title="Older"><i data-lucide="chevron-right"></i></a><?php endif; ?>
      </div>
    </div>
    <div class="card-body">
      <dl class="kv" style="font-size:14px">
        <dt>Email</dt><dd><a href="mailto:<?= e($r['email']) ?>"><?= e($r['email']) ?></a></dd>
        <dt>Phone</dt><dd><?= $r['phone'] ? '<a href="tel:' . e($r['phone']) . '">' . e($r['phone']) . '</a>' : '—' ?></dd>
        <dt>Enquiry type</dt><dd><?= e($r['enquiry_type'] ?: '—') ?></dd>
        <dt>Email notification</dt><dd><span class="badge <?= e($r['mail_status']) ?>"><?= e(ucfirst($r['mail_status'])) ?></span> <?= $r['mail_error'] ? '<small class="muted">' . e($r['mail_error']) . '</small>' : '' ?></dd>
        <dt>IP address</dt><dd class="muted"><?= e($r['ip']) ?></dd>
      </dl>
      <h3 style="font-size:14px;margin:20px 0 8px">Message</h3>
      <div style="padding:16px 18px;background:var(--light);border-left:4px solid var(--accent);border-radius:10px;line-height:1.65;white-space:pre-wrap"><?= e($r['message']) ?></div>
      <div style="display:flex;gap:8px;margin-top:20px;flex-wrap:wrap">
        <a class="btn btn-primary" href="mailto:<?= e($r['email']) ?>?subject=<?= $replySubject ?>"><i data-lucide="reply"></i> Reply by email</a>
        <?php if ($phoneDigits): ?>
          <a class="btn btn-outline" href="tel:<?= e($r['phone']) ?>"><i data-lucide="phone"></i> Call</a>
          <a class="btn btn-outline" href="https://wa.me/<?= e(strlen($phoneDigits) === 10 ? '91' . $phoneDigits : $phoneDigits) ?>" target="_blank" rel="noopener"><i data-lucide="message-circle"></i> WhatsApp</a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="stack">
    <form class="card" method="post">
      <?= csrf_field() ?>
      <div class="card-head"><h2><i data-lucide="clipboard-list"></i> Status & notes</h2></div>
      <div class="card-body">
        <div class="field" style="margin-bottom:14px"><label>Status</label>
          <select name="status"><?php foreach (['new' => 'New', 'read' => 'Read', 'replied' => 'Replied', 'archived' => 'Archived'] as $k => $l): ?><option value="<?= $k ?>" <?= $r['status'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></div>
        <div class="field"><label>Internal notes</label><textarea name="notes" rows="5" placeholder="Only visible to admins"><?= e($r['notes']) ?></textarea></div>
      </div>
      <div class="form-actions"><button class="btn btn-primary" type="submit"><i data-lucide="save"></i> Save</button></div>
    </form>
    <div class="card"><div class="card-body" style="display:flex;gap:8px;flex-wrap:wrap">
      <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="resend"><button class="btn btn-outline btn-sm" type="submit"><i data-lucide="send"></i> Resend notification</button></form>
      <form method="post" data-confirm="This enquiry will be permanently deleted."><?= csrf_field() ?><input type="hidden" name="action" value="delete"><button class="btn btn-ghost danger btn-sm" type="submit"><i data-lucide="trash-2"></i> Delete</button></form>
    </div></div>
  </div>
</div>
<?php admin_footer();
