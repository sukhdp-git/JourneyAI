<?php
declare(strict_types=1);

namespace App\Trading;

/**
 * Rule-based strategy backtester (pure: bars and rules in, trades and statistics out).
 *
 * How a test is simulated, so the result never peeks into the future:
 *  - Conditions are checked on the CLOSE of a bar; the trade is filled at the OPEN of the next bar.
 *  - Stop loss and take profit are checked against each later bar's high/low. If one bar touches both,
 *    the stop is assumed to fill first (the conservative choice, because the order inside a bar is unknown).
 *  - Spread is paid once per trade (on the entry for longs, on the exit for shorts: charts show bid prices).
 *  - One position at a time. Results are in R (multiples of the risk taken) and in account % with fixed-fractional sizing.
 */
final class Backtest
{
    public const TIMEFRAMES = ['15min' => 900, '30min' => 1800, '1h' => 3600, '4h' => 14400, '1day' => 86400];
    public const OPERANDS = [
        'close' => 'Close', 'open' => 'Open', 'high' => 'High', 'low' => 'Low',
        'ema' => 'EMA', 'sma' => 'SMA', 'rsi' => 'RSI', 'atr' => 'ATR',
        'macd' => 'MACD line', 'macd_signal' => 'MACD signal', 'macd_hist' => 'MACD histogram',
        'bb_upper' => 'Bollinger upper', 'bb_mid' => 'Bollinger middle', 'bb_lower' => 'Bollinger lower',
        'hh' => 'Highest high (prev. N bars)', 'll' => 'Lowest low (prev. N bars)', 'value' => 'Number',
    ];
    public const OPS = ['crosses_above' => 'crosses above', 'crosses_below' => 'crosses below', 'gt' => 'is above', 'lt' => 'is below'];
    public const PATTERNS = [
        'bull_engulf' => 'Bullish engulfing', 'bear_engulf' => 'Bearish engulfing', 'inside_bar' => 'Inside bar',
        'pin_bull' => 'Bullish pin bar', 'pin_bear' => 'Bearish pin bar', 'green' => 'Green candle', 'red' => 'Red candle',
    ];
    public const SESSIONS = ['ASIAN' => 'Asia', 'LONDON' => 'London', 'NEW_YORK' => 'New York'];
    private const WARMUP = 50;   // bars skipped at the start so indicators have settled

    /** @var array<string, array<int, ?float>> */
    private array $cache = [];
    private array $o, $h, $l, $c, $t;
    private int $n;

    /** @param array<int, array{0:int,1:float,2:float,3:float,4:float}> $bars [ts, open, high, low, close] ascending, UTC */
    public function __construct(array $bars)
    {
        $this->t = array_column($bars, 0);
        $this->o = array_column($bars, 1);
        $this->h = array_column($bars, 2);
        $this->l = array_column($bars, 3);
        $this->c = array_column($bars, 4);
        $this->n = count($bars);
    }

    // ---------------------------------------------------------------- validation

    /** Cleans a rule set from the browser. Returns [rules, error]. */
    public static function normalize(array $r): array
    {
        $side = in_array($r['side'] ?? '', ['long', 'short', 'both'], true) ? $r['side'] : 'long';
        $out = ['side' => $side, 'long' => [], 'short' => []];
        foreach (['long', 'short'] as $k) {
            if ($side !== 'both' && $side !== $k) {
                continue;
            }
            foreach (array_slice((array) ($r[$k] ?? []), 0, 8) as $cond) {
                $cnd = self::cleanCond((array) $cond);
                if ($cnd === null) {
                    return [null, 'One of the ' . $k . ' conditions is incomplete.'];
                }
                $out[$k][] = $cnd;
            }
            if (!$out[$k]) {
                return [null, 'Add at least one ' . ($k === 'long' ? 'buy' : 'sell') . ' condition.'];
            }
        }
        $f = (array) ($r['filters'] ?? []);
        $out['filters'] = [
            'sessions' => array_values(array_intersect(array_keys(self::SESSIONS), (array) ($f['sessions'] ?? []))),
            'days' => array_values(array_intersect([1, 2, 3, 4, 5, 6, 7], array_map('intval', (array) ($f['days'] ?? [])))),
        ];
        $x = (array) ($r['exit'] ?? []);
        $sl = (array) ($x['sl'] ?? []);
        $tp = (array) ($x['tp'] ?? []);
        $slType = in_array($sl['type'] ?? '', ['atr', 'points', 'percent', 'swing'], true) ? $sl['type'] : 'atr';
        $tpType = in_array($tp['type'] ?? '', ['r', 'atr', 'points', 'none'], true) ? $tp['type'] : 'r';
        $out['exit'] = [
            'sl' => ['type' => $slType, 'value' => self::num($sl['value'] ?? 1.5, 0.0001, 100000), 'len' => (int) self::num($sl['len'] ?? 14, 2, 200)],
            'tp' => ['type' => $tpType, 'value' => self::num($tp['value'] ?? 2, 0.0001, 100000)],
            'be_at_r' => isset($x['be_at_r']) && $x['be_at_r'] !== '' && $x['be_at_r'] !== null ? self::num($x['be_at_r'], 0.1, 20) : null,
            'max_bars' => !empty($x['max_bars']) ? (int) self::num($x['max_bars'], 1, 5000) : null,
            'opposite' => !empty($x['opposite']),
        ];
        $k = (array) ($r['risk'] ?? []);
        $out['risk'] = [
            'balance' => self::num($k['balance'] ?? 10000, 100, 100000000),
            'risk_pct' => self::num($k['risk_pct'] ?? 1, 0.01, 10),
            'spread' => self::num($k['spread'] ?? 0, 0, 100000),
            'max_per_day' => (int) self::num($k['max_per_day'] ?? 0, 0, 50),
        ];
        return [$out, null];
    }

    private static function num(mixed $v, float $min, float $max): float
    {
        $f = is_numeric($v) ? (float) $v : $min;
        return max($min, min($max, $f));
    }

    private static function cleanCond(array $c): ?array
    {
        if (!empty($c['pattern'])) {
            return isset(self::PATTERNS[$c['pattern']]) ? ['pattern' => $c['pattern']] : null;
        }
        $a = self::cleanOperand((array) ($c['a'] ?? []));
        $b = self::cleanOperand((array) ($c['b'] ?? []));
        $op = $c['op'] ?? '';
        if (!$a || !$b || !isset(self::OPS[$op]) || $a['k'] === 'value') {
            return null;
        }
        return ['a' => $a, 'op' => $op, 'b' => $b];
    }

    private static function cleanOperand(array $o): ?array
    {
        $k = $o['k'] ?? '';
        if (!isset(self::OPERANDS[$k])) {
            return null;
        }
        if ($k === 'value') {
            return is_numeric($o['v'] ?? null) ? ['k' => 'value', 'v' => (float) $o['v']] : null;
        }
        $def = ['ema' => 20, 'sma' => 50, 'rsi' => 14, 'atr' => 14, 'bb_upper' => 20, 'bb_mid' => 20, 'bb_lower' => 20, 'hh' => 20, 'll' => 20][$k] ?? 0;
        return $def ? ['k' => $k, 'n' => (int) self::num($o['n'] ?? $def, 1, 400)] : ['k' => $k];
    }

    /** Plain-English summary of a rule set (shown above results and in saved runs). */
    public static function describe(array $r): array
    {
        $op = fn ($o) => $o['k'] === 'value' ? rtrim(rtrim(number_format($o['v'], 5, '.', ''), '0'), '.') : self::OPERANDS[$o['k']] . (isset($o['n']) && !in_array($o['k'], ['macd', 'macd_signal', 'macd_hist'], true) ? ' ' . $o['n'] : '');
        $cond = fn ($c) => isset($c['pattern']) ? self::PATTERNS[$c['pattern']] : $op($c['a']) . ' ' . self::OPS[$c['op']] . ' ' . $op($c['b']);
        $lines = [];
        foreach (['long' => 'Buy when', 'short' => 'Sell when'] as $k => $lbl) {
            if ($r[$k]) {
                $lines[] = $lbl . ' ' . implode(' and ', array_map($cond, $r[$k]));
            }
        }
        $sl = $r['exit']['sl'];
        $tp = $r['exit']['tp'];
        $lines[] = 'Stop: ' . match ($sl['type']) { 'atr' => $sl['value'] . '× ATR ' . $sl['len'], 'points' => $sl['value'] . ' price points', 'percent' => $sl['value'] . '% of price', 'swing' => 'beyond the swing of the last ' . $sl['len'] . ' bars' }
            . ' · Target: ' . match ($tp['type']) { 'r' => $tp['value'] . 'R', 'atr' => $tp['value'] . '× ATR', 'points' => $tp['value'] . ' price points', 'none' => 'none (exit by rules)' }
            . ($r['exit']['be_at_r'] ? ' · Break-even at ' . $r['exit']['be_at_r'] . 'R' : '') . ($r['exit']['max_bars'] ? ' · Max ' . $r['exit']['max_bars'] . ' bars' : '') . ($r['exit']['opposite'] ? ' · Exit on opposite signal' : '');
        if ($r['filters']['sessions'] || $r['filters']['days']) {
            $d = ['', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
            $lines[] = 'Only ' . implode(', ', array_filter([implode(' / ', array_map(fn ($s) => self::SESSIONS[$s], $r['filters']['sessions'])), implode(' ', array_map(fn ($x) => $d[$x], $r['filters']['days']))]));
        }
        return $lines;
    }

    // ---------------------------------------------------------------- indicators (all aligned to bar index)

    /** @return array<int, ?float> */
    public function series(array $o): array
    {
        $key = $o['k'] . ':' . ($o['n'] ?? '') . ':' . ($o['v'] ?? '');
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }
        $n = $o['n'] ?? 0;
        $s = match ($o['k']) {
            'close' => $this->c, 'open' => $this->o, 'high' => $this->h, 'low' => $this->l,
            'value' => array_fill(0, $this->n, (float) $o['v']),
            'ema' => self::ema($this->c, $n), 'sma' => self::sma($this->c, $n), 'rsi' => self::rsi($this->c, $n), 'atr' => $this->atr($n),
            'macd' => $this->macd()[0], 'macd_signal' => $this->macd()[1], 'macd_hist' => $this->macd()[2],
            'bb_upper' => $this->bb($n)[0], 'bb_mid' => self::sma($this->c, $n), 'bb_lower' => $this->bb($n)[1],
            'hh' => $this->extreme($this->h, $n, true), 'll' => $this->extreme($this->l, $n, false),
        };
        return $this->cache[$key] = $s;
    }

    public static function sma(array $x, int $n): array
    {
        $out = [];
        $sum = 0.0;
        foreach ($x as $i => $v) {
            $sum += $v;
            if ($i >= $n) {
                $sum -= $x[$i - $n];
            }
            $out[$i] = $i >= $n - 1 ? $sum / $n : null;
        }
        return $out;
    }

    /** EMA seeded with the SMA of the first N values (the common charting convention). */
    public static function ema(array $x, int $n): array
    {
        $out = [];
        $k = 2 / ($n + 1);
        $prev = null;
        $sum = 0.0;
        foreach ($x as $i => $v) {
            if ($v === null) {
                $out[$i] = null;
                continue;
            }
            if ($prev === null) {
                $sum += $v;
                $cnt = ($cnt ?? 0) + 1;
                if ($cnt === $n) {
                    $prev = $sum / $n;
                    $out[$i] = $prev;
                } else {
                    $out[$i] = null;
                }
                continue;
            }
            $prev = $v * $k + $prev * (1 - $k);
            $out[$i] = $prev;
        }
        return $out;
    }

    /** RSI with Wilder's smoothing. */
    public static function rsi(array $x, int $n): array
    {
        $out = array_fill(0, count($x), null);
        $g = $l = 0.0;
        for ($i = 1, $m = count($x); $i < $m; $i++) {
            $d = $x[$i] - $x[$i - 1];
            $up = max($d, 0);
            $dn = max(-$d, 0);
            if ($i <= $n) {
                $g += $up;
                $l += $dn;
                if ($i === $n) {
                    $g /= $n;
                    $l /= $n;
                    $out[$i] = $l == 0 ? 100.0 : 100 - 100 / (1 + $g / $l);
                }
                continue;
            }
            $g = ($g * ($n - 1) + $up) / $n;
            $l = ($l * ($n - 1) + $dn) / $n;
            $out[$i] = $l == 0 ? 100.0 : 100 - 100 / (1 + $g / $l);
        }
        return $out;
    }

    /** ATR with Wilder's smoothing. */
    public function atr(int $n): array
    {
        $out = array_fill(0, $this->n, null);
        $sum = 0.0;
        $prev = null;
        for ($i = 0; $i < $this->n; $i++) {
            $tr = $i === 0 ? $this->h[0] - $this->l[0] : max($this->h[$i] - $this->l[$i], abs($this->h[$i] - $this->c[$i - 1]), abs($this->l[$i] - $this->c[$i - 1]));
            if ($i < $n) {
                $sum += $tr;
                if ($i === $n - 1) {
                    $prev = $sum / $n;
                    $out[$i] = $prev;
                }
                continue;
            }
            $prev = ($prev * ($n - 1) + $tr) / $n;
            $out[$i] = $prev;
        }
        return $out;
    }

    private function macd(): array
    {
        if (!isset($this->cache['__macd'])) {
            $f = self::ema($this->c, 12);
            $s = self::ema($this->c, 26);
            $line = array_map(fn ($a, $b) => $a !== null && $b !== null ? $a - $b : null, $f, $s);
            $first = array_key_first(array_filter($line, fn ($v) => $v !== null));
            $sig = array_fill(0, $this->n, null);
            if ($first !== null) {
                foreach (self::ema(array_slice($line, $first), 9) as $j => $v) {
                    $sig[$first + $j] = $v;
                }
            }
            $hist = array_map(fn ($a, $b) => $a !== null && $b !== null ? $a - $b : null, $line, $sig);
            $this->cache['__macd'] = [$line, $sig, $hist];
        }
        return $this->cache['__macd'];
    }

    /** Bollinger Bands (SMA ± 2 population standard deviations). */
    private function bb(int $n): array
    {
        $up = $lo = array_fill(0, $this->n, null);
        $mid = self::sma($this->c, $n);
        for ($i = $n - 1; $i < $this->n; $i++) {
            $m = $mid[$i];
            $v = 0.0;
            for ($j = $i - $n + 1; $j <= $i; $j++) {
                $v += ($this->c[$j] - $m) ** 2;
            }
            $sd = sqrt($v / $n);
            $up[$i] = $m + 2 * $sd;
            $lo[$i] = $m - 2 * $sd;
        }
        return [$up, $lo];
    }

    /** Highest high / lowest low of the N bars BEFORE this one (so "close above highest high 20" is a real breakout). */
    private function extreme(array $x, int $n, bool $max): array
    {
        $out = array_fill(0, $this->n, null);
        $dq = [];
        for ($i = 0; $i < $this->n; $i++) {
            if ($i >= $n) {
                while ($dq && $dq[0] < $i - $n) {
                    array_shift($dq);
                }
                $out[$i] = $x[$dq[0]];
            }
            while ($dq && ($max ? $x[end($dq)] <= $x[$i] : $x[end($dq)] >= $x[$i])) {
                array_pop($dq);
            }
            $dq[] = $i;
        }
        return $out;
    }

    // ---------------------------------------------------------------- signals

    private function pattern(string $p, int $i): bool
    {
        if ($i < 1) {
            return false;
        }
        [$o, $h, $l, $c] = [$this->o[$i], $this->h[$i], $this->l[$i], $this->c[$i]];
        [$po, $pc] = [$this->o[$i - 1], $this->c[$i - 1]];
        $body = abs($c - $o);
        $range = max($h - $l, 1e-12);
        return match ($p) {
            'green' => $c > $o,
            'red' => $c < $o,
            'bull_engulf' => $pc < $po && $c > $o && $c >= $po && $o <= $pc,
            'bear_engulf' => $pc > $po && $c < $o && $c <= $po && $o >= $pc,
            'inside_bar' => $h < $this->h[$i - 1] && $l > $this->l[$i - 1],
            'pin_bull' => (min($o, $c) - $l) >= 2 * $body && (min($o, $c) - $l) >= 0.6 * $range,
            'pin_bear' => ($h - max($o, $c)) >= 2 * $body && ($h - max($o, $c)) >= 0.6 * $range,
            default => false,
        };
    }

    private function cond(array $c, int $i): bool
    {
        if (isset($c['pattern'])) {
            return $this->pattern($c['pattern'], $i);
        }
        $a = $this->series($c['a']);
        $b = $this->series($c['b']);
        if ($a[$i] === null || $b[$i] === null) {
            return false;
        }
        return match ($c['op']) {
            'gt' => $a[$i] > $b[$i],
            'lt' => $a[$i] < $b[$i],
            'crosses_above' => $i > 0 && $a[$i - 1] !== null && $b[$i - 1] !== null && $a[$i - 1] <= $b[$i - 1] && $a[$i] > $b[$i],
            'crosses_below' => $i > 0 && $a[$i - 1] !== null && $b[$i - 1] !== null && $a[$i - 1] >= $b[$i - 1] && $a[$i] < $b[$i],
        };
    }

    private function signal(array $conds, int $i): bool
    {
        foreach ($conds as $c) {
            if (!$this->cond($c, $i)) {
                return false;
            }
        }
        return (bool) $conds;
    }

    private function allowed(array $f, int $i): bool
    {
        if ($f['days'] && !in_array((int) gmdate('N', $this->t[$i]), $f['days'], true)) {
            return false;
        }
        if ($f['sessions'] && !in_array(Sessions::bucket(gmdate('Y-m-d H:i:s', $this->t[$i])), $f['sessions'], true)) {
            return false;
        }
        return true;
    }

    // ---------------------------------------------------------------- simulation

    /** @return array{trades: array, stats: array, equity: array, monthly: array, breakdown: array} */
    public function run(array $r): array
    {
        $trades = [];
        $atrS = $this->atr(max(2, $r['exit']['sl']['type'] === 'atr' ? $r['exit']['sl']['len'] : 14));
        $atrT = $this->atr(14);
        $spread = $r['risk']['spread'];
        $perDay = [];
        $i = self::WARMUP;
        while ($i < $this->n - 1) {
            $dir = 0;
            if ($this->allowed($r['filters'], $i)) {
                if ($r['long'] && $this->signal($r['long'], $i)) {
                    $dir = 1;
                } elseif ($r['short'] && $this->signal($r['short'], $i)) {
                    $dir = -1;
                }
            }
            $day = gmdate('Y-m-d', $this->t[$i + 1]);
            if ($dir && $r['risk']['max_per_day'] && ($perDay[$day] ?? 0) >= $r['risk']['max_per_day']) {
                $dir = 0;
            }
            if (!$dir) {
                $i++;
                continue;
            }
            $tr = $this->simulate($r, $i, $dir, $atrS, $atrT, $spread);
            if ($tr === null) {
                $i++;
                continue;
            }
            $perDay[$day] = ($perDay[$day] ?? 0) + 1;
            $trades[] = $tr;
            $i = $tr['exit_i'] + ($tr['exit_at_open'] ? 0 : 1);   // next signal is checked from the bar after the exit
        }
        return $this->report($trades, $r);
    }

    /** Opens a trade at the next bar's open after signal bar $i and walks forward until it closes. */
    private function simulate(array $r, int $i, int $dir, array $atrS, array $atrT, float $spread): ?array
    {
        $e = $i + 1;
        $entry = $this->o[$e] + ($dir === 1 ? $spread : 0);
        $sl = $r['exit']['sl'];
        $dist = match ($sl['type']) {
            'atr' => $atrS[$i] !== null ? $atrS[$i] * $sl['value'] : null,
            'points' => $sl['value'],
            'percent' => $entry * $sl['value'] / 100,
            'swing' => $dir === 1 ? $entry - min(array_slice($this->l, max(0, $i - $sl['len'] + 1), $sl['len'])) : max(array_slice($this->h, max(0, $i - $sl['len'] + 1), $sl['len'])) - $entry,
        };
        if ($dist === null || $dist <= 0) {
            return null;                                               // no valid stop: the trade is skipped, never invented
        }
        $stop = $entry - $dir * $dist;
        $tp = $r['exit']['tp'];
        $tdist = match ($tp['type']) {
            'r' => $dist * $tp['value'], 'atr' => $atrT[$i] !== null ? $atrT[$i] * $tp['value'] : null, 'points' => $tp['value'], 'none' => null,
        };
        $target = $tdist !== null ? $entry + $dir * $tdist : null;
        $be = $r['exit']['be_at_r'];
        $beArmed = false;
        $exit = null;
        $reason = '';
        $atOpen = false;
        for ($j = $e; $j < $this->n; $j++) {
            $hi = $this->h[$j];
            $lo = $this->l[$j];
            // a gap through the stop/target fills at the open, not at the level
            $hitStop = $dir === 1 ? $lo <= $stop : $hi + $spread >= $stop;
            $hitTp = $target !== null && ($dir === 1 ? $hi >= $target : $lo + $spread <= $target);
            if ($hitStop) {
                $op = $dir === 1 ? $this->o[$j] : $this->o[$j] + $spread;
                $exit = $dir === 1 ? min($stop, $op) : max($stop, $op);
                $reason = $beArmed ? 'Break-even' : 'Stop loss';
                break;
            }
            if ($hitTp) {
                $op = $dir === 1 ? $this->o[$j] : $this->o[$j] + $spread;
                $exit = $dir === 1 ? max($target, $op) : min($target, $op);
                $reason = 'Take profit';
                break;
            }
            if ($be !== null && !$beArmed && ($dir === 1 ? $hi - $entry : $entry - ($lo + $spread)) >= $be * $dist) {
                $stop = $entry;                                        // takes effect from the next bar
                $beArmed = true;
            }
            if ($r['exit']['max_bars'] && $j - $e + 1 >= $r['exit']['max_bars']) {
                $exit = $this->c[$j] + ($dir === -1 ? $spread : 0);
                $reason = 'Time exit';
                break;
            }
            if ($r['exit']['opposite'] && $this->signal($dir === 1 ? $r['short'] : $r['long'], $j)) {
                $exit = $this->c[$j] + ($dir === -1 ? $spread : 0);
                $reason = 'Opposite signal';
                break;
            }
        }
        if ($exit === null) {
            $j = $this->n - 1;
            $exit = $this->c[$j] + ($dir === -1 ? $spread : 0);
            $reason = 'End of data';
        }
        $rr = $dir * ($exit - $entry) / $dist;
        return [
            'side' => $dir === 1 ? 'long' : 'short', 'signal_ts' => $this->t[$i], 'entry_ts' => $this->t[$e], 'exit_ts' => $this->t[$j],
            'entry' => $entry, 'stop' => $entry - $dir * $dist, 'target' => $target, 'exit' => $exit, 'r' => round($rr, 4),
            'bars' => $j - $e + 1, 'reason' => $reason, 'exit_i' => $j, 'exit_at_open' => $atOpen,
            'session' => Sessions::bucket(gmdate('Y-m-d H:i:s', $this->t[$e])),
        ];
    }

    private function report(array $trades, array $r): array
    {
        $bal = $start = $r['risk']['balance'];
        $risk = $r['risk']['risk_pct'] / 100;
        $peak = $bal;
        $maxDd = 0.0;
        $cumR = $peakR = $maxDdR = 0.0;
        $equity = [[$this->t[min(self::WARMUP, $this->n - 1)] ?? 0, round($bal, 2)]];
        $wins = $losses = $be = 0;
        $sumW = $sumL = 0.0;
        $streak = $maxStreak = 0;
        $monthly = $bySes = $byDay = $bySide = $byReason = [];
        $bars = 0;
        foreach ($trades as &$tr) {
            $pnl = $bal * $risk * $tr['r'];
            $bal += $pnl;
            $tr['pnl'] = round($pnl, 2);
            $tr['balance'] = round($bal, 2);
            $peak = max($peak, $bal);
            $maxDd = max($maxDd, $peak > 0 ? ($peak - $bal) / $peak * 100 : 0);
            $cumR += $tr['r'];
            $peakR = max($peakR, $cumR);
            $maxDdR = max($maxDdR, $peakR - $cumR);
            $equity[] = [$tr['exit_ts'], round($bal, 2)];
            if ($tr['r'] > 0.0001) {
                $wins++;
                $sumW += $tr['r'];
                $streak = 0;
            } elseif ($tr['r'] < -0.0001) {
                $losses++;
                $sumL += $tr['r'];
                $maxStreak = max($maxStreak, ++$streak);
            } else {
                $be++;
            }
            $bars += $tr['bars'];
            $mk = gmdate('Y-m', $tr['exit_ts']);
            $monthly[$mk] = ($monthly[$mk] ?? 0) + $tr['r'];
            foreach ([[&$bySes, $tr['session']], [&$byDay, gmdate('D', $tr['entry_ts'])], [&$bySide, $tr['side']], [&$byReason, $tr['reason']]] as [&$g, $k]) {
                $g[$k] ??= ['n' => 0, 'w' => 0, 'r' => 0.0];
                $g[$k]['n']++;
                $g[$k]['w'] += $tr['r'] > 0.0001 ? 1 : 0;
                $g[$k]['r'] += $tr['r'];
            }
            unset($g);
        }
        unset($tr);
        $n = count($trades);
        $years = $this->n > 1 ? max(1 / 365, ($this->t[$this->n - 1] - $this->t[0]) / (365.25 * 86400)) : 1;
        $stats = [
            'trades' => $n, 'wins' => $wins, 'losses' => $losses, 'breakeven' => $be,
            'win_rate' => $n ? round($wins / $n * 100, 1) : 0.0,
            'avg_win' => $wins ? round($sumW / $wins, 2) : 0.0, 'avg_loss' => $losses ? round($sumL / $losses, 2) : 0.0,
            'expectancy' => $n ? round(($sumW + $sumL) / $n, 3) : 0.0,
            'profit_factor' => $sumL < 0 ? round($sumW / abs($sumL), 2) : null,
            'net_r' => round($sumW + $sumL, 2), 'max_dd_r' => round($maxDdR, 2),
            'start' => round($start, 2), 'end' => round($bal, 2), 'net_pct' => round(($bal - $start) / $start * 100, 2), 'max_dd_pct' => round($maxDd, 2),
            'max_losing_streak' => $maxStreak, 'avg_bars' => $n ? round($bars / $n, 1) : 0.0,
            'trades_per_month' => round($n / ($years * 12), 1),
            'best' => $n ? round(max(array_column($trades, 'r')), 2) : 0.0, 'worst' => $n ? round(min(array_column($trades, 'r')), 2) : 0.0,
            'from' => $this->n ? gmdate('Y-m-d', $this->t[0]) : null, 'to' => $this->n ? gmdate('Y-m-d', $this->t[$this->n - 1]) : null, 'bars' => $this->n,
        ];
        $fmt = fn ($g) => array_map(fn ($v) => ['n' => $v['n'], 'win_rate' => round($v['w'] / max(1, $v['n']) * 100, 1), 'r' => round($v['r'], 2)], $g);
        ksort($monthly);
        foreach ($trades as &$tr) {
            unset($tr['exit_i'], $tr['exit_at_open']);
        }
        unset($tr);
        return [
            'trades' => $trades, 'stats' => $stats, 'equity' => self::thin($equity, 400),
            'monthly' => array_map(fn ($v) => round($v, 2), $monthly),
            'breakdown' => ['session' => $fmt($bySes), 'weekday' => $fmt($byDay), 'side' => $fmt($bySide), 'exit' => $fmt($byReason)],
        ];
    }

    /** Keeps at most $max points of an equity curve (always keeps the last point and the lowest/highest of each slice). */
    private static function thin(array $pts, int $max): array
    {
        $n = count($pts);
        if ($n <= $max) {
            return $pts;
        }
        $out = [$pts[0]];
        $step = ($n - 1) / ($max / 2);
        for ($k = 0; $k < $max / 2; $k++) {
            $sl = array_slice($pts, (int) round(1 + $k * $step), max(1, (int) round($step)));
            if (!$sl) {
                continue;
            }
            usort($sl, fn ($a, $b) => $a[0] <=> $b[0]);
            $lo = $hi = $sl[0];
            foreach ($sl as $p) {
                if ($p[1] < $lo[1]) { $lo = $p; }
                if ($p[1] > $hi[1]) { $hi = $p; }
            }
            foreach ($lo[0] <= $hi[0] ? [$lo, $hi] : [$hi, $lo] as $p) {
                if (end($out)[0] !== $p[0]) {
                    $out[] = $p;
                }
            }
        }
        if (end($out)[0] !== $pts[$n - 1][0]) {
            $out[] = $pts[$n - 1];
        }
        return $out;
    }
}
