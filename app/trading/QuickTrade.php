<?php
declare(strict_types=1);

namespace App\Trading;

/**
 * Natural-language quick-trade parser.
 *   <buy|long|sell|short> <asset> [@|at] <entry> [sl <price>] [tp <price>] [exit <price>] [<n>r] [<n> lot] [win|loss|be] [#tags] [setup words]
 * Examples: "buy gold 2862 sl 2858 3r 0.5 lot val bounce", "sell btc 62500 sl 63000 tp 61000 0.2"
 */
final class QuickTrade
{
    private const SIDE = ['buy' => 'LONG', 'long' => 'LONG', 'b' => 'LONG', 'sell' => 'SHORT', 'short' => 'SHORT', 's' => 'SHORT'];
    private const TAGS = [
        'fomo' => 'FOMO_ENTRY', 'revenge' => 'REVENGE_TRADE', 'movedstop' => 'MOVED_STOP', 'moved_stop' => 'MOVED_STOP', 'nostop' => 'NO_STOP', 'no_stop' => 'NO_STOP',
        'oversized' => 'OVERSIZED', 'early' => 'EARLY_EXIT', 'earlyexit' => 'EARLY_EXIT', 'late' => 'LATE_ENTRY', 'chasing' => 'CHASING', 'chase' => 'CHASING',
        'ignoredplan' => 'IGNORED_PLAN', 'overtrading' => 'OVERTRADING', 'news' => 'NEWS_GAMBLE',
    ];
    private const NUM = '/^-?\d+(?:\.\d+)?$/';

    public static function parse(string $command): array
    {
        $o = ['symbol' => null, 'side' => null, 'entry' => null, 'stop' => null, 'tp' => null, 'exit' => null, 'target_r' => null, 'rr' => null, 'lots' => null,
            'setup' => null, 'mistake' => null, 'emotion' => null, 'outcome' => null, 'errors' => [], 'warnings' => []];
        $tokens = array_values(array_filter(explode(' ', preg_replace('/\s+/', ' ', trim($command)) ?? ''), 'strlen'));
        if (!$tokens) {
            $o['errors'][] = 'Enter a command, e.g. "buy gold 2862 sl 2858 3r 0.5 lot val bounce"';
            return $o;
        }
        $setup = [];
        $n = count($tokens);
        $isNum = fn (?string $s) => $s !== null && preg_match(self::NUM, $s) === 1;
        for ($i = 0; $i < $n; $i++) {
            $raw = $tokens[$i];
            $t = strtolower($raw);
            $next = $tokens[$i + 1] ?? null;
            if (!$o['side'] && isset(self::SIDE[$t])) {
                $o['side'] = self::SIDE[$t];
            } elseif (str_starts_with($t, '#')) {
                $tag = substr($t, 1);
                if (isset(self::TAGS[$tag])) {
                    $o['mistake'] = self::TAGS[$tag];
                } elseif (isset(Domain::MISTAKES[strtoupper($tag)])) {
                    $o['mistake'] = strtoupper($tag);
                } elseif (in_array(strtoupper($tag), Domain::EMOTIONS, true)) {
                    $o['emotion'] = strtoupper($tag);
                } else {
                    $o['warnings'][] = "Unknown tag $raw";
                }
            } elseif (!$o['symbol'] && !$isNum($t) && ($sym = Instruments::resolve($t))) {
                $o['symbol'] = $sym;
            } elseif (in_array($t, ['@', 'at'], true) && $isNum($next)) {
                $o['entry'] = (float) $next;
                $i++;
            } elseif (in_array($t, ['sl', 'stop'], true) && $isNum($next)) {
                $o['stop'] = (float) $next;
                $i++;
            } elseif (in_array($t, ['tp', 'target'], true) && $isNum($next)) {
                $o['tp'] = (float) $next;
                $i++;
            } elseif (in_array($t, ['exit', 'out', 'closed', 'close'], true) && $isNum($next)) {
                $o['exit'] = (float) $next;
                $i++;
            } elseif (in_array($t, ['lot', 'lots', 'size'], true) && $isNum($next) && $o['lots'] === null) {
                $o['lots'] = (float) $next;
                $i++;
            } elseif (preg_match('/^(-?\d+(?:\.\d+)?)r$/i', $t, $m)) {
                $o['target_r'] = (float) $m[1];
            } elseif (preg_match('/^(\d+(?:\.\d+)?)(?:lots?|l)$/i', $t, $m)) {
                $o['lots'] = (float) $m[1];
            } elseif ($isNum($t)) {
                if ($next !== null && preg_match('/^lots?$/i', $next)) {
                    $o['lots'] = (float) $t;
                    $i++;
                } elseif ($o['entry'] === null) {
                    $o['entry'] = (float) $t;
                } elseif ($o['lots'] === null) {
                    $o['lots'] = (float) $t;
                } else {
                    $o['warnings'][] = "Ignored extra number $raw";
                }
            } elseif (in_array($t, ['win', 'won', 'hit'], true)) {
                $o['outcome'] = 'WIN';
            } elseif (in_array($t, ['loss', 'lost', 'stopped'], true)) {
                $o['outcome'] = 'LOSS';
            } elseif (in_array($t, ['be', 'breakeven', 'scratch'], true)) {
                $o['outcome'] = 'BREAKEVEN';
            } else {
                $setup[] = $raw;
            }
        }
        if ($setup) {
            $s = ucwords(strtolower(mb_substr(implode(' ', $setup), 0, 120)));
            $o['setup'] = preg_replace_callback('/\b(Val|Vah|Poc|Ict|Fvg|Ob|Bos|Choch|Vwap)\b/', fn ($m) => strtoupper($m[1]), $s);
        }
        if (!$o['side']) {
            $o['errors'][] = 'Missing direction (buy/long or sell/short)';
        }
        if (!$o['symbol']) {
            $o['errors'][] = 'Unknown or missing instrument';
        }
        if ($o['entry'] === null) {
            $o['errors'][] = 'Missing entry price';
        }
        if ($o['lots'] !== null && $o['lots'] <= 0) {
            $o['errors'][] = 'Lot size must be positive';
        }
        if ($o['lots'] === null) {
            $o['lots'] = 1.0;
            $o['warnings'][] = 'No lot size given — defaulting to 1.00 lot';
        }
        if ($o['side'] && $o['entry'] !== null && $o['stop'] !== null) {
            if ($o['stop'] == $o['entry']) {
                $o['errors'][] = 'Stop loss cannot equal entry';
            } elseif ($o['side'] === 'LONG' && $o['stop'] > $o['entry']) {
                $o['errors'][] = 'Long stop loss must be below entry';
            } elseif ($o['side'] === 'SHORT' && $o['stop'] < $o['entry']) {
                $o['errors'][] = 'Short stop loss must be above entry';
            }
        }
        if (!$o['errors']) {
            $side = $o['side'];
            $e = $o['entry'];
            $s = $o['stop'];
            if ($o['target_r'] !== null && $s !== null) {
                $target = TradeMath::exitFromR($side, $e, $s, $o['target_r']);
                if ($o['tp'] === null && $o['target_r'] > 0) {
                    $o['tp'] = $target;
                }
                if ($o['exit'] === null && !$o['outcome']) {
                    $o['exit'] = $target;
                }
            } elseif ($o['target_r'] !== null) {
                $o['errors'][] = 'An R target needs a stop loss (sl <price>)';
            }
            if ($o['exit'] === null && $o['outcome'] === 'WIN') {
                $o['tp'] !== null ? $o['exit'] = $o['tp'] : $o['errors'][] = '"win" needs a tp or R target';
            }
            if ($o['exit'] === null && $o['outcome'] === 'LOSS') {
                $s !== null ? $o['exit'] = $s : $o['errors'][] = '"loss" needs a stop loss';
            }
            if ($o['exit'] === null && $o['outcome'] === 'BREAKEVEN') {
                $o['exit'] = $e;
            }
            if ($o['exit'] === null && $o['tp'] !== null && !$o['outcome']) {
                $o['exit'] = $o['tp'];
            }
            if ($o['exit'] !== null && $s !== null) {
                $r = TradeMath::realisedR($side, $e, $s, $o['exit']);
                $o['rr'] = $r === null ? null : round($r, 2);
            }
            if ($o['exit'] === null) {
                $o['warnings'][] = 'No exit — the trade will be logged as OPEN';
            }
            if ($s === null) {
                $o['warnings'][] = 'No stop loss — the R multiple cannot be calculated';
            }
        }
        if ($o['symbol']) {
            $d = Instruments::get($o['symbol'])['decimals'];
            foreach (['entry', 'stop', 'tp', 'exit'] as $k) {
                if ($o[$k] !== null) {
                    $o[$k] = round($o[$k], $d);
                }
            }
        }
        return $o;
    }
}
