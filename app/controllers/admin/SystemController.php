<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Activity;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Paginator;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

/** Activity log, system information and the signed-in admin's own profile. */
final class SystemController extends AdminController
{
    public function logs(Request $req): never
    {
        Auth::authorize('logs');
        $where = ['1=1'];
        $p = [];
        $module = (string) $req->query('module', '');
        $admin = (string) $req->query('admin', '');
        $q = mb_substr(trim((string) $req->query('q', '')), 0, 100);
        if ($module !== '' && preg_match('/^[a-z0-9\/\-_.]+$/', $module)) {
            $where[] = 'l.module = :m';
            $p['m'] = $module;
        }
        if (ctype_digit($admin)) {
            $where[] = 'l.admin_id = :a';
            $p['a'] = (int) $admin;
        }
        if ($q !== '') {
            $where[] = '(l.details LIKE :q OR l.action LIKE :q2 OR l.ip LIKE :q3)';
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $p += ['q' => $like, 'q2' => $like, 'q3' => $like];
        }
        $w = implode(' AND ', $where);
        $total = (int) Database::value("SELECT COUNT(*) FROM activity_logs l WHERE $w", $p);
        $pg = new Paginator($total, 40, $req->int('page', 1));
        $rows = Database::all("SELECT l.*, a.name FROM activity_logs l LEFT JOIN admins a ON a.id = l.admin_id WHERE $w ORDER BY l.id DESC LIMIT :lim OFFSET :off", $p + ['lim' => 40, 'off' => $pg->offset]);
        $modules = array_column(Database::all('SELECT DISTINCT module FROM activity_logs ORDER BY module'), 'module');
        $admins = Database::all('SELECT id, name FROM admins ORDER BY name');
        $logins = Database::all('SELECT email, ip, success, reason, created_at FROM login_attempts ORDER BY id DESC LIMIT 15');
        $this->render('system/logs', compact('rows', 'modules', 'admins', 'module', 'admin', 'q', 'logins') + ['pager' => $pg], 'Activity logs', [['System', null], ['Activity logs', null]]);
    }

    public function info(Request $req): never
    {
        Auth::authorize('system');
        $dirs = ['config/' => ROOT_PATH . '/config', 'storage/' => STORAGE_PATH, 'storage/logs/' => STORAGE_PATH . '/logs', 'storage/sessions/' => STORAGE_PATH . '/sessions', 'uploads/' => UPLOAD_PATH];
        $checks = [];
        foreach ($dirs as $label => $dir) {
            $checks[$label] = is_dir($dir) && is_writable($dir);
        }
        $info = [
            'PHP version' => PHP_VERSION,
            'Database server' => (string) Database::value('SELECT VERSION()'),
            'Web server' => preg_replace('/\/.*$/', '', (string) ($_SERVER['SERVER_SOFTWARE'] ?? 'unknown')),
            'Base URL' => BASE_URL,
            'HTTPS' => is_https() ? 'Yes' : 'No — enable SSL in cPanel',
            'Environment' => APP_ENV . (APP_DEBUG ? ' (debug ON — turn off in production)' : ''),
            'Upload limit (PHP)' => ini_get('upload_max_filesize') . ' per file, ' . ini_get('post_max_size') . ' per request',
            'Upload limit (site)' => setting('media_max_mb', '5') . ' MB',
            'Memory limit' => ini_get('memory_limit'),
            'Installer' => is_file(INSTALL_LOCK) ? 'Locked (installed ' . trim((string) file_get_contents(INSTALL_LOCK)) . ')' : 'Not locked',
            'Session idle timeout' => (Auth::IDLE_TIMEOUT / 60) . ' minutes',
        ];
        $ext = [];
        foreach (['pdo_mysql', 'gd', 'sodium', 'fileinfo', 'mbstring', 'openssl', 'dom', 'exif', 'iconv'] as $e) {
            $ext[$e] = extension_loaded($e);
        }
        $counts = [];
        foreach (['pages', 'services', 'blog_posts', 'leads', 'contact_messages', 'media', 'admins', 'activity_logs', 'email_logs'] as $t) {
            $counts[$t] = (int) Database::value("SELECT COUNT(*) FROM `$t`");
        }
        $this->render('system/info', compact('info', 'checks', 'ext', 'counts'), 'System information', [['System', null], ['System information', null]]);
    }

    public function profile(Request $req): never
    {
        $this->render('system/profile', ['user' => Auth::user()], 'My profile', [['My profile', null]]);
    }

    public function saveProfile(Request $req): never
    {
        $u = Auth::user();
        $row = Database::one('SELECT * FROM admins WHERE id = :id', ['id' => $u['id']]) ?? Response::abort(404);
        $name = trim((string) $req->post('name'));
        $errors = [];
        if ($name === '' || mb_strlen($name) > 120) {
            $errors['name'] = 'Enter your name.';
        }
        $new = (string) ($_POST['new_password'] ?? '');
        $data = ['name' => $name];
        if ($new !== '') {
            if (!password_verify((string) ($_POST['current_password'] ?? ''), $row['password_hash'])) {
                $errors['current_password'] = 'Your current password is incorrect.';
            } elseif (strlen($new) < 10 || !preg_match('/[A-Za-z]/', $new) || !preg_match('/\d/', $new)) {
                $errors['new_password'] = 'Use at least 10 characters including letters and numbers.';
            } elseif ($new !== (string) ($_POST['new_password_confirmation'] ?? '')) {
                $errors['new_password_confirmation'] = 'Passwords do not match.';
            } else {
                $data['password_hash'] = password_hash($new, PASSWORD_DEFAULT);
            }
        }
        if ($errors) {
            $this->fail($errors, '/' . ADMIN_PREFIX . '/profile');
        }
        Database::update('admins', $data, 'id = :id', ['id' => $row['id']]);
        if (isset($data['password_hash'])) {
            Session::regenerate();
        }
        Activity::log('update', 'profile', (int) $row['id'], isset($data['password_hash']) ? 'Changed own password' : 'Updated own profile');
        $this->back('/' . ADMIN_PREFIX . '/profile', 'success', 'Profile saved.');
    }
}
