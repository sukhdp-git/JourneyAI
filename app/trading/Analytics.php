<?php
declare(strict_types=1);

namespace App\Trading;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Performance analytics over a member's closed trades. Every function works on rows already
 * filtered to the signed-in member (and to demo or live data) by the caller.
 * Trade row keys used: id, executed_at (UTC), symbol, side, pnl, rr, risk_amount, strategy_name, setup_tag,
 * session, emotion, mistake_tag, rules_followed.
 */
final class Analytics
{
    public static function closed(array $trades): array
    {
        return array_values(array_filter($trades, fn ($t) => $t['pnl'] !== null && $t['pnl'] !== ''));
    }

    public static function summarize(array $all): array
    {
        $trades = self::closed($all);
        $gp = $gl = 0.0;
        $wins = $losses = $be = $compliant = $rCount = 0;
        $rSum = 0.0;
        $best = $worst = null;
        foreach ($trades as $t) {
            $p = (float) $t['pnl'];
            if ($p > 0) {
                $wins++;
                $gp += $p;
            } elseif ($p < 0) {
                $losses++;
                $gl += -$p;
            } else {
                $be++;
            }
            $best = $best === null || $p > $best ? $p : $best;
            $worst = $worst === null || $p < $worst ? $p : $worst;
            if ($t['rr'] !== null && $t['rr'] !== '') {
                $rSum += (float) $t['rr'];
                $rCount++;
            }
            if ((int) $t['rules_followed']) {
                $compliant++;
            }
        }
        $n = count($trades);
        $avgWin = $wins ? $gp / $wins : null;
        $avgLoss = $losses ? $gl / $losses : null;
        return [
            'trades' => $n, 'wins' => $wins, 'losses' => $losses, 'breakeven' => $be,
            'win_rate' => $n ? $wins / $n : null,
            'net' => round($gp - $gl, 2), 'gross_profit' => round($gp, 2), 'gross_loss' => round($gl, 2),
            'profit_factor' => $gl > 0 ? round($gp / $gl, 2) : null,
            'avg_win' => $avgWin === null ? null : round($avgWin, 2), 'avg_loss' => $avgLoss === null ? null : round($avgLoss, 2),
            'payoff' => $avgWin && $avgLoss ? round($avgWin / $avgLoss, 2) : null,
            'expectancy' => $n ? round(($gp - $gl) / $n, 2) : null,
            'avg_r' => $rCount ? round($rSum / $rCount, 2) : null,
            'best' => $best === null ? null : round($best, 2), 'worst' => $worst === null ? null : round($worst, 2),
            'compliance' => $n ? $compliant / $n : null,
        ];
    }

    /** Equity curve and drawdown from the starting balance (trades sorted ascending). */
    public static function equity(array $sorted, float $start): array
    {
        $eq = $peak = $start;
        $cum = $maxDd = $maxDdPct = 0.0;
        $points = [];
        foreach (self::closed($sorted) as $t) {
            $p = (float) $t['pnl'];
            $cum += $p;
            $eq += $p;
            $peak = max($peak, $eq);
            $dd = $peak - $eq;
            $ddPct = $peak > 0 ? $dd / $peak : 0;
            $maxDd = max($maxDd, $dd);
            $maxDdPct = max($maxDdPct, $ddPct);
            $points[] = ['t' => $t['executed_at'], 'pnl' => round($p, 2), 'cum' => round($cum, 2), 'equity' => round($eq, 2), 'dd' => round($dd, 2), 'dd_pct' => round($ddPct * 100, 2)];
        }
        return ['points' => $points, 'max_dd' => round($maxDd, 2), 'max_dd_pct' => round($maxDdPct * 100, 2), 'final' => round($eq, 2)];
    }

    /** @param callable $keyOf fn(array $trade): ?string */
    public static function groupBy(array $trades, callable $keyOf, bool $sortByNet = true): array
    {
        $g = [];
        foreach (self::closed($trades) as $t) {
            $k = $keyOf($t);
            if ($k === null || $k === '') {
                continue;
            }
            $g[$k][] = $t;
        }
        $out = [];
        foreach ($g as $k => $ts) {
            $out[] = ['key' => (string) $k, 's' => self::summarize($ts)];
        }
        if ($sortByNet) {
            usort($out, fn ($a, $b) => $b['s']['net'] <=> $a['s']['net']);
        }
        return $out;
    }

    public static function local(string $utc, string $tz): DateTimeImmutable
    {
        return (new DateTimeImmutable($utc, new DateTimeZone('UTC')))->setTimezone(new DateTimeZone($tz));
    }

    public static function byWeekday(array $trades, string $tz): array
    {
        $names = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $g = self::groupBy($trades, fn ($t) => (string) ((int) self::local($t['executed_at'], $tz)->format('N')), false);
        usort($g, fn ($a, $b) => (int) $a['key'] <=> (int) $b['key']);
        return array_map(fn ($x) => ['key' => $names[(int) $x['key'] - 1], 's' => $x['s']], $g);
    }

    public static function byHour(array $trades, string $tz): array
    {
        $g = self::groupBy($trades, fn ($t) => self::local($t['executed_at'], $tz)->format('H'), false);
        usort($g, fn ($a, $b) => (int) $a['key'] <=> (int) $b['key']);
        return array_map(fn ($x) => ['key' => $x['key'] . ':00', 's' => $x['s']], $g);
    }

    public static function bestByExpectancy(array $groups, int $min = 3): ?array
    {
        $best = null;
        foreach ($groups as $g) {
            if ($g['s']['trades'] >= $min && $g['s']['expectancy'] !== null && ($best === null || $g['s']['expectancy'] > $best['s']['expectancy'])) {
                $best = $g;
            }
        }
        return $best;
    }

    /**
     * Discipline Leak = Flawless Execution P&L − Actual P&L. The hypothetical is conservative: impulse
     * entries are treated as not taken, stop/size violations have losses capped at 1R, nothing gains upside.
     */
    /** Conservative rule-compliant ("emotion-filtered") P&L of one trade: never adds hypothetical profit. */
    public static function hypothetical(array $t): float
    {
        $p = (float) $t['pnl'];
        $mistake = $t['mistake_tag'] ?: 'NONE';
        if ((int) $t['rules_followed'] && $mistake === 'NONE') {
            return $p;
        }
        $treat = Domain::LEAK_TREATMENT[$mistake !== 'NONE' ? $mistake : 'IGNORED_PLAN'] ?? 'ACTUAL';
        if ($treat === 'SKIP') {
            return 0.0;
        }
        if ($treat === 'CAP_LOSS' && $p < 0 && $t['risk_amount']) {
            return max($p, -abs((float) $t['risk_amount']));
        }
        return $p;
    }

    public static function disciplineLeak(array $all): array
    {
        $actual = $flawless = 0.0;
        $violations = 0;
        $per = [];
        foreach (self::closed($all) as $t) {
            $p = (float) $t['pnl'];
            $actual += $p;
            $h = $p;
            $mistake = $t['mistake_tag'] ?: 'NONE';
            if (!(int) $t['rules_followed'] || $mistake !== 'NONE') {
                $violations++;
                $m = $mistake !== 'NONE' ? $mistake : 'IGNORED_PLAN';
                $treat = Domain::LEAK_TREATMENT[$m] ?? 'ACTUAL';
                if ($treat === 'SKIP') {
                    $h = 0.0;
                } elseif ($treat === 'CAP_LOSS' && $p < 0 && $t['risk_amount']) {
                    $h = max($p, -abs((float) $t['risk_amount']));
                }
                $per[$m] ??= ['trades' => 0, 'actual' => 0.0, 'flawless' => 0.0];
                $per[$m]['trades']++;
                $per[$m]['actual'] += $p;
                $per[$m]['flawless'] += $h;
            }
            $flawless += $h;
        }
        $rows = [];
        foreach ($per as $m => $b) {
            $rows[] = ['mistake' => $m, 'trades' => $b['trades'], 'actual' => round($b['actual'], 2), 'flawless' => round($b['flawless'], 2), 'leak' => round($b['flawless'] - $b['actual'], 2)];
        }
        usort($rows, fn ($a, $b) => $b['leak'] <=> $a['leak']);
        return ['actual' => round($actual, 2), 'flawless' => round($flawless, 2), 'leak' => round($flawless - $actual, 2), 'violations' => $violations, 'by_mistake' => $rows];
    }

    public static function emotionalVsDisciplined(array $all): array
    {
        $tr = self::closed($all);
        $neg = fn ($t) => $t['emotion'] && in_array($t['emotion'], Domain::NEGATIVE_EMOTIONS, true);
        return [
            'disciplined' => self::summarize(array_filter($tr, fn ($t) => (int) $t['rules_followed'] && !$neg($t))),
            'emotional' => self::summarize(array_filter($tr, fn ($t) => !(int) $t['rules_followed'] || $neg($t))),
        ];
    }

    /** Monte Carlo drawdown simulation with a seeded PRNG (reproducible). */
    public static function monteCarlo(float $winRate, float $avgWinR, float $avgLossR, float $riskFraction, int $tradesPerPath, int $paths = 1000, int $seed = 42): array
    {
        mt_srand($seed);
        $th = [0.10, 0.25, 0.50];
        $hits = [0, 0, 0];
        $finals = $maxDds = [];
        $steps = min($tradesPerPath, 40);
        $snap = array_fill(0, $steps + 1, []);
        for ($p = 0; $p < $paths; $p++) {
            $eq = $peak = 1.0;
            $maxDd = 0.0;
            $snap[0][] = 1.0;
            $nextSnap = 1;
            for ($k = 1; $k <= $tradesPerPath; $k++) {
                $r = (mt_rand() / mt_getrandmax()) < $winRate ? $avgWinR : -$avgLossR;
                $eq = max(0.0, $eq * (1 + $riskFraction * $r));
                $peak = max($peak, $eq);
                $dd = $peak > 0 ? ($peak - $eq) / $peak : 1;
                $maxDd = max($maxDd, $dd);
                while ($nextSnap <= $steps && $k >= (int) round($nextSnap * $tradesPerPath / $steps)) {
                    $snap[$nextSnap][] = $eq;
                    $nextSnap++;
                }
            }
            $finals[] = $eq - 1;
            $maxDds[] = $maxDd;
            foreach ($th as $i => $x) {
                if ($maxDd >= $x) {
                    $hits[$i]++;
                }
            }
        }
        sort($finals);
        sort($maxDds);
        $pct = fn (array $s, float $q) => $s ? $s[(int) min(count($s) - 1, max(0, round((count($s) - 1) * $q)))] : 0;
        $bands = [];
        foreach ($snap as $i => $vals) {
            sort($vals);
            $bands[] = ['step' => (int) round($i * $tradesPerPath / max(1, $steps)), 'p5' => round($pct($vals, .05), 4), 'p50' => round($pct($vals, .5), 4), 'p95' => round($pct($vals, .95), 4)];
        }
        return [
            'paths' => $paths, 'trades' => $tradesPerPath,
            'prob' => array_map(fn ($i) => ['dd' => (int) ($th[$i] * 100), 'p' => round($hits[$i] / $paths, 4)], array_keys($th)),
            'median_return' => round($pct($finals, .5), 4), 'p5_return' => round($pct($finals, .05), 4), 'p95_return' => round($pct($finals, .95), 4),
            'median_max_dd' => round($pct($maxDds, .5), 4), 'bands' => $bands,
        ];
    }

    /** Tilt: N losing trades within W minutes; active until the cooldown after the last loss ends. */
    public static function tilt(array $all, int $count, int $windowMin, int $cooldownMin, ?int $now = null): array
    {
        $now ??= time();
        $losses = array_values(array_filter(self::closed($all), fn ($t) => (float) $t['pnl'] < 0));
        usort($losses, fn ($a, $b) => strcmp($a['executed_at'], $b['executed_at']));
        $trigger = null;
        for ($i = $count - 1; $i < count($losses); $i++) {
            $first = strtotime($losses[$i - $count + 1]['executed_at'] . ' UTC');
            $last = strtotime($losses[$i]['executed_at'] . ' UTC');
            if ($last - $first <= $windowMin * 60) {
                $trigger = ['at' => $last, 'cluster' => array_slice($losses, $i - $count + 1, $count)];
            }
        }
        if (!$trigger) {
            return ['active' => false, 'last' => null];
        }
        $ends = $trigger['at'] + $cooldownMin * 60;
        return ['active' => $now < $ends && $now >= $trigger['at'], 'triggered_at' => $trigger['at'], 'ends_at' => $ends, 'losses' => $trigger['cluster'], 'last' => $trigger['at']];
    }
}
