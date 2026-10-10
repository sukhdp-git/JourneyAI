<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Activity;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Paginator;
use App\Core\Request;
use App\Core\Response;
use App\Core\Settings;
use App\Trading\Affiliates;
use App\Trading\Members;

/** Affiliate programme: review applications, issue codes, set commission rates and settle commissions. */
final class AffiliateController extends AdminController
{
    private const STATUSES = ['pending' => 'Applications', 'approved' => 'Approved', 'suspended' => 'Suspended', 'rejected' => 'Rejected'];

    public function index(Request $req): never
    {
        Auth::authorize('billing');
        $status = (string) $req->query('status', '');
        $w = ['1 = 1'];
        $p = [];
        if (isset(self::STATUSES[$status])) {
            $w[] = 'a.status = :s';
            $p['s'] = $status;
        }
        $q = trim((string) $req->query('q', ''));
        if ($q !== '') {
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $w[] = '(a.full_name LIKE :q1 OR a.code LIKE :q2 OR u.email LIKE :q3)';
            $p += ['q1' => $like, 'q2' => $like, 'q3' => $like];
        }
        $where = implode(' AND ', $w);
        $pager = new Paginator((int) Database::value("SELECT COUNT(*) FROM affiliates a JOIN users u ON u.id = a.user_id WHERE $where", $p), 30, $req->int('page', 1));
        $rows = Database::all("SELECT a.*, u.email, u.name AS member_name,
                (SELECT COUNT(*) FROM users r WHERE r.referred_by_affiliate_id = a.id) AS referred,
                (SELECT COUNT(DISTINCT c.user_id) FROM affiliate_commissions c WHERE c.affiliate_id = a.id AND c.status <> 'void') AS paying
            FROM affiliates a JOIN users u ON u.id = a.user_id WHERE $where
            ORDER BY FIELD(a.status, 'pending', 'approved', 'suspended', 'rejected'), a.id DESC LIMIT :lim OFFSET :off", $p + ['lim' => $pager->perPage, 'off' => $pager->offset]);
        $due = [];
        foreach (Database::all("SELECT affiliate_id, currency, SUM(commission) s FROM affiliate_commissions WHERE status IN ('pending','approved') GROUP BY affiliate_id, currency") as $r) {
            $due[$r['affiliate_id']][] = money($r['s'], $r['currency']);
        }
        $counts = array_column(Database::all('SELECT status, COUNT(*) c FROM affiliates GROUP BY status'), 'c', 'status');
        $totals = Database::all("SELECT currency, status, SUM(commission) s FROM affiliate_commissions WHERE status <> 'void' GROUP BY currency, status");
        $this->render('affiliates/index', [
            'rows' => $rows, 'pager' => $pager, 'status' => $status, 'q' => $q, 'counts' => $counts, 'due' => $due, 'totals' => $totals,
            'statuses' => self::STATUSES, 'defaultPct' => Affiliates::defaultPct(), 'enabled' => setting('affiliates_enabled', '1') === '1',
        ], 'Affiliates', [['Members', 'members'], ['Affiliates', null]]);
    }

    public function settings(Request $req): never
    {
        Auth::authorize('billing');
        $pct = (float) $req->post('affiliate_commission_pct', '25');
        if ($pct <= 0 || $pct > 90) {
            $this->back(admin_url('affiliates'), 'error', 'Commission must be between 0.01% and 90%.');
        }
        Settings::save(['affiliate_commission_pct' => (string) round($pct, 2), 'affiliates_enabled' => $req->post('affiliates_enabled') === '1'], 'billing');
        Activity::log('update', 'affiliates', null, 'Programme settings: ' . round($pct, 2) . '%');
        $this->back(admin_url('affiliates'), 'success', 'Affiliate programme settings saved.');
    }

    public function show(Request $req): never
    {
        Auth::authorize('billing');
        $a = $this->target($req);
        $this->render('affiliates/show', [
            'a' => $a, 'stats' => Affiliates::stats((int) $a['id']), 'suggested' => $a['code'] ?: Affiliates::generateCode($a['full_name']),
            'defaultPct' => Affiliates::defaultPct(),
            'referred' => Database::all('SELECT id, name, email, referred_at, plan_id, plan_expires_at FROM users WHERE referred_by_affiliate_id = :a ORDER BY referred_at DESC LIMIT 100', ['a' => $a['id']]),
        ], $a['full_name'], [['Affiliates', 'affiliates'], [$a['full_name'], null]]);
    }

    public function approve(Request $req): never
    {
        Auth::authorize('billing');
        $a = $this->target($req);
        $code = Affiliates::normalizeCode((string) $req->post('code', ''));
        $pct = (float) $req->post('commission_pct', (string) Affiliates::defaultPct());
        $to = admin_url('affiliates/' . $a['id']);
        if (strlen($code) < 3 || strlen($code) > 32) {
            $this->back($to, 'error', 'The code must be 3–32 letters or numbers.');
        }
        if (Database::value('SELECT id FROM affiliates WHERE code = :c AND id <> :id', ['c' => $code, 'id' => $a['id']])) {
            $this->back($to, 'error', 'Another affiliate already uses the code ' . $code . '.');
        }
        if ($pct <= 0 || $pct > 90) {
            $this->back($to, 'error', 'Commission must be between 0.01% and 90%.');
        }
        Database::update('affiliates', ['status' => 'approved', 'code' => $code, 'commission_pct' => round($pct, 2), 'approved_at' => $a['approved_at'] ?: gmdate('Y-m-d H:i:s')], 'id = :id', ['id' => $a['id']]);
        Members::audit((int) $a['user_id'], 'affiliate_approved', $code . ' at ' . round($pct, 2) . '%');
        Activity::log('approve', 'affiliates', (int) $a['id'], $code);
        $this->back($to, 'success', $a['status'] === 'approved' ? 'Affiliate updated.' : 'Approved — code ' . $code . ' is live.');
    }

    public function status(Request $req): never
    {
        Auth::authorize('billing');
        $a = $this->target($req);
        $new = (string) $req->post('status', '');
        if (!in_array($new, ['rejected', 'suspended', 'approved'], true) || ($new === 'approved' && !$a['code'])) {
            $this->back(admin_url('affiliates/' . $a['id']), 'error', 'Choose a valid status.');
        }
        $note = mb_substr(trim((string) $req->post('admin_note', '')), 0, 500);
        Database::update('affiliates', ['status' => $new, 'admin_note' => $note !== '' ? $note : $a['admin_note']], 'id = :id', ['id' => $a['id']]);
        Members::audit((int) $a['user_id'], 'affiliate_' . $new, $note);
        Activity::log($new, 'affiliates', (int) $a['id'], $a['code'] ?? $a['full_name']);
        $this->back(admin_url('affiliates/' . $a['id']), 'success', 'Affiliate ' . ($new === 'approved' ? 're-activated' : $new) . '.');
    }

    /** Moves selected (or all) commissions of this affiliate to approved / paid / void. */
    public function commissions(Request $req): never
    {
        Auth::authorize('billing');
        $a = $this->target($req);
        $to = (string) $req->post('to', '');
        $from = ['approved' => ['pending'], 'paid' => ['pending', 'approved'], 'void' => ['pending', 'approved']][$to] ?? null;
        if (!$from) {
            $this->back(admin_url('affiliates/' . $a['id']), 'error', 'Choose an action.');
        }
        $ids = array_values(array_filter(array_map('intval', (array) ($_POST['ids'] ?? [])), fn ($i) => $i > 0));
        $only = (string) $req->post('only', '');
        $sql = "UPDATE affiliate_commissions SET status = :to, paid_at = IF(:to2 = 'paid', UTC_TIMESTAMP(), paid_at) WHERE affiliate_id = :a AND status IN ('" . implode("','", $from) . "')";
        $p = ['to' => $to, 'to2' => $to, 'a' => $a['id']];
        if ($ids) {
            $in = [];
            foreach ($ids as $i => $id) {
                $in[] = ':i' . $i;
                $p['i' . $i] = $id;
            }
            $sql .= ' AND id IN (' . implode(',', $in) . ')';
        } elseif (in_array($only, ['pending', 'approved'], true)) {
            $sql .= ' AND status = :only';
            $p['only'] = $only;
        } else {
            $this->back(admin_url('affiliates/' . $a['id']), 'error', 'Select at least one commission.');
        }
        $n = Database::query($sql, $p)->rowCount();
        Activity::log('commission_' . $to, 'affiliates', (int) $a['id'], $n . ' commissions');
        $this->back(admin_url('affiliates/' . $a['id']), 'success', $n . ' commission' . ($n === 1 ? '' : 's') . ' marked ' . $to . '.');
    }

    private function target(Request $req): array
    {
        $a = Database::one('SELECT a.*, u.email, u.name AS member_name FROM affiliates a JOIN users u ON u.id = a.user_id WHERE a.id = :id', ['id' => (int) ($req->params['id'] ?? 0)]);
        if (!$a) {
            Response::abort(404);
        }
        return $a;
    }
}
