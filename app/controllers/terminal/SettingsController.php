<?php
declare(strict_types=1);

namespace App\Controllers\Terminal;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Trading\Domain;
use App\Trading\Members;
use App\Trading\Sessions;

/** Profile, preferences, risk rules, password, data export and account deletion. */
final class SettingsController extends TerminalController
{
    private function hasPassword(): bool
    {
        return (string) Database::value('SELECT password_hash FROM users WHERE id = :id', ['id' => $this->uid]) !== '';
    }

    public function index(Request $req): never
    {
        $this->render('settings', [
            'hasPassword' => $this->hasPassword(),
            'logins' => Database::all('SELECT method, success, ip, user_agent, created_at FROM user_logins WHERE user_id = :u ORDER BY id DESC LIMIT 10', ['u' => $this->uid]),
        ], 'Settings', 'settings');
    }

    public function save(Request $req): never
    {
        $num = fn ($k, $min, $max) => is_numeric($req->post($k)) && (float) $req->post($k) >= $min && (float) $req->post($k) <= $max ? (float) $req->post($k) : null;
        $name = mb_substr(trim((string) $req->post('name', '')), 0, 120);
        $tz = (string) $req->post('timezone');
        $errors = [];
        if ($name === '') {
            $errors['name'] = 'Enter your name.';
        }
        if (!Sessions::isValidTz($tz)) {
            $errors['timezone'] = 'Choose a valid timezone.';
        }
        $risk = $num('default_risk_pct', 0.01, 100);
        $rr = $num('default_target_rr', 0.1, 50);
        if ($risk === null) {
            $errors['default_risk_pct'] = 'Risk per trade must be between 0.01% and 100%.';
        }
        if ($rr === null) {
            $errors['default_target_rr'] = 'Target R must be between 0.1 and 50.';
        }
        $tc = (int) $num('tilt_loss_count', 2, 20);
        $tw = (int) $num('tilt_window_minutes', 5, 1440);
        $tcd = (int) $num('tilt_cooldown_minutes', 5, 1440);
        if (!$tc || !$tw || !$tcd) {
            $errors['tilt_loss_count'] = 'Tilt rule: 2–20 losses, window and cooldown 5–1440 minutes.';
        }
        if ($errors) {
            Session::withErrors($errors, $_POST);
            Response::redirect('/terminal/settings');
        }
        $loss = fn ($k) => ($req->post($k) ?? '') === '' ? null : $num($k, 0, 1e12);
        Database::update('users', ['name' => $name], 'id = :id', ['id' => $this->uid]);
        Database::update('user_settings', [
            'timezone' => $tz,
            'language' => isset(Domain::LANGUAGES[$req->post('language')]) ? $req->post('language') : 'en',
            'theme' => isset(Domain::THEMES[$req->post('theme')]) ? $req->post('theme') : 'dark-terminal',
            'base_currency' => in_array($req->post('base_currency'), Domain::CURRENCIES, true) ? $req->post('base_currency') : 'USD',
            'default_risk_pct' => round($risk, 2), 'default_target_rr' => round($rr, 2),
            'max_daily_loss' => $loss('max_daily_loss'), 'max_weekly_loss' => $loss('max_weekly_loss'),
            'tilt_loss_count' => $tc, 'tilt_window_minutes' => $tw, 'tilt_cooldown_minutes' => $tcd,
        ], 'user_id = :u', ['u' => $this->uid]);
        Members::audit($this->uid, 'settings_updated', 'Preferences saved');
        $this->back('/terminal/settings', 'success', 'Settings saved.');
    }

    public function theme(Request $req): never
    {
        if (isset(Domain::THEMES[$req->post('theme')])) {
            Database::update('user_settings', ['theme' => $req->post('theme')], 'user_id = :u', ['u' => $this->uid]);
        }
        if ($req->isAjax()) {
            Response::json(['ok' => true]);
        }
        Response::back('/terminal');
    }

    public function password(Request $req): never
    {
        $hash = (string) Database::value('SELECT password_hash FROM users WHERE id = :id', ['id' => $this->uid]);
        $new = (string) $req->post('new_password', '');
        if ($hash !== '' && !password_verify((string) $req->post('current_password', ''), $hash)) {
            $this->back('/terminal/settings#security', 'error', 'Your current password is incorrect.');
        }
        if (!Members::validPassword($new)) {
            $this->back('/terminal/settings#security', 'error', 'Use at least 10 characters with at least one letter and one number.');
        }
        if ($new !== (string) $req->post('new_password_confirmation', '')) {
            $this->back('/terminal/settings#security', 'error', 'The new passwords do not match.');
        }
        Database::update('users', ['password_hash' => password_hash($new, PASSWORD_DEFAULT)], 'id = :id', ['id' => $this->uid]);
        Members::audit($this->uid, 'password_changed', $hash === '' ? 'Password set' : 'Password changed');
        // Re-issue this session with the new password version so only other sessions are signed out.
        Members::refresh();
        $u = Database::one('SELECT * FROM users WHERE id = :id', ['id' => $this->uid]);
        $_SESSION['member_ver'] = Members::version($u);
        Session::regenerate();
        $this->back('/terminal/settings#security', 'success', $hash === '' ? 'Password set. You can now also sign in with email and password.' : 'Password changed. Other sessions have been signed out.');
    }

    /** Export My Data: everything the member owns as JSON, or the trade ledger as CSV. */
    public function export(Request $req): never
    {
        $u = ['u' => $this->uid];
        if ($req->query('format') === 'csv') {
            $rows = Database::all('SELECT t.*, a.name AS account_name, s.name AS strategy_name FROM trades t JOIN trading_accounts a ON a.id = t.account_id LEFT JOIN strategies s ON s.id = t.strategy_id WHERE t.user_id = :u ORDER BY t.executed_at', $u);
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="JournzeyAI_AllTrades_' . gmdate('Y-m-d') . '.csv"');
            $out = fopen('php://output', 'w');
            $cols = ['account_name', 'executed_at', 'closed_at', 'symbol', 'side', 'status', 'entry_price', 'exit_price', 'stop_loss', 'take_profit', 'lot_size', 'fees', 'pnl', 'rr', 'strategy_name', 'setup_tag', 'session', 'emotion', 'mistake_tag', 'rules_followed', 'source', 'notes'];
            fputcsv($out, $cols, ',', '"', '');
            foreach ($rows as $r) {
                fputcsv($out, array_map(fn ($c) => is_string($r[$c]) && preg_match('/^[=+\-@\t\r]/', $r[$c]) && !is_numeric($r[$c]) ? "'" . $r[$c] : $r[$c], $cols), ',', '"', '');
            }
            exit;
        }
        $profile = Database::one('SELECT id, email, name, signup_method, email_verified, plan_expires_at, onboarded, primary_markets, created_at, last_login_at, login_count FROM users WHERE id = :u', $u);
        $data = [
            'exported_at' => gmdate('c'), 'profile' => $profile,
            'settings' => Database::one('SELECT * FROM user_settings WHERE user_id = :u', $u),
            'accounts' => Database::all('SELECT * FROM trading_accounts WHERE user_id = :u', $u),
            'capital_transactions' => Database::all('SELECT * FROM capital_transactions WHERE user_id = :u', $u),
            'strategies' => Database::all('SELECT * FROM strategies WHERE user_id = :u', $u),
            'trades' => Database::all('SELECT * FROM trades WHERE user_id = :u ORDER BY executed_at', $u),
            'journal_entries' => Database::all('SELECT * FROM journal_entries WHERE user_id = :u', $u),
            'checklist_entries' => Database::all('SELECT * FROM checklist_entries WHERE user_id = :u', $u),
            'ai_conversations' => Database::all('SELECT c.id, c.title, c.created_at, m.role, m.content, m.created_at AS message_at FROM ai_conversations c JOIN ai_messages m ON m.conversation_id = c.id WHERE c.user_id = :u ORDER BY c.id, m.id', $u),
            'webhook_connections' => Database::all('SELECT id, account_id, label, provider, status, events_count, last_event_at, created_at FROM broker_connections WHERE user_id = :u', $u),
            'payments' => Database::all('SELECT gateway, amount, currency, status, period_days, access_until, created_at, paid_at FROM payments WHERE user_id = :u', $u),
            'logins' => Database::all('SELECT method, success, ip, user_agent, created_at FROM user_logins WHERE user_id = :u ORDER BY id DESC LIMIT 500', $u),
        ];
        Members::audit($this->uid, 'data_exported', 'JSON export');
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="JournzeyAI_MyData_' . gmdate('Y-m-d') . '.json"');
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public function deleteAccount(Request $req): never
    {
        $hash = (string) Database::value('SELECT password_hash FROM users WHERE id = :id', ['id' => $this->uid]);
        if (trim((string) $req->post('confirm')) !== 'DELETE') {
            $this->back('/terminal/settings#danger', 'error', 'Type DELETE to confirm account deletion.');
        }
        if ($hash !== '' && !password_verify((string) $req->post('password', ''), $hash)) {
            $this->back('/terminal/settings#danger', 'error', 'Your password is incorrect.');
        }
        $dir = STORAGE_PATH . '/private/screenshots/' . $this->uid;
        if (is_dir($dir)) {
            foreach (glob($dir . '/*') ?: [] as $f) {
                @unlink($f);
            }
            @rmdir($dir);
        }
        // Payments are kept for accounting (user_id becomes NULL); everything else cascades.
        Database::delete('users', 'id = :id', ['id' => $this->uid]);
        unset($_SESSION['member_id'], $_SESSION['member_ver']);
        Session::regenerate();
        Session::flash('success', 'Your account and all of its data have been deleted.');
        Response::redirect('/');
    }
}
