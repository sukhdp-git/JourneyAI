<?php
/**
 * journzey.ai website configuration.
 *
 * Copy this file to config/config.php and fill in your values, OR run the web installer at
 * https://YOUR-DOMAIN/setup which writes config/config.php for you.
 * config/config.php is protected from web access by .htaccess. Never commit real credentials.
 */

// Database (cPanel → MySQL® Databases). Host is usually "localhost" on shared hosting.
define('DB_HOST', 'localhost');
define('DB_PORT', 3306);
define('DB_NAME', 'cpaneluser_journzey');
define('DB_USER', 'cpaneluser_journzey');
define('DB_PASS', 'change-me');

// Full public URL of the site, no trailing slash. Include a sub-folder if installed in one.
define('BASE_URL', 'https://journzey.ai');

// 32+ random characters. Encrypts the SMTP password stored in the database.
// Generate one at the installer or with: php -r "echo bin2hex(random_bytes(32));"
define('APP_KEY', 'replace-with-64-hex-characters-generated-randomly-for-this-site-0000');

// production | development. Never enable debug on a live site.
define('APP_ENV', 'production');
define('APP_DEBUG', false);
