<?php
declare(strict_types=1);

namespace App\Trading;

use App\Core\Database;

/** Daily psychology journal helpers: the system discipline score (kept separate from the trader's own rating). */
final class Journal
{
    /**
     * System discipline score 0–10 from: the journal answer to "Did you follow your rules?" (25),
     * the share of the day's trades that followed the plan (30), mistake tags (20) and staying within the
     * daily loss limit (25). Components without data are left out and the rest re-weighted.
     */
    public static function disciplineScore(?array $entry, array $dayTrades, array $m, array $acc, string $date, string $tz): ?array
    {
        $parts = [];
        $answer = $entry['rules_answer'] ?? null;
        if ($answer === null && isset($entry['compliance']) && $entry['compliance'] !== null) {
            $answer = (int) $entry['compliance'] >= 4 ? 'yes' : ((int) $entry['compliance'] >= 2 ? 'partial' : 'no'); // legacy 1–5 rating
        }
        if ($answer) {
            $parts['Journal: followed rules'] = [['yes' => 25, 'partial' => 12, 'no' => 0][$answer], 25];
        }
        $closed = Analytics::closed($dayTrades);
        if ($dayTrades) {
            $ok = count(array_filter($dayTrades, fn ($t) => (int) $t['rules_followed']));
            $parts['Trades that followed the plan'] = [round(30 * $ok / count($dayTrades), 1), 30];
            $tagged = count(array_filter($dayTrades, fn ($t) => ($t['mistake_tag'] ?? 'NONE') !== 'NONE'));
            $parts['Mistake tags'] = [max(0, 20 - 7 * $tagged), 20];
            $net = array_sum(array_map(fn ($t) => (float) $t['pnl'], $closed));
            $limit = null;
            if ($m['max_daily_loss'] !== null && (float) $m['max_daily_loss'] > 0) {
                if (($m['daily_limit_type'] ?? 'amount') === 'percent') {
                    $fromUtc = (new \DateTimeImmutable($date . ' 00:00:00', new \DateTimeZone($tz)))->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
                    $before = (float) Database::value("SELECT COALESCE(SUM(pnl), 0) FROM trades WHERE account_id = :a AND status = 'CLOSED' AND executed_at < :d", ['a' => $acc['id'], 'd' => $fromUtc]);
                    $flows = (float) Database::value("SELECT COALESCE(SUM(CASE WHEN type = 'WITHDRAWAL' THEN -ABS(amount) WHEN type = 'DEPOSIT' THEN ABS(amount) ELSE amount END), 0) FROM capital_transactions WHERE account_id = :a AND occurred_at < :d", ['a' => $acc['id'], 'd' => $fromUtc]);
                    $limit = ((float) $acc['starting_capital'] + $flows + $before) * (float) $m['max_daily_loss'] / 100;
                } else {
                    $limit = (float) $m['max_daily_loss'];
                }
            }
            $within = $limit === null ? true : -$net < $limit;
            $parts[$limit === null ? 'Daily loss limit (not set)' : 'Stayed within daily loss limit'] = [$within ? 25 : 0, 25];
        }
        if (!$parts) {
            return null;
        }
        $got = array_sum(array_column($parts, 0));
        $max = array_sum(array_column($parts, 1));
        return ['score' => round($got / $max * 10, 1), 'parts' => $parts];
    }
}
