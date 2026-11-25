<?php
declare(strict_types=1);
require __DIR__ . '/partials/layout.php';
require_admin();

// Work out where each image is used (sections, items, settings)
$usage = [];
$imageKeys = function (array $fields): array {
    return array_values(array_map(fn ($f) => $f['key'], array_filter($fields, fn ($f) => ($f['type'] ?? '') === 'image')));
};
foreach (DB::all('SELECT slug, data FROM sections') as $s) {
    $def = Content::def($s['slug']);
    if (!$def) continue;
    $d = Content::decode($s['data']);
    foreach ($imageKeys($def['fields']) as $k) {
        if (!empty($d[$k])) $usage[(int) $d[$k]][] = $def['name'];
    }
}
foreach (DB::all('SELECT section_slug, grp, data FROM section_items') as $it) {
    $g = Content::groupDef($it['section_slug'], $it['grp']);
    if (!$g) continue;
    $d = Content::decode($it['data']);
    foreach ($imageKeys($g['fields']) as $k) {
        if (!empty($d[$k])) $usage[(int) $d[$k]][] = Content::def($it['section_slug'])['name'] . ' › ' . ($d[$g['title']] ?? $g['singular']);
    }
}
foreach (['site_logo' => 'Site logo', 'footer_logo' => 'Footer logo', 'site_favicon' => 'Favicon', 'seo_og_image' => 'Social share image'] as $k => $label) {
    if ($v = (int) setting($k, '0')) $usage[$v][] = $label;
}

$media = DB::all('SELECT * FROM media ORDER BY id DESC');
$totalSize = array_sum(array_column($media, 'size'));

admin_header('Media Library', 'media');
?>
<div class="stack">
  <label class="dropzone" id="dropzone">
    <input type="file" accept="image/*,.avif,.svg" multiple hidden id="fileInput">
    <i data-lucide="cloud-upload"></i>
    <h3>Drag & drop images here, or click to browse</h3>
    <p class="muted">JPG, PNG, WEBP, GIF, AVIF, SVG · max <?= (int) (config('upload.max_size') / 1048576) ?> MB each · server limit <?= e(ini_get('upload_max_filesize')) ?></p>
    <div class="upload-progress" id="uploadProgress"></div>
  </label>

  <div class="card">
    <div class="card-head">
      <div><h2><i data-lucide="images"></i> All media <span class="badge"><?= count($media) ?> files · <?= human_size((int) $totalSize) ?></span></h2><p>Click an image to see details, copy its URL, edit alt text or delete it.</p></div>
      <div style="display:flex;gap:8px;flex-wrap:wrap">
        <div class="input-icon"><i data-lucide="search"></i><input type="search" id="mediaSearch" placeholder="Search…"></div>
        <select id="mediaFilter" style="width:auto"><option value="all">All</option><option value="used">In use</option><option value="unused">Not used</option></select>
      </div>
    </div>
    <div class="card-body">
      <?php if (!$media): ?>
        <div class="empty"><i data-lucide="image-off"></i><p>No images yet.</p></div>
      <?php else: ?>
      <div class="media-grid" id="mediaGrid">
        <?php foreach ($media as $m): $used = $usage[(int) $m['id']] ?? []; ?>
          <div class="media-card" data-id="<?= (int) $m['id'] ?>" data-name="<?= e(strtolower($m['original_name'] . ' ' . $m['alt_text'])) ?>" data-used="<?= $used ? 1 : 0 ?>"
               data-json="<?= e(json_encode(['id' => (int) $m['id'], 'url' => media_url((int) $m['id'], false), 'abs' => media_url((int) $m['id']), 'name' => $m['original_name'], 'alt' => $m['alt_text'], 'mime' => $m['mime'], 'size' => human_size((int) $m['size']), 'dim' => $m['width'] ? $m['width'] . ' × ' . $m['height'] . ' px' : '—', 'date' => date('d M Y', strtotime($m['created_at'])), 'used' => $used])) ?>">
            <?php if ($used): ?><span class="used">In use</span><?php endif; ?>
            <div class="media-img"><img loading="lazy" src="<?= e(media_url((int) $m['id'], false)) ?>" alt="<?= e($m['alt_text']) ?>"></div>
            <div class="media-info"><strong title="<?= e($m['original_name']) ?>"><?= e($m['original_name']) ?></strong><small><?= $m['width'] ? (int) $m['width'] . '×' . (int) $m['height'] . ' · ' : '' ?><?= human_size((int) $m['size']) ?></small></div>
          </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="modal" id="mediaDetail" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-head"><h3><i data-lucide="image"></i> <span data-d="name"></span></h3><div class="modal-tools"></div><button class="icon-btn" data-close-modal><i data-lucide="x"></i></button></div>
    <div class="modal-body">
      <div class="media-detail">
        <div class="media-img"><img data-d="img" src="" alt=""></div>
        <div>
          <dl class="kv">
            <dt>Type</dt><dd data-d="mime"></dd><dt>Size</dt><dd data-d="size"></dd>
            <dt>Dimensions</dt><dd data-d="dim"></dd><dt>Uploaded</dt><dd data-d="date"></dd>
          </dl>
          <div class="field" style="margin-bottom:14px"><label>File URL</label>
            <div style="display:flex;gap:6px"><input type="text" readonly data-d="abs"><button class="btn btn-outline btn-sm" type="button" data-copy-url><i data-lucide="copy"></i></button></div></div>
          <div class="field" style="margin-bottom:14px"><label>Alt text (for SEO & accessibility)</label><input type="text" data-d="alt" maxlength="250"></div>
          <div class="field"><label>Used in</label><div data-d="used" class="help"></div></div>
        </div>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-ghost danger" data-delete><i data-lucide="trash-2"></i> Delete image</button>
      <div><button class="btn btn-ghost" data-close-modal>Close</button> <button class="btn btn-primary" data-save><i data-lucide="save"></i> Save</button></div>
    </div>
  </div>
</div>
<?php admin_footer(); ?>
<script>
(function(){
  const $=(s,r=document)=>r.querySelector(s), $$=(s,r=document)=>[...r.querySelectorAll(s)];
  const dz=$('#dropzone'), input=$('#fileInput'), prog=$('#uploadProgress');
  function upload(files){
    if(!files.length) return;
    prog.innerHTML=[...files].map(f=>`<div class="upload-row"><span>${f.name.replace(/</g,'&lt;')}</span><span>Uploading…</span></div>`).join('');
    G2RuploadFiles(files, ()=>setTimeout(()=>location.reload(),600));
  }
  input.addEventListener('change',()=>upload(input.files));
  ['dragenter','dragover'].forEach(ev=>dz.addEventListener(ev,e=>{e.preventDefault();dz.classList.add('over')}));
  ['dragleave','drop'].forEach(ev=>dz.addEventListener(ev,e=>{e.preventDefault();dz.classList.remove('over')}));
  dz.addEventListener('drop',e=>upload(e.dataTransfer.files));

  const search=$('#mediaSearch'), filter=$('#mediaFilter');
  function apply(){const q=search.value.toLowerCase().trim(), f=filter.value;
    $$('#mediaGrid .media-card').forEach(c=>{const okQ=!q||c.dataset.name.includes(q);const okF=f==='all'||(f==='used')===(c.dataset.used==='1');c.hidden=!(okQ&&okF);});}
  search&&search.addEventListener('input',apply); filter&&filter.addEventListener('change',apply);

  const modal=$('#mediaDetail'); let cur=null;
  document.addEventListener('click',e=>{
    const card=e.target.closest('#mediaGrid .media-card'); if(!card) return;
    cur=JSON.parse(card.dataset.json);
    $('[data-d=name]',modal).textContent=cur.name; $('[data-d=img]',modal).src=cur.url;
    ['mime','size','dim','date'].forEach(k=>$(`[data-d=${k}]`,modal).textContent=cur[k]);
    $('[data-d=abs]',modal).value=cur.abs; $('[data-d=alt]',modal).value=cur.alt;
    $('[data-d=used]',modal).innerHTML=cur.used.length?cur.used.map(u=>'• '+u.replace(/</g,'&lt;')).join('<br>'):'Not used anywhere yet.';
    modal.classList.add('open');
  });
  $('[data-copy-url]',modal).onclick=()=>navigator.clipboard.writeText(cur.abs).then(()=>toast('URL copied'));
  $('[data-save]',modal).onclick=async(e)=>{e.target.classList.add('loading');try{const r=await G2Rapi('ajax/media.php?action=update',{id:cur.id,alt:$('[data-d=alt]',modal).value});toast(r.message);setTimeout(()=>location.reload(),500);}catch(err){toast(err.message,'error');e.target.classList.remove('loading');}};
  $('[data-delete]',modal).onclick=async()=>{
    const warn=cur.used.length?`This image is used in: ${cur.used.join(', ')}. Those places will show no image.`:'This image will be permanently deleted.';
    modal.classList.remove('open');
    if(!(await confirmBox('Delete “'+cur.name+'”?',warn))) return;
    try{const r=await G2Rapi('ajax/media.php?action=delete',{id:cur.id});toast(r.message);setTimeout(()=>location.reload(),500);}catch(err){toast(err.message,'error');}
  };
})();
</script>
