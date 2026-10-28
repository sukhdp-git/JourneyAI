<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Activity;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

/** Wall of L's moderation: approve, reject or delete member-submitted loss autopsies before they go public. */
final class WallController extends AdminController
{
    private const STATUSES = ['pending' => 'In review', 'approved' => 'Published', 'rejected' => 'Rejected'];

    public function index(Request $req): never
    {
        Auth::authorize('members');
        $status = (string) $req->query('status', 'pending');
        $status = isset(self::STATUSES[$status]) ? $status : 'pending';
        $rows = Database::all('SELECT w.*, u.email FROM wall_posts w LEFT JOIN users u ON u.id = w.user_id WHERE w.status = :s ORDER BY w.id DESC LIMIT 200', ['s' => $status]);
        $counts = array_column(Database::all('SELECT status, COUNT(*) c FROM wall_posts GROUP BY status'), 'c', 'status');
        $this->render('wall/index', ['rows' => $rows, 'status' => $status, 'counts' => $counts, 'statuses' => self::STATUSES], "Wall of L's", [['Members', 'members'], ["Wall of L's", null]]);
    }

    public function update(Request $req): never
    {
        Auth::authorize('members');
        $id = (int) ($req->params['id'] ?? 0);
        $action = (string) $req->post('action', '');
        if (!Database::value('SELECT id FROM wall_posts WHERE id = :id', ['id' => $id])) {
            Response::abort(404);
        }
        if ($action === 'delete') {
            Database::delete('wall_posts', 'id = :id', ['id' => $id]);
        } elseif ($action === 'approve') {
            Database::update('wall_posts', ['status' => 'approved', 'approved_at' => gmdate('Y-m-d H:i:s')], 'id = :id', ['id' => $id]);
        } elseif ($action === 'reject') {
            Database::update('wall_posts', ['status' => 'rejected'], 'id = :id', ['id' => $id]);
        } else {
            $this->back(admin_url('wall'), 'error', 'Unknown action.');
        }
        Activity::log($action, 'wall', $id, 'Wall of L\'s post #' . $id);
        $this->back(admin_url('wall') . '?status=' . ($action === 'approve' ? 'approved' : 'pending'), 'success', 'Post #' . $id . ' ' . ($action === 'delete' ? 'deleted' : $action . 'd') . '.');
    }
}
