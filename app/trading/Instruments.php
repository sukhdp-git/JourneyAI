<?php
declare(strict_types=1);

namespace App\Trading;

use App\Core\Database;

/**
 * Centralised instrument specifications. Every P&L, risk, position-size and formatting calculation reads
 * from here — no pip values are hard-coded anywhere else.
 *
 * The built-in table below holds common retail CFD conventions (1 standard lot). Site owners can edit or
 * add instruments in the Control Panel (Members → Instruments); rows in the `instruments` table override
 * the built-in values. Brokers differ, so members can also override P&L with their broker's figure.
 */
final class Instruments
{
    /** symbol => [name, class, base, quote, contractSize, tickSize, pipSize, decimals, aliases] */
    public const DEFAULTS = [
        // Metals
        'XAUUSD' => ['Gold', 'METALS', 'XAU', 'USD', 100, 0.01, 0.1, 2, ['gold', 'xau', 'gc']],
        'XAGUSD' => ['Silver', 'METALS', 'XAG', 'USD', 5000, 0.001, 0.01, 3, ['silver', 'xag', 'si']],
        // Forex majors
        'EURUSD' => ['EUR/USD', 'FOREX', 'EUR', 'USD', 100000, 0.00001, 0.0001, 5, ['eu', 'fiber', 'euro dollar', 'euro']],
        'GBPUSD' => ['GBP/USD', 'FOREX', 'GBP', 'USD', 100000, 0.00001, 0.0001, 5, ['gu', 'cable', 'pound dollar', 'pound']],
        'USDJPY' => ['USD/JPY', 'FOREX', 'USD', 'JPY', 100000, 0.001, 0.01, 3, ['uj', 'yen', 'dollar yen']],
        'AUDUSD' => ['AUD/USD', 'FOREX', 'AUD', 'USD', 100000, 0.00001, 0.0001, 5, ['au', 'aussie', 'aussie dollar']],
        'NZDUSD' => ['NZD/USD', 'FOREX', 'NZD', 'USD', 100000, 0.00001, 0.0001, 5, ['nu', 'kiwi', 'kiwi dollar']],
        'USDCAD' => ['USD/CAD', 'FOREX', 'USD', 'CAD', 100000, 0.00001, 0.0001, 5, ['uc', 'loonie', 'dollar cad']],
        'USDCHF' => ['USD/CHF', 'FOREX', 'USD', 'CHF', 100000, 0.00001, 0.0001, 5, ['swissy', 'dollar swiss']],
        // Forex crosses
        'EURJPY' => ['EUR/JPY', 'FOREX', 'EUR', 'JPY', 100000, 0.001, 0.01, 3, ['ej', 'euro yen']],
        'GBPJPY' => ['GBP/JPY', 'FOREX', 'GBP', 'JPY', 100000, 0.001, 0.01, 3, ['gj', 'guppy', 'pound yen']],
        'EURGBP' => ['EUR/GBP', 'FOREX', 'EUR', 'GBP', 100000, 0.00001, 0.0001, 5, ['eg', 'euro pound']],
        'EURAUD' => ['EUR/AUD', 'FOREX', 'EUR', 'AUD', 100000, 0.00001, 0.0001, 5, []],
        'EURCAD' => ['EUR/CAD', 'FOREX', 'EUR', 'CAD', 100000, 0.00001, 0.0001, 5, []],
        'EURCHF' => ['EUR/CHF', 'FOREX', 'EUR', 'CHF', 100000, 0.00001, 0.0001, 5, []],
        'GBPAUD' => ['GBP/AUD', 'FOREX', 'GBP', 'AUD', 100000, 0.00001, 0.0001, 5, []],
        'GBPCHF' => ['GBP/CHF', 'FOREX', 'GBP', 'CHF', 100000, 0.00001, 0.0001, 5, []],
        'AUDJPY' => ['AUD/JPY', 'FOREX', 'AUD', 'JPY', 100000, 0.001, 0.01, 3, ['aussie yen']],
        'CADJPY' => ['CAD/JPY', 'FOREX', 'CAD', 'JPY', 100000, 0.001, 0.01, 3, []],
        'CHFJPY' => ['CHF/JPY', 'FOREX', 'CHF', 'JPY', 100000, 0.001, 0.01, 3, []],
        'NZDJPY' => ['NZD/JPY', 'FOREX', 'NZD', 'JPY', 100000, 0.001, 0.01, 3, []],
        'AUDNZD' => ['AUD/NZD', 'FOREX', 'AUD', 'NZD', 100000, 0.00001, 0.0001, 5, []],
        // Indices (1 lot = 1 × index point in the index currency)
        'US30' => ['Dow Jones 30', 'INDICES', 'DJI', 'USD', 1, 0.01, 1, 2, ['dow', 'dji', 'ym', 'dj30', 'dow jones', 'wall street']],
        'US500' => ['S&P 500', 'INDICES', 'SPX', 'USD', 1, 0.01, 0.1, 2, ['spx', 'sp500', 'es', 'spx500', 's&p', 's&p 500', 's and p']],
        'NAS100' => ['Nasdaq 100', 'INDICES', 'NDX', 'USD', 1, 0.01, 0.1, 2, ['nas', 'nq', 'ustec', 'us100', 'ndx', 'nasdaq']],
        'US2000' => ['Russell 2000', 'INDICES', 'RUT', 'USD', 1, 0.01, 0.1, 2, ['russell', 'rty', 'rut']],
        'GER40' => ['DAX 40', 'INDICES', 'DAX', 'EUR', 1, 0.01, 1, 2, ['dax', 'de40', 'ger30', 'germany 40']],
        'UK100' => ['FTSE 100', 'INDICES', 'FTSE', 'GBP', 1, 0.01, 1, 2, ['ftse', 'ftse100']],
        'FRA40' => ['CAC 40', 'INDICES', 'CAC', 'EUR', 1, 0.01, 1, 2, ['cac', 'cac40', 'france 40']],
        'EU50' => ['Euro Stoxx 50', 'INDICES', 'SX5E', 'EUR', 1, 0.01, 1, 2, ['stoxx', 'eustx50', 'stoxx50']],
        'JPN225' => ['Nikkei 225 (JP225)', 'INDICES', 'NKY', 'JPY', 1, 1, 1, 0, ['nikkei', 'nk225', 'jp225', 'japan 225']],
        'HK50' => ['Hang Seng 50', 'INDICES', 'HSI', 'HKD', 1, 1, 1, 0, ['hang seng', 'hsi', 'hk33']],
        'AUS200' => ['ASX 200', 'INDICES', 'ASX', 'AUD', 1, 0.1, 1, 1, ['asx', 'asx200', 'aus 200']],
        // Commodities
        'USOIL' => ['WTI Crude', 'COMMODITIES', 'WTI', 'USD', 1000, 0.01, 0.01, 2, ['wti', 'oil', 'cl', 'crude', 'crude oil']],
        'UKOIL' => ['Brent Crude', 'COMMODITIES', 'BRENT', 'USD', 1000, 0.01, 0.01, 2, ['brent', 'bz']],
        'NATGAS' => ['Natural Gas', 'COMMODITIES', 'NG', 'USD', 10000, 0.001, 0.001, 3, ['ng', 'gas', 'xngusd', 'natural gas']],
        'COPPER' => ['Copper', 'COMMODITIES', 'HG', 'USD', 25000, 0.0005, 0.0005, 4, ['hg', 'xcuusd']],
        // Crypto (1 lot = 1 coin)
        'BTCUSDT' => ['Bitcoin', 'CRYPTO', 'BTC', 'USD', 1, 0.01, 1, 2, ['btc', 'bitcoin', 'btcusd', 'xbt']],
        'ETHUSDT' => ['Ethereum', 'CRYPTO', 'ETH', 'USD', 1, 0.01, 0.1, 2, ['eth', 'ethereum', 'ethusd', 'ether']],
        'SOLUSDT' => ['Solana', 'CRYPTO', 'SOL', 'USD', 1, 0.001, 0.01, 3, ['sol', 'solana', 'solusd']],
        'XRPUSDT' => ['XRP', 'CRYPTO', 'XRP', 'USD', 1, 0.0001, 0.001, 4, ['xrp', 'ripple', 'xrpusd']],
        'BNBUSDT' => ['BNB', 'CRYPTO', 'BNB', 'USD', 1, 0.01, 0.1, 2, ['bnb', 'binance coin', 'bnbusd']],
        'ADAUSDT' => ['Cardano', 'CRYPTO', 'ADA', 'USD', 1, 0.0001, 0.001, 4, ['ada', 'cardano', 'adausd']],
        'DOGEUSDT' => ['Dogecoin', 'CRYPTO', 'DOGE', 'USD', 1, 0.00001, 0.0001, 5, ['doge', 'dogecoin', 'dogeusd']],
        'LTCUSDT' => ['Litecoin', 'CRYPTO', 'LTC', 'USD', 1, 0.01, 0.1, 2, ['ltc', 'litecoin', 'ltcusd']],
    ];

    private static ?array $cache = null;

    /** All active instruments: built-in defaults overlaid with Control Panel rows (when the table exists). */
    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }
        $out = [];
        foreach (self::DEFAULTS as $sym => $d) {
            $out[$sym] = [
                'symbol' => $sym, 'name' => $d[0], 'class' => $d[1], 'base' => $d[2], 'quote' => $d[3], 'contract' => (float) $d[4],
                'tick' => (float) $d[5], 'pip' => (float) $d[6], 'decimals' => (int) $d[7], 'aliases' => $d[8], 'min_lot' => 0.01, 'lot_step' => 0.01,
            ];
        }
        try {
            foreach (Database::all('SELECT * FROM instruments ORDER BY sort_order, symbol') as $r) {
                $sym = strtoupper((string) $r['symbol']);
                if (!(int) $r['is_active']) {
                    unset($out[$sym]);
                    continue;
                }
                $out[$sym] = [
                    'symbol' => $sym, 'name' => (string) $r['name'], 'class' => (string) $r['asset_class'], 'base' => strtoupper((string) $r['base_currency']),
                    'quote' => strtoupper((string) $r['quote_currency']), 'contract' => (float) $r['contract_size'], 'tick' => (float) $r['tick_size'],
                    'pip' => (float) $r['pip_size'], 'decimals' => (int) $r['price_decimals'],
                    'aliases' => array_values(array_filter(array_map(fn ($a) => strtolower(trim($a)), explode(',', (string) $r['aliases'])))),
                    'min_lot' => (float) $r['min_lot'] ?: 0.01, 'lot_step' => (float) $r['lot_step'] ?: 0.01,
                ];
            }
        } catch (\Throwable) {
            // Table not migrated yet — built-in specifications are used.
        }
        foreach ($out as &$i) {
            // Value of one tick for one lot, in the quote currency.
            $i['tick_value'] = $i['tick'] * $i['contract'];
            $i['pip_value'] = $i['pip'] * $i['contract'];
        }
        unset($i);
        return self::$cache = $out;
    }

    public static function flush(): void
    {
        self::$cache = null;
    }

    public static function get(string $symbol): ?array
    {
        return self::all()[strtoupper($symbol)] ?? null;
    }

    /** Resolves user/broker/voice text ("gold", "XAUUSD.r", "US500m", "eur/usd", "euro dollar") to a symbol. */
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
        foreach (self::all() as $sym => $d) {
            if (strtolower($sym) === $t || in_array($t, $d['aliases'], true)) {
                return $sym;
            }
        }
        return null;
    }

    /** Multi-word aliases (longest first) for natural-language/voice parsing. */
    public static function phraseAliases(): array
    {
        $out = [];
        foreach (self::all() as $sym => $d) {
            foreach ($d['aliases'] as $a) {
                if (str_contains($a, ' ')) {
                    $out[$a] = $sym;
                }
            }
        }
        uksort($out, fn ($a, $b) => strlen($b) <=> strlen($a));
        return $out;
    }

    public static function format(string $symbol, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }
        $i = self::get($symbol);
        return number_format((float) $value, $i['decimals'] ?? 2, '.', ',');
    }

    /** Distance expressed in pips (FX/metals) or points (indices/crypto/commodities). */
    public static function distanceLabel(string $symbol): string
    {
        $i = self::get($symbol);
        return $i && in_array($i['class'], ['FOREX', 'METALS'], true) ? 'pips' : 'points';
    }
}
