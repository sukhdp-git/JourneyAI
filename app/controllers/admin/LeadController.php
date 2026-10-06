<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Activity;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Paginator;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;

/** Consultation leads: list/search/filter, detail with notes & history, status, assignment, edit, delete, CSV export. */
final class LeadController extends AdminController
{
    public const STATUSES = ['new' => 'New', 'contacted' => 'Contacted', 'follow_up' => 'Follow-up', 'converted' => 'Converted', 'closed' => 'Closed'];

    public function __construct()
    {
        Auth::authorize('leads');
    }

    private function filters(Request $req): array
    {
        $where = ['1=1'];
        $p = [];
        $q = mb_substr(trim((string) $req->query('q', '')), 0, 100);
        if ($q !== '') {
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $where[] = '(l.name LIKE :q1 OR l.email LIKE :q2 OR l.phone LIKE :q3 OR l.company LIKE :q4)';
            $p += ['q1' => $like, 'q2' => $like, 'q3' => $like, 'q4' => $like];
        }
        $status = (string) $req->query('status', '');
        if (isset(self::STATUSES[$status])) {
            $where[] = 'l.status = :st';
            $p['st'] = $status;
        }
        $assigned = (string) $req->query('assigned', '');
        if ($assigned === 'me') {
            $where[] = 'l.assigned_to = :me';
            $p['me'] = Auth::id();
        } elseif ($assigned === 'none') {
            $where[] = 'l.assigned_to IS NULL';
        } elseif (ctype_digit($assigned)) {
            $where[] = 'l.assigned_to = :as';
            $p['as'] = (int) $assigned;
        }
        $service = (string) $req->query('service', '');
        if (ctype_digit($service)) {
            $where[] = 'l.service_id = :sv';
            $p['sv'] = (int) $service;
        }
        $from = (string) $req->query('from', '');
        $to = (string) $req->query('to', '');
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
            $where[] = 'l.created_at >= :from';
            $p['from'] = $from . ' 00:00:00';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            $where[] = 'l.created_at <= :to';
            $p['to'] = $to . ' 23:59:59';
        }
        return [implode(' AND ', $where), $p, compact('q', 'status', 'assigned', 'service', 'from', 'to')];
    }

    public function index(Request $req): never
    {
        [$w, $p, $f] = $this->filters($req);
        $sorts = ['created_at' => 'l.created_at', 'name' => 'l.name', 'status' => 'l.status'];
        $sort = $sorts[(string) $req->query('sort', 'created_at')] ?? 'l.created_at';
        $dir = strtolower((string) $req->query('dir', 'desc')) === 'asc' ? 'ASC' : 'DESC';
        $total = (int) Database::value("SELECT COUNT(*) FROM leads l WHERE $w", $p);
        $pg = new Paginator($total, 25, $req->int('page', 1));
        $rows = Database::all("SELECT l.*, a.name AS assignee FROM leads l LEFT JOIN admins a ON a.id = l.assigned_to WHERE $w ORDER BY $sort $dir, l.id DESC LIMIT :lim OFFSET :off", $p + ['lim' => 25, 'off' => $pg->offset]);
        $counts = array_column(Database::all('SELECT status, COUNT(*) c FROM leads GROUP BY status'), 'c', 'status');
        $this->render('leads/index', [
            'rows' => $rows, 'pager' => $pg, 'f' => $f, 'counts' => $counts, 'admins' => $this->admins(),
            'services' => Database::all('SELECT id, title FROM services ORDER BY sort_order'), 'sort' => (string) $req->query('sort', 'created_at'), 'dir' => $dir,
        ], 'Consultation leads', [['Leads', null], ['Consultation leads', null]]);
    }

    public function export(Request $req): never
    {
        [$w, $p] = $this->filters($req);
        $rows = Database::all("SELECT l.id, l.created_at, l.name, l.email, l.phone, l.whatsapp, l.company, l.service_name, l.preferred_date, l.preferred_time, l.status, a.name AS assignee, l.message, l.email_status FROM leads l LEFT JOIN admins a ON a.id = l.assigned_to WHERE $w ORDER BY l.created_at DESC LIMIT 10000", $p);
        Activity::log('export', 'leads', null, 'Exported ' . count($rows) . ' leads to CSV');
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="leads-' . gmdate('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['ID', 'Created (UTC)', 'Name', 'Email', 'Phone', 'WhatsApp', 'Company', 'Service', 'Preferred date', 'Preferred time', 'Status', 'Assigned to', 'Message', 'Email status']);
        foreach ($rows as $r) {
            // Neutralise spreadsheet formula injection.
            fputcsv($out, array_map(fn ($v) => is_string($v) && preg_match('/^[=+\-@\t\r]/', $v) ? "'" . $v : $v, array_values($r)));
        }
        fclose($out);
        exit;
    }

    public function show(Request $req): never
    {
        $lead = $this->find((int) $req->params['id']);
        $notes = Database::all('SELECT n.*, a.name AS admin_name FROM lead_notes n LEFT JOIN admins a ON a.id = n.admin_id WHERE n.lead_id = :id ORDER BY n.created_at DESC, n.id DESC', ['id' => $lead['id']]);
        $emails = Database::all("SELECT * FROM email_logs WHERE related_type = 'lead' AND related_id = :id ORDER BY id DESC", ['id' => $lead['id']]);
        $this->render('leads/show', [
            'lead' => $lead, 'notes' => $notes, 'emails' => $emails, 'admins' => $this->admins(),
            'services' => Database::all('SELECT id, title FROM services ORDER BY sort_order'),
        ], 'Lead: ' . $lead['name'], [['Leads', null], ['Consultation leads', 'leads'], [$lead['name'], null]]);
    }

    public function status(Request $req): never
    {
        $lead = $this->find((int) $req->params['id']);
        $status = (string) $req->post('status');
        if (!isset(self::STATUSES[$status])) {
            $this->back("/" . ADMIN_PREFIX . "/leads/{$lead['id']}", 'error', 'Choose a valid status.');
        }
        if ($status !== $lead['status']) {
            Database::update('leads', ['status' => $status], 'id = :id', ['id' => $lead['id']]);
            $this->note($lead['id'], 'status', 'Status changed from ' . self::STATUSES[$lead['status']] . ' to ' . self::STATUSES[$status]);
            Activity::log('status', 'leads', (int) $lead['id'], 'Lead status → ' . self::STATUSES[$status]);
        }
        if ($req->isAjax()) {
            Response::json(['ok' => true, 'message' => 'Status updated.']);
        }
        $this->back("/" . ADMIN_PREFIX . "/leads/{$lead['id']}", 'success', 'Status updated.');
    }

    public function assign(Request $req): never
    {
        $lead = $this->find((int) $req->params['id']);
        $to = (string) $req->post('assigned_to');
        $admin = ctype_digit($to) ? Database::one("SELECT id, name FROM admins WHERE id = :id AND status = 'active'", ['id' => (int) $to]) : null;
        if ($to !== '' && !$admin) {
            $this->back("/" . ADMIN_PREFIX . "/leads/{$lead['id']}", 'error', 'Choose a valid admin.');
        }
        Database::update('leads', ['assigned_to' => $admin['id'] ?? null], 'id = :id', ['id' => $lead['id']]);
        $this->note($lead['id'], 'assignment', $admin ? 'Assigned to ' . $admin['name'] : 'Unassigned');
        Activity::log('assign', 'leads', (int) $lead['id'], $admin ? 'Assigned lead to ' . $admin['name'] : 'Unassigned lead');
        $this->back("/" . ADMIN_PREFIX . "/leads/{$lead['id']}", 'success', 'Assignment updated.');
    }

    public function addNote(Request $req): never
    {
        $lead = $this->find((int) $req->params['id']);
        $body = trim((string) $req->post('note'));
        if ($body === '' || mb_strlen($body) > 5000) {
            $this->fail(['note' => 'Write a note (up to 5000 characters).'], "/" . ADMIN_PREFIX . "/leads/{$lead['id']}", 'The note could not be saved.');
        }
        $this->note($lead['id'], 'note', $body);
        Activity::log('note', 'leads', (int) $lead['id'], 'Added a note');
        $this->back("/" . ADMIN_PREFIX . "/leads/{$lead['id']}", 'success', 'Note added.');
    }

    public function update(Request $req): never
    {
        $lead = $this->find((int) $req->params['id']);
        $v = new Validator($_POST, [
            'name' => 'required|max:120', 'email' => 'required|email|max:190', 'phone' => 'phone|max:40', 'whatsapp' => 'phone|max:40',
            'company' => 'max:150', 'preferred_date' => 'date', 'preferred_time' => 'time', 'message' => 'max:5000',
        ]);
        $sid = (string) $req->post('service_id', '');
        $service = ctype_digit($sid) ? Database::one('SELECT id, title FROM services WHERE id = :id', ['id' => (int) $sid]) : null;
        if ($v->fails()) {
            $this->fail($v->errors, "/" . ADMIN_PREFIX . "/leads/{$lead['id']}#edit");
        }
        $d = $v->clean;
        Database::update('leads', [
            'name' => $d['name'], 'email' => strtolower($d['email']), 'phone' => $d['phone'] ?: null, 'whatsapp' => $d['whatsapp'] ?: null,
            'company' => $d['company'] ?: null, 'service_id' => $service['id'] ?? null, 'service_name' => $service['title'] ?? ($sid === '' ? $lead['service_name'] : null),
            'preferred_date' => $d['preferred_date'] ?: null, 'preferred_time' => $d['preferred_time'] ?: null, 'message' => $d['message'] ?: null,
        ], 'id = :id', ['id' => $lead['id']]);
        $this->note($lead['id'], 'edit', 'Lead details edited');
        Activity::log('update', 'leads', (int) $lead['id'], 'Edited lead details');
        $this->back("/" . ADMIN_PREFIX . "/leads/{$lead['id']}", 'success', 'Lead updated.');
    }

    public function delete(Request $req): never
    {
        $lead = $this->find((int) $req->params['id']);
        Database::delete('leads', 'id = :id', ['id' => $lead['id']]);
        Activity::log('delete', 'leads', (int) $lead['id'], 'Deleted lead from ' . $lead['name']);
        $this->back('/' . ADMIN_PREFIX . '/leads', 'success', 'Lead deleted.');
    }

    private function note(int|string $leadId, string $type, string $body): void
    {
        Database::insert('lead_notes', ['lead_id' => (int) $leadId, 'admin_id' => Auth::id(), 'type' => $type, 'body' => $body]);
    }

    private function find(int $id): array
    {
        $l = Database::one('SELECT l.*, a.name AS assignee FROM leads l LEFT JOIN admins a ON a.id = l.assigned_to WHERE l.id = :id', ['id' => $id]);
        return $l ?? Response::abort(404);
    }

    private function admins(): array
    {
        return Database::all("SELECT id, name FROM admins WHERE status = 'active' ORDER BY name");
    }
}
