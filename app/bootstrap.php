<?php
declare(strict_types=1);

/**
 * Application bootstrap: autoloading, configuration, error handling.
 * Every request enters through /index.php, which includes this file.
 */

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('STORAGE_PATH', ROOT_PATH . '/storage');
define('UPLOAD_PATH', ROOT_PATH . '/uploads');
define('CONFIG_FILE', ROOT_PATH . '/config/config.php');
define('INSTALL_LOCK', STORAGE_PATH . '/installed.lock');
define('ADMIN_PREFIX', 'control-panel');

spl_autoload_register(static function (string $class): void {
    $map = ['App\\Core\\' => APP_PATH . '/core/', 'App\\Controllers\\' => APP_PATH . '/controllers/', 'App\\Models\\' => APP_PATH . '/models/', 'PHPMailer\\PHPMailer\\' => ROOT_PATH . '/vendor/phpmailer/phpmailer/src/'];
    foreach ($map as $prefix => $dir) {
        if (str_starts_with($class, $prefix)) {
            $rel = substr($class, strlen($prefix));
            $parts = explode('\\', $rel);
            // Controllers sub-namespace Admin lives in lower-case folder "admin".
            if ($prefix === 'App\\Controllers\\' && count($parts) > 1) {
                $parts[0] = strtolower($parts[0]);
            }
            $file = $dir . implode('/', $parts) . '.php';
            if (is_file($file)) {
                require $file;
            }
            return;
        }
    }
});

require APP_PATH . '/helpers/functions.php';

$configured = is_file(CONFIG_FILE);
if ($configured) {
    require CONFIG_FILE;
}
define('APP_CONFIGURED', $configured && defined('DB_HOST'));
if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', false);
}
if (!defined('APP_ENV')) {
    define('APP_ENV', 'production');
}
if (!defined('BASE_URL')) {
    // Before installation, derive the base URL from the request so the installer works.
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') ? 'https' : 'http';
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    define('BASE_URL', $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $dir);
}

date_default_timezone_set('UTC');
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', STORAGE_PATH . '/logs/php-error.log');
error_reporting(E_ALL);
App\Core\ErrorHandler::register();
