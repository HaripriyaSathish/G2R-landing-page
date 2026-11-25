<?php
/**
 * POST /api/contact.php  (JSON or form-data)
 * fields: name, email, phone, enquiry_type, message, website (honeypot - must be empty)
 */
declare(strict_types=1);
require dirname(__DIR__) . '/core/bootstrap.php';

send_cors_headers();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Method not allowed'], 405);
}

$input = $_POST;
if (str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
    $input = json_decode((string) file_get_contents('php://input'), true) ?: [];
}

// Honeypot: bots fill hidden fields. Pretend success.
if (!empty($input['website'])) {
    json_response(['ok' => true, 'message' => 'Thank you!']);
}

// Read raw (no truncation) so over-long input is rejected rather than silently cut
$raw = static fn (string $k) => trim((string) ($input[$k] ?? ''));

$name    = preg_replace('/ {2,}/', ' ', $raw('name'));
$email   = $raw('email');
$phone   = $raw('phone');
$type    = $raw('enquiry_type');
$message = trim(strip_tags($raw('message')));

$contactSection = DB::one("SELECT data FROM sections WHERE slug = 'contact'");
$contactData    = Content::decode($contactSection['data'] ?? null);
$allowedTypes   = array_values(array_filter(array_map('strval', (array) ($contactData['enquiry_types'] ?? []))));

$errors = [];

// Name: letters and single spaces only, 2-50 chars
if ($name === '') {
    $errors['name'] = 'Please enter your name.';
} elseif (!preg_match('/^[A-Za-z]+( [A-Za-z]+)*$/', $name)) {
    $errors['name'] = 'Name can contain only letters and spaces.';
} elseif (strlen($name) < 2 || strlen($name) > 50) {
    $errors['name'] = 'Name must be between 2 and 50 letters.';
}

// Email: strict format, max 100 chars
if ($email === '') {
    $errors['email'] = 'Please enter your email address.';
} elseif (strlen($email) > 100
    || !filter_var($email, FILTER_VALIDATE_EMAIL)
    || !preg_match('/^[A-Za-z0-9._%+-]+@[A-Za-z0-9-]+(\.[A-Za-z0-9-]+)*\.[A-Za-z]{2,}$/', $email)) {
    $errors['email'] = 'Please enter a valid email address (e.g. name@company.com).';
}

// Mobile: exactly 10 digits, Indian mobile (starts 6-9)
if ($phone === '') {
    $errors['phone'] = 'Please enter your mobile number.';
} elseif (!ctype_digit($phone)) {
    $errors['phone'] = 'Mobile number can contain only digits.';
} elseif (strlen($phone) !== 10) {
    $errors['phone'] = 'Mobile number must be exactly 10 digits.';
} elseif (!preg_match('/^[6-9]/', $phone)) {
    $errors['phone'] = 'Please enter a valid mobile number starting with 6, 7, 8 or 9.';
}

// Enquiry type: required, must be one of the configured options
if ($type === '') {
    $errors['enquiry_type'] = 'Please select an enquiry type.';
} elseif ($allowedTypes && !in_array($type, $allowedTypes, true)) {
    $errors['enquiry_type'] = 'Please select a valid enquiry type.';
}

// Message: 10-1000 chars
$msgLen = mb_strlen($message);
if ($message === '') {
    $errors['message'] = 'Please enter your message.';
} elseif ($msgLen < 10) {
    $errors['message'] = 'Message must be at least 10 characters.';
} elseif ($msgLen > 1000) {
    $errors['message'] = 'Message must be 1000 characters or fewer.';
}
if ($errors) {
    json_response(['ok' => false, 'error' => 'Please fix the highlighted fields.', 'errors' => $errors], 422);
}

// Rate limit: max 5 enquiries per IP per hour
$ip = client_ip();
$recent = (int) DB::value(DB::isPg()
    ? "SELECT COUNT(*) FROM enquiries WHERE ip = ? AND created_at > (LOCALTIMESTAMP - INTERVAL '1 hour')"
    : 'SELECT COUNT(*) FROM enquiries WHERE ip = ? AND created_at > (NOW() - INTERVAL 1 HOUR)', [$ip]);
if ($recent >= 5) {
    json_response(['ok' => false, 'error' => 'Too many submissions. Please try again later or call us directly.'], 429);
}

$id = DB::insert(
    'INSERT INTO enquiries (name, email, phone, enquiry_type, message, ip, user_agent) VALUES (?,?,?,?,?,?,?)',
    [$name, $email, $phone, $type, $message, $ip, mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 250)]
);

// Email notifications (never block the visitor if mail fails)
$mailStatus = 'skipped';
$mailError  = null;
if (Mailer::isConfigured()) {
    try {
        $rows = '';
        foreach (['Name' => $name, 'Email' => $email, 'Phone' => $phone ?: '-', 'Enquiry type' => $type ?: '-'] as $label => $val) {
            $rows .= '<tr><td style="padding:8px 12px;background:#F6F7FC;font-weight:600;width:140px">' . e($label) . '</td><td style="padding:8px 12px">' . e($val) . '</td></tr>';
        }
        $html = email_layout('New website enquiry #' . $id,
            '<table width="100%" cellspacing="0" style="border-collapse:separate;border-spacing:0 6px;font-size:14px">' . $rows . '</table>'
            . '<p style="font-weight:600;margin:18px 0 6px">Message</p><div style="padding:14px;border-left:4px solid ' . e(setting('theme_accent', '#F36B16')) . ';background:#F6F7FC;white-space:pre-wrap;font-size:14px">' . nl2br(e($message)) . '</div>'
        );
        $notifyTo = setting('smtp_notify_to') ?: setting('contact_email');
        (Mailer::fromSettings())->send($notifyTo, 'New enquiry: ' . ($type ?: 'Website') . ' - ' . $name, $html, $email, $name);

        if (setting('smtp_autoreply', '0') === '1') {
            $body = strtr(setting('smtp_autoreply_message', ''), ['{name}' => $name, '{enquiry_type}' => $type ?: 'your requirement', '{email}' => $email]);
            $subject = strtr(setting('smtp_autoreply_subject', 'Thank you for contacting us'), ['{name}' => $name]);
            (Mailer::fromSettings())->send($email, $subject, email_layout('Hi ' . e($name) . ',', '<div style="font-size:15px;line-height:1.6">' . nl2br(e(preg_replace('/^Hi .*?,\s*/', '', $body))) . '</div>'));
        }
        $mailStatus = 'sent';
    } catch (Throwable $e) {
        $mailStatus = 'failed';
        $mailError  = mb_substr($e->getMessage(), 0, 490);
        error_log('[contact mail] ' . $e->getMessage());
    }
}
DB::run('UPDATE enquiries SET mail_status = ?, mail_error = ? WHERE id = ?', [$mailStatus, $mailError, $id]);

$success = $contactData['success_message'] ?? 'Thank you! We will get back to you soon.';
json_response(['ok' => true, 'message' => $success ?: 'Thank you!', 'id' => $id]);
