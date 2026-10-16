<?php
declare(strict_types=1);

namespace App\Trading;

use App\Core\Database;
use App\Core\Logger;

/**
 * Quick Log: a four-step trade logger (instrument → buy/sell → lot size → prices) with favourite instruments,
 * a remembered lot size per instrument and a reference price for the price wheel.
 *
 * Reference prices: the TradingView widgets on the site run inside TradingView's own frames, so the site cannot
 * read prices from them (browser security). The wheel is therefore centred on, in order:
 *   1. a live quote from Twelve Data when the owner has added a key (Control Panel → Integrations), cached 15 s;
 *   2. otherwise the member's own last logged price for that instrument, clearly labelled as such;
 *   3. otherwise nothing — the member types a price and the wheel starts from it. No price is ever invented.
 */
final class QuickLog
{
    public const DEFAULT_SYMBOLS = ['XAUUSD', 'EURUSD', 'GBPUSD', 'NAS100', 'US30'];
    public const MAX_SYMBOLS = 8;

    /** @return array{symbols: string[], lots: array<string, float>} */
    public static function prefs(int $uid, int $accountId): array
    {
        $raw = (string) Database::value('SELECT quick_prefs FROM user_settings WHERE user_id = :u', ['u' => $uid]);
        $p = json_decode($raw, true);
        $p = is_array($p) ? $p : [];
        $all = Instruments::all();
        $symbols = array_values(array_filter((array) ($p['symbols'] ?? []), fn ($s) => is_string($s) && isset($all[$s])));
        if (!$symbols) {
            // First use: the instruments this member trades most in this account, topped up with common ones.
            $mine = array_column(Database::all('SELECT symbol, COUNT(*) n FROM trades WHERE user_id = :u AND account_id = :a GROUP BY symbol ORDER BY n DESC LIMIT 5', ['u' => $uid, 'a' => $accountId]), 'symbol');
            $symbols = array_slice(array_values(array_unique(array_merge(array_filter($mine, fn ($s) => isset($all[$s])), self::DEFAULT_SYMBOLS))), 0, 5);
        }
        $lots = [];
        foreach ((array) ($p['lots'] ?? []) as $s => $l) {
            if (isset($all[$s]) && is_numeric($l) && (float) $l > 0) {
                $lots[$s] = (float) $l;
            }
        }
        return ['symbols' => array_slice($symbols, 0, self::MAX_SYMBOLS), 'lots' => $lots];
    }

    /** Saves the favourite list (validated against the instrument list) and/or a lot size for one symbol. */
    public static function savePrefs(int $uid, int $accountId, ?array $symbols = null, ?string $lotSymbol = null, ?float $lots = null): array
    {
        $p = self::prefs($uid, $accountId);
        $all = Instruments::all();
        if ($symbols !== null) {
            $clean = [];
            foreach ($symbols as $s) {
                $s = strtoupper(trim((string) $s));
                if (isset($all[$s]) && !in_array($s, $clean, true)) {
                    $clean[] = $s;
                }
            }
            if ($clean) {
                $p['symbols'] = array_slice($clean, 0, self::MAX_SYMBOLS);
            }
        }
        if ($lotSymbol !== null && isset($all[$lotSymbol]) && $lots !== null && $lots > 0 && $lots <= 1000000) {
            $p['lots'][$lotSymbol] = $lots;
        }
        Database::query('UPDATE user_settings SET quick_prefs = :p WHERE user_id = :u', ['p' => json_encode($p), 'u' => $uid]);
        return $p;
    }

    /** A comfortable wheel step: 1 pip for forex, otherwise ~1/4000 of the price rounded to 1-2-5 (gold ≈ 1, NAS100 ≈ 5). */
    public static function step(string $symbol, ?float $price): float
    {
        $i = Instruments::get($symbol);
        $tick = max((float) ($i['tick'] ?? 0.01), 1e-8);
        if (($i['class'] ?? '') === 'FOREX') {
            return max((float) $i['pip'], $tick);
        }
        if (!$price || $price <= 0) {
            return max((float) ($i['pip'] ?? $tick), $tick);
        }
        $raw = $price / 4000;
        $mag = 10 ** floor(log10($raw));
        $n = $raw / $mag;
        $nice = ($n < 1.5 ? 1 : ($n < 3.5 ? 2 : ($n < 7.5 ? 5 : 10))) * $mag;
        return max(round($nice / $tick) * $tick, $tick);
    }

    /** @return array{price: ?float, source: string, label: string} */
    public static function reference(int $uid, int $accountId, string $symbol): array
    {
        $live = self::livePrice($symbol);
        if ($live !== null) {
            return ['price' => $live, 'source' => 'live', 'label' => 'Live price (Twelve Data) · ' . gmdate('H:i:s') . ' UTC'];
        }
        $last = Database::one("SELECT COALESCE(exit_price, entry_price) price, executed_at FROM trades WHERE user_id = :u AND symbol = :s AND source <> 'DEMO' ORDER BY executed_at DESC LIMIT 1", ['u' => $uid, 's' => $symbol]);
        if ($last && (float) $last['price'] > 0) {
            return ['price' => (float) $last['price'], 'source' => 'last', 'label' => 'Your last logged ' . $symbol . ' price (' . fmt_date($last['executed_at'], 'M j') . ') — not a live quote'];
        }
        return ['price' => null, 'source' => 'none', 'label' => 'No live price available — type a price and the wheel starts from it'];
    }

    /** Live quote from Twelve Data (only when a key is configured), cached 15 seconds per symbol. */
    private static function livePrice(string $symbol): ?float
    {
        $td = MarketData::symbolFor($symbol);
        if ($td === null || !MarketData::configured()) {
            return null;
        }
        $key = 'px:' . $symbol;
        $cached = Database::one('SELECT payload FROM market_cache WHERE cache_key = :k AND expires_at > UTC_TIMESTAMP()', ['k' => $key]);
        if ($cached) {
            $v = json_decode($cached['payload'], true);
            return is_numeric($v['price'] ?? null) ? (float) $v['price'] : null;
        }
        $ch = curl_init('https://api.twelvedata.com/price?' . http_build_query(['symbol' => $td, 'apikey' => secret_setting('market_data_api_key')]));
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 4, CURLOPT_TIMEOUT => 6]);
        $j = json_decode((string) curl_exec($ch), true);
        curl_close($ch);
        $price = is_array($j) && is_numeric($j['price'] ?? null) && (float) $j['price'] > 0 ? (float) $j['price'] : null;
        if ($price === null) {
            Logger::error('Quick Log price unavailable', ['symbol' => $symbol, 'detail' => mb_substr((string) ($j['message'] ?? 'no response'), 0, 160)]);
            return null;
        }
        Database::query('INSERT INTO market_cache (cache_key, payload, expires_at) VALUES (:k, :p, UTC_TIMESTAMP() + INTERVAL 15 SECOND) ON DUPLICATE KEY UPDATE payload = VALUES(payload), expires_at = VALUES(expires_at)', ['k' => $key, 'p' => json_encode(['price' => $price])]);
        return $price;
    }

    /**
     * Trades that still need their context (strategy/setup, emotion or notes) after a fast log. A trade leaves the
     * list as soon as any of these is filled in on its edit page.
     */
    public const NEEDS_DETAILS_SQL = "t.source IN ('QUICK_LOG', 'QUICK_COMMAND', 'VOICE') AND t.strategy_id IS NULL AND (t.setup_tag IS NULL OR t.setup_tag = '') AND (t.emotion IS NULL OR t.emotion = '') AND (t.notes IS NULL OR t.notes = '' OR t.notes LIKE 'Quick command:%' OR t.notes LIKE 'Voice log:%')";

    public static function needsDetailsCount(int $uid, int $accountId): int
    {
        return (int) Database::value('SELECT COUNT(*) FROM trades t WHERE t.user_id = :u AND t.account_id = :a AND ' . self::NEEDS_DETAILS_SQL, ['u' => $uid, 'a' => $accountId]);
    }
}
