<?php
declare(strict_types=1);
require __DIR__ . '/partials/layout.php';
require_admin();

$tabs = [
    'general' => ['Brand', 'badge-check', [
        ['key' => 'site_name', 'label' => 'Company name', 'type' => 'text', 'required' => true, 'half' => true],
        ['key' => 'site_tagline', 'label' => 'Tagline (under logo)', 'type' => 'text', 'half' => true],
        ['key' => 'site_logo', 'label' => 'Header logo', 'type' => 'image', 'half' => true],
        ['key' => 'footer_logo', 'label' => 'Footer logo', 'type' => 'image', 'half' => true],
        ['key' => 'site_favicon', 'label' => 'Favicon (browser tab icon)', 'type' => 'image', 'help' => 'Square image, at least 64×64.'],
    ]],
    'contact' => ['Contact details', 'phone', [
        ['key' => 'contact_phone', 'label' => 'Phone', 'type' => 'text', 'half' => true],
        ['key' => 'contact_phone_alt', 'label' => 'Alternate phone', 'type' => 'text', 'half' => true],
        ['key' => 'contact_email', 'label' => 'Email', 'type' => 'text', 'half' => true],
        ['key' => 'whatsapp_number', 'label' => 'WhatsApp number', 'type' => 'text', 'half' => true, 'help' => 'With country code, e.g. 919345805849. Leave empty to hide the WhatsApp button.'],
        ['key' => 'contact_address', 'label' => 'Office address', 'type' => 'textarea', 'rows' => 3],
        ['key' => 'contact_map_url', 'label' => 'Google Maps link (optional)', 'type' => 'link'],
    ]],
    'social' => ['Social media', 'share-2', [
        ['key' => 'social_linkedin', 'label' => 'LinkedIn URL', 'type' => 'link', 'half' => true],
        ['key' => 'social_instagram', 'label' => 'Instagram URL', 'type' => 'link', 'half' => true],
        ['key' => 'social_facebook', 'label' => 'Facebook URL', 'type' => 'link', 'half' => true],
        ['key' => 'social_youtube', 'label' => 'YouTube URL', 'type' => 'link', 'half' => true],
        ['key' => 'social_twitter', 'label' => 'X / Twitter URL', 'type' => 'link', 'half' => true, 'help' => 'Leave any field empty to hide that icon.'],
    ]],
    'seo' => ['SEO', 'search', [
        ['key' => 'seo_title', 'label' => 'Page title', 'type' => 'text', 'count' => 60, 'help' => 'Shown in Google results and the browser tab (50–60 characters).'],
        ['key' => 'seo_description', 'label' => 'Meta description', 'type' => 'textarea', 'rows' => 3, 'count' => 160, 'help' => '150–160 characters works best.'],
        ['key' => 'seo_keywords', 'label' => 'Keywords', 'type' => 'text'],
        ['key' => 'seo_og_image', 'label' => 'Social share image', 'type' => 'image', 'help' => 'Shown when the link is shared on WhatsApp, LinkedIn, etc. 1200×630 recommended.'],
    ]],
    'theme' => ['Theme & colours', 'palette', [
        ['type' => 'divider', 'label' => 'Brand colours'],
        ['key' => 'theme_primary', 'label' => 'Primary navy', 'type' => 'color', 'half' => true],
        ['key' => 'theme_indigo', 'label' => 'Deep indigo', 'type' => 'color', 'half' => true],
        ['key' => 'theme_secondary', 'label' => 'Secondary navy', 'type' => 'color', 'half' => true],
        ['key' => 'theme_dark', 'label' => 'Dark navy', 'type' => 'color', 'half' => true],
        ['key' => 'theme_accent', 'label' => 'Orange accent', 'type' => 'color', 'half' => true],
        ['key' => 'theme_light', 'label' => 'Light background', 'type' => 'color', 'half' => true],
        ['key' => 'theme_muted', 'label' => 'Muted grey', 'type' => 'color', 'half' => true],
        ['type' => 'divider', 'label' => 'Typography & motion'],
        ['key' => 'theme_font_heading', 'label' => 'Heading font', 'type' => 'select', 'half' => true, 'options' => ['Manrope' => 'Manrope', 'Poppins' => 'Poppins', 'Montserrat' => 'Montserrat', 'Plus Jakarta Sans' => 'Plus Jakarta Sans', 'Inter' => 'Inter']],
        ['key' => 'theme_font_body', 'label' => 'Body font', 'type' => 'select', 'half' => true, 'options' => ['Inter' => 'Inter', 'Manrope' => 'Manrope', 'Poppins' => 'Poppins', 'Open Sans' => 'Open Sans', 'Roboto' => 'Roboto']],
        ['key' => 'theme_animations', 'label' => 'Enable scroll animations on the website', 'type' => 'checkbox'],
    ]],
    'advanced' => ['Advanced', 'sliders-horizontal', [
        ['key' => 'frontend_url', 'label' => 'Website (frontend) URL', 'type' => 'link', 'help' => 'Used by the "View website" buttons. e.g. http://localhost:5173 or https://g2rglobalsolutions.com'],
        ['key' => 'cors_origins_extra', 'label' => 'Extra allowed frontend origins', 'type' => 'text', 'help' => 'Comma separated. Add your live domain here, e.g. https://g2rglobalsolutions.com'],
    ]],
];
$tab = isset($tabs[$_GET['tab'] ?? '']) ? $_GET['tab'] : 'general';
[$tabLabel, $tabIcon, $fields] = $tabs[$tab];

if (is_post()) {
    verify_csrf();
    $current = [];
    foreach ($fields as $f) {
        if (!empty($f['key'])) $current[$f['key']] = setting($f['key'], '');
    }
    $data = Content::sanitize($fields, (array) ($_POST['s'] ?? []), $current);
    foreach ($fields as $f) {
        if (empty($f['key'])) continue;
        $v = $data[$f['key']];
        if ($f['type'] === 'checkbox') $v = $v ? '1' : '0';
        if ($f['type'] === 'image') $v = $v ? (string) $v : '';
        if ($f['type'] === 'color' && $v === '') continue;
        save_setting($f['key'], $v);
    }
    flash('success', $tabLabel . ' saved.');
    redirect(admin_url('settings.php?tab=' . $tab));
}

$values = [];
foreach ($fields as $f) {
    if (empty($f['key'])) continue;
    $v = setting($f['key'], $f['key'] === 'frontend_url' ? 'http://localhost:5173' : '');
    if ($f['type'] === 'checkbox') $v = $v === '1';
    $values[$f['key']] = $v;
}

admin_header('Site Settings', $tab === 'theme' ? 'theme' : 'settings');
?>
<div class="tabs">
  <?php foreach ($tabs as $k => [$label, $icon]): ?>
    <a class="tab <?= $k === $tab ? 'active' : '' ?>" href="?tab=<?= $k ?>"><i data-lucide="<?= e($icon) ?>"></i> <?= e($label) ?></a>
  <?php endforeach; ?>
</div>
<form class="card" method="post" data-watch style="max-width:960px">
  <?= csrf_field() ?>
  <div class="card-head"><h2><i data-lucide="<?= e($tabIcon) ?>"></i> <?= e($tabLabel) ?></h2></div>
  <div class="card-body">
    <?php if ($tab === 'theme'): ?>
      <div class="alert info" style="margin-bottom:18px"><i data-lucide="info"></i><div>These colours come from your brand guide and are applied to the website and this admin panel. <button type="button" class="btn btn-sm btn-outline" id="resetTheme" style="margin-left:8px">Reset to brand defaults</button></div></div>
    <?php endif; ?>
    <?= render_fields($fields, $values, 's') ?>
    <?php if ($tab === 'theme'): ?>
      <div id="themePreview" style="margin-top:22px;border-radius:14px;overflow:hidden;border:1px solid var(--border)">
        <div data-p="bg" style="padding:26px;color:#fff">
          <small data-p="accent-text" style="font-weight:700;letter-spacing:.12em;text-transform:uppercase">— Live preview</small>
          <h2 data-p="h" style="color:#fff;font-size:28px;margin:8px 0">Connecting <span data-p="accent-text">Exceptional Talent</span></h2>
          <p data-p="body" style="opacity:.8;margin:0 0 16px">This is how your brand colours look together.</p>
          <span data-p="btn" style="display:inline-block;padding:10px 20px;border-radius:30px;color:#fff;font-weight:700">Hire Top Talent →</span>
        </div>
        <div data-p="light" style="padding:18px 26px;display:flex;gap:10px;flex-wrap:wrap" id="swatches"></div>
      </div>
    <?php endif; ?>
  </div>
  <div class="form-actions sticky-actions"><button class="btn btn-primary" type="submit"><i data-lucide="save"></i> Save <?= e(strtolower($tabLabel)) ?></button></div>
</form>
<?php admin_footer(); ?>
<?php if ($tab === 'theme'): ?>
<script>
(function(){
  const defs={theme_primary:'#111C50',theme_indigo:'#1D138C',theme_secondary:'#232A5A',theme_dark:'#0D1338',theme_accent:'#F36B16',theme_light:'#F6F7FC',theme_muted:'#AEB5C6'};
  const v=k=>document.getElementById('f_s_'+k).value;
  function paint(){
    const P=s=>document.querySelector(`[data-p="${s}"]`);
    P('bg').style.background=`linear-gradient(120deg,${v('theme_dark')},${v('theme_primary')} 55%,${v('theme_indigo')})`;
    document.querySelectorAll('[data-p="accent-text"]').forEach(e=>e.style.color=v('theme_accent'));
    P('btn').style.background=v('theme_accent');
    P('light').style.background=v('theme_light');
    const fh=document.getElementById('f_s_theme_font_heading').value; P('h').style.fontFamily=`'${fh}',sans-serif`;
    document.getElementById('swatches').innerHTML=Object.keys(defs).map(k=>`<span style="display:flex;align-items:center;gap:6px;font-size:12px;color:#232A5A"><i style="width:22px;height:22px;border-radius:6px;background:${v(k)};border:1px solid #0001;display:inline-block"></i>${v(k)}</span>`).join('');
  }
  document.querySelector('form').addEventListener('input',paint);
  document.querySelector('form').addEventListener('change',paint);
  document.getElementById('resetTheme').onclick=()=>{Object.entries(defs).forEach(([k,c])=>{const t=document.getElementById('f_s_'+k);t.value=c;document.querySelector(`[data-color-sync="f_s_${k}"]`).value=c;});paint();toast('Defaults restored — click Save to apply');};
  paint();
})();
</script>
<?php endif; ?>
