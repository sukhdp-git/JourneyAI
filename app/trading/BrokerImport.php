<?php
declare(strict_types=1);

namespace App\Trading;

/**
 * CSV import from any broker. Two steps:
 *   1. detect(): finds the header row (skipping report titles), the delimiter and a suggested column mapping, and
 *      decides whether the file lists closed trades (one row per trade) or executions/fills (one row per buy or sell).
 *   2. parse(): applies the mapping the member confirmed and returns normalised trades for Ingest::insert().
 * Execution exports (Zerodha/Upstox tradebooks, Binance/Bybit trade history, IBKR, TradingView order history…) are
 * paired first-in-first-out per instrument into round-trip trades; positions still open at the end are imported as
 * OPEN trades. Nothing is guessed silently: every rejected row is reported with its line number and reason.
 */
final class BrokerImport
{
    /** field => [label, required in "trades" mode, required in "fills" mode] */
    public const FIELDS = [
        'symbol' => ['Instrument / symbol', true, true],
        'side' => ['Buy / sell (direction)', true, false],
        'open' => ['Open date & time (fills: execution time)', true, true],
        'open_time' => ['Open time, if in its own column', false, false],
        'close' => ['Close date & time', false, false],
        'close_time' => ['Close time, if in its own column', false, false],
        'entry' => ['Entry / open price (fills: fill price)', true, true],
        'exit' => ['Exit / close price', false, false],
        'lots' => ['Size (lots, units or quantity)', true, true],
        'sl' => ['Stop loss', false, false],
        'tp' => ['Take profit', false, false],
        'pnl' => ['Profit / P&L', false, false],
        'commission' => ['Commission', false, false],
        'swap' => ['Swap / rollover', false, false],
        'fees' => ['Other fees', false, false],
        'id' => ['Ticket / trade ID', false, false],
        'status' => ['Order status (to skip cancelled rows)', false, false],
    ];

    private const ALIASES = [
        'id' => ['ticket', 'position', 'position id', 'order', 'order id', 'deal', 'trade id', 'id', 'trade #', 'trade number', 'trade no', 'order no', 'execution id', 'exec id', 'transaction id'],
        'symbol' => ['symbol', 'instrument', 'item', 'market', 'pair', 'contract', 'ticker', 'asset', 'security', 'scrip', 'tradingsymbol', 'trading symbol', 'coin', 'product', 'underlying', 'name'],
        'side' => ['type', 'side', 'direction', 'action', 'market pos.', 'market pos', 'buy/sell', 'b/s', 'order side', 'trade type', 'transaction type', 'buy sell', 'deal type', 'position side', 'opening direction', 'order type'],
        'open' => ['open time', 'opentime', 'entry time', 'time', 'open date', 'date', 'opening time', 'open date/time', 'date/time', 'datetime', 'date time', 'trade date', 'trade time', 'execution time', 'exec time', 'filled time', 'fill time', 'time(utc)', 'date(utc)', 'created time', 'order execution time', 'executed at', 'entry date', 'timestamp', 'time opened', 'opened'],
        'close' => ['close time', 'closetime', 'exit time', 'closing time', 'close date', 'exit date', 'time closed', 'closed', 'close date/time'],
        'entry' => ['open price', 'entry price', 'price', 'entry', 'opening price', 'avg price', 'average price', 'fill price', 'avg. price', 'executed price', 'trade price', 'avg fill price', 'average fill price', 'avg entry price', 'price open', 't. price', 'tradeprice', 'exec price', 'open'],
        'exit' => ['close price', 'exit price', 'exit', 'closing price', 'avg exit price', 'price close', 'close'],
        'sl' => ['s / l', 's/l', 'sl', 'stop loss', 'stoploss', 'stop'],
        'tp' => ['t / p', 't/p', 'tp', 'take profit', 'takeprofit', 'target', 'limit'],
        'lots' => ['volume', 'lots', 'size', 'quantity', 'qty', 'lot', 'filled qty', 'executed qty', 'filled', 'executed', 'closing quantity', 'amount', 'contracts', 'shares', 'units', 'lot size', 'position size', 'qty.', 'filled quantity', 'opening quantity'],
        'pnl' => ['profit', 'pnl', 'p&l', 'net profit', 'realized pnl', 'net p/l', 'profit/loss', 'realised pnl', 'realized p&l', 'realised p&l', 'gross p/l', 'net pnl', 'closed p&l', 'closed pnl', 'p/l', 'net p&l', 'profit (usd)', 'net usd', 'net eur', 'net gbp', 'net', 'result', 'gain'],
        'fees' => ['fees', 'fee', 'charges', 'total charges', 'trading fee'],
        'commission' => ['commission', 'commissions', 'comm', 'comm/fee', 'commission/fee', 'ibcommission'],
        'swap' => ['swap', 'swaps', 'rollover', 'financing', 'overnight fee'],
        'status' => ['status', 'state', 'order status'],
    ];

    private static function norm(string $h): string
    {
        $h = strtolower(trim($h, " \t\"'\xEF\xBB\xBF"));
        return trim(preg_replace('/\s+/', ' ', $h) ?? $h);
    }

    private static function known(): array
    {
        static $k = null;
        return $k ??= array_merge(...array_values(self::ALIASES));
    }

    /** Splits the file into lines and finds the delimiter and the header row (the row that matches the most column names). */
    private static function layout(string $content): array
    {
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;
        $lines = preg_split('/\r\n|\n|\r/', $content) ?: [];
        $known = self::known();
        $best = [-1, ',', 0];
        foreach (array_slice($lines, 0, 40) as $i => $line) {
            if (trim($line) === '') {
                continue;
            }
            foreach ([',', ';', "\t", '|'] as $dl) {
                $cells = array_map([self::class, 'norm'], str_getcsv($line, $dl, '"', ''));
                if (count($cells) < 3) {
                    continue;
                }
                $score = count(array_filter($cells, fn ($c) => $c !== '' && (in_array($c, $known, true) || in_array(preg_replace('/\s*\(.*\)$/', '', $c), $known, true))));
                if ($score > $best[2]) {
                    $best = [$i, $dl, $score];
                }
            }
        }
        return ['lines' => $lines, 'header_line' => $best[0], 'delim' => $best[1], 'score' => $best[2]];
    }

    /** @return array{ok: bool, error?: string, headers?: array, mapping?: array, mode?: string, date_order?: string, sample?: array, rows?: int, delim?: string, header_line?: int} */
    public static function detect(string $content): array
    {
        $L = self::layout($content);
        if ($L['header_line'] < 0 || $L['score'] < 2) {
            return ['ok' => false, 'error' => 'No header row was found. The file needs a row of column names (for example Symbol, Type, Time, Price, Volume, Profit).'];
        }
        $headers = str_getcsv($L['lines'][$L['header_line']], $L['delim'], '"', '');
        $norm = array_map(fn ($h) => preg_replace('/\s*\(.*\)$/', '', self::norm((string) $h)) ?? '', $headers);
        $map = array_fill_keys(array_keys(self::FIELDS), null);
        $taken = [];
        // Exact matches first, in a fixed priority order so "price" becomes the entry before it can become the exit.
        foreach (['id', 'symbol', 'side', 'close', 'open', 'exit', 'entry', 'sl', 'tp', 'lots', 'pnl', 'commission', 'swap', 'fees', 'status'] as $f) {
            foreach (self::ALIASES[$f] as $alias) {
                foreach ($norm as $i => $h) {
                    if (!isset($taken[$i]) && $h === $alias) {
                        $map[$f] = $i;
                        $taken[$i] = true;
                        break 2;
                    }
                }
            }
        }
        // MT4/MT5 "Positions" reports repeat "Time" and "Price": the second occurrence is the close.
        foreach ([['time', 'close'], ['price', 'exit']] as [$name, $f]) {
            $idx = array_keys($norm, $name, true);
            if (count($idx) >= 2 && $map[$f] === null) {
                $map[$f] = $idx[1];
                $taken[$idx[1]] = true;
            }
        }
        // "Open Date" + "Open Time": the date column is the open, the time column completes it.
        if ($map['open'] !== null && !preg_match('/date/', $norm[$map['open']])) {
            foreach ($norm as $i => $h) {
                if (!isset($taken[$i]) && preg_match('/^(open|opening|entry|trade|execution)? ?date$/', $h)) {
                    $map['open_time'] = $map['open'];
                    $map['open'] = $i;
                    $taken[$i] = true;
                    break;
                }
            }
        }
        if ($map['close'] !== null && !preg_match('/date/', $norm[$map['close']])) {
            foreach ($norm as $i => $h) {
                if (!isset($taken[$i]) && preg_match('/^(close|closing|exit) date$/', $h)) {
                    $map['close_time'] = $map['close'];
                    $map['close'] = $i;
                    $taken[$i] = true;
                    break;
                }
            }
        }
        // A separate "Time" column next to a "Date" column (e.g. "Date", "Time" or "Open Date", "Open Time").
        if ($map['open'] !== null && $map['open_time'] === null && preg_match('/date/', $norm[$map['open']]) && !preg_match('/time/', $norm[$map['open']])) {
            foreach ($norm as $i => $h) {
                if (!isset($taken[$i]) && preg_match('/^(open )?time$|^(entry|trade|execution) time$/', $h)) {
                    $map['open_time'] = $i;
                    $taken[$i] = true;
                    break;
                }
            }
        }
        $rows = self::rows($L);
        $sample = array_slice($rows, 0, 6);
        $mode = $map['exit'] === null && $map['pnl'] === null && $map['close'] === null ? 'fills' : 'trades';
        $dates = $map['open'] !== null ? array_map(fn ($r) => (string) ($r[1][$map['open']] ?? ''), array_slice($rows, 0, 200)) : [];
        return ['ok' => true, 'headers' => array_map(fn ($h) => mb_substr(trim((string) $h), 0, 60), $headers), 'mapping' => $map, 'mode' => $mode,
            'date_order' => self::guessOrder($dates), 'sample' => array_map(fn ($r) => $r[1], $sample), 'rows' => count($rows), 'delim' => $L['delim'], 'header_line' => $L['header_line']];
    }

    /** @return array<int, array{0: int, 1: array}> [line number, cells] for every non-empty row after the header */
    private static function rows(array $L): array
    {
        $out = [];
        $n = count($L['lines']);
        $width = count(str_getcsv($L['lines'][$L['header_line']], $L['delim'], '"', ''));
        for ($i = $L['header_line'] + 1; $i < $n && count($out) < 20000; $i++) {
            $line = $L['lines'][$i];
            if (trim($line, " \t,;|\"") === '') {
                continue;
            }
            $cells = str_getcsv($line, $L['delim'], '"', '');
            if (!preg_match('/\d/', $line) && count(array_filter($cells, fn ($c) => in_array(self::norm((string) $c), self::known(), true))) >= 3) {
                break; // the next report section starts (e.g. "Orders" after "Positions")
            }
            if (count($cells) < max(2, (int) floor($width / 2))) {
                continue; // section titles and totals lines in statement exports
            }
            $out[] = [$i + 1, array_map(fn ($c) => trim((string) $c), $cells)];
        }
        return $out;
    }

    /** dmy / mdy / ymd from sample values: a first part above 12 means day-first, a second part above 12 means month-first. */
    public static function guessOrder(array $values): string
    {
        $dmy = $mdy = $dots = 0;
        foreach ($values as $v) {
            if (preg_match('/^\s*(\d{4})[.\-\/]\d{1,2}[.\-\/]\d{1,2}/', $v)) {
                return 'ymd';
            }
            if (preg_match('/^\s*(\d{1,2})([.\-\/])(\d{1,2})\2\d{2,4}/', $v, $m)) {
                $dots += $m[2] === '.' ? 1 : 0;
                if ((int) $m[1] > 12) {
                    $dmy++;
                } elseif ((int) $m[3] > 12) {
                    $mdy++;
                }
            }
        }
        return $dmy > $mdy ? 'dmy' : ($mdy > $dmy ? 'mdy' : ($dots ? 'dmy' : 'mdy'));
    }

    /** Numbers like "1,234.56", "1.234,56", "(12.50)", "−7", "$1,000", "12.5 USD". */
    public static function num(?string $v): ?float
    {
        $s = trim((string) $v);
        if ($s === '' || $s === '-' || $s === '—') {
            return null;
        }
        $s = str_replace(['−', '–', "\xC2\xA0", ' ', "'"], ['-', '-', '', '', ''], $s);
        if (preg_match('/^[-+]?\d+(\.\d+)?[eE][-+]?\d+$/', $s)) {
            return (float) $s; // scientific notation from spreadsheets
        }
        $neg = (bool) preg_match('/^\(.*\)$/', $s);
        $s = preg_replace('/[^\d.,\-+]/', '', $s) ?? ''; // drops currency/asset codes: "$1,000", "0.5BTC", "12.5 USD"
        if (str_contains($s, ',') && str_contains($s, '.')) {
            $s = strrpos($s, ',') > strrpos($s, '.') ? str_replace(['.', ','], ['', '.'], $s) : str_replace(',', '', $s);
        } elseif (str_contains($s, ',')) {
            $s = preg_match('/,\d{3}(,\d{3})*$/', $s) && !preg_match('/^-?0,/', $s) ? str_replace(',', '', $s) : str_replace(',', '.', $s);
        }
        if (!is_numeric($s)) {
            return null;
        }
        return $neg ? -abs((float) $s) : (float) $s;
    }

    /** Broker date/time (in the broker's timezone, offset minutes from UTC) → UTC 'Y-m-d H:i:s'. */
    public static function date(?string $d, ?string $t, string $order, int $offsetMin): ?string
    {
        $s = trim((string) $d . ' ' . (string) $t);
        if ($s === '') {
            return null;
        }
        if (preg_match('/^\d{10}(\d{3})?(\.\d+)?$/', trim((string) $d))) {
            $n = (float) trim((string) $d);
            return gmdate('Y-m-d H:i:s', (int) ($n > 1e11 ? $n / 1000 : $n));
        }
        $s = preg_replace('/\s+(UTC|GMT)$/i', '', str_replace('T', ' ', $s)) ?? $s;
        $tz = null;
        if (preg_match('/(Z|[+-]\d{2}:?\d{2})$/', $s, $z)) {
            $tz = $z[1];
            $s = trim(substr($s, 0, -strlen($z[1])));
        }
        $y = $mo = $da = null;
        if (preg_match('/^(\d{4})[.\-\/](\d{1,2})[.\-\/](\d{1,2})(.*)$/', $s, $m)) {
            [$y, $mo, $da, $rest] = [(int) $m[1], (int) $m[2], (int) $m[3], $m[4]];
        } elseif (preg_match('/^(\d{1,2})[.\-\/](\d{1,2})[.\-\/](\d{2,4})(.*)$/', $s, $m)) {
            $y = (int) $m[3] < 100 ? 2000 + (int) $m[3] : (int) $m[3];
            [$mo, $da] = $order === 'dmy' ? [(int) $m[2], (int) $m[1]] : [(int) $m[1], (int) $m[2]];
            $rest = $m[4];
        } else {
            if (!preg_match('/\d{4}|[a-z]{3}/i', $s)) {
                return null; // a time without a date (e.g. the wrong column) is never "today"
            }
            $ts = strtotime($s . ($tz ?? ' UTC'));
            return $ts === false ? null : gmdate('Y-m-d H:i:s', $ts - ($tz ? 0 : $offsetMin * 60));
        }
        if (!checkdate($mo, $da, $y)) {
            return null;
        }
        $h = $i = $sec = 0;
        if (preg_match('/(\d{1,2}):(\d{2})(?::(\d{2}))?(?:[.,]\d+)?\s*([ap]\.?m\.?)?/i', (string) $rest, $tm)) {
            [$h, $i, $sec] = [(int) $tm[1], (int) $tm[2], (int) ($tm[3] ?? 0)];
            $ap = strtolower(str_replace('.', '', $tm[4] ?? ''));
            if ($ap === 'pm' && $h < 12) {
                $h += 12;
            } elseif ($ap === 'am' && $h === 12) {
                $h = 0;
            }
            if ($h > 23 || $i > 59 || $sec > 59) {
                return null;
            }
        }
        $ts = gmmktime($h, $i, $sec, $mo, $da, $y);
        if ($tz !== null) {
            $off = $tz === 'Z' ? 0 : ((int) substr($tz, 1, 2) * 60 + (int) substr($tz, -2)) * ($tz[0] === '-' ? -1 : 1);
            return gmdate('Y-m-d H:i:s', $ts - $off * 60);
        }
        return gmdate('Y-m-d H:i:s', $ts - $offsetMin * 60);
    }

    private static function side(string $raw, ?float $qty): ?string
    {
        $r = strtolower(trim($raw));
        if ($r === '' && $qty !== null && $qty != 0.0) {
            return $qty > 0 ? 'LONG' : 'SHORT'; // signed quantity exports (e.g. IBKR)
        }
        if (preg_match('/\b(buy|long|bot|bought|b)\b/', $r)) {
            return 'LONG';
        }
        if (preg_match('/\b(sell|short|sld|sold|s)\b/', $r)) {
            return 'SHORT';
        }
        return null;
    }

    /**
     * @param array $map  field => column index (int) or null
     * @param array $opt  mode (trades|fills), date_order (dmy|mdy|ymd), offset (minutes), size_unit (auto|lots|units)
     * @return array{trades: array, errors: array, skipped: int, open: int}
     */
    public static function parse(string $content, array $map, array $opt): array
    {
        $L = self::layout($content);
        $out = ['trades' => [], 'errors' => [], 'skipped' => 0, 'open' => 0];
        if ($L['header_line'] < 0) {
            $out['errors'][] = ['file', 'No header row was found.'];
            return $out;
        }
        $mode = ($opt['mode'] ?? 'trades') === 'fills' ? 'fills' : 'trades';
        foreach (self::FIELDS as $f => [$label, $reqT, $reqF]) {
            if (($mode === 'fills' ? $reqF : $reqT) && !is_int($map[$f] ?? null)) {
                $out['errors'][] = ['mapping', 'Choose the column for “' . $label . '”.'];
            }
        }
        if ($out['errors']) {
            return $out;
        }
        $order = in_array($opt['date_order'] ?? '', ['dmy', 'mdy', 'ymd'], true) ? $opt['date_order'] : 'mdy';
        $offset = max(-720, min(840, (int) ($opt['offset'] ?? 0)));
        $unit = in_array($opt['size_unit'] ?? '', ['lots', 'units'], true) ? $opt['size_unit'] : 'auto';
        $fills = [];
        foreach (self::rows($L) as [$line, $r]) {
            $get = fn (string $k) => is_int($map[$k] ?? null) ? trim((string) ($r[$map[$k]] ?? '')) : '';
            $ref = 'line ' . $line;
            $status = strtolower($get('status'));
            $sideRaw = strtolower($get('side'));
            if ($status !== '' && preg_match('/cancel|reject|expire|pending|open order|working/', $status)) {
                $out['skipped']++;
                continue;
            }
            if (preg_match('/balance|credit|deposit|withdraw|transfer|dividend|interest|funding|bonus/', $sideRaw . ' ' . strtolower($get('symbol')))) {
                $out['skipped']++;
                continue;
            }
            if ($get('symbol') === '' && $get('entry') === '') {
                $out['skipped']++;
                continue;
            }
            if ($mode === 'trades' && preg_match('/\b(limit|stop)\b/', $sideRaw)) {
                $out['skipped']++; // pending orders (buy limit, sell stop…) in MT4/MT5 history
                continue;
            }
            $qtyRaw = self::num($get('lots'));
            $side = self::side($sideRaw, is_int($map['side'] ?? null) ? null : $qtyRaw);
            if ($side === null) {
                if (preg_match('/limit|stop/', $sideRaw)) {
                    $out['skipped']++; // pending orders in MT4/MT5 history
                } else {
                    $out['errors'][] = [$ref, 'Unrecognised direction “' . mb_substr($get('side'), 0, 20) . '”'];
                }
                continue;
            }
            $sym = Instruments::resolve($get('symbol'));
            if (!$sym) {
                $out['errors'][] = [$ref, 'Unsupported instrument “' . mb_substr($get('symbol'), 0, 24) . '” — add it under Control Panel → Instruments or check the symbol column'];
                continue;
            }
            $inst = Instruments::get($sym);
            $open = self::date($get('open'), $get('open_time'), $order, $offset);
            $entry = self::num($get('entry'));
            $lots = $qtyRaw === null ? null : abs($qtyRaw);
            if ($lots !== null && ($unit === 'units' || ($unit === 'auto' && $inst['class'] === 'FOREX' && $lots >= 1000))) {
                $lots = round($lots / max(1.0, (float) $inst['contract']), 4); // 100,000 units = 1 standard lot
            }
            if (!$open) {
                $out['errors'][] = [$ref, 'Unreadable date/time “' . mb_substr(trim($get('open') . ' ' . $get('open_time')), 0, 30) . '” — try another date order'];
                continue;
            }
            if (!$entry || $entry <= 0) {
                $out['errors'][] = [$ref, 'Invalid price'];
                continue;
            }
            if (!$lots || $lots <= 0) {
                $out['errors'][] = [$ref, 'Invalid size'];
                continue;
            }
            if ($mode === 'fills') {
                $fills[] = ['sym' => $sym, 'side' => $side, 'time' => $open, 'price' => $entry, 'lots' => $lots, 'comm' => abs((float) (self::num($get('commission')) ?? 0)) + abs((float) (self::num($get('fees')) ?? 0)), 'id' => $get('id'), 'line' => $line];
                continue;
            }
            $profit = self::num($get('pnl'));
            $comm = self::num($get('commission'));
            $swap = self::num($get('swap'));
            $feesCol = self::num($get('fees'));
            // Statements report commission/swap as signed P&L components (MT4/MT5, cTrader): net = profit + commission + swap.
            $net = $profit !== null ? round($profit + ($comm ?? 0) + ($swap ?? 0), 2) : null;
            $fees = $feesCol !== null ? abs($feesCol) : ($comm !== null || $swap !== null ? max(0.0, round(-(($comm ?? 0) + ($swap ?? 0)), 2)) : 0.0);
            $exit = self::num($get('exit'));
            $out['trades'][] = [
                'broker_trade_id' => mb_substr($get('id') !== '' ? $get('id') : 'csv-' . substr(hash('sha256', "$sym|$side|$open|$entry|$lots|$exit"), 0, 24), 0, 80),
                'symbol' => $sym, 'side' => $side, 'executed_at' => $open, 'closed_at' => self::date($get('close'), $get('close_time'), $order, $offset),
                'entry' => $entry, 'exit' => $exit && $exit > 0 ? $exit : null, 'sl' => ($v = self::num($get('sl'))) && $v > 0 ? $v : null,
                'tp' => ($v = self::num($get('tp'))) && $v > 0 ? $v : null, 'lots' => $lots, 'pnl' => $net, 'fees' => $fees,
            ];
        }
        if ($mode === 'fills') {
            [$out['trades'], $out['open']] = self::pairFills($fills);
        }
        return $out;
    }

    /**
     * First-in-first-out pairing of executions into round trips per instrument. A sell after buys closes them
     * (oldest first); any size left over opens a new position in the other direction. Prices are kept exactly;
     * P&L is calculated from them when the trade is saved.
     * @return array{0: array, 1: int} [trades, number of positions still open]
     */
    public static function pairFills(array $fills): array
    {
        usort($fills, fn ($a, $b) => [$a['time'], $a['line']] <=> [$b['time'], $b['line']]);
        $book = [];
        $trades = [];
        foreach ($fills as $f) {
            $q = &$book[$f['sym']];
            $q ??= [];
            $left = $f['lots'];
            $commPerLot = $f['lots'] > 0 ? $f['comm'] / $f['lots'] : 0.0;
            while ($left > 1e-9 && $q && $q[0]['side'] !== $f['side']) {
                $o = &$q[0];
                $take = min($o['lots'], $left);
                $trades[] = [
                    'broker_trade_id' => 'fifo-' . substr(hash('sha256', implode('|', [$f['sym'], $o['side'], $o['time'], $o['price'], $f['time'], $f['price'], $take, $o['id'], $f['id']])), 0, 28),
                    'symbol' => $f['sym'], 'side' => $o['side'], 'executed_at' => $o['time'], 'closed_at' => $f['time'],
                    'entry' => $o['price'], 'exit' => $f['price'], 'sl' => null, 'tp' => null, 'lots' => round($take, 6), 'pnl' => null,
                    'fees' => round($take * ($o['commPerLot'] + $commPerLot), 2),
                ];
                $o['lots'] -= $take;
                $left -= $take;
                if ($o['lots'] <= 1e-9) {
                    array_shift($q);
                }
                unset($o);
            }
            if ($left > 1e-9) {
                $q[] = ['side' => $f['side'], 'time' => $f['time'], 'price' => $f['price'], 'lots' => $left, 'id' => $f['id'], 'commPerLot' => $commPerLot];
            }
            unset($q);
        }
        $open = 0;
        foreach ($book as $sym => $q) {
            foreach ($q as $o) {
                $open++;
                $trades[] = [
                    'broker_trade_id' => 'fifo-open-' . substr(hash('sha256', implode('|', [$sym, $o['side'], $o['time'], $o['price'], $o['lots'], $o['id']])), 0, 24),
                    'symbol' => $sym, 'side' => $o['side'], 'executed_at' => $o['time'], 'closed_at' => null,
                    'entry' => $o['price'], 'exit' => null, 'sl' => null, 'tp' => null, 'lots' => round($o['lots'], 6), 'pnl' => null, 'fees' => round($o['lots'] * $o['commPerLot'], 2),
                ];
            }
        }
        return [$trades, $open];
    }
}
