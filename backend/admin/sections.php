<?php
declare(strict_types=1);
require __DIR__ . '/partials/layout.php';
require_admin();

$schema = Content::schema();
$rows = DB::all('SELECT s.*, (SELECT COUNT(*) FROM section_items i WHERE i.section_slug = s.slug) AS item_count FROM sections s ORDER BY sort_order, id');

admin_header('Page Sections', 'sections');
?>
<div class="card">
  <div class="card-head">
    <div><h2><i data-lucide="layers"></i> Landing page sections</h2><p>Drag <i data-lucide="grip-vertical" style="width:14px;height:14px;vertical-align:-2px"></i> to reorder the page, use the switch to show/hide, click a section to edit.</p></div>
    <div class="input-icon"><i data-lucide="search"></i><input type="search" placeholder="Find a section…" data-filter-list="#sectionList"></div>
  </div>
  <div class="card-body">
    <div class="sortable-list section-cards" data-sortable="sections" id="sectionList">
      <?php foreach ($rows as $i => $r): $def = $schema[$r['slug']] ?? null; if (!$def) continue; ?>
        <div class="sort-item <?= $r['is_active'] ? '' : 'inactive' ?>" data-id="<?= (int) $r['id'] ?>">
          <span class="drag-handle" title="Drag to reorder"><i data-lucide="grip-vertical"></i></span>
          <span class="order-num"><?= $i + 1 ?></span>
          <span class="sec-icon <?= $r['is_active'] ? '' : 'off' ?>"><i data-lucide="<?= e($def['icon']) ?>"></i></span>
          <a class="sort-body" href="section.php?slug=<?= e($r['slug']) ?>">
            <strong><?= e($def['name']) ?></strong>
            <small><?= e($def['description']) ?></small>
          </a>
          <div class="sort-actions">
            <?php if ($def['groups']): ?><span class="badge hide-sm"><?= (int) $r['item_count'] ?> items</span><?php endif; ?>
            <span class="cell-sub hide-sm">Updated <?= e(date('d M', strtotime($r['updated_at']))) ?></span>
            <label class="switch" title="Show on website"><input type="checkbox" data-toggle="section" data-id="<?= (int) $r['id'] ?>" <?= $r['is_active'] ? 'checked' : '' ?>><span class="slider"></span></label>
            <a class="btn btn-sm btn-navy" href="section.php?slug=<?= e($r['slug']) ?>"><i data-lucide="pencil"></i> Edit</a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php admin_footer();
