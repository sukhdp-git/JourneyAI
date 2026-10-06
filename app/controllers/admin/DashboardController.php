<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Mailer;
use App\Core\Request;
use App\Trading\AiCoach;
use App\Trading\GoogleAuth;
use App\Trading\MarketData;
use App\Trading\Payments;

/** Dashboard: every number comes from the database. */
final class DashboardController extends AdminController
{
    public function index(Request $req): never
    {
        $members = Auth::can('members') || Auth::can('members.view');
        $m = [];
        if ($members) {
            $m['users'] = Database::one("SELECT COUNT(*) total, COALESCE(SUM(signup_method = 'google'), 0) google, COALESCE(SUM(signup_method = 'email'), 0) email,
                    COALESCE(SUM(plan_id IS NOT NULL AND plan_expires_at > UTC_TIMESTAMP()), 0) paid, COALESCE(SUM(last_login_at >= UTC_DATE()), 0) today,
                    COALESCE(SUM(created_at >= UTC_TIMESTAMP() - INTERVAL 7 DAY), 0) week, COALESCE(SUM(status = 'suspended'), 0) suspended,
                    COALESCE(SUM(last_login_at >= UTC_TIMESTAMP() - INTERVAL 30 DAY), 0) active30
                FROM users");
            $m['logins_today'] = (int) Database::value('SELECT COUNT(*) FROM user_logins WHERE success = 1 AND created_at >= UTC_DATE()');
            $m['failed_today'] = (int) Database::value('SELECT COUNT(*) FROM user_logins WHERE success = 0 AND created_at >= UTC_DATE()');
            $m['trades'] = (int) Database::value("SELECT COUNT(*) FROM trades WHERE source <> 'DEMO'");
            $m['trades_week'] = (int) Database::value("SELECT COUNT(*) FROM trades WHERE source <> 'DEMO' AND created_at >= UTC_TIMESTAMP() - INTERVAL 7 DAY");
            $m['ai_today'] = (int) Database::value("SELECT COUNT(*) FROM ai_messages WHERE role = 'user' AND created_at >= UTC_DATE()");
            $daily = [];
            foreach (Database::all("SELECT DATE(created_at) d, signup_method m, COUNT(*) c FROM users WHERE created_at >= UTC_DATE() - INTERVAL 29 DAY GROUP BY DATE(created_at), signup_method") as $r) {
                $daily[$r['d']][$r['m']] = (int) $r['c'];
            }
            $series = [];
            for ($i = 29; $i >= 0; $i--) {
                $day = gmdate('Y-m-d', strtotime("-$i days"));
                $series[] = ['date' => $day, 'google' => $daily[$day]['google'] ?? 0, 'email' => $daily[$day]['email'] ?? 0];
            }
            $m['series'] = $series;
            $m['recent_users'] = Database::all('SELECT u.id, u.name, u.email, u.signup_method, u.created_at, u.last_login_at, (u.plan_id IS NOT NULL AND u.plan_expires_at > UTC_TIMESTAMP()) paid FROM users u ORDER BY u.id DESC LIMIT 8');
            $m['recent_logins'] = Database::all('SELECT l.user_id, l.email, l.method, l.success, l.created_at, u.name FROM user_logins l LEFT JOIN users u ON u.id = l.user_id ORDER BY l.id DESC LIMIT 8');
        }
        if (Auth::can('billing')) {
            $m['revenue'] = Database::all("SELECT currency, SUM(amount) total, SUM(CASE WHEN paid_at >= UTC_TIMESTAMP() - INTERVAL 30 DAY THEN amount ELSE 0 END) last30, COUNT(*) n FROM payments WHERE status = 'paid' AND gateway <> 'manual' GROUP BY currency");
        }
        if (Auth::can('messages')) {
            $m['messages_new'] = (int) Database::value("SELECT COUNT(*) FROM contact_messages WHERE status = 'new'");
        }
        $m['smtp_enabled'] = (int) Mailer::config()['is_enabled'] === 1;
        $m['google'] = GoogleAuth::configured();
        $m['gateway'] = Payments::gateway();
        $m['ai'] = AiCoach::configured();
        $m['market'] = MarketData::configured();
        $m['demo_testimonials'] = (int) Database::value("SELECT COUNT(*) FROM testimonials WHERE is_demo = 1 AND status = 'published'");
        $m['activity'] = Auth::can('logs') ? Database::all('SELECT l.action, l.module, l.details, l.created_at, a.name FROM activity_logs l LEFT JOIN admins a ON a.id = l.admin_id ORDER BY l.id DESC LIMIT 6') : [];
        $this->render('dashboard', ['m' => $m, 'canMembers' => $members], 'Dashboard', [['Dashboard', null]]);
    }
}
