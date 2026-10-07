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

    /** Spoken number words → digits (covers lot sizes like "one lot", "half a lot", "point five lots"). */
    private const WORDS = ['zero' => '0', 'one' => '1', 'two' => '2', 'three' => '3', 'four' => '4', 'five' => '5', 'six' => '6', 'seven' => '7', 'eight' => '8', 'nine' => '9', 'ten' => '10'];

    /**
     * Turns a natural spoken/typed sentence into the command grammar, e.g.
     * "Bought gold at 2,645.50, stop loss 2639, take profit 2660, 0.5 lots." → "buy gold at 2645.50 sl 2639 tp 2660 0.5 lots".
     */
    public static function normalize(string $text): string
    {
        $t = mb_strtolower(trim($text));
        $t = preg_replace('/(\d),(\d{3})/', '$1$2', $t) ?? $t;               // 2,645.50 → 2645.50
        $t = preg_replace('/(\d),(\d{3})/', '$1$2', $t) ?? $t;               // 1,234,567
        foreach (Instruments::phraseAliases() as $phrase => $sym) {           // "euro dollar" → eurusd
            $t = preg_replace('/\b' . preg_quote($phrase, '/') . '\b/u', ' ' . strtolower($sym) . ' ', $t) ?? $t;
        }
        $ccy = 'eur|usd|gbp|jpy|aud|nzd|cad|chf|xau|xag|btc|eth|sol|xrp';
        $t = preg_replace('/\b(' . $ccy . ')\s*\/?\s*(' . $ccy . ')\b/', '$1$2', $t) ?? $t; // "eur usd", "eur/usd" → eurusd
        $t = preg_replace_callback('/\b(?:zero\s+)?point\s+(' . implode('|', array_keys(self::WORDS)) . ')\b/', fn ($m) => ' 0.' . self::WORDS[$m[1]], $t) ?? $t; // "point five" → 0.5
        $t = preg_replace('/[,;!?]+|\.(?=\s|$)/', ' ', $t) ?? $t;               // punctuation (keeps decimal points)
        $rules = [
            '/\b(?:went|go|going)\s+long\b|\bbought\b|\bbuying\b|\blonged\b/' => ' buy ',
            '/\b(?:went|go|going)\s+short\b|\bsold\b|\bselling\b|\bshorted\b/' => ' sell ',
            '/\bstop[\s-]*loss\b|\bstop\s+at\b|\bstopped\s+at\b|\bstop\b|\bs\s*l\b/' => ' sl ',
            '/\btake[\s-]*profit\b|\bprofit\s+target\b|\btarget(?:ing|ed)?\b|\bt\s*p\b/' => ' tp ',
            '/\b(?:exited|closed|exit|close|out|got\s+out)(?:\s+(?:at|@))?\b/' => ' exit ',
            '/\bentry(?:\s+price)?(?:\s+(?:at|of))?\b|\bentered(?:\s+at)?\b|\bin\s+at\b/' => ' at ',
            '/\bhalf\s+(?:a\s+)?lots?\b/' => ' 0.5 lot ',
            '/\b(?:a\s+)?quarter\s+(?:of\s+)?(?:a\s+)?lots?\b/' => ' 0.25 lot ',
            '/\bpoint\s+(\d+)/' => ' 0.$1',
            '/\bstandard\s+lots?\b/' => ' lot ',
            '/\bmicro\s+lots?\b/' => ' microlot ',
        ];
        foreach ($rules as $re => $rep) {
            $t = preg_replace($re, $rep, $t) ?? $t;
        }
        $t = preg_replace_callback('/\b(' . implode('|', array_keys(self::WORDS)) . ')\b(?=\s+(?:lots?|microlot))/', fn ($m) => self::WORDS[$m[1]], $t) ?? $t;
        $t = preg_replace('/\b(\d+(?:\.\d+)?)\s+microlots?\b/', '$1 microlot', $t) ?? $t;
        $t = preg_replace_callback('/\b(\d+(?:\.\d+)?)\s+microlot\b/', fn ($m) => rtrim(rtrim(number_format((float) $m[1] / 100, 4, '.', ''), '0'), '.') . ' lot', $t) ?? $t;
        // Filler words that carry no trade information in speech.
        $t = preg_replace('/\b(i|we|my|the|a|an|and|with|of|for|on|trade|position|price|was|then|it|size|sized|lot\s+size\s+of|took|take|placed|put|set|order|market)\b/', ' ', $t) ?? $t;
        return trim(preg_replace('/\s+/', ' ', $t) ?? $t);
    }

    public static function parse(string $command, bool $voice = false): array
    {
        if ($voice) {
            $command = self::normalize($command);
        }
        $o = ['symbol' => null, 'side' => null, 'entry' => null, 'stop' => null, 'tp' => null, 'exit' => null, 'target_r' => null, 'rr' => null, 'lots' => null,
            'setup' => null, 'mistake' => null, 'emotion' => null, 'outcome' => null, 'errors' => [], 'warnings' => [], 'normalized' => $command, 'exit_spoken' => null, 'lots_spoken' => false];
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
                $o['exit_spoken'] = (float) $next;
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
        if ($voice) {
            $setup = []; // spoken filler is never turned into a setup name
        }
        $o['lots_spoken'] = $o['lots'] !== null;
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
            foreach (['entry', 'stop', 'tp', 'exit', 'exit_spoken'] as $k) {
                if ($o[$k] !== null) {
                    $o[$k] = round($o[$k], $d);
                }
            }
        }
        return $o;
    }
}
