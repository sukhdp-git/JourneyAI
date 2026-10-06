<?php
declare(strict_types=1);

namespace App\Core;

use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * SMTP email through PHPMailer. Credentials live in smtp_settings (password encrypted with APP_KEY)
 * and never reach the browser. Every attempt is written to email_logs. Callers must persist their
 * data BEFORE calling send(): a delivery failure never throws.
 */
final class Mailer
{
    public static function config(): array
    {
        $row = Database::one('SELECT * FROM smtp_settings WHERE id = 1') ?? [];
        return $row + ['host' => '', 'port' => 587, 'username' => '', 'password_enc' => '', 'encryption' => 'tls', 'from_email' => '', 'from_name' => '', 'reply_to' => '', 'is_enabled' => 0];
    }

    /** @return array{status:string,error:string} status: sent | failed | disabled */
    public static function send(string $to, string $subject, string $html, array $opts = []): array
    {
        $cfg = self::config();
        $result = ['status' => 'sent', 'error' => ''];
        $password = '';
        if (!(int) $cfg['is_enabled'] || $cfg['host'] === '' || $cfg['from_email'] === '') {
            $result = ['status' => 'disabled', 'error' => 'SMTP is disabled or not configured.'];
        } elseif (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $result = ['status' => 'failed', 'error' => 'Invalid recipient address.'];
        } else {
            $password = $cfg['password_enc'] !== '' ? (Crypto::decrypt($cfg['password_enc']) ?? '') : '';
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = $cfg['host'];
                $mail->Port = (int) $cfg['port'];
                $mail->SMTPAuth = $cfg['username'] !== '';
                $mail->Username = $cfg['username'];
                $mail->Password = $password;
                $mail->SMTPSecure = match ($cfg['encryption']) {
                    'ssl' => PHPMailer::ENCRYPTION_SMTPS,
                    'tls' => PHPMailer::ENCRYPTION_STARTTLS,
                    default => '',
                };
                $mail->SMTPAutoTLS = $cfg['encryption'] !== 'none';
                $mail->Timeout = 15;
                $mail->SMTPDebug = 0;
                $mail->CharSet = PHPMailer::CHARSET_UTF8;
                $mail->setFrom($cfg['from_email'], $cfg['from_name'] ?: Settings::get('site_name', 'journzey.ai'));
                $replyTo = $opts['reply_to'] ?? $cfg['reply_to'];
                if ($replyTo && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
                    $mail->addReplyTo($replyTo, $opts['reply_name'] ?? '');
                }
                $mail->addAddress($to);
                $mail->isHTML(true);
                $mail->Subject = str_replace(["\r", "\n"], ' ', $subject);
                $mail->Body = self::wrap($subject, $html);
                $mail->AltBody = trim(html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $html)), ENT_QUOTES, 'UTF-8'));
                $mail->send();
            } catch (MailException|\Throwable $e) {
                $result = ['status' => 'failed', 'error' => self::safeError($mail->ErrorInfo ?: $e->getMessage(), $password)];
            }
        }
        self::log($to, $subject, $result, $opts);
        return $result;
    }

    /** Sends a stored template. Returns status "disabled" when the template is switched off. */
    public static function sendTemplate(string $key, string $to, array $vars, array $opts = []): array
    {
        $tpl = Database::one('SELECT * FROM email_templates WHERE template_key = :k', ['k' => $key]);
        if (!$tpl || !(int) $tpl['is_enabled']) {
            $r = ['status' => 'disabled', 'error' => 'Template "' . $key . '" is disabled.'];
            self::log($to, $tpl['subject'] ?? $key, $r, $opts + ['template' => $key]);
            return $r;
        }
        $vars += ['site_name' => Settings::get('site_name', 'journzey.ai'), 'site_url' => BASE_URL, 'date' => fmt_date(gmdate('Y-m-d H:i:s'), 'M j, Y g:i A T')];
        $subject = render_vars($tpl['subject'], $vars, false);
        $body = render_vars($tpl['body'], $vars, true);
        return self::send($to, $subject, $body, $opts + ['template' => $key]);
    }

    /** Removes anything that could reveal secrets from an SMTP error message. */
    public static function safeError(string $msg, string $password = ''): string
    {
        if ($password !== '') {
            $msg = str_replace([$password, base64_encode($password)], '[hidden]', $msg);
        }
        $msg = preg_replace('/(AUTH|PLAIN|LOGIN)\s+[A-Za-z0-9+\/=]{8,}/i', '$1 [hidden]', $msg) ?? $msg;
        $msg = trim(strip_tags($msg));
        if (str_contains(strtolower($msg), 'could not authenticate')) {
            $msg .= ' Check the SMTP username and password.';
        } elseif (str_contains(strtolower($msg), 'could not connect')) {
            $msg .= ' Check the host, port and encryption, and that your host allows outbound SMTP.';
        }
        return mb_substr($msg, 0, 400);
    }

    private static function log(string $to, string $subject, array $result, array $opts): void
    {
        try {
            Database::insert('email_logs', [
                'template_key' => $opts['template'] ?? null,
                'recipient' => mb_substr($to, 0, 190),
                'subject' => mb_substr($subject, 0, 255),
                'status' => $result['status'],
                'error' => $result['error'] !== '' ? $result['error'] : null,
                'related_type' => $opts['related_type'] ?? null,
                'related_id' => $opts['related_id'] ?? null,
            ]);
        } catch (\Throwable $e) {
            Logger::error('Email log failed: ' . $e->getMessage());
        }
    }

    private static function wrap(string $title, string $body): string
    {
        $site = e(Settings::get('site_name', 'journzey.ai'));
        $primary = e(Settings::get('color_primary', '#6366f1'));
        return '<!doctype html><html><body style="margin:0;background:#f4f5f8;font-family:Arial,Helvetica,sans-serif;color:#1f2433">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding:28px 12px">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e6e8ef">'
            . '<tr><td style="background:' . $primary . ';padding:18px 28px;color:#ffffff;font-size:18px;font-weight:bold">' . $site . '</td></tr>'
            . '<tr><td style="padding:28px;font-size:15px;line-height:1.6">' . $body . '</td></tr>'
            . '<tr><td style="padding:16px 28px;border-top:1px solid #eef0f4;font-size:12px;color:#7a8194">' . $site . ' · ' . e(BASE_URL) . '</td></tr>'
            . '</table></td></tr></table></body></html>';
    }
}
