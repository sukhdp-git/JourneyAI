<?php
declare(strict_types=1);

namespace App\Trading;

use App\Core\Database;

/**
 * Prop-firm account rules (profit target, daily loss, overall loss — static or trailing —, minimum trading days and
 * consistency) and live progress against them. Presets are TYPICAL rule sets for common evaluation models, not any
 * particular firm's terms: members confirm and edit every value to match their own firm.
 */
final class PropRules
{
    /** key => [label, account_type, target %, daily loss %, overall loss %, overall mode, min trading days, consistency %] */
    public const PRESETS = [
        'two_step_1' => ['2-step challenge · Phase 1', 'PROP_CHALLENGE', 10, 5, 10, 'static', 4, null],
        'two_step_2' => ['2-step challenge · Phase 2 (verification)', 'PROP_CHALLENGE', 5, 5, 10, 'static', 4, null],
        'one_step' => ['1-step challenge', 'PROP_CHALLENGE', 10, 4, 6, 'trailing', 3, null],
        'three_step' => ['3-step challenge (each phase)', 'PROP_CHALLENGE', 6, 4, 8, 'static', 3, null],
        'instant' => ['Instant funding', 'PROP_FUNDED', null, 3, 6, 'trailing', null, 20],
        'funded' => ['Funded account (after passing)', 'PROP_FUNDED', null, 5, 10, 'static', null, null],
        'futures' => ['Futures evaluation', 'PROP_CHALLENGE', 6, null, 4, 'trailing', 5, 30],
        'custom' => ['Custom rules', 'PROP_CHALLENGE', null, null, null, 'static', null, null],
    ];

    public static function isProp(array $acc): bool
    {
        return in_array($acc['account_type'] ?? '', ['PROP_CHALLENGE', 'PROP_FUNDED'], true);
    }

    /** Presets as plain arrays for the add-account form (auto-fill). */
    public static function presetsForJs(): array
    {
        $o = [];
        foreach (self::PRESETS as $k => [$l, $t, $target, $daily, $total, $mode, $days, $cons]) {
            $o[$k] = ['label' => $l, 'type' => $t, 'target' => $target, 'daily' => $daily, 'total' => $total, 'mode' => $mode, 'days' => $days, 'consistency' => $cons];
        }
        return $o;
    }

    /** Validated prop-rule columns from a form post. */
    public static function input(callable $post): array
    {
        $pct = function (string $k, float $max = 100) use ($post): ?float {
            $v = trim((string) $post($k));
            return is_numeric($v) && (float) $v > 0 && (float) $v <= $max ? round((float) $v, 2) : null;
        };
        $days = trim((string) $post('min_trading_days'));
        $preset = (string) $post('prop_preset');
        return [
            'prop_firm' => mb_substr(trim((string) $post('prop_firm')), 0, 80) ?: null,
            'prop_preset' => isset(self::PRESETS[$preset]) ? $preset : null,
            'profit_target_pct' => $pct('profit_target_pct', 1000),
            'max_total_loss_pct' => $pct('max_total_loss_pct'),
            'total_loss_mode' => $post('total_loss_mode') === 'trailing' ? 'trailing' : 'static',
            'min_trading_days' => ctype_digit($days) && (int) $days > 0 && (int) $days <= 365 ? (int) $days : null,
            'consistency_pct' => $pct('consistency_pct'),
        ];
    }

    /**
     * Progress of a prop account against its rules. Overall loss is measured from the starting capital (static) or from
     * the highest closed-trade balance reached (trailing); the daily loss uses the account's daily limit and today's P&L.
     */
    public static function status(array $acc, array $balance, array $limits, string $tz): array
    {
        $cap = (float) $acc['starting_capital'];
        $cur = $acc['currency'];
        $rows = Database::all("SELECT executed_at, pnl FROM trades WHERE account_id = :a AND status = 'CLOSED' ORDER BY COALESCE(closed_at, executed_at), id", ['a' => $acc['id']]);
        $zone = new \DateTimeZone($tz);
        $days = [];
        $run = $cap;
        $peak = $cap;
        foreach ($rows as $r) {
            $run += (float) $r['pnl'];
            $peak = max($peak, $run);
            $d = (new \DateTimeImmutable($r['executed_at'], new \DateTimeZone('UTC')))->setTimezone($zone)->format('Y-m-d');
            $days[$d] = ($days[$d] ?? 0) + (float) $r['pnl'];
        }
        $equity = (float) $balance['equity'];
        $peak = max($peak, $equity);
        $profit = round((float) $balance['pnl'], 2);
        $items = [];
        $breached = false;

        // Profit target
        $targetOk = true;
        if ($acc['profit_target_pct'] !== null) {
            $target = round($cap * (float) $acc['profit_target_pct'] / 100, 2);
            $p = $target > 0 ? max(0, $profit) / $target : 0;
            $targetOk = $profit >= $target;
            $items[] = ['key' => 'target', 'label' => 'Profit target', 'rule' => self::pct($acc['profit_target_pct']) . ' = ' . money($target, $cur),
                'value' => money($profit, $cur, true), 'cls' => $profit > 0 ? 'up' : ($profit < 0 ? 'down' : ''), 'progress' => min(1, $p), 'tone' => $targetOk ? 'good' : 'neutral',
                'note' => $targetOk ? 'Target reached' : money(max(0, $target - $profit), $cur) . ' to go'];
        }
        // Daily loss (shared with the terminal's daily limit banner and beep)
        $dl = $limits['daily'] ?? null;
        if ($dl && $dl['limit'] !== null) {
            $hit = $dl['reached'];
            $breached = $breached || $hit;
            $items[] = ['key' => 'daily', 'label' => 'Daily loss limit', 'rule' => ($acc['daily_limit_type'] === 'percent' ? self::pct($acc['max_daily_loss']) . ' = ' : '') . money($dl['limit'], $cur),
                'value' => money(-$dl['loss'], $cur), 'cls' => $dl['loss'] > 0 ? 'down' : '', 'progress' => min(1, (float) ($dl['used'] ?? 0)), 'tone' => $hit ? 'bad' : (($dl['used'] ?? 0) >= 0.8 ? 'warn' : 'good'),
                'note' => $hit ? 'Daily limit reached — stop for today' : money($dl['remaining'], $cur) . ' left today'];
        }
        // Overall / max loss
        if ($acc['max_total_loss_pct'] !== null) {
            $allowed = round($cap * (float) $acc['max_total_loss_pct'] / 100, 2);
            $trailing = $acc['total_loss_mode'] === 'trailing';
            $floor = round(($trailing ? $peak : $cap) - $allowed, 2);
            $lost = max(0, ($trailing ? $peak : $cap) - $equity);
            $hit = $equity <= $floor;
            $breached = $breached || $hit;
            $items[] = ['key' => 'total', 'label' => $trailing ? 'Max loss (trailing)' : 'Max overall loss', 'rule' => self::pct($acc['max_total_loss_pct']) . ' = ' . money($allowed, $cur) . ($trailing ? ' from peak' : ''),
                'value' => money(-$lost, $cur), 'cls' => $lost > 0 ? 'down' : '', 'progress' => $allowed > 0 ? min(1, $lost / $allowed) : 0, 'tone' => $hit ? 'bad' : ($allowed > 0 && $lost / $allowed >= 0.8 ? 'warn' : 'good'),
                'note' => $hit ? 'Breached — equity at or below ' . money($floor, $cur) : 'Floor ' . money($floor, $cur) . ' · ' . money(max(0, $equity - $floor), $cur) . ' room'];
        }
        // Minimum trading days
        $daysOk = true;
        if ($acc['min_trading_days'] !== null) {
            $n = count($days);
            $need = (int) $acc['min_trading_days'];
            $daysOk = $n >= $need;
            $items[] = ['key' => 'days', 'label' => 'Minimum trading days', 'rule' => $need . ' days', 'value' => $n . ' / ' . $need,
                'progress' => $need ? min(1, $n / $need) : 1, 'tone' => $daysOk ? 'good' : 'neutral', 'note' => $daysOk ? 'Requirement met' : ($need - $n) . ' more day' . ($need - $n === 1 ? '' : 's')];
        }
        // Consistency: best day may not exceed X% of total profit
        $consOk = true;
        if ($acc['consistency_pct'] !== null) {
            $best = $days ? max($days) : 0;
            $share = $profit > 0 ? max(0, $best) / $profit : null;
            $limit = (float) $acc['consistency_pct'] / 100;
            $consOk = $share === null || $share <= $limit;
            $items[] = ['key' => 'consistency', 'label' => 'Consistency rule', 'rule' => 'Best day ≤ ' . self::pct($acc['consistency_pct']) . ' of profit',
                'value' => $share === null ? '—' : round($share * 100) . '%', 'progress' => $share === null ? 0 : min(1, $share / max(0.01, $limit)), 'tone' => $consOk ? 'good' : 'warn',
                'note' => $share === null ? 'Applies once in profit' : ($consOk ? 'Within the rule' : 'Best day too large — spread profit over more days')];
        }
        $status = $breached ? 'breached' : ($acc['profit_target_pct'] !== null && $targetOk && $daysOk && $consOk ? 'passed' : 'active');
        return ['items' => $items, 'status' => $status, 'firm' => $acc['prop_firm'], 'preset' => self::PRESETS[$acc['prop_preset'] ?? ''][0] ?? null, 'trading_days' => count($days)];
    }

    private static function pct($v): string
    {
        return rtrim(rtrim(number_format((float) $v, 2), '0'), '.') . '%';
    }
}
