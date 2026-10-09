<?php
declare(strict_types=1);

namespace App\Trading;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Edge intelligence built on the member's own closed trades: audits, best/worst trading windows, A+ setup
 * detection, the Ruin Probability Radar, the discipline-leak curve, strengths & leaks and periodic reviews.
 * All wording is historical/statistical — nothing here predicts or guarantees future results.
 */
final class Edge
{
    public const MIN_GROUP = 3;      // trades needed before a group is ranked
    public const MIN_WINDOW = 5;     // trades needed for a best/anti window
    public const APLUS = ['trades' => 20, 'win_rate' => 0.60, 'avg_r' => 1.0, 'profit_factor' => 2.0];
    public const RISK_SCENARIOS = [0.5, 1.0, 1.5, 2.0, 3.0, 4.0];
    private const SESSION_TZ = ['ASIAN' => 'Asia/Tokyo', 'LONDON' => 'Europe/London', 'NEW_YORK' => 'America/New_York'];
    private const SESSION_TZ_LABEL = ['ASIAN' => 'Tokyo time', 'LONDON' => 'London time', 'NEW_YORK' => 'New York time'];

    // ------------------------------------------------------------------ helpers
    public static function strategyOf(array $t): string
    {
        return $t['strategy_name'] ?: ($t['setup_tag'] ?: 'Unassigned');
    }

    public static function sessionOf(array $t): string
    {
        return Sessions::bucket($t['executed_at']);
    }

    public static function inBehaviour(array $t, string $key): bool
    {
        [, $mistakes, $emotions] = Domain::BEHAVIOURS[$key];
        return in_array($t['mistake_tag'] ?? 'NONE', $mistakes, true) || ($t['emotion'] && in_array($t['emotion'], $emotions, true));
    }

    /** Group summaries keyed by label, filtered to groups with enough trades. */
    private static function groups(array $trades, callable $key, int $min = self::MIN_GROUP): array
    {
        return array_values(array_filter(Analytics::groupBy($trades, $key), fn ($g) => $g['s']['trades'] >= $min));
    }

    private static function hourLabel(int $h, int $span = 1): string
    {
        return sprintf('%02d:00–%02d:00', $h, ($h + $span) % 24);
    }

    // ------------------------------------------------------------------ sessions & strategies
    /** Win rate / P&L for Asian, London and New York (DST-aware buckets). */
    public static function sessionStats(array $trades): array
    {
        $out = [];
        foreach (Domain::SESSION_BUCKETS as $k => $label) {
            $out[$k] = ['label' => $label, 's' => Analytics::summarize(array_filter($trades, fn ($t) => self::sessionOf($t) === $k))];
        }
        return $out;
    }

    /** Ranked strategy scoreboard. $sort: net | win | pf | avgr */
    public static function scoreboard(array $trades, string $sort = 'net'): array
    {
        $rows = Analytics::groupBy($trades, [self::class, 'strategyOf'], false);
        $val = fn ($r) => match ($sort) {
            'win' => $r['s']['win_rate'] ?? -1,
            'pf' => $r['s']['profit_factor'] ?? ($r['s']['gross_loss'] == 0 && $r['s']['gross_profit'] > 0 ? INF : -1),
            'avgr' => $r['s']['avg_r'] ?? -INF,
            default => $r['s']['net'],
        };
        usort($rows, fn ($a, $b) => $val($b) <=> $val($a));
        return $rows;
    }

    // ------------------------------------------------------------------ audits
    /** Positive edges and negative-expectancy traps for a period (usually the previous month). */
    public static function audit(array $trades, string $tz): array
    {
        $pos = [];
        $neg = [];
        $dims = [
            'Strategy' => [self::class, 'strategyOf'],
            'Setup' => fn ($t) => $t['setup_tag'] ?: null,
            'Instrument' => fn ($t) => $t['symbol'],
            'Session' => fn ($t) => Domain::SESSION_BUCKETS[self::sessionOf($t)],
            'Trading hour' => fn ($t) => self::hourLabel((int) Analytics::local($t['executed_at'], $tz)->format('G')),
        ];
        foreach ($dims as $dim => $fn) {
            $g = self::groups($trades, $fn, 2);
            usort($g, fn ($a, $b) => $b['s']['net'] <=> $a['s']['net']);
            $dupe = fn ($k) => $dim === 'Setup' && array_filter(array_merge($pos, $neg), fn ($x) => $x['dim'] === 'Strategy' && $x['key'] === $k);
            if ($g && $g[0]['s']['net'] > 0 && ($g[0]['s']['expectancy'] ?? 0) > 0 && !$dupe($g[0]['key'])) {
                $pos[] = ['dim' => $dim, 'key' => $g[0]['key'], 's' => $g[0]['s']];
            }
            $w = end($g);
            if ($w && $w['s']['net'] < 0 && !$dupe($w['key']) && (!$pos || end($pos)['key'] !== $w['key'] || end($pos)['dim'] !== $dim)) {
                $neg[] = ['dim' => $dim, 'key' => $w['key'], 's' => $w['s']];
            }
        }
        foreach (Domain::BEHAVIOURS as $k => [$label]) {
            $sub = array_filter($trades, fn ($t) => self::inBehaviour($t, $k));
            $s = Analytics::summarize($sub);
            if ($s['trades'] > 0 && $s['net'] < 0) {
                $neg[] = ['dim' => 'Behaviour', 'key' => $label, 's' => $s];
            }
        }
        $broken = Analytics::summarize(array_filter($trades, fn ($t) => !(int) $t['rules_followed']));
        if ($broken['trades'] > 0 && $broken['net'] < 0) {
            $neg[] = ['dim' => 'Behaviour', 'key' => 'All rule violations', 's' => $broken];
        }
        usort($neg, fn ($a, $b) => $a['s']['net'] <=> $b['s']['net']);
        return ['positive' => $pos, 'negative' => $neg, 'summary' => Analytics::summarize($trades)];
    }

    // ------------------------------------------------------------------ windows
    /**
     * Combinations of session × instrument × strategy with at least MIN_WINDOW trades, each with its
     * strongest/weakest 2-hour window in that session's own local time.
     */
    public static function windows(array $trades, string $level = 'full'): array
    {
        $combos = [];
        foreach (Analytics::closed($trades) as $t) {
            $k = self::sessionOf($t) . '|' . ($level === 'strategy' ? '*' : $t['symbol']) . '|' . ($level === 'symbol' ? '*' : self::strategyOf($t));
            $combos[$k][] = $t;
        }
        $out = [];
        foreach ($combos as $k => $ts) {
            if (count($ts) < self::MIN_WINDOW) {
                continue;
            }
            [$sess, $sym, $strat] = explode('|', $k, 3);
            $hours = [];
            foreach ($ts as $t) {
                $h = (int) Analytics::local($t['executed_at'], self::SESSION_TZ[$sess])->format('G');
                $hours[$h][] = $t;
            }
            $best = $worst = null;
            foreach (array_keys($hours) as $h) {
                $win = array_merge($hours[$h], $hours[($h + 1) % 24] ?? []);
                $s = Analytics::summarize($win);
                $row = ['from' => $h, 'label' => self::hourLabel($h, 2) . ' ' . self::SESSION_TZ_LABEL[$sess], 's' => $s];
                if ($best === null || $s['net'] > $best['s']['net']) {
                    $best = $row;
                }
                if ($worst === null || $s['net'] < $worst['s']['net']) {
                    $worst = $row;
                }
            }
            $setups = array_count_values(array_filter(array_map(fn ($t) => $t['setup_tag'] ?: null, $ts)));
            arsort($setups);
            $out[] = ['level' => $level, 'session' => $sess, 'session_label' => Domain::SESSION_BUCKETS[$sess], 'symbol' => $sym === '*' ? null : $sym, 'strategy' => $strat === '*' ? null : $strat,
                'setup' => $setups ? array_key_first($setups) : null, 's' => Analytics::summarize($ts), 'best_hours' => $best, 'worst_hours' => $worst];
        }
        return $out;
    }

    /** Tries session × instrument × strategy first, then widens to session × instrument, then session × strategy. */
    public static function bestWindow(array $trades): ?array
    {
        foreach (['full', 'symbol', 'strategy'] as $level) {
            $w = array_values(array_filter(self::windows($trades, $level), fn ($x) => $x['s']['net'] > 0 && ($x['s']['expectancy'] ?? 0) > 0));
            usort($w, fn ($a, $b) => [$b['s']['avg_r'] ?? 0, $b['s']['net']] <=> [$a['s']['avg_r'] ?? 0, $a['s']['net']]);
            if ($w) {
                return $w[0];
            }
        }
        return null;
    }

    public static function antiWindow(array $trades): ?array
    {
        foreach (['full', 'symbol', 'strategy'] as $level) {
            $w = array_values(array_filter(self::windows($trades, $level), fn ($x) => $x['s']['net'] < 0));
            usort($w, fn ($a, $b) => $a['s']['net'] <=> $b['s']['net']);
            if ($w) {
                return $w[0];
            }
        }
        return null;
    }

    /** Best single hour / session / instrument / strategy / setup by expectancy (min. trades). */
    public static function bestDimensions(array $trades, string $tz): array
    {
        $pick = function (callable $fn) use ($trades) {
            $g = array_values(array_filter(self::groups($trades, $fn), fn ($x) => $x['s']['net'] > 0));
            usort($g, fn ($a, $b) => ($b['s']['expectancy'] ?? 0) <=> ($a['s']['expectancy'] ?? 0));
            return $g[0] ?? null;
        };
        return [
            'Hour (' . $tz . ')' => $pick(fn ($t) => self::hourLabel((int) Analytics::local($t['executed_at'], $tz)->format('G'))),
            'Session' => $pick(fn ($t) => Domain::SESSION_BUCKETS[self::sessionOf($t)]),
            'Instrument' => $pick(fn ($t) => $t['symbol']),
            'Strategy' => $pick([self::class, 'strategyOf']),
            'Setup' => $pick(fn ($t) => $t['setup_tag'] ?: null),
        ];
    }

    /** A+ only with a meaningful sample and strong metrics; otherwise null (no risk message is shown). */
    public static function aPlus(?array $window): ?array
    {
        if (!$window) {
            return null;
        }
        $s = $window['s'];
        $pf = $s['profit_factor'];
        $ok = $s['trades'] >= self::APLUS['trades'] && ($s['win_rate'] ?? 0) >= self::APLUS['win_rate']
            && ($s['avg_r'] ?? 0) >= self::APLUS['avg_r'] && ($pf === null ? $s['gross_profit'] > 0 : $pf >= self::APLUS['profit_factor']);
        return $ok ? $window : null;
    }

    // ------------------------------------------------------------------ Ruin Probability Radar
    /**
     * Blow-up radar. Reads the last 30 days of closed trades (falling back to the last 30 trades when fewer than
     * 8 have a measurable R): win rate, average reward-to-risk, drawdown and position sizing (risk % per trade).
     * It then re-samples those actual trades — each one's % gain/loss of the account, so real position sizes are
     * kept — over the number of trades expected in the next 14 calendar days. "Blown" = the account falls by
     * $threshold from its peak at any point. Seeded, so the same journal always gives the same answer.
     */
    public static function ruinRadar(array $all, float $equity, float $defaultRiskPct, float $threshold = 0.10, int $paths = 2000): array
    {
        $closed = Analytics::closed($all);
        $since = time() - 30 * 86400;
        $sample = array_values(array_filter($closed, fn ($t) => strtotime($t['executed_at'] . ' UTC') >= $since));
        $basis = 'last 30 days';
        if (count(self::rValues($sample)) < 8) {
            $sample = array_slice($closed, -30);
            $basis = 'last ' . count($sample) . ' trades (fewer than 8 in the last 30 days)';
        }
        $rs = self::rValues($sample);
        if (count($rs) < 8) {
            return ['ok' => false, 'needed' => 8, 'have' => count($rs)];
        }
        // Equity at the start of the sample (today's equity minus the sample's P&L), then each trade's % return.
        $pnlSum = array_sum(array_map(fn ($t) => (float) $t['pnl'], $sample));
        $startEq = max(1.0, $equity - $pnlSum);
        $eq = $startEq;
        $peak = $startEq;
        $returns = $riskPcts = $dds = [];
        $maxDd = 0.0;
        foreach ($sample as $t) {
            $before = max(1.0, $eq);
            $returns[] = (float) $t['pnl'] / $before;
            if ($t['risk_amount'] && (float) $t['risk_amount'] > 0) {
                $riskPcts[] = (float) $t['risk_amount'] / $before * 100;
            }
            $eq += (float) $t['pnl'];
            $peak = max($peak, $eq);
            $dd = $peak > 0 ? max(0, 1 - $eq / $peak) : 0;
            $dds[] = $dd;
            $maxDd = max($maxDd, $dd);
        }
        sort($riskPcts);
        $current = $riskPcts ? round($riskPcts[intdiv(count($riskPcts), 2)], 2) : $defaultRiskPct;
        $current = max(0.05, min(25, $current));
        $days = [];
        foreach ($sample as $t) {
            $days[substr($t['executed_at'], 0, 10)] = ($days[substr($t['executed_at'], 0, 10)] ?? 0) + 1;
        }
        $perDay = count($sample) / max(1, count($days));
        $horizon = (int) max(5, min(200, round($perDay * 10)));   // ~10 trading days in 14 calendar days
        $run = function (callable $draw) use ($horizon, $paths, $threshold): array {
            mt_srand(20260707);
            $hits = 0;
            $worst = [];
            for ($p = 0; $p < $paths; $p++) {
                $e = $pk = 1.0;
                $max = 0.0;
                for ($k = 0; $k < $horizon; $k++) {
                    $e = max(0.0, $e * (1 + $draw()));
                    $pk = max($pk, $e);
                    $max = max($max, $pk > 0 ? 1 - $e / $pk : 1);
                }
                $worst[] = $max;
                if ($max >= $threshold) {
                    $hits++;
                }
            }
            sort($worst);
            return ['p' => round($hits / $paths, 4), 'median_dd' => round($worst[intdiv($paths, 2)], 4), 'p95_dd' => round($worst[(int) floor($paths * 0.95)], 4)];
        };
        $nRet = count($returns);
        $nR = count($rs);
        $headline = $run(fn () => $returns[mt_rand(0, $nRet - 1)]);
        $scen = [];
        foreach (array_unique(array_merge(self::RISK_SCENARIOS, [$current])) as $r) {
            $scen[(string) $r] = ['risk' => (float) $r] + $run(fn () => $rs[mt_rand(0, $nR - 1)] * (float) $r / 100);
        }
        ksort($scen, SORT_NUMERIC);
        $wins = array_filter($rs, fn ($r) => $r > 0);
        $losses = array_filter($rs, fn ($r) => $r <= 0);
        $avgWin = $wins ? array_sum($wins) / count($wins) : 0;
        $avgLoss = $losses ? abs(array_sum($losses) / count($losses)) : 0;
        $streak = $maxStreak = 0;
        foreach ($sample as $t) {
            $streak = (float) $t['pnl'] < 0 ? $streak + 1 : 0;
            $maxStreak = max($maxStreak, $streak);
        }
        $medDay = $days ? (function () use ($days) { $v = array_values($days); sort($v); return $v[intdiv(count($v), 2)]; })() : 0;
        $p = $headline['p'];
        return [
            'ok' => true, 'basis' => $basis, 'threshold' => $threshold, 'paths' => $paths, 'horizon' => $horizon, 'current' => $current,
            'current_p' => $p, 'median_dd' => $headline['median_dd'], 'p95_dd' => $headline['p95_dd'],
            'level' => $p >= 0.5 ? ['Critical', 'down'] : ($p >= 0.2 ? ['High', 'down'] : ($p >= 0.05 ? ['Elevated', 'warn'] : ['Low', 'up'])),
            'scenarios' => array_values($scen),
            'inputs' => [
                'trades' => count($sample), 'win_rate' => count($wins) / $nR, 'avg_win_r' => round($avgWin, 2), 'avg_loss_r' => round($avgLoss, 2),
                'rr' => $avgLoss > 0 ? round($avgWin / $avgLoss, 2) : null,
                'max_dd' => round($maxDd, 4), 'avg_dd' => round(array_sum($dds) / max(1, count($dds)), 4),
                'risk_avg' => $riskPcts ? round(array_sum($riskPcts) / count($riskPcts), 2) : null, 'risk_max' => $riskPcts ? round(max($riskPcts), 2) : null,
                'per_day' => round($perDay, 1), 'max_loss_streak' => $maxStreak,
                'fomo' => count(array_filter($sample, fn ($t) => self::inBehaviour($t, 'FOMO'))), 'revenge' => count(array_filter($sample, fn ($t) => self::inBehaviour($t, 'REVENGE'))),
                'overtrading' => count(array_filter($sample, fn ($t) => self::inBehaviour($t, 'OVERTRADING'))) + count(array_filter($days, fn ($c) => $medDay > 0 && $c > 2 * $medDay)),
            ],
        ];
    }

    /** R multiple of each trade (stored R, else P&L ÷ planned risk). */
    private static function rValues(array $trades): array
    {
        $out = [];
        foreach ($trades as $t) {
            if ($t['rr'] !== null && $t['rr'] !== '') {
                $out[] = (float) $t['rr'];
            } elseif ($t['risk_amount'] && (float) $t['risk_amount'] > 0) {
                $out[] = (float) $t['pnl'] / (float) $t['risk_amount'];
            }
        }
        return $out;
    }

    // ------------------------------------------------------------------ Discipline leak
    /** Actual vs rule-compliant (emotion-filtered) cumulative P&L, plus the leak by behaviour. */
    public static function leak(array $trades, string $tz): array
    {
        $trades = Analytics::closed($trades);
        $act = $hyp = 0.0;
        $a = $h = [];
        foreach ($trades as $t) {
            $act += (float) $t['pnl'];
            $hyp += Analytics::hypothetical($t);
            $x = Analytics::local($t['executed_at'], $tz)->format('M j');
            $a[] = ['x' => $x, 'y' => round($act, 2)];
            $h[] = ['x' => $x, 'y' => round($hyp, 2)];
        }
        $by = [];
        foreach (Domain::BEHAVIOURS as $k => [$label]) {
            $sub = array_filter($trades, fn ($t) => self::inBehaviour($t, $k));
            if (!$sub) {
                continue;
            }
            $actual = array_sum(array_map(fn ($t) => (float) $t['pnl'], $sub));
            $flaw = array_sum(array_map(fn ($t) => Analytics::hypothetical($t), $sub));
            $by[] = ['label' => $label, 'trades' => count($sub), 'actual' => round($actual, 2), 'leak' => round($flaw - $actual, 2)];
        }
        usort($by, fn ($x, $y) => $y['leak'] <=> $x['leak']);
        return ['actual' => round($act, 2), 'flawless' => round($hyp, 2), 'leak' => round($hyp - $act, 2), 'curve' => ['actual' => $a, 'filtered' => $h], 'by' => $by,
            'violations' => count(array_filter($trades, fn ($t) => !(int) $t['rules_followed'] || ($t['mistake_tag'] ?? 'NONE') !== 'NONE'))];
    }

    // ------------------------------------------------------------------ AI Coach home: strengths & leaks
    public static function strengthsAndLeaks(array $all, string $tz, array $m, float $equity, string $cur): array
    {
        $trades = Analytics::closed($all);
        if (count($trades) < 10) {
            return ['enough' => false, 'strengths' => [], 'leaks' => []];
        }
        $s = [];
        $best = self::bestDimensions($trades, $tz);
        foreach ($best as $dim => $g) {
            if ($g && $g['s']['trades'] >= 5) {
                $s[] = ['title' => 'Strongest ' . strtolower(preg_replace('/ \(.*/', '', $dim)) . ': ' . $g['key'],
                    'detail' => sprintf('%s over %d trades · win rate %s · expectancy %s/trade%s', money($g['s']['net'], $cur, true), $g['s']['trades'], pct($g['s']['win_rate'], 0), money($g['s']['expectancy'], $cur, true), $g['s']['avg_r'] !== null ? ' · avg ' . number_format($g['s']['avg_r'], 2) . 'R' : '')];
            }
        }
        $sum = Analytics::summarize($trades);
        if ($sum['avg_r'] !== null && $sum['payoff'] !== null && $sum['avg_r'] > 0) {
            $s[] = ['title' => 'R profile: average ' . number_format($sum['avg_r'], 2) . 'R per trade', 'detail' => 'Average win is ' . number_format($sum['payoff'], 2) . '× the average loss across ' . $sum['trades'] . ' trades.'];
        }
        $l = [];
        foreach (['FOMO', 'REVENGE', 'OVERTRADING', 'IMPULSIVE', 'MOVED_STOP', 'CHASING'] as $k) {
            $sub = array_filter($trades, fn ($t) => self::inBehaviour($t, $k));
            $x = Analytics::summarize($sub);
            if ($x['trades'] >= 2 && $x['net'] < 0) {
                $l[] = ['title' => Domain::BEHAVIOURS[$k][0] . ' cost ' . money($x['net'], $cur), 'detail' => $x['trades'] . ' tagged trades · win rate ' . pct($x['win_rate'], 0) . '.'];
            }
        }
        $worst = function (callable $fn, string $label) use ($trades, $cur) {
            $g = self::groups($trades, $fn, 3);
            usort($g, fn ($a, $b) => $a['s']['net'] <=> $b['s']['net']);
            return $g && $g[0]['s']['net'] < 0 ? ['title' => 'Weak ' . $label . ': ' . $g[0]['key'], 'detail' => money($g[0]['s']['net'], $cur) . ' over ' . $g[0]['s']['trades'] . ' trades · win rate ' . pct($g[0]['s']['win_rate'], 0) . '.'] : null;
        };
        foreach ([[fn ($t) => Domain::SESSION_BUCKETS[self::sessionOf($t)], 'session'], [fn ($t) => self::hourLabel((int) Analytics::local($t['executed_at'], $tz)->format('G')), 'trading hour'], [[self::class, 'strategyOf'], 'strategy']] as [$fn, $label]) {
            if ($w = $worst($fn, $label)) {
                $l[] = $w;
            }
        }
        $limit = $equity * (float) $m['default_risk_pct'] / 100 * 1.5;
        $over = array_filter($trades, fn ($t) => $t['risk_amount'] && (float) $t['risk_amount'] > $limit);
        if ($limit > 0 && count($over) >= 2) {
            $l[] = ['title' => count($over) . ' trades risked more than 1.5× your ' . rtrim(rtrim((string) $m['default_risk_pct'], '0'), '.') . '% rule', 'detail' => 'Those trades netted ' . money(array_sum(array_map(fn ($t) => (float) $t['pnl'], $over)), $cur, true) . '.'];
        }
        if ($sum['compliance'] !== null && $sum['compliance'] < 0.8) {
            $l[] = ['title' => 'Rule compliance ' . pct($sum['compliance'], 0), 'detail' => 'Below 80% of trades followed your plan.'];
        }
        return ['enough' => true, 'strengths' => $s, 'leaks' => $l];
    }

    // ------------------------------------------------------------------ Weekly / monthly intelligence
    public static function review(array $trades, array $journals, string $tz, string $cur): array
    {
        $closed = Analytics::closed($trades);
        $sum = Analytics::summarize($closed);
        $top = $closed;
        usort($top, fn ($a, $b) => (float) $b['pnl'] <=> (float) $a['pnl']);
        $dims = self::bestDimensions($closed, $tz);
        $mistakes = array_count_values(array_filter(array_map(fn ($t) => ($t['mistake_tag'] ?? 'NONE') !== 'NONE' ? (Domain::MISTAKES[$t['mistake_tag']] ?? $t['mistake_tag']) : null, $closed)));
        arsort($mistakes);
        $emo = [];
        foreach (Analytics::groupBy($closed, fn ($t) => $t['emotion'] ? ucfirst(strtolower($t['emotion'])) : null) as $g) {
            $emo[] = $g['key'] . ': ' . $g['s']['trades'] . ' trades, ' . money($g['s']['net'], $cur, true);
        }
        $jEmo = array_count_values(array_filter(array_map(fn ($j) => $j['emotional_state'] ? (Domain::JOURNAL_EMOTIONS[$j['emotional_state']] ?? ucfirst(strtolower($j['emotional_state']))) : null, $journals)));
        arsort($jEmo);
        $answers = array_count_values(array_filter(array_map(fn ($j) => $j['rules_answer'] ?? null, $journals)));
        $leak = self::leak($closed, $tz);
        $improve = [];
        if ($leak['by']) {
            $improve[] = 'Reduce ' . strtolower($leak['by'][0]['label']) . ' — it cost about ' . money($leak['by'][0]['leak'], $cur) . ' compared with rule-compliant execution (historical estimate).';
        }
        if ($sum['compliance'] !== null && $sum['compliance'] < 0.9) {
            $improve[] = 'Raise rule compliance from ' . pct($sum['compliance'], 0) . ' — check your plan before every entry.';
        }
        if (($anti = self::antiWindow($closed)) !== null) {
            $improve[] = 'Consider reducing risk in ' . self::windowName($anti) . ', historically ' . money($anti['s']['net'], $cur) . '.';
        }
        if (!$improve && $sum['trades']) {
            $improve[] = 'Keep logging emotions and mistakes on every trade so patterns become clearer.';
        }
        return [
            'summary' => $sum,
            'best_trades' => array_slice(array_filter($top, fn ($t) => (float) $t['pnl'] > 0), 0, 3),
            'dims' => $dims, 'best_window' => self::bestWindow($closed), 'worst_window' => self::antiWindow($closed), 'leak' => $leak,
            'mistakes' => array_filter($mistakes, fn ($c) => $c >= 2), 'emotions' => $emo, 'journal_emotions' => $jEmo, 'answers' => $answers,
            'lessons' => array_values(array_filter($journals, fn ($j) => trim((string) $j['key_lesson']) !== '')), 'improve' => $improve,
        ];
    }

    public static function windowName(array $w): string
    {
        return implode(' · ', array_filter([$w['session_label'] . ' session', $w['symbol'], $w['strategy'] && $w['strategy'] !== 'Unassigned' ? $w['strategy'] : null]));
    }

    /** Monday-start week / calendar month ranges in the member's timezone. */
    public static function periodRange(string $period, string $tz): array
    {
        $now = new DateTimeImmutable('now', new DateTimeZone($tz));
        return match ($period) {
            'last_week' => [$now->modify('monday last week')->format('Y-m-d'), $now->modify('sunday last week')->format('Y-m-d'), 'Last week'],
            'month' => [$now->format('Y-m-01'), $now->format('Y-m-d'), 'This month'],
            'last_month' => [$now->modify('first day of last month')->format('Y-m-d'), $now->modify('last day of last month')->format('Y-m-d'), 'Last month'],
            default => [$now->modify('monday this week')->format('Y-m-d'), $now->format('Y-m-d'), 'This week'],
        };
    }
}
