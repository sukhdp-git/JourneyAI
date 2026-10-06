<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Activity;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Paginator;
use App\Core\Request;
use App\Core\Response;
use App\Trading\Members;
use App\Trading\Payments;

/** Website members (traders): directory, profile detail, sign-in history, payments and plan management. */
final class MemberController extends AdminController
{
    private function canView(): void
    {
        if (!Auth::can('members') && !Auth::can('members.view')) {
            Auth::authorize('members.view');
        }
    }

    private function filters(Request $req): array
    {
        $w = ['1 = 1'];
        $p = [];
        $q = trim((string) $req->query('q', ''));
        if ($q !== '') {
            $w[] = '(u.email LIKE :q1 OR u.name LIKE :q2)';
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $p += ['q1' => $like, 'q2' => $like];
        }
        $method = (string) $req->query('method', '');
        if (in_array($method, ['google', 'email'], true)) {
            $w[] = 'u.signup_method = :m';
            $p['m'] = $method;
        }
        $plan = (string) $req->query('plan', '');
        if ($plan === 'paid') {
            $w[] = 'u.plan_id IS NOT NULL AND u.plan_expires_at > UTC_TIMESTAMP()';
        } elseif ($plan === 'free') {
            $w[] = '(u.plan_id IS NULL OR u.plan_expires_at IS NULL OR u.plan_expires_at <= UTC_TIMESTAMP())';
        } elseif ($plan === 'expired') {
            $w[] = 'u.plan_expires_at IS NOT NULL AND u.plan_expires_at <= UTC_TIMESTAMP()';
        }
        $status = (string) $req->query('status', '');
        if (in_array($status, ['active', 'suspended'], true)) {
            $w[] = 'u.status = :s';
            $p['s'] = $status;
        }
        $active = (string) $req->query('active', '');
        if ($active === 'today') {
            $w[] = 'u.last_login_at >= UTC_DATE()';
        } elseif ($active === '7d') {
            $w[] = 'u.last_login_at >= UTC_TIMESTAMP() - INTERVAL 7 DAY';
        } elseif ($active === 'never') {
            $w[] = 'u.last_login_at IS NULL';
        }
        return [implode(' AND ', $w), $p, compact('q', 'method', 'plan', 'status', 'active')];
    }

    public function index(Request $req): never
    {
        $this->canView();
        [$where, $p, $f] = $this->filters($req);
        $sorts = ['created' => 'u.created_at DESC', 'login' => 'u.last_login_at IS NULL, u.last_login_at DESC', 'name' => 'u.name ASC', 'logins' => 'u.login_count DESC', 'trades' => 'trades DESC'];
        $sort = isset($sorts[$req->query('sort')]) ? (string) $req->query('sort') : 'created';
        $total = (int) Database::value("SELECT COUNT(*) FROM users u WHERE $where", $p);
        $pager = new Paginator($total, 25, $req->int('page', 1));
        $rows = Database::all("SELECT u.id, u.name, u.email, u.avatar_url, u.signup_method, u.status, u.plan_expires_at, u.created_at, u.last_login_at, u.login_count, u.onboarded,
                pl.name AS plan_name, (u.plan_id IS NOT NULL AND u.plan_expires_at > UTC_TIMESTAMP()) AS is_paid,
                (SELECT COUNT(*) FROM trades t WHERE t.user_id = u.id AND t.source <> 'DEMO') AS trades,
                (SELECT COUNT(*) FROM trading_accounts a WHERE a.user_id = u.id AND a.is_demo = 0) AS live_accounts
            FROM users u LEFT JOIN plans pl ON pl.id = u.plan_id WHERE $where ORDER BY {$sorts[$sort]}, u.id DESC LIMIT :lim OFFSET :off", $p + ['lim' => $pager->perPage, 'off' => $pager->offset]);
        $stats = Database::one("SELECT COUNT(*) total, SUM(signup_method = 'google') google, SUM(signup_method = 'email') email,
                SUM(plan_id IS NOT NULL AND plan_expires_at > UTC_TIMESTAMP()) paid, SUM(last_login_at >= UTC_DATE()) today, SUM(created_at >= UTC_TIMESTAMP() - INTERVAL 7 DAY) week
            FROM users");
        if ($req->query('export') === 'csv') {
            Auth::authorize('members');
            $all = Database::all("SELECT u.id, u.name, u.email, u.signup_method, u.status, u.email_verified, pl.name AS plan, u.plan_expires_at, u.created_at, u.last_login_at, u.login_count, u.signup_ip, u.last_login_ip
                FROM users u LEFT JOIN plans pl ON pl.id = u.plan_id WHERE $where ORDER BY u.id", $p);
            Activity::log('export', 'members', null, count($all) . ' members exported');
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="members-' . gmdate('Y-m-d') . '.csv"');
            $out = fopen('php://output', 'w');
            fputcsv($out, array_keys($all[0] ?? ['id' => 1]), ',', '"', '');
            foreach ($all as $r) {
                fputcsv($out, array_map(fn ($v) => is_string($v) && preg_match('/^[=+\-@\t\r]/', $v) ? "'" . $v : $v, $r), ',', '"', '');
            }
            exit;
        }
        $this->render('members/index', ['rows' => $rows, 'pager' => $pager, 'f' => $f, 'sort' => $sort, 'stats' => $stats], 'Members', [['Members', null]]);
    }

    public function show(Request $req): never
    {
        $this->canView();
        $id = (int) $req->params['id'];
        $u = Database::one('SELECT u.*, pl.name AS plan_name, s.timezone, s.language, s.theme, s.base_currency FROM users u LEFT JOIN plans pl ON pl.id = u.plan_id LEFT JOIN user_settings s ON s.user_id = u.id WHERE u.id = :id', ['id' => $id]) ?? Response::abort(404);
        $hasPassword = !empty($u['password_hash']);
        unset($u['password_hash']);
        $accounts = Database::all("SELECT a.*, (SELECT COUNT(*) FROM trades t WHERE t.account_id = a.id) trades, (SELECT COALESCE(SUM(pnl), 0) FROM trades t WHERE t.account_id = a.id AND t.status = 'CLOSED') pnl FROM trading_accounts a WHERE a.user_id = :u ORDER BY a.is_demo, a.id", ['u' => $id]);
        $this->render('members/show', [
            'u' => $u, 'hasPassword' => $hasPassword, 'accounts' => $accounts,
            'logins' => Database::all('SELECT * FROM user_logins WHERE user_id = :u ORDER BY id DESC LIMIT 25', ['u' => $id]),
            'payments' => Database::all('SELECT p.*, pl.name AS plan_name FROM payments p LEFT JOIN plans pl ON pl.id = p.plan_id WHERE p.user_id = :u ORDER BY p.id DESC', ['u' => $id]),
            'audit' => Database::all('SELECT action, details, ip, created_at FROM user_audit_logs WHERE user_id = :u ORDER BY id DESC LIMIT 25', ['u' => $id]),
            'counts' => Database::one("SELECT (SELECT COUNT(*) FROM trades WHERE user_id = :u1 AND source <> 'DEMO') trades, (SELECT COUNT(*) FROM trades WHERE user_id = :u5 AND source = 'DEMO') demo_trades, (SELECT COUNT(*) FROM journal_entries WHERE user_id = :u2) journals,
                (SELECT COUNT(*) FROM ai_messages WHERE user_id = :u3 AND role = 'user') ai, (SELECT COUNT(*) FROM broker_connections WHERE user_id = :u4) webhooks", ['u1' => $id, 'u2' => $id, 'u3' => $id, 'u4' => $id, 'u5' => $id]),
            'plans' => Database::all('SELECT id, name, interval_days FROM plans ORDER BY sort_order, id'),
        ], $u['name'], [['Members', 'members'], [$u['name'], null]]);
    }

    private function target(Request $req): array
    {
        Auth::authorize('members');
        return Database::one('SELECT * FROM users WHERE id = :id', ['id' => (int) $req->params['id']]) ?? Response::abort(404);
    }

    public function status(Request $req): never
    {
        $u = $this->target($req);
        $new = $u['status'] === 'active' ? 'suspended' : 'active';
        Database::update('users', ['status' => $new], 'id = :id', ['id' => $u['id']]);
        Members::audit((int) $u['id'], 'admin_' . $new, 'By Control Panel admin #' . Auth::id());
        Activity::log($new === 'suspended' ? 'suspend' : 'activate', 'members', (int) $u['id'], $u['email']);
        $this->back(admin_url('members/' . $u['id']), 'success', $new === 'suspended' ? 'Member suspended — they are signed out and cannot sign in.' : 'Member re-activated.');
    }

    public function note(Request $req): never
    {
        $u = $this->target($req);
        Database::update('users', ['admin_note' => mb_substr(trim((string) $req->post('admin_note', '')), 0, 5000) ?: null], 'id = :id', ['id' => $u['id']]);
        $this->back(admin_url('members/' . $u['id']), 'success', 'Note saved.');
    }

    public function grant(Request $req): never
    {
        $u = $this->target($req);
        Auth::authorize('billing');
        $plan = Database::one('SELECT * FROM plans WHERE id = :id', ['id' => $req->int('plan_id')]);
        $days = $req->int('days');
        if (!$plan || $days < 1 || $days > 3660) {
            $this->back(admin_url('members/' . $u['id']), 'error', 'Choose a plan and 1–3660 days.');
        }
        $amount = is_numeric($req->post('amount')) && (float) $req->post('amount') >= 0 ? round((float) $req->post('amount'), 2) : 0.0;
        $note = mb_substr(trim((string) $req->post('note', '')), 0, 200);
        Database::transaction(function () use ($u, $plan, $days, $amount, $note) {
            $pid = Database::insert('payments', ['user_id' => $u['id'], 'plan_id' => $plan['id'], 'gateway' => 'manual', 'gateway_order_id' => 'manual-' . bin2hex(random_bytes(6)),
                'amount' => $amount, 'currency' => $plan['currency'], 'status' => 'paid', 'period_days' => $days, 'customer_email' => $u['email'],
                'note' => trim('Granted by admin #' . Auth::id() . ($note !== '' ? ': ' . $note : '')), 'paid_at' => gmdate('Y-m-d H:i:s')]);
            Payments::extend((int) $u['id'], (int) $plan['id'], $days, $pid);
        });
        Activity::log('grant_plan', 'members', (int) $u['id'], $plan['name'] . ' +' . $days . ' days');
        $this->back(admin_url('members/' . $u['id']), 'success', $plan['name'] . ' granted for ' . $days . ' days.');
    }

    public function revoke(Request $req): never
    {
        $u = $this->target($req);
        Auth::authorize('billing');
        Database::update('users', ['plan_expires_at' => gmdate('Y-m-d H:i:s')], 'id = :id', ['id' => $u['id']]);
        Members::audit((int) $u['id'], 'plan_revoked', 'By Control Panel admin #' . Auth::id());
        Activity::log('revoke_plan', 'members', (int) $u['id'], $u['email']);
        $this->back(admin_url('members/' . $u['id']), 'success', 'Plan access ended now. Live accounts are read-only for this member.');
    }

    public function delete(Request $req): never
    {
        $u = $this->target($req);
        if (trim((string) $req->post('confirm_email')) !== $u['email']) {
            $this->back(admin_url('members/' . $u['id']), 'error', 'Type the member\'s email exactly to delete the account.');
        }
        $dir = STORAGE_PATH . '/private/screenshots/' . (int) $u['id'];
        if (is_dir($dir)) {
            foreach (glob($dir . '/*') ?: [] as $file) {
                @unlink($file);
            }
            @rmdir($dir);
        }
        Database::delete('users', 'id = :id', ['id' => $u['id']]);
        Activity::log('delete', 'members', (int) $u['id'], $u['email']);
        $this->back(admin_url('members'), 'success', 'Member and all of their data deleted. Payment records are kept for accounting.');
    }

    public function logins(Request $req): never
    {
        $this->canView();
        $w = ['1 = 1'];
        $p = [];
        $method = (string) $req->query('method', '');
        if (in_array($method, ['google', 'email', 'signup_google', 'signup_email', 'reset'], true)) {
            $w[] = 'l.method = :m';
            $p['m'] = $method;
        }
        if (in_array($req->query('result'), ['ok', 'failed'], true)) {
            $w[] = 'l.success = ' . ($req->query('result') === 'ok' ? 1 : 0);
        }
        if (($q = trim((string) $req->query('q', ''))) !== '') {
            $w[] = '(l.email LIKE :q OR l.ip LIKE :q2)';
            $p += ['q' => '%' . addcslashes($q, '%_\\') . '%', 'q2' => '%' . addcslashes($q, '%_\\') . '%'];
        }
        $where = implode(' AND ', $w);
        $pager = new Paginator((int) Database::value("SELECT COUNT(*) FROM user_logins l WHERE $where", $p), 50, $req->int('page', 1));
        $rows = Database::all("SELECT l.*, u.name FROM user_logins l LEFT JOIN users u ON u.id = l.user_id WHERE $where ORDER BY l.id DESC LIMIT :lim OFFSET :off", $p + ['lim' => $pager->perPage, 'off' => $pager->offset]);
        $today = Database::one("SELECT COUNT(*) total, SUM(success = 1) ok, SUM(success = 0) failed, COUNT(DISTINCT CASE WHEN success = 1 THEN user_id END) people FROM user_logins WHERE created_at >= UTC_DATE()");
        $this->render('members/logins', ['rows' => $rows, 'pager' => $pager, 'method' => $method, 'result' => (string) $req->query('result', ''), 'q' => $q, 'today' => $today], 'Sign-in log', [['Members', 'members'], ['Sign-in log', null]]);
    }

    public function payments(Request $req): never
    {
        Auth::authorize('billing');
        $w = ['1 = 1'];
        $p = [];
        $status = (string) $req->query('status', '');
        if (in_array($status, ['created', 'paid', 'failed', 'refunded'], true)) {
            $w[] = 'p.status = :s';
            $p['s'] = $status;
        }
        $where = implode(' AND ', $w);
        $pager = new Paginator((int) Database::value("SELECT COUNT(*) FROM payments p WHERE $where", $p), 50, $req->int('page', 1));
        $rows = Database::all("SELECT p.*, u.name, u.email, pl.name AS plan_name FROM payments p LEFT JOIN users u ON u.id = p.user_id LEFT JOIN plans pl ON pl.id = p.plan_id WHERE $where ORDER BY p.id DESC LIMIT :lim OFFSET :off", $p + ['lim' => $pager->perPage, 'off' => $pager->offset]);
        $revenue = Database::all("SELECT currency, SUM(amount) total, COUNT(*) n, SUM(CASE WHEN paid_at >= UTC_TIMESTAMP() - INTERVAL 30 DAY THEN amount ELSE 0 END) last30 FROM payments WHERE status = 'paid' AND gateway <> 'manual' GROUP BY currency");
        $counts = array_column(Database::all('SELECT status, COUNT(*) c FROM payments GROUP BY status'), 'c', 'status');
        $this->render('members/payments', ['rows' => $rows, 'pager' => $pager, 'status' => $status, 'revenue' => $revenue, 'counts' => $counts, 'gateway' => Payments::gateway()], 'Payments', [['Members', 'members'], ['Payments', null]]);
    }
}
