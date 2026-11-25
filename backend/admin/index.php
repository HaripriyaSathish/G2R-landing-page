<?php
declare(strict_types=1);
require __DIR__ . '/partials/layout.php';
$admin = require_admin();

$stats = [
    'new'      => (int) DB::value("SELECT COUNT(*) FROM enquiries WHERE status = 'new'"),
    'total'    => (int) DB::value('SELECT COUNT(*) FROM enquiries'),
    'media'    => (int) DB::value('SELECT COUNT(*) FROM media'),
    'sections' => (int) DB::value('SELECT COUNT(*) FROM sections WHERE is_active = 1'),
    'all_sections' => (int) DB::value('SELECT COUNT(*) FROM sections'),
    'items'    => (int) DB::value('SELECT COUNT(*) FROM section_items'),
];
$recent = DB::all('SELECT * FROM enquiries ORDER BY id DESC LIMIT 6');
$week   = DB::all(DB::isPg()
    ? "SELECT TO_CHAR(created_at::date, 'YYYY-MM-DD') d, COUNT(*) c FROM enquiries WHERE created_at >= CURRENT_DATE - 6 GROUP BY created_at::date"
    : "SELECT DATE(created_at) d, COUNT(*) c FROM enquiries WHERE created_at >= CURDATE() - INTERVAL 6 DAY GROUP BY DATE(created_at)");
$byDay  = array_column($week, 'c', 'd');
$max    = max(1, ...array_map('intval', array_values($byDay) ?: [0]));
$smtpOn = Mailer::isConfigured();
$hour   = (int) date('G');
$greet  = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

admin_header('Dashboard', 'dashboard');
?>
<div class="welcome">
  <div>
    <h2><?= e($greet) ?>, <span><?= e($admin['name'] ?: $admin['username']) ?></span></h2>
    <p>Here's what's happening with your website today.</p>
  </div>
  <a class="btn btn-primary" href="sections.php"><i data-lucide="pencil-line"></i> Edit page content</a>
</div>

<?php if (!$smtpOn): ?>
<div class="alert warn" style="margin-bottom:22px"><i data-lucide="mail-warning"></i><div><strong>Email notifications are off.</strong> Enquiries are saved here, but no email is sent. <a href="smtp.php">Set up SMTP →</a></div></div>
<?php endif; ?>

<div class="grid grid-4">
  <a class="card stat" href="enquiries.php?status=new"><div class="stat-icon orange"><i data-lucide="inbox"></i></div><div><strong><?= $stats['new'] ?></strong><span>New enquiries</span></div></a>
  <a class="card stat" href="enquiries.php"><div class="stat-icon"><i data-lucide="messages-square"></i></div><div><strong><?= $stats['total'] ?></strong><span>Total enquiries</span></div></a>
  <a class="card stat" href="sections.php"><div class="stat-icon"><i data-lucide="layers"></i></div><div><strong><?= $stats['sections'] ?>/<?= $stats['all_sections'] ?></strong><span>Sections live</span></div></a>
  <a class="card stat" href="media.php"><div class="stat-icon orange"><i data-lucide="images"></i></div><div><strong><?= $stats['media'] ?></strong><span>Media files</span></div></a>
</div>

<div class="grid grid-2" style="margin-top:22px">
  <div class="card">
    <div class="card-head"><h2><i data-lucide="inbox"></i> Latest enquiries</h2><a class="btn btn-sm btn-outline" href="enquiries.php">View all</a></div>
    <?php if (!$recent): ?>
      <div class="empty"><i data-lucide="mail-open"></i><p>No enquiries yet. They'll appear here when visitors submit the contact form.</p></div>
    <?php else: ?>
      <div class="table-wrap"><table class="table">
        <tr><th>From</th><th>Enquiry</th><th>Status</th><th>Received</th></tr>
        <?php foreach ($recent as $r): ?>
          <tr class="<?= $r['status'] === 'new' ? 'unread' : '' ?>" onclick="location='enquiry.php?id=<?= (int) $r['id'] ?>'" style="cursor:pointer">
            <td><div class="cell-title"><?= e($r['name']) ?></div><div class="cell-sub"><?= e($r['email']) ?></div></td>
            <td><?= e($r['enquiry_type'] ?: '—') ?></td>
            <td><span class="badge <?= e($r['status']) ?>"><?= e(ucfirst($r['status'])) ?></span></td>
            <td class="cell-sub"><?= e(date('d M, h:i A', strtotime($r['created_at']))) ?></td>
          </tr>
        <?php endforeach; ?>
      </table></div>
    <?php endif; ?>
  </div>

  <div class="stack">
    <div class="card">
      <div class="card-head"><h2><i data-lucide="bar-chart-3"></i> Last 7 days</h2></div>
      <div class="card-body">
        <div style="display:flex;align-items:flex-end;gap:10px;height:130px">
          <?php for ($i = 6; $i >= 0; $i--): $d = date('Y-m-d', strtotime("-{$i} day")); $c = (int) ($byDay[$d] ?? 0); ?>
            <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:6px;height:100%;justify-content:flex-end" title="<?= $c ?> enquiries">
              <small class="muted"><?= $c ?></small>
              <div style="width:100%;border-radius:8px 8px 4px 4px;background:<?= $i === 0 ? 'var(--accent)' : 'linear-gradient(180deg,var(--indigo),var(--primary))' ?>;height:<?= max(6, (int) round($c / $max * 90)) ?>px"></div>
              <small class="muted"><?= date('D', strtotime($d)) ?></small>
            </div>
          <?php endfor; ?>
        </div>
      </div>
    </div>
    <?php
      $checks = [
          ['Upload your logo', setting('site_logo') !== '', 'settings.php'],
          ['Check contact details', setting('contact_phone') !== '' && setting('contact_email') !== '', 'settings.php?tab=contact'],
          ['Add social media links', (bool) array_filter([setting('social_linkedin'), setting('social_instagram'), setting('social_facebook')], fn ($u) => $u !== '' && $u !== '#'), 'settings.php?tab=social'],
          ['Set up email (SMTP)', $smtpOn, 'smtp.php'],
          ['Write SEO title & description', setting('seo_title') !== '' && setting('seo_description') !== '', 'settings.php?tab=seo'],
          ['Change the default password', !password_verify('admin123', (string) DB::value('SELECT password_hash FROM admins WHERE id = ?', [$admin['id']])), 'profile.php'],
      ];
      $done = count(array_filter($checks, fn ($c) => $c[1]));
    ?>
    <?php if ($done < count($checks)): ?>
    <div class="card">
      <div class="card-head"><h2><i data-lucide="list-checks"></i> Getting started</h2><span class="badge"><?= $done ?>/<?= count($checks) ?> done</span></div>
      <div class="card-body">
        <div class="progress"><span style="width:<?= (int) round($done / count($checks) * 100) ?>%"></span></div>
        <div class="checklist">
          <?php foreach ($checks as [$label, $ok, $href]): ?>
            <a class="check-row <?= $ok ? 'done' : '' ?>" href="<?= e($href) ?>"><span class="tick"><?= $ok ? '<i data-lucide="check"></i>' : '' ?></span><span><?= e($label) ?></span><i data-lucide="chevron-right"></i></a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>
    <div class="card">
      <div class="card-head"><h2><i data-lucide="zap"></i> Quick actions</h2></div>
      <div class="card-body" style="display:grid;gap:10px">
        <a class="btn btn-outline" href="section.php?slug=hero" style="justify-content:flex-start"><i data-lucide="sparkles"></i> Edit hero banner</a>
        <a class="btn btn-outline" href="media.php" style="justify-content:flex-start"><i data-lucide="upload"></i> Upload images</a>
        <a class="btn btn-outline" href="settings.php" style="justify-content:flex-start"><i data-lucide="phone"></i> Update contact details</a>
        <a class="btn btn-outline" href="smtp.php" style="justify-content:flex-start"><i data-lucide="mail-check"></i> Email settings <span class="badge <?= $smtpOn ? 'on' : 'off' ?>" style="margin-left:auto"><?= $smtpOn ? 'On' : 'Off' ?></span></a>
      </div>
    </div>
  </div>
</div>
<?php admin_footer();
