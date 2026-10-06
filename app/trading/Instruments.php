<?php
declare(strict_types=1);

namespace App\Trading;

/**
 * Centralised instrument metadata (1 standard lot, common retail CFD conventions). Every P&L, risk and
 * formatting calculation goes through this table. Brokers differ, so members can override P&L with the
 * broker-reported figure on any trade.
 */
final class Instruments
{
    /** symbol => [name, class, base, quote, contractSize, tickSize, pipSize, decimals, aliases, ticker] */
    private const DATA = [
        'XAUUSD' => ['Gold', 'METALS', 'XAU', 'USD', 100, 0.01, 0.1, 2, ['gold', 'xau', 'gc'], true],
        'XAGUSD' => ['Silver', 'METALS', 'XAG', 'USD', 5000, 0.001, 0.01, 3, ['silver', 'xag', 'si'], true],
        'USOIL' => ['WTI Crude', 'COMMODITIES', 'WTI', 'USD', 1000, 0.01, 0.01, 2, ['wti', 'oil', 'cl', 'crude'], true],
        'UKOIL' => ['Brent Crude', 'COMMODITIES', 'BRENT', 'USD', 1000, 0.01, 0.01, 2, ['brent', 'bz'], true],
        'NATGAS' => ['Natural Gas', 'COMMODITIES', 'NG', 'USD', 10000, 0.001, 0.001, 3, ['ng', 'gas', 'xngusd'], true],
        'COPPER' => ['Copper', 'COMMODITIES', 'HG', 'USD', 25000, 0.0005, 0.0005, 4, ['hg', 'xcuusd'], true],
        'US500' => ['S&P 500', 'INDICES', 'SPX', 'USD', 1, 0.01, 0.1, 2, ['spx', 'sp500', 'es', 'spx500'], true],
        'NAS100' => ['Nasdaq 100', 'INDICES', 'NDX', 'USD', 1, 0.01, 0.1, 2, ['nas', 'nq', 'ustec', 'us100', 'ndx'], true],
        'US30' => ['Dow Jones 30', 'INDICES', 'DJI', 'USD', 1, 0.01, 1, 2, ['dow', 'dji', 'ym', 'dj30'], true],
        'GER40' => ['DAX 40', 'INDICES', 'DAX', 'EUR', 1, 0.01, 1, 2, ['dax', 'de40', 'ger30'], true],
        'UK100' => ['FTSE 100', 'INDICES', 'FTSE', 'GBP', 1, 0.01, 1, 2, ['ftse', 'ftse100'], true],
        'JPN225' => ['Nikkei 225', 'INDICES', 'NKY', 'JPY', 1, 1, 1, 0, ['nikkei', 'nk225', 'jp225'], true],
        'EURUSD' => ['EUR/USD', 'FOREX', 'EUR', 'USD', 100000, 0.00001, 0.0001, 5, ['eu', 'fiber'], true],
        'GBPUSD' => ['GBP/USD', 'FOREX', 'GBP', 'USD', 100000, 0.00001, 0.0001, 5, ['gu', 'cable'], true],
        'USDJPY' => ['USD/JPY', 'FOREX', 'USD', 'JPY', 100000, 0.001, 0.01, 3, ['uj', 'yen'], true],
        'AUDUSD' => ['AUD/USD', 'FOREX', 'AUD', 'USD', 100000, 0.00001, 0.0001, 5, ['au', 'aussie'], false],
        'USDCAD' => ['USD/CAD', 'FOREX', 'USD', 'CAD', 100000, 0.00001, 0.0001, 5, ['uc', 'loonie'], false],
        'USDCHF' => ['USD/CHF', 'FOREX', 'USD', 'CHF', 100000, 0.00001, 0.0001, 5, ['swissy'], false],
        'GBPJPY' => ['GBP/JPY', 'FOREX', 'GBP', 'JPY', 100000, 0.001, 0.01, 3, ['gj', 'guppy'], false],
        'EURJPY' => ['EUR/JPY', 'FOREX', 'EUR', 'JPY', 100000, 0.001, 0.01, 3, ['ej'], false],
        'BTCUSDT' => ['Bitcoin', 'CRYPTO', 'BTC', 'USD', 1, 0.01, 1, 2, ['btc', 'bitcoin', 'btcusd', 'xbt'], true],
        'ETHUSDT' => ['Ethereum', 'CRYPTO', 'ETH', 'USD', 1, 0.01, 0.1, 2, ['eth', 'ethereum', 'ethusd'], true],
        'SOLUSDT' => ['Solana', 'CRYPTO', 'SOL', 'USD', 1, 0.001, 0.01, 3, ['sol', 'solana', 'solusd'], false],
    ];

    public static function all(): array
    {
        $out = [];
        foreach (self::DATA as $sym => $d) {
            $out[$sym] = self::get($sym);
        }
        return $out;
    }

    public static function get(string $symbol): ?array
    {
        $sym = strtoupper($symbol);
        $d = self::DATA[$sym] ?? null;
        if (!$d) {
            return null;
        }
        return ['symbol' => $sym, 'name' => $d[0], 'class' => $d[1], 'base' => $d[2], 'quote' => $d[3], 'contract' => $d[4], 'tick' => $d[5], 'pip' => $d[6], 'decimals' => $d[7], 'ticker' => $d[9]];
    }

    /** Resolves user/broker text ("gold", "XAUUSD.r", "US500m", "eur/usd") to an instrument symbol. */
    public static function resolve(string $text): ?string
    {
        $raw = strtolower(trim($text));
        if ($raw === '') {
            return null;
        }
        $compact = preg_replace('/[\/_\-\s]/', '', $raw) ?? $raw;
        foreach ([$raw, $compact] as $candidate) {
            if ($s = self::lookup($candidate)) {
                return $s;
            }
        }
        $stripped = preg_replace(['/\.(r|m|pro|raw|ecn|cash|std|i|a|b|c)$/i', '/[+#!.]$/'], '', $compact) ?? $compact;
        return self::lookup($stripped) ?? self::lookup(preg_replace('/[a-z]$/', '', $stripped) ?? '');
    }

    private static function lookup(string $t): ?string
    {
        if ($t === '') {
            return null;
        }
        foreach (self::DATA as $sym => $d) {
            if (strtolower($sym) === $t || in_array($t, $d[8], true)) {
                return $sym;
            }
        }
        return null;
    }

    public static function format(string $symbol, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }
        $i = self::get($symbol);
        return number_format((float) $value, $i['decimals'] ?? 2, '.', ',');
    }

    public static function tickerSymbols(): array
    {
        return array_keys(array_filter(self::DATA, fn ($d) => $d[9]));
    }
}
