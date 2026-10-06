<?php
declare(strict_types=1);

namespace App\Trading;

use App\Core\Database;

/**
 * Member-scoped data access. Every query includes user_id = the signed-in member — ids from the browser
 * are only ever used together with that ownership filter.
 */
final class Ledger
{
    // ------------------------------------------------------------------ accounts
    public static function accounts(int $uid, bool $archived = false): array
    {
        return Database::all('SELECT * FROM trading_accounts WHERE user_id = :u' . ($archived ? '' : ' AND is_archived = 0') . ' ORDER BY is_demo ASC, id ASC', ['u' => $uid]);
    }

    public static function account(int $uid, int $id): ?array
    {
        return Database::one('SELECT * FROM trading_accounts WHERE id = :id AND user_id = :u', ['id' => $id, 'u' => $uid]);
    }

    /** The account the terminal is showing. Falls back to the first account (creating a demo account if none). */
    public static function active(array $m): array
    {
        $acc = $m['active_account_id'] ? self::account((int) $m['id'], (int) $m['active_account_id']) : null;
        if (!$acc || (int) $acc['is_archived']) {
            $acc = self::accounts((int) $m['id'])[0] ?? null;
            if (!$acc) {
                $id = Database::insert('trading_accounts', ['user_id' => $m['id'], 'name' => 'Demo Account', 'broker_name' => 'Paper trading', 'currency' => $m['base_currency'] ?? 'USD', 'starting_capital' => '10000.00', 'is_demo' => 1]);
                $acc = self::account((int) $m['id'], $id);
            }
            Database::update('user_settings', ['active_account_id' => $acc['id']], 'user_id = :u', ['u' => $m['id']]);
        }
        $acc['writable'] = self::writable($m, $acc);
        return $acc;
    }

    /** Demo accounts are always writable; live accounts need an active paid plan. */
    public static function writable(array $m, array $acc): bool
    {
        return (int) $acc['is_demo'] === 1 || $m['ent']['live'];
    }

    public static function liveCount(int $uid): int
    {
        return (int) Database::value('SELECT COUNT(*) FROM trading_accounts WHERE user_id = :u AND is_demo = 0 AND is_archived = 0', ['u' => $uid]);
    }

    public static function balance(array $acc): array
    {
        $pnl = (float) Database::value('SELECT COALESCE(SUM(pnl), 0) FROM trades WHERE account_id = :a AND status = \'CLOSED\'', ['a' => $acc['id']]);
        $flows = (float) Database::value('SELECT COALESCE(SUM(CASE WHEN type = \'WITHDRAWAL\' THEN -ABS(amount) WHEN type = \'DEPOSIT\' THEN ABS(amount) ELSE amount END), 0) FROM capital_transactions WHERE account_id = :a', ['a' => $acc['id']]);
        $start = (float) $acc['starting_capital'];
        return ['start' => $start, 'pnl' => round($pnl, 2), 'flows' => round($flows, 2), 'equity' => round($start + $flows + $pnl, 2), 'return' => $start > 0 ? $pnl / $start : null];
    }

    // ------------------------------------------------------------------ strategies
    public static function strategies(int $uid, bool $activeOnly = false): array
    {
        return Database::all('SELECT * FROM strategies WHERE user_id = :u' . ($activeOnly ? ' AND active = 1' : '') . ' ORDER BY name', ['u' => $uid]);
    }

    public static function ensureTemplates(int $uid): void
    {
        foreach (Domain::STRATEGY_TEMPLATES as [$name, $desc, $rr, $list]) {
            Database::query('INSERT IGNORE INTO strategies (user_id, name, description, target_rr, checklist) VALUES (:u, :n, :d, :r, :c)', ['u' => $uid, 'n' => $name, 'd' => $desc, 'r' => $rr, 'c' => implode("\n", $list)]);
        }
    }

    // ------------------------------------------------------------------ trades
    public const TRADE_SELECT = 'SELECT t.*, s.name AS strategy_name FROM trades t LEFT JOIN strategies s ON s.id = t.strategy_id';

    public static function trade(int $uid, int $id): ?array
    {
        return Database::one(self::TRADE_SELECT . ' WHERE t.id = :id AND t.user_id = :u', ['id' => $id, 'u' => $uid]);
    }

    /** Filtered, member-scoped trade query. Returns [rows, total]. */
    public static function trades(int $uid, int $accountId, array $f, string $tz, int $limit = 50, int $offset = 0): array
    {
        [$where, $p] = self::where($uid, $accountId, $f, $tz);
        $sorts = ['executed_at' => 't.executed_at', 'symbol' => 't.symbol', 'pnl' => 't.pnl', 'rr' => 't.rr'];
        $sort = $sorts[$f['sort'] ?? ''] ?? 't.executed_at';
        $dir = ($f['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
        $total = (int) Database::value('SELECT COUNT(*) FROM trades t WHERE ' . $where, $p);
        $rows = Database::all(self::TRADE_SELECT . " WHERE $where ORDER BY $sort $dir, t.id DESC LIMIT :lim OFFSET :off", $p + ['lim' => $limit, 'off' => $offset]);
        return [$rows, $total];
    }

    /** Closed trades for analytics, ascending by time. */
    public static function forAnalytics(int $uid, int $accountId, string $tz, ?string $from = null, ?string $to = null): array
    {
        [$where, $p] = self::where($uid, $accountId, ['from' => $from, 'to' => $to], $tz);
        return Database::all(self::TRADE_SELECT . " WHERE $where AND t.status = 'CLOSED' ORDER BY t.executed_at ASC, t.id ASC", $p);
    }

    private static function where(int $uid, int $accountId, array $f, string $tz): array
    {
        $w = ['t.user_id = :u', 't.account_id = :a'];
        $p = ['u' => $uid, 'a' => $accountId];
        if (!empty($f['q'])) {
            $w[] = '(t.symbol LIKE :q1 OR t.setup_tag LIKE :q2 OR t.notes LIKE :q3)';
            $like = '%' . addcslashes((string) $f['q'], '%_\\') . '%';
            $p += ['q1' => $like, 'q2' => $like, 'q3' => $like];
        }
        foreach (['symbol' => 't.symbol', 'side' => 't.side', 'session' => 't.session', 'emotion' => 't.emotion', 'mistake' => 't.mistake_tag'] as $k => $col) {
            if (!empty($f[$k])) {
                $w[] = "$col = :f_$k";
                $p["f_$k"] = (string) $f[$k];
            }
        }
        if (!empty($f['strategy']) && ctype_digit((string) $f['strategy'])) {
            $w[] = 't.strategy_id = :st';
            $p['st'] = (int) $f['strategy'];
        }
        if (($f['result'] ?? '') === 'win') {
            $w[] = 't.pnl > 0';
        } elseif (($f['result'] ?? '') === 'loss') {
            $w[] = 't.pnl < 0';
        } elseif (($f['result'] ?? '') === 'open') {
            $w[] = "t.status = 'OPEN'";
        }
        foreach (['from' => '>=', 'to' => '<'] as $k => $op) {
            if (!empty($f[$k]) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $f[$k])) {
                $d = new \DateTimeImmutable($f[$k] . ' 00:00:00', new \DateTimeZone($tz));
                if ($k === 'to') {
                    $d = $d->modify('+1 day');
                }
                $w[] = "t.executed_at $op :d_$k";
                $p["d_$k"] = $d->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
            }
        }
        return [implode(' AND ', $w), $p];
    }

    /**
     * Validates member input and computes P&L/R on the server.
     * @return array{0: array, 1: array} [errors, clean row]
     */
    public static function validateTrade(array $m, array $acc, array $in): array
    {
        $e = [];
        $num = fn ($v) => is_numeric($v) ? (float) $v : null;
        $sym = Instruments::resolve((string) ($in['symbol'] ?? '')) ?? '';
        if ($sym === '') {
            $e['symbol'] = 'Choose a supported instrument.';
        }
        $side = in_array($in['side'] ?? '', Domain::SIDES, true) ? $in['side'] : '';
        if ($side === '') {
            $e['side'] = 'Choose long or short.';
        }
        $tz = $m['timezone'] ?: 'UTC';
        $exec = null;
        try {
            $raw = trim((string) ($in['executed_at'] ?? ''));
            $exec = $raw === '' ? gmdate('Y-m-d H:i:s') : (new \DateTimeImmutable($raw, new \DateTimeZone($tz)))->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
            if (strtotime($exec . ' UTC') > time() + 3600) {
                $e['executed_at'] = 'Execution time cannot be in the future.';
            }
        } catch (\Throwable) {
            $e['executed_at'] = 'Enter a valid date and time.';
        }
        $entry = $num($in['entry_price'] ?? null);
        $exit = ($in['exit_price'] ?? '') === '' ? null : $num($in['exit_price']);
        $stop = ($in['stop_loss'] ?? '') === '' ? null : $num($in['stop_loss']);
        $tp = ($in['take_profit'] ?? '') === '' ? null : $num($in['take_profit']);
        $lots = $num($in['lot_size'] ?? null);
        $fees = ($in['fees'] ?? '') === '' ? 0.0 : $num($in['fees']);
        if ($entry === null || $entry <= 0) {
            $e['entry_price'] = 'Enter a positive entry price.';
        }
        if (($in['exit_price'] ?? '') !== '' && ($exit === null || $exit <= 0)) {
            $e['exit_price'] = 'Enter a valid exit price.';
        }
        if (($in['stop_loss'] ?? '') !== '' && ($stop === null || $stop <= 0)) {
            $e['stop_loss'] = 'Enter a valid stop loss.';
        }
        if (($in['take_profit'] ?? '') !== '' && ($tp === null || $tp <= 0)) {
            $e['take_profit'] = 'Enter a valid take profit.';
        }
        if ($lots === null || $lots <= 0 || $lots > 1000000) {
            $e['lot_size'] = 'Enter a positive lot size.';
        }
        if ($fees === null || $fees < 0) {
            $e['fees'] = 'Fees must be zero or positive.';
        }
        if (!$e && $stop !== null && $entry !== null) {
            if ($stop == $entry) {
                $e['stop_loss'] = 'Stop loss cannot equal entry.';
            } elseif ($side === 'LONG' && $stop > $entry) {
                $e['stop_loss'] = 'A long stop loss must be below entry.';
            } elseif ($side === 'SHORT' && $stop < $entry) {
                $e['stop_loss'] = 'A short stop loss must be above entry.';
            }
        }
        $strategyId = null;
        if (!empty($in['strategy_id'])) {
            $strategyId = (int) Database::value('SELECT id FROM strategies WHERE id = :id AND user_id = :u', ['id' => (int) $in['strategy_id'], 'u' => $m['id']]) ?: null;
            if (!$strategyId) {
                $e['strategy_id'] = 'Choose one of your strategies.';
            }
        }
        $emotion = in_array($in['emotion'] ?? '', Domain::EMOTIONS, true) ? $in['emotion'] : null;
        $mistake = isset(Domain::MISTAKES[$in['mistake_tag'] ?? '']) ? $in['mistake_tag'] : 'NONE';
        $override = ($in['pnl'] ?? '') !== '';
        $pnlIn = $override ? $num($in['pnl']) : null;
        if ($override && $pnlIn === null) {
            $e['pnl'] = 'Enter the broker P&L as a number.';
        }
        if ($e) {
            return [$e, []];
        }
        $calc = TradeMath::compute($sym, $side, $entry, $exit, $stop, $lots, $fees, $acc['currency']);
        $pnl = $override ? TradeMath::money($pnlIn) : $calc['pnl'];
        if ($exit !== null && $pnl === null) {
            return [['pnl' => 'This instrument is not quoted in ' . $acc['currency'] . '. Enter the P&L reported by your broker.'], []];
        }
        $inst = Instruments::get($sym);
        return [[], [
            'symbol' => $sym, 'asset_class' => $inst['class'], 'side' => $side, 'executed_at' => $exec,
            'closed_at' => $exit !== null ? $exec : null, 'status' => $exit !== null || $override ? 'CLOSED' : 'OPEN',
            'entry_price' => $entry, 'exit_price' => $exit, 'stop_loss' => $stop, 'take_profit' => $tp, 'lot_size' => $lots, 'fees' => $fees,
            'pnl' => $pnl, 'pnl_override' => $override ? 1 : 0, 'rr' => $calc['rr'], 'risk_amount' => $calc['risk'],
            'strategy_id' => $strategyId, 'setup_tag' => mb_substr(trim((string) ($in['setup_tag'] ?? '')), 0, 120) ?: null,
            'session' => Sessions::classify($exec), 'emotion' => $emotion, 'mistake_tag' => $mistake,
            'rules_followed' => in_array($in['rules_followed'] ?? '1', ['1', 'on', 1, true], true) ? 1 : 0,
            'notes' => mb_substr(trim((string) ($in['notes'] ?? '')), 0, 5000) ?: null,
        ]];
    }

    // ------------------------------------------------------------------ demo data
    /** Loads the 42-trade DEMO DATA set into a fresh demo account (transaction). Returns the account id. */
    public static function loadDemo(array $m): int
    {
        return Database::transaction(function () use ($m) {
            $uid = (int) $m['id'];
            self::ensureTemplates($uid);
            $acc = Database::one('SELECT id FROM trading_accounts WHERE user_id = :u AND has_demo_data = 1 LIMIT 1', ['u' => $uid]);
            if ($acc) {
                Database::delete('trades', 'account_id = :a AND user_id = :u', ['a' => $acc['id'], 'u' => $uid]);
                $accId = (int) $acc['id'];
            } else {
                $accId = Database::insert('trading_accounts', ['user_id' => $uid, 'name' => 'Demo Journal (sample data)', 'broker_name' => 'DEMO DATA', 'currency' => 'USD', 'starting_capital' => '25000.00', 'is_demo' => 1, 'has_demo_data' => 1]);
            }
            $strategies = array_column(self::strategies($uid), 'id', 'name');
            foreach (DemoData::trades() as $t) {
                $calc = TradeMath::compute($t['symbol'], $t['side'], $t['entry'], $t['exit'], $t['stop'], $t['lots'], 0, 'USD');
                Database::insert('trades', [
                    'user_id' => $uid, 'account_id' => $accId, 'strategy_id' => $strategies[$t['strategy']] ?? null, 'executed_at' => $t['executed_at'], 'closed_at' => $t['closed_at'],
                    'symbol' => $t['symbol'], 'asset_class' => Instruments::get($t['symbol'])['class'], 'side' => $t['side'], 'status' => 'CLOSED',
                    'entry_price' => $t['entry'], 'exit_price' => $t['exit'], 'stop_loss' => $t['stop'], 'take_profit' => $t['tp'], 'lot_size' => $t['lots'],
                    'pnl' => $calc['pnl'], 'rr' => $calc['rr'], 'risk_amount' => $calc['risk'], 'setup_tag' => $t['strategy'], 'session' => Sessions::classify($t['executed_at']),
                    'emotion' => $t['emotion'], 'mistake_tag' => $t['mistake'], 'rules_followed' => $t['rules'], 'notes' => $t['notes'], 'source' => 'DEMO',
                ]);
            }
            Database::delete('journal_entries', 'user_id = :u AND is_demo = 1', ['u' => $uid]);
            foreach (DemoData::JOURNALS as [$date, $comp, $emo, $disc, $refl, $lesson]) {
                Database::insert('journal_entries', ['user_id' => $uid, 'account_id' => $accId, 'journal_date' => $date, 'is_demo' => 1, 'compliance' => $comp, 'emotional_state' => $emo, 'discipline_rating' => $disc, 'reflection' => '[DEMO DATA] ' . $refl, 'key_lesson' => $lesson]);
            }
            Database::update('user_settings', ['active_account_id' => $accId], 'user_id = :u', ['u' => $uid]);
            Members::audit($uid, 'demo_loaded', 'Loaded demo dataset');
            return $accId;
        });
    }
}
