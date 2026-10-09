<?php
declare(strict_types=1);

namespace App\Trading;

use App\Core\Database;

/**
 * Daily and weekly loss limits — set separately on each trading account — configured as a fixed amount or as a % of the
 * account capital at the start of the day/week. Journal-level warnings only — nothing here can block
 * orders at a broker.
 */
final class RiskLimits
{
    public static function status(array $m, array $acc, array $balance, string $tz): array
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone($tz));
        $today = $now->format('Y-m-d');
        $monday = $now->modify('monday this week')->format('Y-m-d');
        $out = [];
        foreach (['daily' => [$today, 'max_daily_loss', 'daily_limit_type'], 'weekly' => [$monday, 'max_weekly_loss', 'weekly_limit_type']] as $k => [$from, $col, $typeCol]) {
            $trades = Ledger::forAnalytics((int) $m['id'], (int) $acc['id'], $tz, $from, $today);
            $net = round(array_sum(array_map(fn ($t) => (float) $t['pnl'], $trades)), 2);
            $loss = max(0.0, -$net);
            $base = (float) $balance['equity'] - $net;
            $value = $acc[$col] ?? null;
            $type = ($acc[$typeCol] ?? 'amount') === 'percent' ? 'percent' : 'amount';
            $limit = null;
            if ($value !== null && $value !== '' && (float) $value > 0) {
                $limit = $type === 'percent' ? round($base * (float) $value / 100, 2) : round((float) $value, 2);
            }
            $risked = (float) Database::value(
                'SELECT COALESCE(SUM(risk_amount), 0) FROM trades WHERE user_id = :u AND account_id = :a AND executed_at >= :f',
                ['u' => $m['id'], 'a' => $acc['id'], 'f' => (new \DateTimeImmutable($from . ' 00:00:00', new \DateTimeZone($tz)))->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s')]
            );
            $used = $limit ? $loss / $limit : null;
            $out[$k] = [
                'net' => $net, 'loss' => round($loss, 2), 'risked' => round($risked, 2), 'base' => round($base, 2), 'type' => $type,
                'value' => $value === null ? null : (float) $value, 'limit' => $limit, 'used' => $used, 'remaining' => $limit === null ? null : round(max(0, $limit - $loss), 2),
                'reached' => $limit !== null && $loss >= $limit, 'warning' => $limit !== null && $loss < $limit && $used >= 0.8, 'trades' => count($trades),
            ];
        }
        return $out;
    }
}
