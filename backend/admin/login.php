<?php
declare(strict_types=1);
require dirname(__DIR__) . '/core/bootstrap.php';
start_session();

if (current_admin()) {
    redirect(admin_url('index.php'));
}

$error = '';
$username = '';
$lockedUntil = $_SESSION['login_lock'] ?? 0;

if (is_post()) {
    verify_csrf();
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($lockedUntil > time()) {
        $error = 'Too many attempts. Try again in ' . ceil(($lockedUntil - time()) / 60) . ' minute(s).';
    } else {
        $admin = DB::one('SELECT * FROM admins WHERE username = ?', [$username]);
        if ($admin && password_verify($password, $admin['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int) $admin['id'];
            unset($_SESSION['login_fail'], $_SESSION['login_lock']);
            DB::run('UPDATE admins SET last_login = NOW() WHERE id = ?', [$admin['id']]);
            if (password_needs_rehash($admin['password_hash'], PASSWORD_DEFAULT)) {
                DB::run('UPDATE admins SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $admin['id']]);
            }
            flash('success', 'Welcome back, ' . ($admin['name'] ?: $admin['username']) . '!');
            redirect(admin_url('index.php'));
        }
        $_SESSION['login_fail'] = ($_SESSION['login_fail'] ?? 0) + 1;
        if ($_SESSION['login_fail'] >= 5) {
            $_SESSION['login_lock'] = time() + 300;
            $_SESSION['login_fail'] = 0;
        }
        $error = 'Invalid username or password.';
    }
}

$logo = media_url((int) setting('site_logo', '0'), false);
$site = setting('site_name', 'G2R Global Solutions');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Login · <?= e($site) ?></title>
<?php if ($logo): ?><link rel="icon" href="<?= e($logo) ?>"><?php endif; ?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(admin_url('assets/admin.css')) ?>?v=4">
<script src="https://unpkg.com/lucide@0.460.0/dist/umd/lucide.min.js"></script>
</head>
<body>
<div class="login-page">
  <section class="login-art">
    <div class="login-brand">
      <?php if ($logo): ?><span class="brand-logo"><img src="<?= e($logo) ?>" alt=""></span><?php endif; ?>
      <div><strong><?= e($site) ?></strong><small><?= e(setting('site_tagline', 'Admin Panel')) ?></small></div>
    </div>
    <div>
      <h2>Manage your website <span>content</span> with ease.</h2>
      <p>Update every section, image, enquiry and email setting of your landing page from one place.</p>
    </div>
    <div class="float-dots"><span style="left:12%;top:22%"></span><span style="left:70%;top:14%;animation-delay:1s;width:7px;height:7px"></span><span style="left:40%;top:78%;animation-delay:2s;background:#fff;opacity:.5"></span></div>
    <p class="small" style="opacity:.6">© <?= date('Y') ?> <?= e($site) ?></p>
  </section>
  <section class="login-form-wrap">
    <form class="login-form" method="post" autocomplete="on">
      <?= csrf_field() ?>
      <h1>Welcome back 👋</h1>
      <p>Sign in to the admin panel</p>
      <?php if ($error): ?><div class="alert err" style="margin-bottom:16px"><i data-lucide="circle-alert"></i><?= e($error) ?></div><?php endif; ?>
      <div class="field">
        <label for="username">Username</label>
        <div class="input-icon"><i data-lucide="user"></i><input type="text" id="username" name="username" value="<?= e($username) ?>" required autofocus autocomplete="username"></div>
      </div>
      <div class="field">
        <label for="password">Password</label>
        <div class="input-icon pw-wrap"><i data-lucide="lock"></i><input type="password" id="password" name="password" required autocomplete="current-password"><button type="button" data-pw-toggle aria-label="Show password"><i data-lucide="eye"></i></button></div>
      </div>
      <button class="btn btn-primary" type="submit"><i data-lucide="log-in"></i> Sign in</button>
    </form>
  </section>
</div>
<script>
lucide.createIcons();
document.querySelectorAll('[data-pw-toggle]').forEach(b=>b.onclick=()=>{const i=b.previousElementSibling;i.type=i.type==='password'?'text':'password';b.innerHTML='<i data-lucide="'+(i.type==='password'?'eye':'eye-off')+'"></i>';lucide.createIcons();});
document.querySelector('form').addEventListener('submit',e=>e.target.querySelector('button[type=submit]').classList.add('loading'));
</script>
</body>
</html>
