<?php
declare(strict_types=1);

namespace App\Trading;

/**
 * Monday/Tuesday morning brief: the previous week's (Mon–Sun) mistakes turned into at most three short
 * "don'ts" and three "do's", each with one highlighted number. Built from the active account's closed trades.
 */
final class WeeklyBrief
{
    private const MISTAKE_TIPS = [
        'FOMO_ENTRY' => 'Don’t chase FOMO entries — wait for your setup', 'REVENGE_TRADE' => 'Don’t revenge-trade — pause 15 min after a loss',
        'MOVED_STOP' => 'Don’t move your stop once the trade is live', 'NO_STOP' => 'Never enter without a stop loss',
        'OVERSIZED' => 'Don’t oversize — stick to your risk %', 'EARLY_EXIT' => 'Don’t cut winners early — let them reach target',
        'LATE_ENTRY' => 'Don’t enter late — skip moves you missed', 'CHASING' => 'Don’t chase price', 'IGNORED_PLAN' => 'Don’t ignore your trading plan',
        'OVERTRADING' => 'Don’t overtrade — cap your trades per day', 'NEWS_GAMBLE' => 'Don’t gamble on news releases', 'IMPULSIVE_ENTRY' => 'Don’t take impulsive entries',
    ];

    /** Show automatically on Monday and Tuesday mornings (before noon, member's time). */
    public static function isBriefTime(string $tz, ?\DateTimeImmutable $now = null): bool
    {
        $now = ($now ?? new \DateTimeImmutable('now'))->setTimezone(new \DateTimeZone($tz));
        return in_array((int) $now->format('N'), [1, 2], true) && (int) $now->format('G') < 12;
    }

    public static function build(array $m, array $acc, string $tz): array
    {
        $zone = new \DateTimeZone($tz);
        $mon = (new \DateTimeImmutable('now', $zone))->modify('monday this week')->modify('-7 days');
        $from = $mon->format('Y-m-d');
        $to = $mon->modify('+6 days')->format('Y-m-d');
        $trades = Ledger::forAnalytics((int) $m['id'], (int) $acc['id'], $tz, $from, $to);
        $cur = $acc['currency'];
        $money = fn (float $v) => money($v, $cur, true);
        $dont = $do = [];
        $s = Analytics::summarize($trades);
        if ($trades) {
            // Tagged mistakes, worst first
            $mist = [];
            foreach ($trades as $t) {
                $k = $t['mistake_tag'];
                if ($k && isset(self::MISTAKE_TIPS[$k])) {
                    $mist[$k] = ['n' => ($mist[$k]['n'] ?? 0) + 1, 'pnl' => ($mist[$k]['pnl'] ?? 0) + (float) $t['pnl']];
                }
            }
            uasort($mist, fn ($a, $b) => $a['pnl'] <=> $b['pnl']);
            foreach ($mist as $k => $x) {
                $dont[] = ['text' => self::MISTAKE_TIPS[$k], 'stat' => $x['n'] . '× · ' . $money($x['pnl']), 'w' => $x['pnl']];
            }
            // Broken rules
            $broken = array_filter($trades, fn ($t) => $t['rules_followed'] !== null && (int) $t['rules_followed'] === 0);
            if ($broken) {
                $bp = array_sum(array_map(fn ($t) => (float) $t['pnl'], $broken));
                $dont[] = ['text' => 'Don’t skip your checklist — follow every rule', 'stat' => count($broken) . ' rule breaks · ' . $money($bp), 'w' => $bp - 0.01];
            }
            // Negative emotions
            $neg = array_filter($trades, fn ($t) => in_array($t['emotion'], Domain::NEGATIVE_EMOTIONS, true));
            $same = $broken && !array_diff_key($neg, $broken);   // the same trades as the rule breaks: say it once
            if (count($neg) >= 2 && !$same) {
                $np = array_sum(array_map(fn ($t) => (float) $t['pnl'], $neg));
                if ($np < 0) {
                    $dont[] = ['text' => 'Don’t trade when anxious, bored or angry — step away', 'stat' => count($neg) . ' emotional trades · ' . $money($np), 'w' => $np];
                }
            }
            // Sessions
            $sess = Analytics::groupBy($trades, fn ($t) => $t['session']);
            $worst = null;
            $best = null;
            foreach ($sess as $g) {
                if ($g['s']['trades'] >= 2 && $g['s']['net'] < 0 && (!$worst || $g['s']['net'] < $worst['s']['net'])) {
                    $worst = $g;
                }
                if ($g['s']['trades'] >= 2 && $g['s']['net'] > 0 && (!$best || $g['s']['net'] > $best['s']['net'])) {
                    $best = $g;
                }
            }
            if ($worst) {
                $dont[] = ['text' => 'Cut back on the ' . (Domain::SESSIONS[$worst['key']] ?? $worst['key']) . ' session', 'stat' => $money($worst['s']['net']) . ' · ' . pct($worst['s']['win_rate'], 0) . ' win', 'w' => $worst['s']['net']];
            }
            // Overtrading days
            $perDay = [];
            foreach ($trades as $t) {
                $d = (new \DateTimeImmutable($t['executed_at'], new \DateTimeZone('UTC')))->setTimezone($zone)->format('l');
                $perDay[$d] = ($perDay[$d] ?? 0) + 1;
            }
            arsort($perDay);
            $busiest = array_key_first($perDay);
            if ($perDay[$busiest] >= 6 && $perDay[$busiest] >= 2 * (count($trades) / max(1, count($perDay)))) {
                $dont[] = ['text' => 'Don’t overtrade — set a daily trade limit', 'stat' => $perDay[$busiest] . ' trades on ' . $busiest, 'w' => -1];
            }
            usort($dont, fn ($a, $b) => $a['w'] <=> $b['w']);
            // Do's
            if ($best) {
                $do[] = ['text' => 'Focus on the ' . (Domain::SESSIONS[$best['key']] ?? $best['key']) . ' session', 'stat' => $money($best['s']['net']) . ' · ' . pct($best['s']['win_rate'], 0) . ' win'];
            }
            $setups = Analytics::groupBy($trades, fn ($t) => $t['strategy_name'] ?: null);
            foreach ($setups as $g) {
                if ($g['key'] && $g['s']['trades'] >= 2 && $g['s']['net'] > 0) {
                    $do[] = ['text' => 'Keep trading your “' . $g['key'] . '” strategy', 'stat' => $money($g['s']['net']) . ' · ' . $g['s']['trades'] . ' trades'];
                    break;
                }
            }
            $syms = Analytics::groupBy($trades, fn ($t) => $t['symbol']);
            foreach ($syms as $g) {
                if ($g['s']['trades'] >= 2 && $g['s']['net'] > 0) {
                    $do[] = ['text' => 'Your best market was ' . $g['key'], 'stat' => $money($g['s']['net'])];
                    break;
                }
            }
            $kept = array_filter($trades, fn ($t) => (int) $t['rules_followed'] === 1);
            if ($kept && $broken) {
                $kp = array_sum(array_map(fn ($t) => (float) $t['pnl'], $kept));
                if ($kp > 0) {
                    $do[] = ['text' => 'Follow your rules — it pays', 'stat' => $money($kp) . ' when you did'];
                }
            }
        }
        foreach (['Write tomorrow’s plan before the session opens', 'Risk the same % on every trade', 'Log every trade within the hour'] as $tip) {
            if (count($do) >= 3) {
                break;
            }
            $do[] = ['text' => $tip, 'stat' => ''];
        }
        if (!$dont && $trades) {
            $dont[] = ['text' => 'Don’t change what works — no rule breaks last week', 'stat' => '0 tagged mistakes'];
        }
        return [
            'week' => (new \DateTimeImmutable($from))->format('M j') . ' – ' . (new \DateTimeImmutable($to))->format('M j'), 'from' => $from,
            'trades' => count($trades), 'net' => $s['net'], 'net_fmt' => $money((float) $s['net']), 'win_rate' => $s['win_rate'],
            'dont' => array_slice(array_map(fn ($x) => ['text' => $x['text'], 'stat' => $x['stat']], $dont), 0, 3), 'do' => array_slice($do, 0, 3),
        ];
    }
}
