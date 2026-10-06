<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Activity;
use App\Core\Auth;
use App\Core\Crypto;
use App\Core\Database;
use App\Core\Mailer;
use App\Core\Paginator;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Validator;

/** SMTP settings (password encrypted at rest, never sent back to the browser), test email and delivery log. */
final class EmailController extends AdminController
{
    public function smtp(Request $req): never
    {
        Auth::authorize('email.smtp');
        $cfg = Mailer::config();
        $hasPassword = !empty($cfg['password_enc']);
        unset($cfg['password_enc']);
        $this->render('email/smtp', ['cfg' => $cfg, 'hasPassword' => $hasPassword, 'me' => Auth::user()], 'SMTP settings', [['Email', null], ['SMTP settings', null]]);
    }

    public function saveSmtp(Request $req): never
    {
        Auth::authorize('email.smtp');
        $v = new Validator($_POST, [
            'host' => 'max:190', 'port' => 'required|int|between:1,65535', 'username' => 'max:190', 'encryption' => 'required|in:none,ssl,tls',
            'from_email' => 'email|max:190', 'from_name' => 'max:120', 'reply_to' => 'email|max:190',
        ], ['host' => 'SMTP host', 'from_email' => 'From email', 'reply_to' => 'Reply-to']);
        $d = $v->clean;
        $enabled = $req->post('is_enabled') === '1';
        $errors = $v->errors;
        if ($d['host'] !== '' && !preg_match('/^[A-Za-z0-9.\-]+$/', $d['host'])) {
            $errors['host'] = 'Enter a host name such as mail.yourdomain.com.';
        }
        if ($enabled && ($d['host'] === '' || $d['from_email'] === '')) {
            $errors['host'] ??= 'Host and From email are required to enable SMTP.';
        }
        if ($errors) {
            $this->fail($errors, '/' . ADMIN_PREFIX . '/email/smtp');
        }
        $data = [
            'host' => $d['host'], 'port' => (int) $d['port'], 'username' => $d['username'], 'encryption' => $d['encryption'],
            'from_email' => strtolower($d['from_email']), 'from_name' => $d['from_name'], 'reply_to' => strtolower($d['reply_to']), 'is_enabled' => $enabled ? 1 : 0,
        ];
        $pw = (string) ($_POST['password'] ?? '');
        $changedPw = false;
        if ($req->post('clear_password') === '1') {
            $data['password_enc'] = null;
            $changedPw = true;
        } elseif ($pw !== '') {
            $data['password_enc'] = Crypto::encrypt($pw);
            $changedPw = true;
        }
        Database::query('INSERT IGNORE INTO smtp_settings (id) VALUES (1)');
        Database::update('smtp_settings', $data, 'id = 1');
        Activity::log('update', 'smtp', 1, 'Updated SMTP settings' . ($changedPw ? ' (password changed)' : '') . '; enabled: ' . ($enabled ? 'yes' : 'no'));
        $this->back('/' . ADMIN_PREFIX . '/email/smtp', 'success', 'SMTP settings saved.');
    }

    public function test(Request $req): never
    {
        Auth::authorize('email.smtp');
        $to = strtolower(trim((string) $req->post('test_to')));
        $back = '/' . ADMIN_PREFIX . '/' . ($req->post('from') === 'test' ? 'email/test' : 'email/smtp');
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->fail(['test_to' => 'Enter a valid recipient email address.'], $back, 'Enter a valid recipient.');
        }
        if (!RateLimiter::hit('smtp-test:' . Auth::id(), 10, 600)) {
            $this->back($back, 'error', 'Too many test emails. Please wait a few minutes.');
        }
        $cfg = Mailer::config();
        if (!(int) $cfg['is_enabled']) {
            $this->back($back, 'error', 'SMTP is disabled. Tick “Enable SMTP” and save before sending a test.');
        }
        $r = Mailer::send($to, 'Test email from ' . setting('site_name', 'journzey.ai'), '<p>This is a test email sent from the Control Panel at ' . e(fmt_date(gmdate('Y-m-d H:i:s'), 'M j, Y g:i A T')) . '.</p><p>If you can read this, SMTP is configured correctly.</p>', ['template' => 'test', 'related_type' => 'test']);
        Activity::log('test_email', 'smtp', null, 'Test email to ' . $to . ': ' . $r['status']);
        if ($r['status'] === 'sent') {
            $this->back($back, 'success', 'Test email sent to ' . $to . '. Check the inbox (and spam folder).');
        }
        $this->back($back, 'error', 'Email delivery failed: ' . $r['error']);
    }

    public function testForm(Request $req): never
    {
        Auth::authorize('email.smtp');
        $cfg = Mailer::config();
        $this->render('email/test', ['enabled' => (int) $cfg['is_enabled'] === 1, 'host' => $cfg['host']], 'Send test email', [['Email', null], ['Send test email', null]]);
    }

    public function logs(Request $req): never
    {
        Auth::authorize('email.templates');
        $status = (string) $req->query('status', '');
        $where = in_array($status, ['sent', 'failed', 'disabled'], true) ? 'status = :s' : '1=1';
        $p = $where === '1=1' ? [] : ['s' => $status];
        $total = (int) Database::value("SELECT COUNT(*) FROM email_logs WHERE $where", $p);
        $pg = new Paginator($total, 30, $req->int('page', 1));
        $rows = Database::all("SELECT * FROM email_logs WHERE $where ORDER BY id DESC LIMIT :lim OFFSET :off", $p + ['lim' => 30, 'off' => $pg->offset]);
        $this->render('email/logs', ['rows' => $rows, 'pager' => $pg, 'status' => $status], 'Email delivery log', [['Email', null], ['Delivery log', null]]);
    }
}
