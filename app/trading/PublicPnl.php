<?php
declare(strict_types=1);

namespace App\Trading;

use App\Core\Database;

/**
 * Public "verified P&L" links. A member picks an account and a date range; anyone with the link sees the figures,
 * computed live from the journal each time (closed trades only). Members choose whether money amounts, the trade
 * list, the equity curve and their first name are shown. Nothing else (notes, screenshots, balances, account names)
 * is ever published.
 */
final class PublicPnl
{
    public const SOURCES = ['CSV_IMPORT' => 'Imported from broker statement', 'VOICE' => 'Voice log', 'QUICK_COMMAND' => 'Quick command', 'MANUAL' => 'Manual entry', 'DEMO' => 'Demo data'];

    public static function links(int $uid): array
    {
        return Database::all('SELECT l.*, a.name AS account_name, a.currency FROM public_pnl_links l JOIN trading_accounts a ON a.id = l.account_id WHERE l.user_id = :u ORDER BY l.id DESC', ['u' => $uid]);
    }

    public static function create(int $uid, array $d): string
    {
        $code = substr(strtr(base64_encode(random_bytes(9)), '+/', 'Kz'), 0, 12);
        Database::insert('public_pnl_links', $d + ['user_id' => $uid, 'code' => $code]);
        return $code;
    }

    public static function find(string $code): ?array
    {
        if (!preg_match('/^[A-Za-z0-9]{12}$/', $code)) {
            return null;
        }
        return Database::one('SELECT l.*, a.currency, a.starting_capital, a.is_demo, a.has_demo_data, a.account_type, a.prop_firm, u.name AS user_name, s.timezone
            FROM public_pnl_links l JOIN trading_accounts a ON a.id = l.account_id JOIN users u ON u.id = l.user_id LEFT JOIN user_settings s ON s.user_id = l.user_id
            WHERE l.code = :c AND l.is_active = 1 AND u.status = \'active\'', ['c' => $code]);
    }

    /** Everything the public page shows, computed live for the link's account and date range. */
    public static function report(array $l): array
    {
        $tz = $l['timezone'] ?: 'UTC';
        $trades = Ledger::forAnalytics((int) $l['user_id'], (int) $l['account_id'], $tz, $l['date_from'], $l['date_to']);
        // Balance at the start of the range = starting capital + closed P&L and capital flows before it
        $zone = new \DateTimeZone($tz);
        $fromUtc = (new \DateTimeImmutable($l['date_from'] . ' 00:00:00', $zone))->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        $before = (float) Database::value("SELECT COALESCE(SUM(pnl), 0) FROM trades WHERE account_id = :a AND status = 'CLOSED' AND executed_at < :f", ['a' => $l['account_id'], 'f' => $fromUtc]);
        $flows = (float) Database::value("SELECT COALESCE(SUM(CASE WHEN type = 'WITHDRAWAL' THEN -ABS(amount) WHEN type = 'DEPOSIT' THEN ABS(amount) ELSE amount END), 0) FROM capital_transactions WHERE account_id = :a AND occurred_at < :f", ['a' => $l['account_id'], 'f' => $fromUtc]);
        $start = (float) $l['starting_capital'] + $before + $flows;
        $sum = Analytics::summarize($trades);
        $eq = Analytics::equity($trades, $start);
        // Daily and monthly breakdowns (member's timezone)
        $days = $months = [];
        foreach ($trades as $t) {
            $local = (new \DateTimeImmutable($t['executed_at'], new \DateTimeZone('UTC')))->setTimezone($zone);
            $d = $local->format('Y-m-d');
            $mo = $local->format('Y-m');
            $p = (float) $t['pnl'];
            $days[$d] = ($days[$d] ?? 0) + $p;
            $months[$mo] ??= ['net' => 0.0, 'trades' => 0, 'wins' => 0];
            $months[$mo]['net'] += $p;
            $months[$mo]['trades']++;
            $months[$mo]['wins'] += $p > 0 ? 1 : 0;
        }
        // Transparency: where the trades came from and how soon they were logged
        $src = [];
        $prompt = 0;
        foreach ($trades as $t) {
            $k = $t['source'] ?: 'MANUAL';
            $src[$k] = ($src[$k] ?? 0) + 1;
            if (strtotime((string) $t['created_at'] . ' UTC') - strtotime((string) $t['executed_at'] . ' UTC') <= 86400 * 2 || $k === 'CSV_IMPORT') {
                $prompt++;
            }
        }
        $n = count($trades);
        $rs = array_values(array_filter(array_map(fn ($t) => $t['rr'] === null ? null : (float) $t['rr'], $trades), fn ($v) => $v !== null));
        return [
            'trades' => $trades, 'sum' => $sum, 'eq' => $eq, 'start' => round($start, 2), 'return' => $start > 0 ? $sum['net'] / $start : null,
            'days' => $days, 'months' => $months, 'green_days' => count(array_filter($days, fn ($v) => $v > 0)), 'red_days' => count(array_filter($days, fn ($v) => $v < 0)),
            'best_day' => $days ? max($days) : null, 'worst_day' => $days ? min($days) : null, 'total_r' => $rs ? round(array_sum($rs), 2) : null,
            'sources' => $src, 'prompt_pct' => $n ? $prompt / $n : null, 'imported_pct' => $n ? ($src['CSV_IMPORT'] ?? 0) / $n : null,
        ];
    }

    /** Validated link settings from a form post. */
    public static function input(callable $post, array $accountIds, string $tz): array|string
    {
        $acc = (int) $post('account_id');
        if (!in_array($acc, $accountIds, true)) {
            return 'Choose one of your accounts.';
        }
        $from = (string) $post('date_from');
        $to = (string) $post('date_to');
        $ok = fn ($d) => preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && \DateTime::createFromFormat('Y-m-d', $d);
        if (!$ok($from) || !$ok($to)) {
            return 'Choose a start and end date.';
        }
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }
        $today = (new \DateTimeImmutable('now', new \DateTimeZone($tz)))->format('Y-m-d');
        if ($from > $today) {
            return 'The start date cannot be in the future.';
        }
        return [
            'account_id' => $acc, 'date_from' => $from, 'date_to' => min($to, '2099-12-31'),
            'title' => mb_substr(trim((string) $post('title')), 0, 80) ?: null,
            'show_amounts' => $post('show_amounts') === '1' ? 1 : 0, 'show_trades' => $post('show_trades') === '1' ? 1 : 0,
            'show_equity' => $post('show_equity') === '1' ? 1 : 0, 'show_name' => $post('show_name') === '1' ? 1 : 0,
        ];
    }
}
