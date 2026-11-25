<?php
declare(strict_types=1);
require __DIR__ . '/partials/layout.php';
require_admin();

$slug = (string) ($_GET['section'] ?? '');
$grp  = (string) ($_GET['grp'] ?? '');
$id   = (int) ($_GET['id'] ?? 0);
$def  = Content::def($slug);
$g    = Content::groupDef($slug, $grp);
if (!$def || !$g) {
    flash('error', 'Unknown item type.');
    redirect(admin_url('sections.php'));
}
$back = admin_url('section.php?slug=' . $slug);
$item = $id ? DB::one('SELECT * FROM section_items WHERE id = ? AND section_slug = ? AND grp = ?', [$id, $slug, $grp]) : null;
if ($id && !$item) {
    flash('error', 'Item not found.');
    redirect($back);
}

if (is_post()) {
    verify_csrf();
    if (($_POST['action'] ?? '') === 'delete' && $item) {
        DB::run('DELETE FROM section_items WHERE id = ?', [$item['id']]);
        flash('success', $g['singular'] . ' deleted.');
        redirect($back);
    }
    $data = Content::sanitize($g['fields'], (array) ($_POST['data'] ?? []), $item ? Content::decode($item['data']) : []);
    $errors = [];
    foreach ($g['fields'] as $f) {
        if (!empty($f['required']) && empty($data[$f['key']])) {
            $errors[] = $f['label'] . ' is required.';
        }
    }
    if ($errors) {
        flash('error', implode(' ', $errors));
    } else {
        $active = isset($_POST['is_active']) ? 1 : 0;
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        if ($item) {
            DB::run('UPDATE section_items SET data = ?, is_active = ? WHERE id = ?', [$json, $active, $item['id']]);
            flash('success', $g['singular'] . ' updated.');
        } else {
            $order = (int) DB::value('SELECT COALESCE(MAX(sort_order),0)+1 FROM section_items WHERE section_slug = ? AND grp = ?', [$slug, $grp]);
            DB::run('INSERT INTO section_items (section_slug, grp, sort_order, is_active, data) VALUES (?,?,?,?,?)', [$slug, $grp, $order, $active, $json]);
            flash('success', $g['singular'] . ' added.');
        }
        redirect(isset($_POST['add_another']) ? admin_url("item.php?section={$slug}&grp={$grp}") : $back);
    }
}

$data = $item ? Content::decode($item['data']) : [];
$title = $item ? 'Edit ' . strtolower($g['singular']) : 'Add ' . strtolower($g['singular']);

admin_header($title, 'sections', [
    'slug' => $slug,
    'breadcrumb' => ['Page Sections' => admin_url('sections.php'), $def['name'] => $back],
]);
?>
<form class="card" method="post" data-watch style="max-width:860px">
  <?= csrf_field() ?>
  <div class="card-head">
    <div><h2><i data-lucide="<?= e($def['icon']) ?>"></i> <?= e($g['label']) ?></h2><p><?= e($def['name']) ?></p></div>
    <label class="switch"><input type="checkbox" name="is_active" value="1" <?= !$item || $item['is_active'] ? 'checked' : '' ?>><span class="slider"></span><span>Visible</span></label>
  </div>
  <div class="card-body"><?= render_fields($g['fields'], $data) ?></div>
  <div class="form-actions sticky-actions">
    <a class="btn btn-ghost" href="<?= e($back) ?>">Cancel</a>
    <?php if (!$item): ?><button class="btn btn-outline" type="submit" name="add_another" value="1"><i data-lucide="copy-plus"></i> Save & add another</button><?php endif; ?>
    <button class="btn btn-primary" type="submit"><i data-lucide="save"></i> <?= $item ? 'Save changes' : 'Add ' . e(strtolower($g['singular'])) ?></button>
  </div>
</form>
<?php admin_footer();
