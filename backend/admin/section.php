<?php
declare(strict_types=1);
require __DIR__ . '/partials/layout.php';
require_admin();

$slug = (string) ($_GET['slug'] ?? '');
$def  = Content::def($slug);
$row  = $def ? DB::one('SELECT * FROM sections WHERE slug = ?', [$slug]) : null;
if (!$def || !$row) {
    flash('error', 'Section not found.');
    redirect(admin_url('sections.php'));
}

if (is_post()) {
    verify_csrf();
    $data = Content::sanitize($def['fields'], (array) ($_POST['data'] ?? []), Content::decode($row['data']));
    DB::run('UPDATE sections SET data = ?, is_active = ? WHERE id = ?', [
        json_encode($data, JSON_UNESCAPED_UNICODE), isset($_POST['is_active']) ? 1 : 0, $row['id'],
    ]);
    flash('success', $def['name'] . ' saved successfully.');
    redirect(admin_url('section.php?slug=' . $slug));
}

$data = Content::decode($row['data']);
$front = rtrim(setting('frontend_url', 'http://localhost:5173'), '/');

admin_header($def['name'], 'sections', [
    'slug' => $slug,
    'breadcrumb' => ['Page Sections' => admin_url('sections.php')],
]);
?>
<div class="stack">
  <form class="card" method="post" data-watch>
    <?= csrf_field() ?>
    <div class="card-head">
      <div><h2><i data-lucide="<?= e($def['icon']) ?>"></i> Section content</h2><p><?= e($def['description']) ?></p></div>
      <div style="display:flex;gap:12px;align-items:center">
        <label class="switch"><input type="checkbox" name="is_active" value="1" <?= $row['is_active'] ? 'checked' : '' ?>><span class="slider"></span><span>Visible on website</span></label>
        <a class="btn btn-sm btn-ghost" href="<?= e($front . '/#' . $slug) ?>" target="_blank" rel="noopener"><i data-lucide="eye"></i> View</a>
      </div>
    </div>
    <div class="card-body">
      <?= render_fields($def['fields'], $data) ?>
    </div>
    <div class="form-actions sticky-actions">
      <a class="btn btn-ghost" href="sections.php">Back</a>
      <button class="btn btn-primary" type="submit"><i data-lucide="save"></i> Save changes</button>
    </div>
  </form>

  <?php foreach ($def['groups'] as $grp => $g):
      $items = Content::items($slug, $grp); ?>
    <div class="card">
      <div class="card-head">
        <div><h2><i data-lucide="list"></i> <?= e($g['label']) ?> <span class="badge"><?= count($items) ?></span></h2><p>Drag to reorder · switch to show/hide · click edit to change.</p></div>
        <a class="btn btn-primary btn-sm" href="item.php?section=<?= e($slug) ?>&grp=<?= e($grp) ?>"><i data-lucide="plus"></i> Add <?= e(strtolower($g['singular'])) ?></a>
      </div>
      <div class="card-body">
        <?php if (!$items): ?>
          <div class="empty"><i data-lucide="inbox"></i><p>No <?= e(strtolower($g['label'])) ?> yet.</p></div>
        <?php else: ?>
        <div class="sortable-list" data-sortable="items">
          <?php foreach ($items as $i => $it):
              $d = Content::decode($it['data']);
              $title = $d[$g['title']] ?? ('#' . $it['id']);
              $sub = $d[$g['subtitle'] ?? ''] ?? '';
              if (is_array($sub)) { $sub = implode(', ', $sub); }
          ?>
            <div class="sort-item <?= $it['is_active'] ? '' : 'inactive' ?>" data-id="<?= (int) $it['id'] ?>">
              <span class="drag-handle"><i data-lucide="grip-vertical"></i></span>
              <span class="order-num"><?= $i + 1 ?></span>
              <?php if (!empty($g['image'])): $u = media_url(isset($d[$g['image']]) ? (int) $d[$g['image']] : null, false); ?>
                <span class="sort-thumb"><?= $u ? '<img src="' . e($u) . '" alt="">' : '<i data-lucide="image-off"></i>' ?></span>
              <?php elseif (!empty($g['icon'])): ?>
                <span class="sort-thumb icon"><i data-lucide="<?= e($d[$g['icon']] ?? 'circle') ?: 'circle' ?>"></i></span>
              <?php endif; ?>
              <a class="sort-body" href="item.php?section=<?= e($slug) ?>&grp=<?= e($grp) ?>&id=<?= (int) $it['id'] ?>">
                <strong><?= e($title) ?></strong>
                <?php if ($sub): ?><small><?= e($sub) ?></small><?php endif; ?>
              </a>
              <div class="sort-actions">
                <label class="switch" title="Show on website"><input type="checkbox" data-toggle="item" data-id="<?= (int) $it['id'] ?>" <?= $it['is_active'] ? 'checked' : '' ?>><span class="slider"></span></label>
                <a class="icon-btn sm" title="Edit" href="item.php?section=<?= e($slug) ?>&grp=<?= e($grp) ?>&id=<?= (int) $it['id'] ?>"><i data-lucide="pencil"></i></a>
                <form method="post" action="item.php?section=<?= e($slug) ?>&grp=<?= e($grp) ?>&id=<?= (int) $it['id'] ?>" data-confirm="Delete “<?= e($title) ?>”? This cannot be undone." data-no-loading="1">
                  <?= csrf_field() ?><input type="hidden" name="action" value="delete">
                  <button class="icon-btn sm danger" title="Delete" type="submit"><i data-lucide="trash-2"></i></button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php admin_footer();
