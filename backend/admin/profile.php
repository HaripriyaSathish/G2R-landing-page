<?php
declare(strict_types=1);
require __DIR__ . '/partials/layout.php';
$admin = require_admin();

if (is_post()) {
    verify_csrf();
    $full = DB::one('SELECT * FROM admins WHERE id = ?', [$admin['id']]);
    if (($_POST['action'] ?? '') === 'password') {
        $cur = (string) ($_POST['current'] ?? '');
        $new = (string) ($_POST['new'] ?? '');
        $cnf = (string) ($_POST['confirm'] ?? '');
        if (!password_verify($cur, $full['password_hash'])) {
            flash('error', 'Current password is incorrect.');
        } elseif (strlen($new) < 8) {
            flash('error', 'New password must be at least 8 characters.');
        } elseif ($new !== $cnf) {
            flash('error', 'New passwords do not match.');
        } else {
            DB::run('UPDATE admins SET password_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $admin['id']]);
            session_regenerate_id(true);
            flash('success', 'Password changed successfully.');
        }
    } else {
        $username = trim((string) ($_POST['username'] ?? ''));
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        if (!preg_match('/^[a-zA-Z0-9_.\-]{3,60}$/', $username)) {
            flash('error', 'Username must be 3–60 characters (letters, numbers, _ . -).');
        } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Please enter a valid email.');
        } elseif (DB::value('SELECT COUNT(*) FROM admins WHERE username = ? AND id <> ?', [$username, $admin['id']])) {
            flash('error', 'That username is already taken.');
        } else {
            DB::run('UPDATE admins SET username = ?, name = ?, email = ? WHERE id = ?', [$username, $name ?: 'Administrator', $email ?: null, $admin['id']]);
            flash('success', 'Profile updated.');
        }
    }
    redirect(admin_url('profile.php'));
}

admin_header('My Account', 'profile');
?>
<div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(340px,1fr));max-width:980px">
  <form class="card" method="post">
    <?= csrf_field() ?><input type="hidden" name="action" value="profile">
    <div class="card-head"><h2><i data-lucide="user-cog"></i> Profile</h2></div>
    <div class="card-body"><div class="form-grid">
      <div class="field"><label>Display name</label><input type="text" name="name" value="<?= e($admin['name']) ?>"></div>
      <div class="field"><label>Username (for login)</label><input type="text" name="username" value="<?= e($admin['username']) ?>" required></div>
      <div class="field"><label>Email</label><input type="email" name="email" value="<?= e($admin['email']) ?>"><small class="help">Used as the default address for SMTP test emails.</small></div>
      <?php if ($admin['last_login']): ?><p class="muted small" style="grid-column:span 2;margin:0">Last login: <?= e(date('d M Y, h:i A', strtotime($admin['last_login']))) ?></p><?php endif; ?>
    </div></div>
    <div class="form-actions"><button class="btn btn-primary" type="submit"><i data-lucide="save"></i> Save profile</button></div>
  </form>
  <form class="card" method="post" autocomplete="off">
    <?= csrf_field() ?><input type="hidden" name="action" value="password">
    <div class="card-head"><h2><i data-lucide="key-round"></i> Change password</h2></div>
    <div class="card-body"><div class="form-grid">
      <div class="field"><label>Current password</label><div class="pw-wrap"><input type="password" name="current" required autocomplete="current-password"><button type="button" data-pw-toggle><i data-lucide="eye"></i></button></div></div>
      <div class="field"><label>New password</label><div class="pw-wrap"><input type="password" name="new" minlength="8" required autocomplete="new-password"><button type="button" data-pw-toggle><i data-lucide="eye"></i></button></div><small class="help">At least 8 characters.</small></div>
      <div class="field"><label>Confirm new password</label><input type="password" name="confirm" minlength="8" required autocomplete="new-password"></div>
    </div></div>
    <div class="form-actions"><button class="btn btn-primary" type="submit"><i data-lucide="lock"></i> Update password</button></div>
  </form>
</div>
<?php admin_footer();
