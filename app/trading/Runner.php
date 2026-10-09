<?php
declare(strict_types=1);

namespace App\Trading;

use App\Core\Database;

/**
 * Runner audit: "what if you had left 20% of the position running for 2 more hours after you closed?"
 * The runner's stop is moved to breakeven (the entry price). Prices come from the configured market-data
 * provider (1-minute candles). Demo-journal trades use a clearly labelled simulated price path instead, so the
 * feature can be explored without real data. Results are stored per trade, so each trade is fetched only once.
 */
final class Runner
{
    public const SHARE = 0.20;
    public const HOURS = 2;
    public const BATCH = 8;   // trades analysed per click (free market-data plans allow ~8 requests a minute)

    /** Why a trade cannot be audited, or null when it can. */
    public static function ineligible(array $t): ?string
    {
        if ($t['status'] !== 'CLOSED' || $t['exit_price'] === null || $t['stop_loss'] === null) {
            return 'Needs a closed trade with a stop loss and exit price.';
        }
        $dir = $t['side'] === 'LONG' ? 1 : -1;
        if (((float) $t['exit_price'] - (float) $t['entry_price']) * $dir <= 0) {
            return 'Only profitable exits are audited — a runner would have been stopped with the rest of the trade.';
        }
        if (abs((float) $t['entry_price'] - (float) $t['stop_loss']) <= 0) {
            return 'The stop loss equals the entry price.';
        }
        $closed = strtotime(($t['closed_at'] ?: $t['executed_at']) . ' UTC');
        if ($closed > time() - self::HOURS * 3600) {
            return 'Available ' . self::HOURS . ' hours after the trade was closed.';
        }
        return null;
    }

    /** Runs the audit for one trade and stores the result. @return array the stored row */
    public static function audit(array $t, bool $demo): array
    {
        $from = $t['closed_at'] ?: $t['executed_at'];
        $to = gmdate('Y-m-d H:i:s', strtotime($from . ' UTC') + self::HOURS * 3600);
        try {
            $candles = $demo ? self::simulated($t) : MarketData::candles($t['symbol'], $from, $to);
        } catch (\RuntimeException $e) {
            return self::store($t, ['status' => 'unavailable', 'message' => mb_substr($e->getMessage(), 0, 250), 'is_demo' => 0]);
        }
        $dir = $t['side'] === 'LONG' ? 1 : -1;
        $entry = (float) $t['entry_price'];
        $exit = (float) $t['exit_price'];
        $riskDist = abs($entry - (float) $t['stop_loss']);
        $best = $exit;
        $stopped = false;
        $runnerExit = $exit;
        foreach ($candles as $c) {
            if (($dir === 1 && $c['l'] <= $entry) || ($dir === -1 && $c['h'] >= $entry)) {
                $stopped = true;
                $runnerExit = $entry;
                break;
            }
            $best = $dir === 1 ? max($best, $c['h']) : min($best, $c['l']);
            $runnerExit = $c['c'];
        }
        $extraR = self::SHARE * ($runnerExit - $exit) * $dir / $riskDist;
        // Money per 1R: the planned risk, else derived from the realised P&L per price unit.
        $perR = null;
        if ($t['risk_amount'] && (float) $t['risk_amount'] > 0) {
            $perR = (float) $t['risk_amount'];
        } elseif ($t['pnl'] !== null && abs($exit - $entry) > 0) {
            $perR = (float) $t['pnl'] / (abs($exit - $entry) / $riskDist);
        }
        return self::store($t, [
            'status' => 'ok', 'runner_exit' => $runnerExit, 'best_price' => $best, 'stopped' => $stopped ? 1 : 0,
            'extra_r' => round($extraR, 4), 'extra_pnl' => $perR !== null ? round($extraR * $perR, 2) : null, 'is_demo' => $demo ? 1 : 0, 'message' => null,
        ]);
    }

    private static function store(array $t, array $row): array
    {
        $row += ['runner_exit' => null, 'best_price' => null, 'stopped' => 0, 'extra_r' => null, 'extra_pnl' => null];
        $row = ['trade_id' => (int) $t['id'], 'user_id' => (int) $t['user_id']] + $row;
        Database::query('REPLACE INTO trade_runner_audits (trade_id, user_id, status, runner_exit, best_price, stopped, extra_r, extra_pnl, is_demo, message, checked_at)
            VALUES (:trade_id, :user_id, :status, :runner_exit, :best_price, :stopped, :extra_r, :extra_pnl, :is_demo, :message, UTC_TIMESTAMP())', $row);
        return $row;
    }

    /**
     * Deterministic random walk for DEMO DATA trades only (seeded by the trade id): 120 one-minute candles whose
     * typical move is scaled to the trade's own stop distance. Never used for real accounts.
     */
    private static function simulated(array $t): array
    {
        mt_srand(9100 + (int) $t['id']);
        $price = (float) $t['exit_price'];
        $step = abs((float) $t['entry_price'] - (float) $t['stop_loss']) / 9;
        $drift = (mt_rand(0, 100) - 45) / 1000;   // slight per-trade bias, mostly continuation
        $out = [];
        for ($i = 0; $i < self::HOURS * 60; $i++) {
            $move = ((mt_rand(0, 2000) / 1000) - 1 + $drift) * $step;
            $o = $price;
            $price += $move;
            $out[] = ['t' => '', 'h' => max($o, $price) + abs($move) * 0.3, 'l' => min($o, $price) - abs($move) * 0.3, 'c' => $price];
        }
        return $out;
    }

    /** Eligible closed trades of the account that have not been audited yet (most recent first). */
    public static function pending(int $uid, int $accountId, int $limit = self::BATCH): array
    {
        $rows = Database::all("SELECT t.* FROM trades t LEFT JOIN trade_runner_audits r ON r.trade_id = t.id
            WHERE t.user_id = :u AND t.account_id = :a AND t.status = 'CLOSED' AND t.stop_loss IS NOT NULL AND t.exit_price IS NOT NULL AND (r.trade_id IS NULL OR (r.status = 'unavailable' AND r.checked_at < UTC_TIMESTAMP() - INTERVAL 1 DAY))
            ORDER BY t.executed_at DESC LIMIT 200", ['u' => $uid, 'a' => $accountId]);
        return array_slice(array_values(array_filter($rows, fn ($t) => self::ineligible($t) === null)), 0, $limit);
    }

    public static function pendingCount(int $uid, int $accountId): int
    {
        return count(self::pending($uid, $accountId, 200));
    }

    /** Aggregate of stored audits for the account, plus the most recent results. */
    public static function summary(int $uid, int $accountId): array
    {
        $rows = Database::all("SELECT r.*, t.symbol, t.side, t.executed_at, t.exit_price, t.pnl FROM trade_runner_audits r JOIN trades t ON t.id = r.trade_id
            WHERE r.user_id = :u AND t.account_id = :a AND r.status = 'ok' ORDER BY t.executed_at DESC", ['u' => $uid, 'a' => $accountId]);
        $unavailable = (int) Database::value("SELECT COUNT(*) FROM trade_runner_audits r JOIN trades t ON t.id = r.trade_id WHERE r.user_id = :u AND t.account_id = :a AND r.status = 'unavailable'", ['u' => $uid, 'a' => $accountId]);
        $helped = array_filter($rows, fn ($r) => (float) $r['extra_r'] > 0.0001);
        $hurt = array_filter($rows, fn ($r) => (float) $r['extra_r'] < -0.0001);
        $money = array_filter(array_column($rows, 'extra_pnl'), fn ($v) => $v !== null);
        return [
            'count' => count($rows), 'helped' => count($helped), 'hurt' => count($hurt), 'flat' => count($rows) - count($helped) - count($hurt),
            'extra_r' => round(array_sum(array_map('floatval', array_column($rows, 'extra_r'))), 2),
            'extra_pnl' => $money ? round(array_sum(array_map('floatval', $money)), 2) : null,
            'stopped' => count(array_filter($rows, fn ($r) => (int) $r['stopped'] === 1)),
            'demo' => (bool) array_filter($rows, fn ($r) => (int) $r['is_demo'] === 1),
            'recent' => array_slice($rows, 0, 5), 'unavailable' => $unavailable,
        ];
    }
}
