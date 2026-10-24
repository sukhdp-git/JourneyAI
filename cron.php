<?php
/**
 * journzey.ai scheduled jobs (plan reminder emails). Run from a cPanel cron job, e.g. every hour:
 *   php /home/USERNAME/public_html/cron.php
 * Command line only: requests from a browser get a 404.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__ . '/app/bootstrap.php';

if (!APP_CONFIGURED || !is_file(INSTALL_LOCK)) {
    fwrite(STDERR, "journzey.ai is not installed yet.\n");
    exit(1);
}
App\Core\Migrator::ensure();
App\Core\Settings::save(['jobs_last_run' => (string) time()], 'system');
$n = App\Trading\Reminders::run();
echo gmdate('Y-m-d H:i') . " UTC reminders: {$n['expiring']} expiring, {$n['renewing']} renewing, {$n['expired']} ended\n";
