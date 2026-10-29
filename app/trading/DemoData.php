<?php
declare(strict_types=1);

namespace App\Trading;

/**
 * DEMO DATA journal (always loaded into a demo account, is_demo = 1, never mixed into live analytics).
 *
 * The trades are generated deterministically but anchored to the day the demo is loaded, so every feature
 * that looks at "recent" trades has something to show: the last trading day (Roast Mode), this month (Boss
 * Fight), this week (Psych Art), the last 30 days (Blow-up radar, Edge audit) and the last 60 trades (Death
 * Clock, Ghost). It tells one honest story in three phases over ~100 business days:
 *   1. Disciplined: ~1% risk, playbook setups only, profitable.
 *   2. Drift: sizes creep up and rule breaks start (FOMO, early exits, B-setups).
 *   3. Tilt month: 2-3% risk, revenge clusters, moved stops, overtrading in the NY afternoon. The clean
 *      trades still win, so the Ghost (same trades, rules followed) stays well ahead of the real account.
 * The last trading day before today ends with two losses and a revenge trade (roast + tilt detection).
 */
final class DemoData
{
    /** [strategy, symbol, side (LONG|SHORT|BOTH), UTC hour, minute-from, win probability, win R from, win R to] */
    private const SETUPS = [
        ['VAL Bounce', 'XAUUSD', 'LONG', 8, 5, 0.64, 1.8, 3.0],
        ['Fair Value Gap', 'XAUUSD', 'SHORT', 7, 50, 0.56, 1.6, 2.6],
        ['ICT Silver Bullet', 'NAS100', 'BOTH', 14, 40, 0.57, 2.0, 3.0],
        ['Order Block', 'EURUSD', 'BOTH', 9, 5, 0.52, 1.4, 2.2],
        ['VAH Rejection', 'US500', 'SHORT', 14, 30, 0.52, 1.8, 2.6],
        ['Liquidity Sweep', 'BTCUSDT', 'BOTH', 13, 50, 0.48, 1.8, 2.4],
        ['POC', 'EURUSD', 'BOTH', 8, 40, 0.58, 1.1, 1.5],
        ['Volume Profile', 'US500', 'LONG', 14, 30, 0.50, 1.8, 2.4],
        ['Trend Following', 'USOIL', 'BOTH', 14, 10, 0.45, 1.8, 2.6],
    ];
    /** Setup weights in each phase (index = SETUPS row): the disciplined phase sticks to the A+ setups. */
    private const WEIGHTS = [
        1 => [6, 3, 4, 2, 2, 1, 2, 2, 1],
        2 => [4, 3, 3, 3, 2, 2, 2, 2, 2],
        3 => [3, 2, 3, 3, 2, 3, 2, 2, 3],
    ];
    /** [base price, total drift over the period, typical stop distance] */
    private const MODEL = [
        'XAUUSD' => [4050, 260, 6], 'US500' => [6580, 260, 11], 'NAS100' => [24300, 900, 38],
        'EURUSD' => [1.158, 0.022, 0.0022], 'BTCUSDT' => [109000, 9000, 950], 'USOIL' => [64, 5, 0.38],
    ];
    /** Mistakes per phase: chance that a trade breaks a rule, and which rule. */
    private const LEAKS = [
        1 => [0.06, ['EARLY_EXIT', 'LATE_ENTRY', 'FOMO_ENTRY']],
        2 => [0.22, ['FOMO_ENTRY', 'EARLY_EXIT', 'IGNORED_PLAN', 'OVERSIZED', 'NEWS_GAMBLE', 'MOVED_STOP']],
        3 => [0.42, ['FOMO_ENTRY', 'MOVED_STOP', 'OVERSIZED', 'CHASING', 'OVERTRADING', 'NO_STOP', 'IGNORED_PLAN']],
    ];
    private const NOTES = [
        'NONE_W' => ['Textbook execution, held to target.', 'Waited for confirmation, clean fill.', 'Partials at 1R, runner to target.', 'Plan followed start to finish.', 'A+ setup at the right time of day.'],
        'NONE_L' => ['Valid setup, stopped out. Part of the edge.', 'Good trade, bad outcome.', 'Clean loss, plan followed.', 'Invalidated by the news drift. Fine.'],
        'FOMO_ENTRY' => ['Chased the candle after missing the entry.', 'Jumped in before confirmation. FOMO.', 'Saw it move without me and entered late.'],
        'REVENGE_TRADE' => ['Revenge flip right after the loss.', 'Wanted the money back. Doubled size.', 'Third trade in 20 minutes. Tilt.'],
        'MOVED_STOP' => ['Moved my stop wider "just this once".', 'Stop moved twice. Loss doubled.', 'Gave it room. It took the room and more.'],
        'OVERSIZED' => ['Doubled size after two winners.', 'Size by mood, not by plan.', 'Too big. Every tick hurt.'],
        'EARLY_EXIT' => ['Cut it early on noise; target hit later.', 'Closed at the first pullback. Fear.'],
        'LATE_ENTRY' => ['Entered after the move; poor location.'],
        'IGNORED_PLAN' => ['Took a B-setup outside the playbook.', 'Not in my plan. Took it anyway.'],
        'NEWS_GAMBLE' => ['Traded straight into tier-1 news.', 'Held through CPI. Gamble.'],
        'CHASING' => ['Chased the extension late in the session.', 'Bought the top of the move.'],
        'OVERTRADING' => ['Boredom trade in the NY afternoon.', 'Fifth trade of the day. Should have closed the platform.'],
        'NO_STOP' => ['No hard stop. Mental stop failed.'],
    ];
    private const EMOTION = [
        'FOMO_ENTRY' => 'FOMO', 'REVENGE_TRADE' => 'REVENGE', 'MOVED_STOP' => 'FRUSTRATED', 'OVERSIZED' => 'GREEDY', 'EARLY_EXIT' => 'ANXIOUS', 'LATE_ENTRY' => 'ANXIOUS',
        'IGNORED_PLAN' => 'ANXIOUS', 'NEWS_GAMBLE' => 'FOMO', 'CHASING' => 'GREEDY', 'OVERTRADING' => 'BORED', 'NO_STOP' => 'FRUSTRATED',
    ];
    public const START_CAPITAL = 25000.0;
    private const DAYS = 100;
    /** RNG seed (chosen so every feature has a meaningful example; see tests). */
    public static int $seed = 532;

    /** Business days (Y-m-d), oldest first, ending with the last weekday before today in $tz. */
    public static function businessDays(string $tz = 'UTC', ?int $now = null, int $n = self::DAYS): array
    {
        $z = new \DateTimeZone(in_array($tz, \DateTimeZone::listIdentifiers(), true) ? $tz : 'UTC');
        $d = (new \DateTimeImmutable('@' . ($now ?? time())))->setTimezone($z)->setTime(12, 0)->modify('-1 day');
        $out = [];
        while (count($out) < $n) {
            if ((int) $d->format('N') <= 5) {
                $out[] = $d->format('Y-m-d');
            }
            $d = $d->modify('-1 day');
        }
        return array_reverse($out);
    }

    /** @return array<array> trade rows ready to insert (without user/account/strategy ids), oldest first */
    public static function trades(string $tz = 'UTC', ?int $now = null): array
    {
        mt_srand(self::$seed);
        $rand = fn () => mt_rand() / mt_getrandmax();
        $pick = function (array $list) use ($rand) { return $list[(int) floor($rand() * count($list)) % count($list)]; };
        $days = self::businessDays($tz, $now);
        $n = count($days);
        $equity = self::START_CAPITAL;
        $out = [];
        foreach ($days as $k => $day) {
            $back = $n - 1 - $k;                                   // 0 = last trading day before today
            $phase = $back >= 60 ? 1 : ($back >= 30 ? 2 : 3);
            if ($back <= 3) {
                $plan = self::finalDays($back);                    // scripted: roast, tilt, a varied Psych Art week
            } else {
                $roll = $rand();
                $count = $phase === 1 ? ($roll < 0.25 ? 0 : ($roll < 0.72 ? 1 : 2)) : ($phase === 2 ? ($roll < 0.2 ? 0 : ($roll < 0.62 ? 1 : 2)) : ($roll < 0.15 ? 0 : ($roll < 0.62 ? 1 : ($roll < 0.94 ? 2 : 3))));
                $plan = [];
                for ($i = 0; $i < $count; $i++) {
                    [$rate, $pool] = self::LEAKS[$phase];
                    $plan[] = ['setup' => self::weighted(self::WEIGHTS[$phase], $rand), 'leak' => $rand() < $rate ? $pick($pool) : 'NONE'];
                }
                // tilt month: after a losing day start, a revenge trade sometimes follows within minutes
                if ($phase === 3 && $count >= 1 && $rand() < 0.28) {
                    $plan[] = ['setup' => null, 'leak' => 'REVENGE_TRADE'];
                }
            }
            $prev = null;
            foreach ($plan as $i => $p) {
                $leak = $p['leak'];
                if ($leak === 'REVENGE_TRADE' && $prev) {
                    $s = self::SETUPS[$prev['idx']];
                    $side = $prev['side'] === 'LONG' ? 'SHORT' : 'LONG';
                    $minute = $prev['minute'] + 9 + (int) floor($rand() * 14);
                } else {
                    $idx = $p['setup'] ?? self::weighted(self::WEIGHTS[$phase], $rand);
                    $s = self::SETUPS[$idx];
                    $side = $s[2] === 'BOTH' ? ($rand() < 0.5 ? 'LONG' : 'SHORT') : $s[2];
                    $minute = $s[3] * 60 + $s[4] + (int) floor($rand() * 12) + $i * 50;
                    if (in_array($leak, ['OVERTRADING', 'CHASING', 'FOMO_ENTRY', 'LATE_ENTRY'], true)) {
                        $minute = max($minute, 15 * 60 + 35 + (int) floor($rand() * 70));   // the NY-afternoon anti-window
                    }
                    $p['idx'] = $idx;
                }
                $idx = $p['idx'] ?? $prev['idx'];
                [$strategy, $sym, , , , $winP, $rLo, $rHi] = self::SETUPS[$idx];
                // outcome in R (relative to the planned stop)
                $r = match ($leak) {
                    'FOMO_ENTRY', 'CHASING', 'OVERTRADING', 'LATE_ENTRY', 'IGNORED_PLAN', 'NEWS_GAMBLE' => $rand() < 0.22 ? round(0.8 + $rand() * 0.8, 2) : -round(1 + $rand() * 0.15, 2),
                    'REVENGE_TRADE' => $rand() < 0.2 ? round(0.6 + $rand(), 2) : -round(1 + $rand() * 0.3, 2),
                    'MOVED_STOP' => -round(1.7 + $rand() * 0.9, 2),
                    'NO_STOP' => -round(2.0 + $rand() * 0.6, 2),
                    'EARLY_EXIT' => round(0.35 + $rand() * 0.35, 2),
                    default => $rand() < $winP ? round($rLo + $rand() * ($rHi - $rLo), 2) : -round(1 + $rand() * 0.05, 2),
                };
                if (isset($p['r'])) {
                    $r = $p['r'];
                }
                $riskPct = [1 => 0.9 + $rand() * 0.25, 2 => 1.15 + $rand() * 0.5, 3 => 1.35 + $rand() * 0.45][$phase];
                if (isset($p['risk'])) {
                    $riskPct = $p['risk'];
                }
                if ($leak === 'OVERSIZED') {
                    $riskPct *= 2.0;
                } elseif ($leak === 'REVENGE_TRADE') {
                    $riskPct *= 1.5;
                }
                $row = self::price($sym, $side, $k / max(1, $n - 1), $r, $equity * $riskPct / 100, $rand);
                $hh = intdiv(min($minute, 17 * 60 + 20), 60);
                $mm = min($minute, 17 * 60 + 20) % 60;
                $exec = sprintf('%s %02d:%02d:00', $day, $hh, $mm);
                $closed = gmdate('Y-m-d H:i:s', strtotime($exec . ' UTC') + (8 + (int) floor($rand() * ($leak === 'REVENGE_TRADE' ? 10 : 90))) * 60);
                $win = $r > 0;
                $notesKey = $leak === 'NONE' ? ($win ? 'NONE_W' : 'NONE_L') : $leak;
                $emotion = $leak === 'NONE' ? ($win ? $pick(['CALM', 'FOCUSED', 'CONFIDENT', 'DISCIPLINED']) : $pick(['CALM', 'NEUTRAL', 'PATIENT'])) : self::EMOTION[$leak];
                $out[] = $row + [
                    'executed_at' => $exec, 'closed_at' => $closed, 'symbol' => $sym, 'side' => $side, 'strategy' => $strategy, 'emotion' => $emotion,
                    'mistake' => $leak, 'rules' => $leak === 'NONE' ? 1 : 0, 'notes' => '[DEMO DATA] ' . ($p['note'] ?? $pick(self::NOTES[$notesKey])),
                ];
                $equity += $row['pnl_est'];
                $prev = ['idx' => $idx, 'side' => $side, 'minute' => $minute];
            }
        }
        return $out;
    }

    /** The last four trading days are scripted so the Mind tab always has a story to tell. */
    private static function finalDays(int $back): array
    {
        return match ($back) {
            // the last trading day: a valid loss, another loss, then a revenge flip within minutes → roast + tilt
            0 => [
                ['setup' => 0, 'leak' => 'NONE', 'r' => -1.0, 'risk' => 0.9, 'note' => 'Valid VAL bounce, stopped out. Fine.'],
                ['setup' => 1, 'leak' => 'NONE', 'r' => -1.0, 'risk' => 0.9, 'note' => 'Second loss. Should have stopped here.'],
                ['setup' => null, 'leak' => 'REVENGE_TRADE', 'r' => -1.2, 'risk' => 0.9, 'note' => 'Revenge flip after two losses. Classic tilt.'],
            ],
            1 => [['setup' => 2, 'leak' => 'NONE', 'r' => 2.4, 'risk' => 1.8], ['setup' => 4, 'leak' => 'FOMO_ENTRY', 'r' => -1.05, 'risk' => 2.0]],
            2 => [['setup' => 3, 'leak' => 'MOVED_STOP', 'r' => -2.3, 'risk' => 1.9], ['setup' => 6, 'leak' => 'NONE', 'r' => 1.4, 'risk' => 1.8]],
            default => [['setup' => 0, 'leak' => 'NONE', 'r' => 2.6, 'risk' => 1.9], ['setup' => 2, 'leak' => 'NONE', 'r' => -1.0, 'risk' => 1.9]],
        };
    }

    private static function weighted(array $w, callable $rand): int
    {
        $x = $rand() * array_sum($w);
        foreach ($w as $i => $v) {
            if (($x -= $v) <= 0) {
                return $i;
            }
        }
        return 0;
    }

    /** Entry, stop, target, exit and lot size for a trade that risks $riskMoney and ends at $r R. */
    private static function price(string $sym, string $side, float $progress, float $r, float $riskMoney, callable $rand): array
    {
        $inst = Instruments::get($sym);
        [$base, $drift, $stopD] = self::MODEL[$sym];
        $dec = $inst['decimals'];
        $entry = round($base + $drift * ($progress - 0.5) + sin($progress * 19 + strlen($sym)) * $drift * 0.12 + ($rand() - 0.5) * $drift * 0.06, $dec);
        $dist = round($stopD * (0.8 + $rand() * 0.4), $dec);
        $stop = round($side === 'LONG' ? $entry - $dist : $entry + $dist, $dec);
        $exit = round(TradeMath::exitFromR($side, $entry, $stop, $r), $dec);
        $tp = round(TradeMath::exitFromR($side, $entry, $stop, max(2, ceil(abs($r)))), $dec);
        $one = TradeMath::compute($sym, $side, $entry, $exit, $stop, 1.0, 0, 'USD');
        $lots = $one['risk'] > 0 ? max(0.01, round($riskMoney / $one['risk'], 2)) : 0.1;
        return ['entry' => $entry, 'stop' => $stop, 'tp' => $tp, 'exit' => $exit, 'lots' => $lots, 'pnl_est' => (float) ($one['pnl'] ?? 0) * $lots];
    }

    /** Journal entries on the notable days of the demo (dates follow the trades). */
    public static function journals(string $tz = 'UTC', ?int $now = null): array
    {
        $days = self::businessDays($tz, $now);
        $n = count($days);
        $at = fn (int $back) => $days[$n - 1 - $back];
        return [
            [$at(95), 5, 'CALM', 9, 'Followed the pre-market routine. Waited for acceptance back into value before entering gold.', 'Patience at value edges pays.'],
            [$at(84), 5, 'FOCUSED', 9, 'Only A+ setups today. London open gold is where my edge lives.', 'My edge is strongest at London open on gold.'],
            [$at(71), 4, 'CALM', 8, 'Two valid losses, one winner. Stuck to 1% risk.', 'Valid losses are part of the edge.'],
            [$at(58), 3, 'ANXIOUS', 6, 'Closed gold early on noise. Price hit my target an hour later.', 'Let the stop and target do their job.'],
            [$at(49), 2, 'GREEDY', 4, 'Doubled position size after two winners. The loss cost more than both wins combined.', 'Size is fixed by the plan, not by mood.'],
            [$at(41), 2, 'FOMO', 3, 'Traded into CPI despite my rule. Pure gamble.', 'No positions 15 minutes either side of tier-1 news.'],
            [$at(27), 2, 'FRUSTRATED', 3, 'Moved my stop twice on NAS. The loss reached 2.5R.', 'The stop only moves toward profit.'],
            [$at(19), 1, 'BORED', 2, 'Five trades, three after 15:30 UTC out of boredom. All three lost.', 'Close the platform when my session ends.'],
            [$at(11), 3, 'ANXIOUS', 5, 'Risking 2.5% a trade now without deciding to. Need to reset size.', 'Back to 1% until the rules are followed for a full week.'],
            [$at(3), 4, 'CALM', 7, 'Good day: two clean trades, plan followed.', 'Fewer trades, better trades.'],
            [$at(0), 1, 'REVENGE', 2, 'Two losses, then a revenge flip within minutes. I knew the rule and broke it anyway.', 'After two losses in a session, I stop for the day.'],
        ];
    }
}
