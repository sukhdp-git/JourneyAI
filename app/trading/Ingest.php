<?php
declare(strict_types=1);

namespace App\Trading;

use App\Core\Database;

/**
 * Manual CSV statement import (MT4/MT5, cTrader, NinjaTrader history exports). Rows are normalised and are
 * idempotent per account via broker_trade_id. There is no live broker synchronisation.
 */
final class Ingest
{
    private const ALIASES = [
        'id' => ['ticket', 'position', 'position id', 'order', 'deal', 'trade id', 'id', 'trade #', 'trade number'],
        'symbol' => ['symbol', 'instrument', 'item', 'market', 'pair'],
        'side' => ['type', 'side', 'direction', 'action', 'market pos.', 'market pos', 'buy/sell'],
        'open' => ['open time', 'opentime', 'entry time', 'time', 'open date', 'date', 'opening time'],
        'close' => ['close time', 'closetime', 'exit time', 'closing time', 'close date'],
        'entry' => ['open price', 'entry price', 'price', 'entry', 'opening price'],
        'exit' => ['close price', 'exit price', 'exit', 'closing price'],
        'sl' => ['s / l', 's/l', 'sl', 'stop loss', 'stoploss'],
        'tp' => ['t / p', 't/p', 'tp', 'take profit', 'takeprofit'],
        'lots' => ['volume', 'lots', 'size', 'quantity', 'qty', 'lot'],
        'pnl' => ['profit', 'pnl', 'p&l', 'net profit', 'realized pnl', 'net p/l', 'profit/loss'],
        'fees' => ['fees', 'fee'],
        'commission' => ['commission', 'commissions'],
        'swap' => ['swap', 'swaps', 'rollover'],
    ];

    private static function num(?string $v): ?float
    {
        if ($v === null) {
            return null;
        }
        $s = preg_replace('/^\((.*)\)$/', '-$1', preg_replace('/[\s,]/', '', $v));
        return preg_match('/^-?\d+(\.\d+)?$/', $s) ? (float) $s : null;
    }

    /** Parses a broker timestamp in server time (offset minutes from UTC) → UTC 'Y-m-d H:i:s'. */
    public static function date(?string $v, int $offsetMin = 0): ?string
    {
        $s = trim((string) $v);
        if ($s === '') {
            return null;
        }
        if (preg_match('/^(\d{4})[.\-\/](\d{2})[.\-\/](\d{2})[ T](\d{2}):(\d{2})(?::(\d{2}))?$/', $s, $m)) {
            $ts = gmmktime((int) $m[4], (int) $m[5], (int) ($m[6] ?? 0), (int) $m[2], (int) $m[3], (int) $m[1]) - $offsetMin * 60;
            return gmdate('Y-m-d H:i:s', $ts);
        }
        if (ctype_digit($s) && strlen($s) >= 10) {
            $ts = strlen($s) >= 13 ? intdiv((int) $s, 1000) : (int) $s;
            return gmdate('Y-m-d H:i:s', $ts);
        }
        $ts = strtotime($s . (preg_match('/(Z|[+-]\d{2}:?\d{2}|UTC|GMT)$/i', $s) ? '' : ' UTC'));
        return $ts === false ? null : gmdate('Y-m-d H:i:s', $ts - (preg_match('/(Z|[+-]\d{2}:?\d{2}|UTC|GMT)$/i', $s) ? 0 : $offsetMin * 60));
    }

    /**
     * @return array{trades: array, errors: array, skipped: int}
     */
    public static function parseCsv(string $content, int $offsetMin = 0): array
    {
        $out = ['trades' => [], 'errors' => [], 'skipped' => 0];
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        $lines = preg_split('/\r\n|\n|\r/', trim($content));
        if (!$lines || $lines[0] === '') {
            $out['errors'][] = ['header', 'The file is empty.'];
            return $out;
        }
        $delim = str_contains($lines[0], ';') && !str_contains($lines[0], ',') ? ';' : (str_contains($lines[0], "\t") && !str_contains($lines[0], ',') ? "\t" : ',');
        $fh = fopen('php://temp', 'r+');
        fwrite($fh, implode("\n", $lines));
        rewind($fh);
        $header = array_map(fn ($h) => preg_replace('/\s+/', ' ', strtolower(trim((string) $h))), fgetcsv($fh, 0, $delim, '"', '') ?: []);
        $cols = [];
        foreach (self::ALIASES as $k => $aliases) {
            foreach ($header as $i => $h) {
                if (in_array($h, $aliases, true)) {
                    $cols[$k] = $i;
                    break;
                }
            }
        }
        foreach (['symbol', 'side', 'open', 'entry', 'lots'] as $req) {
            if (!isset($cols[$req])) {
                $out['errors'][] = ['header', 'Missing a column for ' . $req . ' (accepted: ' . implode(', ', self::ALIASES[$req]) . ')'];
            }
        }
        if ($out['errors']) {
            fclose($fh);
            return $out;
        }
        $line = 1;
        while (($r = fgetcsv($fh, 0, $delim, '"', '')) !== false) {
            $line++;
            if ($line > 20001) {
                $out['errors'][] = ['file', 'Only the first 20,000 rows were read.'];
                break;
            }
            if ($r === [null] || $r === ['']) {
                continue;
            }
            $get = fn ($k) => isset($cols[$k]) ? trim((string) ($r[$cols[$k]] ?? '')) : null;
            $ref = 'row ' . $line;
            $sideRaw = strtolower((string) $get('side'));
            if ($sideRaw === '' || preg_match('/balance|credit|deposit|withdraw/', $sideRaw) || preg_match('/limit|stop/', $sideRaw)) {
                $out['skipped']++;
                continue;
            }
            $side = preg_match('/^(buy|long|b)\b/', $sideRaw) ? 'LONG' : (preg_match('/^(sell|short|s)\b/', $sideRaw) ? 'SHORT' : null);
            if (!$side) {
                $out['errors'][] = [$ref, 'Unrecognised side "' . mb_substr((string) $get('side'), 0, 20) . '"'];
                continue;
            }
            $sym = Instruments::resolve((string) $get('symbol'));
            if (!$sym) {
                $out['errors'][] = [$ref, 'Unsupported instrument "' . mb_substr((string) $get('symbol'), 0, 20) . '"'];
                continue;
            }
            $open = self::date($get('open'), $offsetMin);
            $entry = self::num($get('entry'));
            $lots = self::num($get('lots'));
            if (!$open) {
                $out['errors'][] = [$ref, 'Invalid open time'];
                continue;
            }
            if (!$entry || $entry <= 0 || !$lots || $lots <= 0) {
                $out['errors'][] = [$ref, !$entry || $entry <= 0 ? 'Invalid entry price' : 'Invalid volume'];
                continue;
            }
            $profit = self::num($get('pnl'));
            $comm = self::num($get('commission'));
            $swap = self::num($get('swap'));
            $feesCol = self::num($get('fees'));
            // Statements report commission/swap as signed P&L components: net = profit + commission + swap.
            $net = $profit !== null ? round($profit + ($comm ?? 0) + ($swap ?? 0), 2) : null;
            $fees = $feesCol ?? ($comm !== null || $swap !== null ? round(-(($comm ?? 0) + ($swap ?? 0)), 2) : null);
            $exit = self::num($get('exit'));
            $out['trades'][] = [
                'broker_trade_id' => mb_substr((string) ($get('id') ?: 'csv-' . substr(hash('sha256', "$sym|$side|$open|$entry|$lots|$exit"), 0, 24)), 0, 80),
                'symbol' => $sym, 'side' => $side, 'executed_at' => $open, 'closed_at' => self::date($get('close'), $offsetMin),
                'entry' => $entry, 'exit' => $exit && $exit > 0 ? $exit : null, 'sl' => ($v = self::num($get('sl'))) && $v > 0 ? $v : null,
                'tp' => ($v = self::num($get('tp'))) && $v > 0 ? $v : null, 'lots' => $lots, 'pnl' => $net, 'fees' => $fees !== null && $fees >= 0 ? $fees : 0.0,
            ];
        }
        fclose($fh);
        return $out;
    }

    /**
     * Inserts a normalised trade unless the same broker_trade_id already exists for the account.
     * @return string 'imported' | 'duplicate' | error message
     */
    public static function insert(int $uid, array $acc, array $t, string $source): string
    {
        if (Database::value('SELECT id FROM trades WHERE account_id = :a AND broker_trade_id = :b', ['a' => $acc['id'], 'b' => $t['broker_trade_id']])) {
            return 'duplicate';
        }
        $stop = $t['sl'];
        if ($stop !== null && (($t['side'] === 'LONG' && $stop >= $t['entry']) || ($t['side'] === 'SHORT' && $stop <= $t['entry']))) {
            $stop = null; // trailing stops moved past entry are not an initial risk level
        }
        $calc = TradeMath::compute($t['symbol'], $t['side'], (float) $t['entry'], $t['exit'], $stop, (float) $t['lots'], (float) $t['fees'], $acc['currency']);
        $pnl = $t['pnl'] !== null ? TradeMath::money((float) $t['pnl']) : $calc['pnl'];
        $closed = $t['exit'] !== null || $t['pnl'] !== null;
        if ($closed && $pnl === null) {
            return 'P&L missing and the instrument is not quoted in ' . $acc['currency'];
        }
        try {
            Database::insert('trades', [
                'user_id' => $uid, 'account_id' => $acc['id'], 'executed_at' => $t['executed_at'], 'closed_at' => $t['closed_at'] ?? ($closed ? $t['executed_at'] : null),
                'symbol' => $t['symbol'], 'asset_class' => Instruments::get($t['symbol'])['class'], 'side' => $t['side'], 'status' => $closed ? 'CLOSED' : 'OPEN',
                'entry_price' => $t['entry'], 'exit_price' => $t['exit'], 'stop_loss' => $stop, 'take_profit' => $t['tp'], 'lot_size' => $t['lots'], 'fees' => $t['fees'],
                'pnl' => $closed ? $pnl : null, 'pnl_override' => $t['pnl'] !== null ? 1 : 0, 'rr' => $calc['rr'], 'risk_amount' => $calc['risk'],
                'setup_tag' => $t['setup'] ?? null, 'session' => Sessions::classify($t['executed_at']), 'mistake_tag' => 'NONE', 'rules_followed' => 1,
                'notes' => $t['notes'] ?? null, 'source' => $source, 'broker_trade_id' => $t['broker_trade_id'],
            ]);
        } catch (\PDOException $e) {
            return $e->getCode() === '23000' ? 'duplicate' : 'Could not save the trade';
        }
        return 'imported';
    }
}
