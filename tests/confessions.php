<?php
/**
 * Spam-shield and handle checks for Confessions. Run from SSH (never via the web):  php tests/confessions.php
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__ . '/../app/bootstrap.php';

use App\Trading\Confessions as C;

$fail = 0;
$t = function (string $text, bool $blocked) use (&$fail): void {
    $why = C::guard($text);
    $ok = ($why !== null) === $blocked;
    $fail += $ok ? 0 : 1;
    printf("%s %-7s %-70s %s\n", $ok ? 'PASS' : 'FAIL', $blocked ? 'block' : 'allow', mb_strimwidth($text, 0, 70, '…'), $why ?? '');
};
// must be blocked
foreach ([
    'DM me for free signals', 'Join my VIP group, 90% accuracy', 'Contact me on telegram @goldking', 'whatsapp +91 98765 43210 for account management',
    'check www.bestsignals.com', 'go to t.me/goldvip', 'visit goldpro dot com today', 'my channel t e l e g r a m goldvip', 'Guaranteed profit every week',
    'I will pass your challenge, inbox me', 'Copy my trades and double your account', 'link in bio for my course', 'use my code JZ50 for discount',
    'send usdt to 0x52908400098527886E0F7030069857D2E4169EE7', 'email me at gold.trader@gmail.com', 'DM karo bhai group join karo', 'd.m me for the setup',
    'FREE SIGNALS EVERY DAY JOIN NOW BROTHERS LETS GO', 'buy my indicator, 100% win', 'mentorship available, 5 slots', 'earn $500 daily with my system',
] as $s) {
    $t($s, true);
}
// honest trading talk must pass
foreach ([
    'Moved my stop 4 times on gold today. Lost 3R. Never again.', 'Ignored the signals my own plan gave me and revenge traded NAS.',
    'First green week after 3 months of journaling. Small, but mine.', 'Is risking 1% too small for a 5k account? Beginner question.',
    'Took a 2.5R win on EURUSD at London open, waited for the retest.', 'My win rate is 42% but profit factor 1.6. Is that good?',
    'Lost 15000 on the account this year, rebuilding slowly with 0.5% risk.', 'Gold went from 4120 to 4180 while I sat on my hands. FOMO is real 😭',
    'Rule: 2 losses = laptop closed. Day 12 of following it.', 'DXY up, gold down. I forgot to check the dollar again.',
] as $s) {
    $t($s, false);
}
// handles
foreach ([['calm_trader', true], ['ab', false], ['journzey_team', false], ['admin', false], ['vip_signals', false], ['gold.monk', true], ['_x_', false], ['trader9876543210', false]] as [$h, $ok]) {
    $good = C::validateHandle($h) === null;
    $fail += $good === $ok ? 0 : 1;
    printf("%s handle %-20s %s\n", $good === $ok ? 'PASS' : 'FAIL', $h, $good ? 'ok' : C::validateHandle($h));
}
printf("%s respect: 3 likes = %s, 1000 likes = %s\n", C::respect(3) === '$45' && C::respect(1000) === '$15K' ? 'PASS' : 'FAIL', C::respect(3), C::respect(1000));
echo $fail ? "\n$fail check(s) failed\n" : "\nAll Confessions checks passed\n";
exit($fail ? 1 : 0);
