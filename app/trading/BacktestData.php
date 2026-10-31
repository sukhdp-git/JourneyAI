<?php
declare(strict_types=1);

namespace App\Trading;

use App\Core\Database;
use App\Core\Logger;

/**
 * Price history for the backtester. Two sources, both stored as 15-minute (or slower) UTC candles in bt_bars:
 *  - Twelve Data (shared cache, one dataset per instrument, filled page by page so no request runs long), and
 *  - a member's own CSV export (MT5 / MT4 / TradingView / generic), private to that member.
 * Prices are never generated: when history is missing the backtest simply cannot run.
 */
final class BacktestData
{
    public const YEARS = 2;
    public const BASE = 900;            // 15-minute candles are the smallest stored timeframe
    private const PAGE = 5000;          // Twelve Data maximum per request
    public const MAX_UPLOADS = 5;
    public const MAX_ROWS = 400000;

    // ---------------------------------------------------------------- datasets

    /** Datasets a member can test on: Twelve Data instruments (cached or not yet fetched) + their own uploads. */
    public static function forMember(int $uid): array
    {
        $shared = [];
        $have = [];
        foreach (Database::all("SELECT * FROM bt_datasets WHERE user_id IS NULL AND source = 'twelvedata'") as $d) {
            $have[$d['symbol']] = $d;
        }
        if (MarketData::configured()) {
            foreach (array_keys(MarketData::MAP) as $s) {
                $shared[] = $have[$s] ?? ['id' => 0, 'symbol' => $s, 'name' => $s, 'source' => 'twelvedata', 'status' => 'empty', 'rows_count' => 0, 'first_ts' => null, 'last_ts' => null, 'tf' => self::BASE];
            }
        } else {
            $shared = array_values(array_filter($have, fn ($d) => $d['status'] === 'ready'));
        }
        $own = Database::all("SELECT * FROM bt_datasets WHERE user_id = :u ORDER BY id DESC", ['u' => $uid]);
        return ['shared' => $shared, 'own' => $own];
    }

    /** A dataset the member may use (shared or their own), or null. */
    public static function find(int $uid, int $id): ?array
    {
        return Database::one('SELECT * FROM bt_datasets WHERE id = :id AND (user_id IS NULL OR user_id = :u)', ['id' => $id, 'u' => $uid]);
    }

    /** Candles [ts, o, h, l, c] between two UTC timestamps, resampled to $tf seconds. */
    public static function bars(array $ds, int $tf, int $from, int $to): array
    {
        $rows = Database::pdo()->prepare('SELECT ts, o, h, l, c FROM bt_bars WHERE dataset_id = :d AND ts BETWEEN :a AND :b ORDER BY ts');
        $rows->execute(['d' => $ds['id'], 'a' => $from, 'b' => $to]);
        $bars = [];
        while ($r = $rows->fetch(\PDO::FETCH_NUM)) {
            $bars[] = [(int) $r[0], (float) $r[1], (float) $r[2], (float) $r[3], (float) $r[4]];
        }
        return $tf > (int) $ds['tf'] ? self::resample($bars, $tf) : $bars;
    }

    /** Aggregates ascending candles into $tf-second buckets aligned to UTC (days start at 00:00 UTC). */
    public static function resample(array $bars, int $tf): array
    {
        $out = [];
        $cur = null;
        foreach ($bars as [$t, $o, $h, $l, $c]) {
            $b = intdiv($t, $tf) * $tf;
            if ($cur && $cur[0] === $b) {
                $cur[2] = max($cur[2], $h);
                $cur[3] = min($cur[3], $l);
                $cur[4] = $c;
                continue;
            }
            if ($cur) {
                $out[] = $cur;
            }
            $cur = [$b, $o, $h, $l, $c];
        }
        if ($cur) {
            $out[] = $cur;
        }
        return $out;
    }

    private static function store(int $dsId, array $bars): void
    {
        foreach (array_chunk($bars, 1000) as $chunk) {
            $ph = [];
            $vals = [];
            foreach ($chunk as $k => [$t, $o, $h, $l, $c]) {
                $ph[] = "(:d$k, :t$k, :o$k, :h$k, :l$k, :c$k)";
                $vals += ["d$k" => $dsId, "t$k" => $t, "o$k" => $o, "h$k" => $h, "l$k" => $l, "c$k" => $c];
            }
            Database::query('INSERT INTO bt_bars (dataset_id, ts, o, h, l, c) VALUES ' . implode(',', $ph) . ' ON DUPLICATE KEY UPDATE o = VALUES(o), h = VALUES(h), l = VALUES(l), c = VALUES(c)', $vals);
        }
    }

    private static function refreshStats(int $dsId): array
    {
        $s = Database::one('SELECT COUNT(*) AS n, MIN(ts) AS a, MAX(ts) AS b FROM bt_bars WHERE dataset_id = :d', ['d' => $dsId]);
        Database::query('UPDATE bt_datasets SET rows_count = :n, first_ts = :a, last_ts = :b WHERE id = :d', ['n' => (int) $s['n'], 'a' => $s['a'], 'b' => $s['b'], 'd' => $dsId]);
        return $s;
    }

    public static function deleteOwn(int $uid, int $id): bool
    {
        $ds = Database::one('SELECT id FROM bt_datasets WHERE id = :id AND user_id = :u', ['id' => $id, 'u' => $uid]);
        if (!$ds) {
            return false;
        }
        Database::query('DELETE FROM bt_bars WHERE dataset_id = :d', ['d' => $id]);
        Database::query('DELETE FROM bt_datasets WHERE id = :d', ['d' => $id]);
        return true;
    }

    // ---------------------------------------------------------------- CSV upload

    /**
     * Parses a candle export. Recognises header names (date, time, datetime, timestamp, open, high, low, close; MT5's <DATE> style too)
     * or, without a header, "date[,time],open,high,low,close". Dates: 2024.01.02 / 2024-01-02 / 02/01/2024 (d/m/Y) / ISO / Unix seconds.
     * $offsetHours converts the file's clock (e.g. broker server time UTC+2) to UTC.
     * @return array{bars: array, skipped: int, error: ?string}
     */
    public static function parseCsv(string $text, float $offsetHours = 0.0): array
    {
        $text = preg_replace('/^\xEF\xBB\xBF/', '', $text);
        $lines = preg_split('/\r\n|\r|\n/', trim((string) $text));
        if (count($lines) < 3) {
            return ['bars' => [], 'skipped' => 0, 'error' => 'The file has no candle rows.'];
        }
        $first = $lines[0];
        $delim = "\t";
        foreach ([",", ";", "\t", "|"] as $dl) {
            if (substr_count($first, $dl) >= 4) {
                $delim = $dl;
                break;
            }
        }
        $head = array_map(fn ($h) => strtolower(trim($h, " \t<>\"'")), str_getcsv($first, $delim));
        $col = fn (array $names) => ($k = array_values(array_intersect($names, $head))) ? array_search($k[0], $head, true) : null;
        $hasHeader = $col(['open', 'o']) !== null;
        if ($hasHeader) {
            $map = ['date' => $col(['date', 'day']), 'time' => $col(['time']), 'dt' => $col(['datetime', 'timestamp', 'date_time', 'gmt time', 'time (utc)', 'local time', 'dt'])];
            if ($map['dt'] === null && $map['date'] === null && $map['time'] !== null) {
                $map['dt'] = $map['time'];
                $map['time'] = null;
            }
            $map += ['o' => $col(['open', 'o']), 'h' => $col(['high', 'h']), 'l' => $col(['low', 'l']), 'c' => $col(['close', 'c'])];
            array_shift($lines);
        } else {
            $cells = str_getcsv($first, $delim);
            $timeCol = isset($cells[1]) && preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', trim($cells[1]));
            $map = $timeCol ? ['date' => 0, 'time' => 1, 'dt' => null, 'o' => 2, 'h' => 3, 'l' => 4, 'c' => 5] : ['date' => null, 'time' => null, 'dt' => 0, 'o' => 1, 'h' => 2, 'l' => 3, 'c' => 4];
        }
        if (in_array(null, [$map['o'], $map['h'], $map['l'], $map['c']], true) || ($map['dt'] === null && $map['date'] === null)) {
            return ['bars' => [], 'skipped' => 0, 'error' => 'Could not find the date, open, high, low and close columns.'];
        }
        $bars = [];
        $skipped = 0;
        $shift = (int) round($offsetHours * 3600);
        foreach ($lines as $ln) {
            if (trim($ln) === '') {
                continue;
            }
            $c = str_getcsv($ln, $delim);
            $raw = $map['dt'] !== null ? ($c[$map['dt']] ?? '') : trim(($c[$map['date']] ?? '') . ' ' . ($map['time'] !== null ? ($c[$map['time']] ?? '') : ''));
            $ts = self::parseTime(trim($raw));
            $v = [];
            foreach (['o', 'h', 'l', 'c'] as $k) {
                $x = str_replace([' ', "'"], '', (string) ($c[$map[$k]] ?? ''));
                if ($delim === ';' && str_contains($x, ',') && !str_contains($x, '.')) {
                    $x = str_replace(',', '.', $x);       // European decimal comma
                }
                $v[$k] = is_numeric($x) ? (float) $x : null;
            }
            if ($ts === null || in_array(null, $v, true) || $v['h'] < max($v['o'], $v['c']) - 1e-9 || $v['l'] > min($v['o'], $v['c']) + 1e-9 || $v['l'] <= 0) {
                $skipped++;
                continue;
            }
            $bars[$ts - $shift] = [$ts - $shift, $v['o'], $v['h'], $v['l'], $v['c']];
        }
        ksort($bars);
        return ['bars' => array_values($bars), 'skipped' => $skipped, 'error' => $bars ? null : 'No valid candle rows were found.'];
    }

    public static function parseTime(string $s): ?int
    {
        if ($s === '') {
            return null;
        }
        if (preg_match('/^\d{9,13}$/', $s)) {
            $n = (int) $s;
            return $n > 20000000000 ? intdiv($n, 1000) : $n;   // milliseconds → seconds
        }
        $s = preg_replace('/^(\d{4})\.(\d{2})\.(\d{2})/', '$1-$2-$3', $s);
        if (preg_match('#^(\d{1,2})[/.](\d{1,2})[/.](\d{4})(.*)$#', $s, $m)) {
            $s = sprintf('%04d-%02d-%02d%s', $m[3], $m[2], $m[1], $m[4]);   // day/month/year (MT4, Dukascopy, most non-US exports)
        }
        try {
            $d = new \DateTimeImmutable($s, new \DateTimeZone('UTC'));
            return $d->getTimestamp();
        } catch (\Throwable) {
            return null;
        }
    }

    /** Most common gap between candles, in seconds. */
    public static function detectTf(array $bars): int
    {
        $gaps = [];
        for ($i = 1, $n = min(count($bars), 3000); $i < $n; $i++) {
            $g = $bars[$i][0] - $bars[$i - 1][0];
            if ($g > 0) {
                $gaps[$g] = ($gaps[$g] ?? 0) + 1;
            }
        }
        arsort($gaps);
        return (int) (array_key_first($gaps) ?? 0);
    }

    /** Saves an upload as the member's private dataset. Candles faster than 15 min are combined into 15-min candles. */
    public static function saveUpload(int $uid, string $name, string $symbol, array $bars): array
    {
        if ((int) Database::value('SELECT COUNT(*) FROM bt_datasets WHERE user_id = :u', ['u' => $uid]) >= self::MAX_UPLOADS) {
            return [null, 'You can keep ' . self::MAX_UPLOADS . ' uploaded datasets. Delete one first.'];
        }
        $tf = self::detectTf($bars);
        if ($tf <= 0) {
            return [null, 'Could not work out the candle timeframe.'];
        }
        if ($tf < self::BASE) {
            $bars = self::resample($bars, self::BASE);
            $tf = self::BASE;
        }
        if (!isset(array_flip(Backtest::TIMEFRAMES)[$tf])) {
            return [null, 'Use 15-minute, 30-minute, 1-hour, 4-hour or daily candles (this file looks like ' . round($tf / 60) . '-minute candles).'];
        }
        if (count($bars) > self::MAX_ROWS) {
            $bars = array_slice($bars, -self::MAX_ROWS);
        }
        Database::query('INSERT INTO bt_datasets (user_id, source, symbol, name, tf, status) VALUES (:u, :s, :sym, :n, :tf, :st)',
            ['u' => $uid, 's' => 'upload', 'sym' => $symbol, 'n' => $name, 'tf' => $tf, 'st' => 'ready']);
        $id = (int) Database::pdo()->lastInsertId();
        self::store($id, $bars);
        self::refreshStats($id);
        return [$id, null];
    }

    // ---------------------------------------------------------------- Twelve Data (shared cache)

    /**
     * Fetches ONE page (up to 5,000 fifteen-minute candles) for an instrument and reports progress. The browser calls this
     * repeatedly, so no single request runs long on shared hosting. Pages go backwards from now until 2 years are stored;
     * a dataset that is already complete only fetches the candles since its last update.
     * @return array{ok: bool, done: bool, progress: int, rows: int, wait?: int, error?: string, id?: int}
     */
    public static function fetchStep(string $symbol): array
    {
        $td = MarketData::symbolFor($symbol);
        if (!$td || !MarketData::configured()) {
            return ['ok' => false, 'done' => false, 'progress' => 0, 'rows' => 0, 'error' => 'Market data is not connected for ' . $symbol . '. Upload a CSV instead.'];
        }
        Database::query("INSERT IGNORE INTO bt_datasets (user_id, shared_key, source, symbol, name, tf, status) VALUES (NULL, :k, 'twelvedata', :s, :n, :tf, 'fetching')", ['k' => 'td:' . $symbol, 's' => $symbol, 'n' => $symbol, 'tf' => self::BASE]);
        $ds = Database::one('SELECT * FROM bt_datasets WHERE shared_key = :k', ['k' => 'td:' . $symbol]);
        $now = time();
        $want = $now - self::YEARS * 365 * 86400 - 7 * 86400;
        $backfilling = $ds['status'] !== 'ready';
        $params = ['symbol' => $td, 'interval' => '15min', 'timezone' => 'UTC', 'outputsize' => self::PAGE, 'apikey' => secret_setting('market_data_api_key')];
        if ($backfilling) {
            $params['order'] = 'DESC';
            if ($ds['cursor_ts']) {
                $params['end_date'] = gmdate('Y-m-d H:i:s', (int) $ds['cursor_ts']);
            }
        } else {
            if ($now - (int) $ds['last_ts'] < 3600) {
                return ['ok' => true, 'done' => true, 'progress' => 100, 'rows' => (int) $ds['rows_count'], 'id' => (int) $ds['id']];
            }
            $params['order'] = 'ASC';
            $params['start_date'] = gmdate('Y-m-d H:i:s', (int) $ds['last_ts']);
        }
        $j = self::http('https://api.twelvedata.com/time_series?' . http_build_query($params));
        if (($j['code'] ?? 0) == 429 || stripos((string) ($j['message'] ?? ''), 'credits') !== false) {
            return ['ok' => true, 'done' => false, 'wait' => 62, 'progress' => self::progress($ds, $want), 'rows' => (int) $ds['rows_count'], 'id' => (int) $ds['id']];
        }
        $vals = $j['values'] ?? [];
        if (($j['status'] ?? '') === 'error' && !str_contains(strtolower((string) ($j['message'] ?? '')), 'no data')) {
            Logger::error('Backtest data fetch failed', ['symbol' => $symbol, 'message' => mb_substr((string) ($j['message'] ?? 'no response'), 0, 200)]);
            return ['ok' => false, 'done' => false, 'progress' => self::progress($ds, $want), 'rows' => (int) $ds['rows_count'], 'error' => 'The market-data provider did not return history for ' . $symbol . ' (' . mb_substr((string) ($j['message'] ?? 'no response'), 0, 120) . ').'];
        }
        $bars = [];
        foreach ($vals as $v) {
            $t = self::parseTime((string) $v['datetime']);
            if ($t !== null && is_numeric($v['open'] ?? null)) {
                $bars[] = [$t, (float) $v['open'], (float) $v['high'], (float) $v['low'], (float) $v['close']];
            }
        }
        if ($bars) {
            self::store((int) $ds['id'], $bars);
        }
        $s = self::refreshStats((int) $ds['id']);
        $oldest = $bars ? min(array_column($bars, 0)) : null;
        if ($backfilling) {
            $done = !$bars || $oldest <= $want || count($vals) < self::PAGE;
            Database::query('UPDATE bt_datasets SET cursor_ts = :c, status = :st, fetched_at = UTC_TIMESTAMP() WHERE id = :d',
                ['c' => $oldest !== null ? $oldest - 1 : $ds['cursor_ts'], 'st' => $done ? 'ready' : 'fetching', 'd' => $ds['id']]);
            if ($done) {
                Database::query('DELETE FROM bt_bars WHERE dataset_id = :d AND ts < :w', ['d' => $ds['id'], 'w' => $want]);
                $s = self::refreshStats((int) $ds['id']);
            }
            $ds = array_merge($ds, ['first_ts' => $s['a'], 'last_ts' => $s['b']]);
            return ['ok' => true, 'done' => $done, 'progress' => $done ? 100 : self::progress($ds, $want), 'rows' => (int) $s['n'], 'id' => (int) $ds['id']];
        }
        Database::query('UPDATE bt_datasets SET fetched_at = UTC_TIMESTAMP() WHERE id = :d', ['d' => $ds['id']]);
        $more = count($vals) >= self::PAGE;
        return ['ok' => true, 'done' => !$more, 'progress' => $more ? 90 : 100, 'rows' => (int) $s['n'], 'id' => (int) $ds['id']];
    }

    private static function progress(array $ds, int $want): int
    {
        if (!$ds['first_ts'] || !$ds['last_ts']) {
            return 2;
        }
        return (int) max(2, min(99, round(((int) $ds['last_ts'] - (int) $ds['first_ts']) / max(1, (int) $ds['last_ts'] - $want) * 100)));
    }

    private static function http(string $url): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 25, CURLOPT_CONNECTTIMEOUT => 8]);
        $body = curl_exec($ch);
        curl_close($ch);
        $j = json_decode((string) $body, true);
        return is_array($j) ? $j : ['status' => 'error', 'message' => 'no response'];
    }
}
