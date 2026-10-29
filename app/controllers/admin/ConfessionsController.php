<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Activity;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

/**
 * Confessions moderation: reported or auto-hidden posts and replies, what the spam shield blocked, and member
 * handles (mute for 7 days, suspend, restore). The real email is shown here only, never in the feed.
 */
final class ConfessionsController extends AdminController
{
    private const TABS = ['reported' => 'Reported', 'hidden' => 'Hidden', 'blocked' => 'Blocked by the shield', 'recent' => 'All recent', 'members' => 'Handles'];

    public function index(Request $req): never
    {
        Auth::authorize('members');
        $tab = (string) $req->query('tab', 'reported');
        $tab = isset(self::TABS[$tab]) ? $tab : 'reported';
        $posts = $comments = $blocked = $members = [];
        $sel = 'SELECT c.*, p.handle, u.email FROM confessions c JOIN confession_profiles p ON p.user_id = c.user_id JOIN users u ON u.id = c.user_id';
        $csel = 'SELECT m.*, p.handle, u.email, c.body AS post_body FROM confession_comments m JOIN confessions c ON c.id = m.confession_id JOIN confession_profiles p ON p.user_id = m.user_id JOIN users u ON u.id = m.user_id';
        if ($tab === 'reported') {
            $posts = Database::all("$sel WHERE c.reports > 0 AND c.status <> 'removed' ORDER BY c.reports DESC, c.id DESC LIMIT 200");
            $comments = Database::all("$csel WHERE m.reports > 0 AND m.status <> 'removed' ORDER BY m.reports DESC, m.id DESC LIMIT 200");
        } elseif ($tab === 'hidden') {
            $posts = Database::all("$sel WHERE c.status = 'hidden' ORDER BY c.id DESC LIMIT 200");
            $comments = Database::all("$csel WHERE m.status = 'hidden' ORDER BY m.id DESC LIMIT 200");
        } elseif ($tab === 'recent') {
            $posts = Database::all("$sel ORDER BY c.id DESC LIMIT 100");
        } elseif ($tab === 'blocked') {
            $blocked = Database::all('SELECT b.*, p.handle, u.email FROM confession_blocked b JOIN users u ON u.id = b.user_id LEFT JOIN confession_profiles p ON p.user_id = b.user_id ORDER BY b.id DESC LIMIT 200');
        } else {
            $members = Database::all("SELECT p.*, u.email, (SELECT COUNT(*) FROM confessions c WHERE c.user_id = p.user_id) AS posts,
                (SELECT COUNT(*) FROM confession_blocked b WHERE b.user_id = p.user_id) AS blocked FROM confession_profiles p JOIN users u ON u.id = p.user_id ORDER BY blocked DESC, p.created_at DESC LIMIT 300");
        }
        $counts = [
            'reported' => (int) Database::value("SELECT COUNT(*) FROM confessions WHERE reports > 0 AND status <> 'removed'") + (int) Database::value("SELECT COUNT(*) FROM confession_comments WHERE reports > 0 AND status <> 'removed'"),
            'hidden' => (int) Database::value("SELECT COUNT(*) FROM confessions WHERE status = 'hidden'") + (int) Database::value("SELECT COUNT(*) FROM confession_comments WHERE status = 'hidden'"),
            'blocked' => (int) Database::value('SELECT COUNT(*) FROM confession_blocked WHERE created_at > NOW() - INTERVAL 7 DAY'),
        ];
        $this->render('confessions/index', ['paidOnly' => \App\Core\Settings::bool('confessions_paid_only'), 'tab' => $tab, 'tabs' => self::TABS, 'posts' => $posts, 'comments' => $comments, 'blocked' => $blocked, 'members' => $members, 'counts' => $counts],
            'Confessions', [['Members', 'members'], ['Confessions', null]]);
    }

    public function update(Request $req): never
    {
        Auth::authorize('members');
        $id = (int) ($req->params['id'] ?? 0);
        $kind = (string) $req->post('kind', 'post');
        $action = (string) $req->post('action', '');
        $back = admin_url('confessions') . '?tab=' . urlencode((string) $req->post('tab', 'reported'));
        if ($kind === 'member') {
            if (!Database::value('SELECT 1 FROM confession_profiles WHERE user_id = :u', ['u' => $id])) {
                Response::abort(404);
            }
            $set = match ($action) {
                'mute' => ['muted_until' => gmdate('Y-m-d H:i:s', time() + 7 * 86400)],
                'unmute' => ['muted_until' => null],
                'ban' => ['banned' => 1],
                'unban' => ['banned' => 0],
                default => null,
            };
            if ($set === null) {
                $this->back($back, 'error', 'Unknown action.');
            }
            Database::update('confession_profiles', $set, 'user_id = :u', ['u' => $id]);
            Activity::log($action, 'confessions', $id, 'Confessions member #' . $id);
            $this->back($back, 'success', 'Member updated.');
        }
        $table = $kind === 'comment' ? 'confession_comments' : 'confessions';
        $row = Database::one("SELECT * FROM $table WHERE id = :id", ['id' => $id]);
        if (!$row) {
            Response::abort(404);
        }
        $status = match ($action) { 'restore' => 'visible', 'hide' => 'hidden', 'remove' => 'removed', default => null };
        if ($status === null) {
            $this->back($back, 'error', 'Unknown action.');
        }
        Database::update($table, ['status' => $status] + ($action === 'restore' ? ['reports' => 0] : []), 'id = :id', ['id' => $id]);
        if ($kind === 'comment' && $row['status'] !== $status) {
            $delta = ($status === 'visible' ? 1 : 0) - ($row['status'] === 'visible' ? 1 : 0);
            if ($delta) {
                Database::query('UPDATE confessions SET comments = GREATEST(comments + :d, 0) WHERE id = :c', ['d' => $delta, 'c' => $row['confession_id']]);
            }
        }
        Activity::log($action, 'confessions', $id, 'Confession ' . $kind . ' #' . $id);
        $this->back($back, 'success', ucfirst($kind) . ' #' . $id . ' ' . ($action === 'restore' ? 'restored' : ($action === 'hide' ? 'hidden' : 'removed')) . '.');
    }

    /** Switch between "open to every member" (testing) and "members with an active plan only". */
    public function access(Request $req): never
    {
        Auth::authorize('members');
        $on = $req->post('paid_only') === '1';
        \App\Core\Settings::save(['confessions_paid_only' => $on ? '1' : '0'], 'confessions');
        Activity::log('update', 'confessions', 0, 'Confessions access: ' . ($on ? 'plan members only' : 'all members'));
        $this->back(admin_url('confessions'), 'success', $on ? 'Confessions is now for members with an active plan only.' : 'Confessions is now open to every member.');
    }
}
