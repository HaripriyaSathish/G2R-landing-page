<?php
declare(strict_types=1);
require __DIR__ . '/partials/layout.php';
$admin = require_admin();

$testLog = null;
$testResult = null;

if (is_post()) {
    verify_csrf();
    $p = $_POST;
    $enc = in_array($p['smtp_encryption'] ?? '', ['ssl', 'tls', 'none'], true) ? $p['smtp_encryption'] : 'tls';
    $save = [
        'smtp_enabled'    => isset($p['smtp_enabled']) ? '1' : '0',
        'smtp_host'       => trim((string) ($p['smtp_host'] ?? '')),
        'smtp_port'       => (string) max(1, (int) ($p['smtp_port'] ?? 587)),
        'smtp_encryption' => $enc,
        'smtp_username'   => trim((string) ($p['smtp_username'] ?? '')),
        'smtp_from_email' => trim((string) ($p['smtp_from_email'] ?? '')),
        'smtp_from_name'  => trim((string) ($p['smtp_from_name'] ?? '')),
        'smtp_notify_to'  => trim((string) ($p['smtp_notify_to'] ?? '')),
        'smtp_autoreply'  => isset($p['smtp_autoreply']) ? '1' : '0',
        'smtp_autoreply_subject' => trim((string) ($p['smtp_autoreply_subject'] ?? '')),
        'smtp_autoreply_message' => trim(str_replace("\r\n", "\n", (string) ($p['smtp_autoreply_message'] ?? ''))),
    ];
    // Keep stored password unless a new one is typed
    if (($p['smtp_password'] ?? '') !== '') {
        $save['smtp_password'] = (string) $p['smtp_password'];
    }
    if (!empty($p['clear_password'])) {
        $save['smtp_password'] = '';
    }
    $errors = [];
    foreach (array_filter(array_map('trim', explode(',', $save['smtp_notify_to']))) as $em) {
        if (!filter_var($em, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid notification email: {$em}";
    }
    if ($save['smtp_from_email'] !== '' && !filter_var($save['smtp_from_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = '"From email" is not valid.';
    }
    if ($errors) {
        flash('error', implode(' ', $errors));
    } else {
        foreach ($save as $k => $v) save_setting($k, $v);
        settings_all(true);
        flash('success', 'SMTP settings saved.');
    }

    if (($p['action'] ?? '') === 'test' && !$errors) {
        $to = trim((string) ($p['test_to'] ?? '')) ?: ($admin['email'] ?: setting('smtp_notify_to'));
        $mailer = Mailer::fromSettings();
        try {
            $mailer->send($to, 'SMTP test from ' . setting('site_name', 'your website'),
                email_layout('SMTP is working 🎉', '<p style="font-size:15px;line-height:1.6">This test email confirms that your website can send emails. Contact form enquiries will now be delivered to <strong>' . e(setting('smtp_notify_to')) . '</strong>.</p><p style="color:#6B7391;font-size:13px">Sent ' . date('d M Y, h:i A') . '</p>'));
            $testResult = ['ok', 'Test email sent to ' . $to . '. Check the inbox (and spam folder).'];
        } catch (Throwable $e) {
            $testResult = ['err', $e->getMessage()];
        }
        $testLog = implode("\n", $mailer->getLog());
    } elseif (!$errors) {
        redirect(admin_url('smtp.php'));
    }
}

$s = fn ($k, $d = '') => setting($k, $d);
$hasPw = $s('smtp_password') !== '';
admin_header('SMTP / Email Settings', 'smtp');
?>
<div class="grid grid-2">
  <form class="card" method="post" data-watch id="smtpForm">
    <?= csrf_field() ?>
    <div class="card-head">
      <div><h2><i data-lucide="mail-check"></i> Outgoing mail server</h2><p>Used for contact-form notifications and auto-replies.</p></div>
      <label class="switch"><input type="checkbox" name="smtp_enabled" value="1" <?= $s('smtp_enabled') === '1' ? 'checked' : '' ?>><span class="slider"></span><span>Enable email sending</span></label>
    </div>
    <div class="card-body">
      <?php if ($testResult): ?>
        <div class="alert <?= $testResult[0] ?>" style="margin-bottom:18px"><i data-lucide="<?= $testResult[0] === 'ok' ? 'circle-check' : 'circle-alert' ?>"></i><div><?= e($testResult[1]) ?></div></div>
      <?php endif; ?>
      <div class="field" style="margin-bottom:16px"><label>Quick setup</label>
        <div style="display:flex;gap:6px;flex-wrap:wrap">
          <button type="button" class="btn btn-sm btn-outline" data-preset="smtp.gmail.com,587,tls">Gmail</button>
          <button type="button" class="btn btn-sm btn-outline" data-preset="smtp.office365.com,587,tls">Outlook / 365</button>
          <button type="button" class="btn btn-sm btn-outline" data-preset="smtp.zoho.in,465,ssl">Zoho (India)</button>
          <button type="button" class="btn btn-sm btn-outline" data-preset="smtp.hostinger.com,465,ssl">Hostinger</button>
          <button type="button" class="btn btn-sm btn-outline" data-preset="mail.yourdomain.com,465,ssl">cPanel</button>
        </div></div>
      <div class="form-grid">
        <div class="field half"><label for="smtp_host">SMTP host</label><input type="text" id="smtp_host" name="smtp_host" value="<?= e($s('smtp_host')) ?>" placeholder="smtp.gmail.com"></div>
        <div class="field half" style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
          <div class="field"><label for="smtp_port">Port</label><input type="number" id="smtp_port" name="smtp_port" value="<?= e($s('smtp_port', '587')) ?>"></div>
          <div class="field"><label for="smtp_encryption">Security</label><select id="smtp_encryption" name="smtp_encryption">
            <?php foreach (['tls' => 'TLS (587)', 'ssl' => 'SSL (465)', 'none' => 'None (25)'] as $k => $l): ?><option value="<?= $k ?>" <?= $s('smtp_encryption', 'tls') === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></div>
        </div>
        <div class="field half"><label for="smtp_username">Username</label><div class="input-icon"><i data-lucide="at-sign"></i><input type="text" id="smtp_username" name="smtp_username" value="<?= e($s('smtp_username')) ?>" autocomplete="off" placeholder="you@gmail.com"></div></div>
        <div class="field half"><label for="smtp_password">Password</label><div class="input-icon pw-wrap"><i data-lucide="key-round"></i><input type="password" id="smtp_password" name="smtp_password" value="" autocomplete="new-password" placeholder="<?= $hasPw ? '•••••••• (saved — leave blank to keep)' : 'App password' ?>"><button type="button" data-pw-toggle><i data-lucide="eye"></i></button></div>
          <?php if ($hasPw): ?><label class="help" style="display:flex;gap:6px;align-items:center;font-weight:400"><input type="checkbox" name="clear_password" value="1"> Remove saved password</label><?php endif; ?></div>
        <div class="field half"><label for="smtp_from_email">From email</label><input type="text" id="smtp_from_email" name="smtp_from_email" value="<?= e($s('smtp_from_email')) ?>" placeholder="Usually same as username"></div>
        <div class="field half"><label for="smtp_from_name">From name</label><input type="text" id="smtp_from_name" name="smtp_from_name" value="<?= e($s('smtp_from_name')) ?>"></div>
        <div class="field"><label for="smtp_notify_to">Send enquiry notifications to</label><input type="text" id="smtp_notify_to" name="smtp_notify_to" value="<?= e($s('smtp_notify_to')) ?>"><small class="help">Separate multiple addresses with commas.</small></div>

        <div class="field-divider"><span>Auto-reply to visitor</span></div>
        <div class="field"><label class="switch"><input type="checkbox" name="smtp_autoreply" value="1" <?= $s('smtp_autoreply') === '1' ? 'checked' : '' ?>><span class="slider"></span><span>Send a thank-you email to the visitor</span></label></div>
        <div class="field"><label for="ar_subject">Subject</label><input type="text" id="ar_subject" name="smtp_autoreply_subject" value="<?= e($s('smtp_autoreply_subject')) ?>"></div>
        <div class="field"><label for="ar_msg">Message</label><textarea id="ar_msg" name="smtp_autoreply_message" rows="7"><?= e($s('smtp_autoreply_message')) ?></textarea><small class="help">Placeholders: {name}, {email}, {enquiry_type}</small></div>
      </div>
    </div>
    <div class="form-actions sticky-actions"><button class="btn btn-primary" type="submit" name="action" value="save"><i data-lucide="save"></i> Save settings</button></div>
  </form>

  <div class="stack">
    <div class="card">
      <div class="card-head"><h2><i data-lucide="send"></i> Send a test email</h2></div>
      <div class="card-body">
        <p class="muted" style="margin-top:0">Saves the form and sends a test message so you know it works.</p>
        <div class="field" style="margin-bottom:12px"><label for="test_to">Send test to</label><input type="text" id="test_to" name="test_to" form="smtpForm" value="<?= e($_POST['test_to'] ?? ($admin['email'] ?: $s('smtp_notify_to'))) ?>"></div>
        <button class="btn btn-navy" type="submit" form="smtpForm" name="action" value="test" style="width:100%"><i data-lucide="send"></i> Save & send test</button>
        <?php if ($testLog): ?><details style="margin-top:14px"><summary class="muted" style="cursor:pointer">Connection log</summary><pre class="log"><?= e($testLog) ?></pre></details><?php endif; ?>
      </div>
    </div>
    <div class="card">
      <div class="card-head"><h2><i data-lucide="lightbulb"></i> Using Gmail?</h2></div>
      <div class="card-body help" style="font-size:13px;line-height:1.7">
        1. Turn on <strong>2-Step Verification</strong> in your Google account.<br>
        2. Open <a href="https://myaccount.google.com/apppasswords" target="_blank" rel="noopener">App passwords</a> and create one for “Mail”.<br>
        3. Paste the 16-character code as the password above (not your normal Gmail password).<br>
        4. Host <code>smtp.gmail.com</code>, port <code>587</code>, security <code>TLS</code>.
      </div>
    </div>
  </div>
</div>
<?php admin_footer(); ?>
<script>
document.querySelectorAll('[data-preset]').forEach(b=>b.onclick=()=>{const [h,p,e]=b.dataset.preset.split(',');smtp_host.value=h;smtp_port.value=p;smtp_encryption.value=e;toast('Preset applied — enter your username & password');});
smtp_encryption.onchange=()=>{smtp_port.value={tls:587,ssl:465,none:25}[smtp_encryption.value];};
</script>
