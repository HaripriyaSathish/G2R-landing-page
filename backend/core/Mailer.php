<?php
declare(strict_types=1);

/**
 * Lightweight SMTP mailer (no Composer needed).
 * Supports SSL (465), STARTTLS (587) and plain (25) with AUTH LOGIN / PLAIN.
 * All settings come from the admin panel (settings table, smtp_* keys).
 */
final class Mailer
{
    private $socket = null;
    private array $log = [];

    public function __construct(private array $cfg)
    {
    }

    public static function fromSettings(): self
    {
        return new self([
            'host'       => setting('smtp_host'),
            'port'       => (int) setting('smtp_port', '587'),
            'encryption' => setting('smtp_encryption', 'tls'), // ssl | tls | none
            'username'   => setting('smtp_username'),
            'password'   => setting('smtp_password'),
            'from_email' => setting('smtp_from_email') ?: setting('smtp_username'),
            'from_name'  => setting('smtp_from_name') ?: setting('site_name', 'Website'),
            'timeout'    => 20,
        ]);
    }

    public static function isConfigured(): bool
    {
        return setting('smtp_enabled', '0') === '1' && setting('smtp_host') !== '';
    }

    public function getLog(): array
    {
        return $this->log;
    }

    /**
     * @param string|array $to  email or list of emails
     */
    public function send($to, string $subject, string $html, ?string $replyTo = null, ?string $replyName = null): void
    {
        $recipients = array_values(array_filter(array_map('trim', is_array($to) ? $to : explode(',', $to))));
        if (!$recipients) {
            throw new RuntimeException('No recipient email address.');
        }
        foreach ($recipients as $r) {
            if (!filter_var($r, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Invalid recipient: ' . $r);
            }
        }
        if (empty($this->cfg['host'])) {
            throw new RuntimeException('SMTP host is not set.');
        }
        $from = $this->cfg['from_email'];
        if (!filter_var($from, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('"From email" is not a valid address.');
        }

        try {
            $this->connect();
            $this->cmd('MAIL FROM:<' . $from . '>', [250]);
            foreach ($recipients as $r) {
                $this->cmd('RCPT TO:<' . $r . '>', [250, 251]);
            }
            $this->cmd('DATA', [354]);
            $message = $this->buildMessage($recipients, $subject, $html, $replyTo, $replyName);
            // dot-stuffing
            $message = preg_replace('/^\./m', '..', $message);
            $this->write($message . "\r\n.");
            $this->expect([250]);
            $this->cmd('QUIT', [221], false);
        } finally {
            if (is_resource($this->socket)) {
                fclose($this->socket);
            }
            $this->socket = null;
        }
    }

    private function connect(): void
    {
        $enc  = strtolower((string) $this->cfg['encryption']);
        $host = $this->cfg['host'];
        $port = $this->cfg['port'] ?: ($enc === 'ssl' ? 465 : 587);
        $remote = ($enc === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;

        $ctx = stream_context_create(['ssl' => [
            'verify_peer'       => true,
            'verify_peer_name'  => true,
            'allow_self_signed' => false,
            'SNI_enabled'       => true,
            'peer_name'         => $host,
        ]]);
        $errno = 0;
        $errstr = '';
        $this->socket = @stream_socket_client($remote, $errno, $errstr, $this->cfg['timeout'], STREAM_CLIENT_CONNECT, $ctx);
        if (!$this->socket) {
            throw new RuntimeException("Could not connect to {$host}:{$port} - {$errstr} ({$errno})");
        }
        stream_set_timeout($this->socket, $this->cfg['timeout']);
        $this->expect([220]);

        $ehloHost = preg_replace('/[^a-z0-9.\-]/i', '', $_SERVER['SERVER_NAME'] ?? 'localhost') ?: 'localhost';
        $this->cmd('EHLO ' . $ehloHost, [250]);

        if ($enc === 'tls') {
            $this->cmd('STARTTLS', [220]);
            $crypto = STREAM_CRYPTO_METHOD_TLS_CLIENT;
            if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
                $crypto |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
            }
            if (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')) {
                $crypto |= STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
            }
            if (!@stream_socket_enable_crypto($this->socket, true, $crypto)) {
                throw new RuntimeException('STARTTLS negotiation failed. Try SSL on port 465.');
            }
            $this->cmd('EHLO ' . $ehloHost, [250]);
        }

        if (!empty($this->cfg['username'])) {
            $this->cmd('AUTH LOGIN', [334]);
            $this->cmd(base64_encode($this->cfg['username']), [334], true, true);
            $this->cmd(base64_encode((string) $this->cfg['password']), [235], true, true);
        }
    }

    private function buildMessage(array $to, string $subject, string $html, ?string $replyTo, ?string $replyName): string
    {
        $fromName = $this->encodeHeader($this->cfg['from_name']);
        $boundary = 'b' . bin2hex(random_bytes(12));
        $domain   = substr(strrchr($this->cfg['from_email'], '@') ?: '@localhost', 1);
        $headers  = [
            'Date: ' . date('r'),
            'From: ' . $fromName . ' <' . $this->cfg['from_email'] . '>',
            'To: ' . implode(', ', $to),
            'Subject: ' . $this->encodeHeader($subject),
            'Message-ID: <' . bin2hex(random_bytes(10)) . '@' . $domain . '>',
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
            'X-Mailer: G2R-Mailer',
        ];
        if ($replyTo && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
            $headers[] = 'Reply-To: ' . ($replyName ? $this->encodeHeader($replyName) . ' ' : '') . '<' . $replyTo . '>';
        }
        $text = trim(html_entity_decode(strip_tags(preg_replace('/<br\s*\/?>|<\/p>|<\/tr>/i', "\n", $html)), ENT_QUOTES, 'UTF-8'));

        $body  = "--{$boundary}\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($text));
        $body .= "--{$boundary}\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($html));
        $body .= "--{$boundary}--";

        return implode("\r\n", $headers) . "\r\n\r\n" . $body;
    }

    private function encodeHeader(string $value): string
    {
        $value = str_replace(["\r", "\n"], '', $value);
        return preg_match('/[^\x20-\x7E]/', $value) ? '=?UTF-8?B?' . base64_encode($value) . '?=' : $value;
    }

    private function cmd(string $command, array $codes, bool $strict = true, bool $secret = false): string
    {
        $this->write($command, $secret);
        return $this->expect($codes, $strict);
    }

    private function write(string $data, bool $secret = false): void
    {
        $this->log[] = 'C: ' . ($secret ? '********' : (strlen($data) > 200 ? substr($data, 0, 200) . '…' : $data));
        fwrite($this->socket, $data . "\r\n");
    }

    private function expect(array $codes, bool $strict = true): string
    {
        $response = '';
        while (($line = fgets($this->socket, 1024)) !== false) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        $this->log[] = 'S: ' . trim($response);
        $code = (int) substr($response, 0, 3);
        if ($strict && !in_array($code, $codes, true)) {
            $msg = trim($response) ?: 'No response from server (timeout).';
            if ($code === 535) {
                $msg .= ' — check username/password (Gmail needs an App Password).';
            }
            throw new RuntimeException('SMTP error: ' . $msg);
        }
        return $response;
    }
}

/** Wraps content in the branded HTML email layout. */
function email_layout(string $title, string $content): string
{
    $navy   = setting('theme_primary', '#111C50');
    $orange = setting('theme_accent', '#F36B16');
    $site   = e(setting('site_name', 'G2R Global Solutions'));
    $logo   = media_url((int) setting('site_logo', '0'));
    $logoHtml = $logo ? '<img src="' . e($logo) . '" alt="' . $site . '" height="48" style="display:block;margin:0 auto 8px;border-radius:8px;background:#fff">' : '';
    $year = date('Y');
    return <<<HTML
<!doctype html><html><body style="margin:0;background:#F6F7FC;font-family:Inter,Segoe UI,Arial,sans-serif;color:#232A5A">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#F6F7FC;padding:32px 12px"><tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 8px 30px rgba(17,28,80,.08)">
<tr><td style="background:{$navy};padding:28px 32px;text-align:center">{$logoHtml}<div style="color:#fff;font-size:20px;font-weight:800;font-family:Manrope,Arial,sans-serif">{$site}</div></td></tr>
<tr><td style="height:4px;background:{$orange}"></td></tr>
<tr><td style="padding:32px"><h2 style="margin:0 0 16px;font-family:Manrope,Arial,sans-serif;color:{$navy};font-size:22px">{$title}</h2>{$content}</td></tr>
<tr><td style="padding:18px 32px;background:#F6F7FC;color:#AEB5C6;font-size:12px;text-align:center">© {$year} {$site}</td></tr>
</table></td></tr></table></body></html>
HTML;
}
