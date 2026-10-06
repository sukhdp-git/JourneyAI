<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Activity;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Paginator;
use App\Core\Request;
use App\Core\Response;

/** Contact form messages. */
final class MessageController extends AdminController
{
    public const STATUSES = ['new' => 'New', 'read' => 'Read', 'replied' => 'Replied', 'archived' => 'Archived'];

    public function __construct()
    {
        Auth::authorize('messages');
    }

    public function index(Request $req): never
    {
        $where = ['1=1'];
        $p = [];
        $q = mb_substr(trim((string) $req->query('q', '')), 0, 100);
        if ($q !== '') {
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $where[] = '(name LIKE :a OR email LIKE :b OR subject LIKE :c OR message LIKE :d)';
            $p += ['a' => $like, 'b' => $like, 'c' => $like, 'd' => $like];
        }
        $status = (string) $req->query('status', '');
        if (isset(self::STATUSES[$status])) {
            $where[] = 'status = :s';
            $p['s'] = $status;
        }
        $w = implode(' AND ', $where);
        $total = (int) Database::value("SELECT COUNT(*) FROM contact_messages WHERE $w", $p);
        $pg = new Paginator($total, 25, $req->int('page', 1));
        $rows = Database::all("SELECT * FROM contact_messages WHERE $w ORDER BY created_at DESC, id DESC LIMIT :lim OFFSET :off", $p + ['lim' => 25, 'off' => $pg->offset]);
        $counts = array_column(Database::all('SELECT status, COUNT(*) c FROM contact_messages GROUP BY status'), 'c', 'status');
        $this->render('messages/index', ['rows' => $rows, 'pager' => $pg, 'q' => $q, 'status' => $status, 'counts' => $counts], 'Contact messages', [['Leads', null], ['Contact messages', null]]);
    }

    public function show(Request $req): never
    {
        $m = Database::one('SELECT * FROM contact_messages WHERE id = :id', ['id' => (int) $req->params['id']]) ?? Response::abort(404);
        if ($m['status'] === 'new') {
            Database::update('contact_messages', ['status' => 'read'], 'id = :id', ['id' => $m['id']]);
            $m['status'] = 'read';
        }
        $emails = Database::all("SELECT * FROM email_logs WHERE related_type = 'contact' AND related_id = :id ORDER BY id DESC", ['id' => $m['id']]);
        $this->render('messages/show', ['m' => $m, 'emails' => $emails], 'Message from ' . $m['name'], [['Leads', null], ['Contact messages', 'messages'], [$m['name'], null]]);
    }

    public function status(Request $req): never
    {
        $m = Database::one('SELECT id FROM contact_messages WHERE id = :id', ['id' => (int) $req->params['id']]) ?? Response::abort(404);
        $s = (string) $req->post('status');
        if (isset(self::STATUSES[$s])) {
            Database::update('contact_messages', ['status' => $s], 'id = :id', ['id' => $m['id']]);
            Activity::log('status', 'messages', (int) $m['id'], 'Message status → ' . self::STATUSES[$s]);
        }
        $this->back('/' . ADMIN_PREFIX . '/messages/' . $m['id'], 'success', 'Status updated.');
    }

    public function delete(Request $req): never
    {
        $m = Database::one('SELECT id, name FROM contact_messages WHERE id = :id', ['id' => (int) $req->params['id']]) ?? Response::abort(404);
        Database::delete('contact_messages', 'id = :id', ['id' => $m['id']]);
        Activity::log('delete', 'messages', (int) $m['id'], 'Deleted message from ' . $m['name']);
        $this->back('/' . ADMIN_PREFIX . '/messages', 'success', 'Message deleted.');
    }
}
