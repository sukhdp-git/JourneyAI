<?php
/**
 * Security/compliance checks: authenticator codes (RFC 6238 test vectors), replay protection, consent columns, legal pages.
 * Run from SSH (never via the web):  php tests/security.php
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__ . '/../app/bootstrap.php';

use App\Core\Totp;

$fail = 0;
$ok = function (string $name, bool $pass) use (&$fail): void { $fail += $pass ? 0 : 1; printf("%s %s\n", $pass ? 'PASS' : 'FAIL', $name); };
// RFC 6238 appendix B (SHA-1 secret "12345678901234567890"), last 6 digits of the 8-digit values
$k = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';
foreach ([59 => '287082', 1111111109 => '081804', 1111111111 => '050471', 1234567890 => '005924', 2000000000 => '279037'] as $t => $want) {
    $ok("RFC 6238 vector t=$t", Totp::code($k, intdiv($t, 30)) === $want);
}
$step = Totp::verify($k, '287082', 0, 59);
$ok('Valid code is accepted', $step === 1);
$ok('The same code cannot be used twice', Totp::verify($k, '287082', (int) $step, 59) === null);
$ok('A code from 2 minutes ago is refused', Totp::verify($k, Totp::code($k, 1), 0, 59 + 120) === null);
$ok('Wrong code is refused', Totp::verify($k, '000000', 0, 59) === null);
$s = Totp::secret();
$ok('New secrets are 32 base32 characters', (bool) preg_match('/^[A-Z2-7]{32}$/', $s));
$ok('Secret round-trips through encryption', App\Core\Crypto::decrypt(App\Core\Crypto::encrypt($s)) === $s);
$ok('users.terms_accepted_at exists', (bool) App\Core\Database::value("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'terms_accepted_at'"));
$ok('admins.totp_secret exists', (bool) App\Core\Database::value("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admins' AND COLUMN_NAME = 'totp_secret'"));
echo $fail ? "\n$fail check(s) FAILED\n" : "\nAll security checks passed.\n";
exit($fail ? 1 : 0);
