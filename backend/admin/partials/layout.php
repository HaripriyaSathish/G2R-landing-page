<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/core/bootstrap.php';
require_once __DIR__ . '/fields.php';

function admin_header(string $title, string $active = '', array $opts = []): void
{
    $admin   = require_admin();
    $logo    = media_url((int) setting('site_logo', '0'), false);
    $site    = setting('site_name', 'G2R Global Solutions');
    $newEnq  = (int) DB::value("SELECT COUNT(*) FROM enquiries WHERE status = 'new'");
    $front   = setting('frontend_url', 'http://localhost:5173');
    $schema  = Content::schema();
    $nav = [
        ['dashboard', 'index.php', 'layout-dashboard', 'Dashboard'],
        ['sections', 'sections.php', 'layers', 'Page Sections'],
        ['media', 'media.php', 'images', 'Media Library'],
        ['enquiries', 'enquiries.php', 'inbox', 'Enquiries', $newEnq],
        ['_label', 'Configuration'],
        ['settings', 'settings.php', 'settings', 'Site Settings'],
        ['theme', 'settings.php?tab=theme', 'palette', 'Theme & Colours'],
        ['smtp', 'smtp.php', 'mail-check', 'SMTP / Email'],
        ['profile', 'profile.php', 'user-cog', 'My Account'],
    ];
    ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> · <?= e($site) ?> Admin</title>
<?php if ($logo): ?><link rel="icon" href="<?= e($logo) ?>"><?php endif; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(admin_url('assets/admin.css')) ?>?v=5">
<style>:root{--primary:<?= e(setting('theme_primary', '#111C50')) ?>;--indigo:<?= e(setting('theme_indigo', '#1D138C')) ?>;--dark:<?= e(setting('theme_dark', '#0D1338')) ?>;--accent:<?= e(setting('theme_accent', '#F36B16')) ?>;}</style>
<script src="https://unpkg.com/lucide@0.460.0/dist/umd/lucide.min.js"></script>
</head>
<body>
<div class="app" id="app">
  <aside class="sidebar" id="sidebar">
    <a class="brand" href="<?= e(admin_url('index.php')) ?>">
      <?php if ($logo): ?><span class="brand-logo"><img src="<?= e($logo) ?>" alt=""></span><?php endif; ?>
      <span class="brand-text"><strong><?= e($site) ?></strong><small>Admin Panel</small></span>
    </a>
    <nav class="nav">
      <?php foreach ($nav as $n): ?>
        <?php if ($n[0] === '_label'): ?>
          <div class="nav-label"><?= e($n[1]) ?></div>
        <?php else: ?>
          <a class="nav-link <?= $active === $n[0] ? 'active' : '' ?>" href="<?= e(admin_url($n[1])) ?>">
            <i data-lucide="<?= e($n[2]) ?>"></i><span><?= e($n[3]) ?></span>
            <?php if (!empty($n[4])): ?><em class="pill"><?= (int) $n[4] ?></em><?php endif; ?>
          </a>
          <?php if ($n[0] === 'sections' && $active === 'sections'): ?>
            <div class="nav-sub">
              <?php foreach ($schema as $slug => $def): ?>
                <a class="<?= ($opts['slug'] ?? '') === $slug ? 'active' : '' ?>" href="<?= e(admin_url('section.php?slug=' . $slug)) ?>"><i data-lucide="<?= e($def['icon'] ?? 'square') ?>"></i><span><?= e($def['name']) ?></span></a>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        <?php endif; ?>
      <?php endforeach; ?>
    </nav>
    <a class="nav-visit" href="<?= e($front) ?>" target="_blank" rel="noopener"><i data-lucide="external-link"></i> View Website</a>
  </aside>
  <div class="sidebar-backdrop" data-toggle-sidebar></div>

  <main class="main">
    <header class="topbar">
      <button class="icon-btn menu-btn" data-toggle-sidebar aria-label="Menu"><i data-lucide="menu"></i></button>
      <div class="topbar-title">
        <?php if (!empty($opts['breadcrumb'])): ?>
          <div class="crumbs"><?php foreach ($opts['breadcrumb'] as $label => $href): ?><a href="<?= e($href) ?>"><?= e($label) ?></a><i data-lucide="chevron-right"></i><?php endforeach; ?></div>
        <?php endif; ?>
        <h1><?= e($title) ?></h1>
      </div>
      <div class="topbar-actions">
        <a class="btn btn-ghost hide-sm" href="<?= e($front) ?>" target="_blank" rel="noopener"><i data-lucide="monitor"></i> Preview site</a>
        <div class="dropdown">
          <button class="avatar-btn" data-dropdown><span class="avatar"><?= e(strtoupper(substr($admin['name'] ?: $admin['username'], 0, 1))) ?></span><span class="hide-sm"><?= e($admin['name'] ?: $admin['username']) ?></span><i data-lucide="chevron-down"></i></button>
          <div class="dropdown-menu">
            <a href="<?= e(admin_url('profile.php')) ?>"><i data-lucide="user-cog"></i> My account</a>
            <a href="<?= e(admin_url('logout.php')) ?>"><i data-lucide="log-out"></i> Log out</a>
          </div>
        </div>
      </div>
    </header>
    <div class="content">
      <?php foreach (['success', 'error'] as $t): if ($m = flash($t)): ?>
        <div class="toast-data" data-type="<?= $t ?>" data-message="<?= e($m) ?>"></div>
      <?php endif; endforeach; ?>
<?php
}

function admin_footer(): void
{
    ?>
    </div>
  </main>
</div>
<div id="toasts" class="toasts"></div>
<?php include __DIR__ . '/modals.php'; ?>
<script>window.G2R = { csrf: <?= json_encode(csrf_token()) ?>, base: <?= json_encode(admin_url('')) ?> };</script>
<script src="<?= e(admin_url('assets/admin.js')) ?>?v=4"></script>
</body>
</html>
<?php
}
