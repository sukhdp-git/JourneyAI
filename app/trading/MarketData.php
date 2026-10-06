<?php
declare(strict_types=1);

namespace App\Trading;

use App\Core\Database;
use App\Core\Logger;

/**
 * Market quotes via Twelve Data (https://twelvedata.com) when an API key is configured; cached for 60 s.
 * Without a key, static reference levels are returned and the UI labels them DEMO DATA — never as live prices.
 */
final class MarketData
{
    private const MAP = [
        'XAUUSD' => 'XAU/USD', 'XAGUSD' => 'XAG/USD', 'USOIL' => 'WTI/USD', 'UKOIL' => 'BRENT/USD', 'NATGAS' => 'NG/USD', 'COPPER' => 'XCU/USD',
        'US500' => 'SPX', 'NAS100' => 'NDX', 'US30' => 'DJI', 'GER40' => 'DAX', 'UK100' => 'FTSE', 'JPN225' => 'N225',
        'EURUSD' => 'EUR/USD', 'GBPUSD' => 'GBP/USD', 'USDJPY' => 'USD/JPY', 'BTCUSDT' => 'BTC/USD', 'ETHUSDT' => 'ETH/USD',
    ];
    private const DEMO = [
        'XAUUSD' => ['3352.40', 0.42], 'XAGUSD' => ['38.215', -0.31], 'USOIL' => ['66.12', 0.85], 'UKOIL' => ['69.48', 0.71], 'NATGAS' => ['3.214', -1.12],
        'COPPER' => ['4.6120', 0.18], 'US500' => ['6412.50', 0.24], 'NAS100' => ['23410.75', 0.38], 'US30' => ['44890.00', 0.11], 'GER40' => ['24120.00', -0.15],
        'UK100' => ['9180.00', 0.05], 'JPN225' => ['41250', 0.62], 'EURUSD' => ['1.16420', -0.08], 'GBPUSD' => ['1.34510', 0.04], 'USDJPY' => ['147.820', 0.19],
        'BTCUSDT' => ['112450.00', 1.24], 'ETHUSDT' => ['4310.50', 1.87],
    ];

    public static function configured(): bool
    {
        return secret_setting('market_data_api_key') !== '';
    }

    /** @return array{live: bool, quotes: array, error: ?string} */
    public static function quotes(): array
    {
        if (!self::configured()) {
            $q = [];
            foreach (self::DEMO as $s => [$p, $c]) {
                $q[] = ['symbol' => $s, 'name' => Instruments::get($s)['name'], 'price' => $p, 'change' => ($c > 0 ? '+' : '') . number_format($c, 2) . '%', 'up' => $c >= 0];
            }
            return ['live' => false, 'quotes' => $q, 'error' => null];
        }
        $cached = Database::one('SELECT payload FROM market_cache WHERE cache_key = :k AND expires_at > UTC_TIMESTAMP()', ['k' => 'quotes']);
        if ($cached) {
            return json_decode($cached['payload'], true);
        }
        $url = 'https://api.twelvedata.com/quote?' . http_build_query(['symbol' => implode(',', self::MAP), 'apikey' => secret_setting('market_data_api_key')]);
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
        $body = curl_exec($ch);
        curl_close($ch);
        $data = json_decode((string) $body, true);
        $out = ['live' => true, 'quotes' => [], 'error' => null];
        if (!is_array($data) || (($data['status'] ?? '') === 'error')) {
            Logger::error('Market data error: ' . mb_substr((string) ($data['message'] ?? 'no response'), 0, 200));
            $out['error'] = 'Market data is temporarily unavailable.';
        } else {
            foreach (self::MAP as $s => $td) {
                $q = $data[$td] ?? null;
                if (!$q || ($q['status'] ?? '') === 'error' || empty($q['close'])) {
                    $out['quotes'][] = ['symbol' => $s, 'name' => Instruments::get($s)['name'], 'price' => 'n/a', 'change' => 'unavailable', 'up' => true];
                    continue;
                }
                $pc = (float) ($q['percent_change'] ?? 0);
                $out['quotes'][] = ['symbol' => $s, 'name' => Instruments::get($s)['name'], 'price' => Instruments::format($s, $q['close']), 'change' => ($pc > 0 ? '+' : '') . number_format($pc, 2) . '%', 'up' => $pc >= 0];
            }
        }
        Database::query('INSERT INTO market_cache (cache_key, payload, expires_at) VALUES (:k, :p, UTC_TIMESTAMP() + INTERVAL 60 SECOND) ON DUPLICATE KEY UPDATE payload = VALUES(payload), expires_at = VALUES(expires_at)', ['k' => 'quotes', 'p' => json_encode($out)]);
        return $out;
    }

    /** 1-minute candles (UTC) for the runner auditor. Throws when unavailable — prices are never invented. */
    public static function candles(string $symbol, string $startUtc, string $endUtc): array
    {
        $td = self::MAP[$symbol] ?? null;
        if (!$td || !self::configured()) {
            throw new \RuntimeException('No market-data mapping for ' . $symbol . '.');
        }
        $url = 'https://api.twelvedata.com/time_series?' . http_build_query(['symbol' => $td, 'interval' => '1min', 'start_date' => $startUtc, 'end_date' => $endUtc, 'timezone' => 'UTC', 'order' => 'ASC', 'outputsize' => 5000, 'apikey' => secret_setting('market_data_api_key')]);
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15]);
        $j = json_decode((string) curl_exec($ch), true);
        curl_close($ch);
        if (!is_array($j) || ($j['status'] ?? '') === 'error' || empty($j['values'])) {
            throw new \RuntimeException('Price history is unavailable for this trade on the current market-data plan.');
        }
        return array_map(fn ($v) => ['t' => $v['datetime'], 'h' => (float) $v['high'], 'l' => (float) $v['low'], 'c' => (float) $v['close']], $j['values']);
    }

    /** Test call used by the Control Panel. */
    public static function test(string $key): array
    {
        $ch = curl_init('https://api.twelvedata.com/quote?symbol=EUR/USD&apikey=' . rawurlencode($key));
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
        $j = json_decode((string) curl_exec($ch), true);
        curl_close($ch);
        if (isset($j['close'])) {
            return [true, 'Connected to Twelve Data (EUR/USD ' . $j['close'] . ').'];
        }
        return [false, 'Twelve Data rejected the request: ' . mb_substr((string) ($j['message'] ?? 'no response'), 0, 160)];
    }
}
