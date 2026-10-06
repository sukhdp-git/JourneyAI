<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Mailer;
use App\Core\Request;

/** Dashboard: every number comes from the database. */
final class DashboardController extends AdminController
{
    public function index(Request $req): never
    {
        $leads = Auth::can('leads');
        $m = [];
        if ($leads) {
            $s = array_column(Database::all('SELECT status, COUNT(*) c FROM leads GROUP BY status'), 'c', 'status');
            $m['leads_total'] = array_sum($s);
            $m['leads_new'] = (int) ($s['new'] ?? 0);
            $m['leads_follow'] = (int) ($s['follow_up'] ?? 0);
            $m['leads_converted'] = (int) ($s['converted'] ?? 0);
            $m['leads_status'] = $s;
            $m['leads_week'] = (int) Database::value('SELECT COUNT(*) FROM leads WHERE created_at >= UTC_TIMESTAMP() - INTERVAL 7 DAY');
            $m['email_failed'] = (int) Database::value("SELECT COUNT(*) FROM leads WHERE email_status = 'failed' AND created_at >= UTC_TIMESTAMP() - INTERVAL 30 DAY");
            // Leads per day, last 30 days (UTC days).
            $daily = array_column(Database::all('SELECT DATE(created_at) d, COUNT(*) c FROM leads WHERE created_at >= UTC_DATE() - INTERVAL 29 DAY GROUP BY DATE(created_at)'), 'c', 'd');
            $series = [];
            for ($i = 29; $i >= 0; $i--) {
                $day = gmdate('Y-m-d', strtotime("-$i days"));
                $series[] = ['date' => $day, 'count' => (int) ($daily[$day] ?? 0)];
            }
            $m['series'] = $series;
            $m['recent_leads'] = Database::all('SELECT id, name, email, service_name, status, created_at FROM leads ORDER BY created_at DESC, id DESC LIMIT 6');
        }
        if (Auth::can('messages')) {
            $m['messages_total'] = (int) Database::value('SELECT COUNT(*) FROM contact_messages');
            $m['messages_new'] = (int) Database::value("SELECT COUNT(*) FROM contact_messages WHERE status = 'new'");
            $m['recent_messages'] = Database::all('SELECT id, name, subject, status, created_at FROM contact_messages ORDER BY created_at DESC, id DESC LIMIT 5');
        }
        $m['services'] = (int) Database::value("SELECT COUNT(*) FROM services WHERE status = 'published'");
        $m['posts'] = (int) Database::value("SELECT COUNT(*) FROM blog_posts WHERE status IN ('published','scheduled') AND published_at <= UTC_TIMESTAMP()");
        $m['drafts'] = (int) Database::value("SELECT COUNT(*) FROM blog_posts WHERE status = 'draft' OR (status = 'scheduled' AND published_at > UTC_TIMESTAMP())");
        $m['pages'] = (int) Database::value("SELECT COUNT(*) FROM pages WHERE status = 'published'");
        $m['media'] = (int) Database::value('SELECT COUNT(*) FROM media');
        $m['demo_testimonials'] = (int) Database::value("SELECT COUNT(*) FROM testimonials WHERE is_demo = 1 AND status = 'published'");
        $m['smtp_enabled'] = (int) Mailer::config()['is_enabled'] === 1;
        $m['activity'] = Auth::can('logs') ? Database::all('SELECT l.action, l.module, l.details, l.created_at, a.name FROM activity_logs l LEFT JOIN admins a ON a.id = l.admin_id ORDER BY l.id DESC LIMIT 6') : [];
        $this->render('dashboard', ['m' => $m, 'canLeads' => $leads], 'Dashboard', [['Dashboard', null]]);
    }
}
