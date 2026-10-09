<?php
declare(strict_types=1);

namespace App\Controllers\Terminal;

use App\Core\Database;
use App\Core\Request;
use App\Trading\Analytics;
use App\Trading\Domain;
use App\Trading\Ledger;

/** Dashboard (date-filtered performance) and the monthly P&L calendar. */
final class InsightsController extends TerminalController
{
    public function dashboard(Request $req): never
    {
        [$from, $to, $key] = $this->range($req);
        $trades = Ledger::forAnalytics($this->uid, (int) $this->acc['id'], $this->tz, $from, $to);
        $sum = Analytics::summarize($trades);
        // Equity starts from the balance at the start of the range so the curve is continuous.
        $before = $from ? Ledger::forAnalytics($this->uid, (int) $this->acc['id'], $this->tz, null, (new \DateTimeImmutable($from))->modify('-1 day')->format('Y-m-d')) : [];
        $start = (float) $this->acc['starting_capital'] + array_sum(array_map(fn ($t) => (float) $t['pnl'], $before));
        $eq = Analytics::equity($trades, $start);
        $label = fn ($t) => fmt_date($t, 'M j');
        $equity = $this->capitalCurve($trades, $from, $to, $label);
        $cum = array_map(fn ($p) => ['x' => $label($p['t']), 'y' => $p['cum']], $eq['points']);
        $dd = array_map(fn ($p) => ['x' => $label($p['t']), 'y' => -$p['dd_pct']], $eq['points']);
        $bars = fn (array $g, ?callable $lab = null) => array_map(fn ($x) => ['label' => $lab ? $lab($x['key']) : $x['key'], 'value' => $x['s']['net'], 'sub' => $x['s']['trades'] . ' trades · ' . pct($x['s']['win_rate'], 0) . ' win'], $g);
        $this->render('dashboard', [
            'sum' => $sum, 'eq' => $eq, 'start' => $start, 'range' => $key, 'from' => $from, 'to' => $to,
            'equity' => $equity, 'cum' => $cum, 'dd' => $dd,
            'byWeekday' => $bars(Analytics::byWeekday($trades, $this->tz)),
            'bySession' => $bars(Analytics::groupBy($trades, fn ($t) => $t['session']), fn ($k) => Domain::SESSIONS[$k] ?? $k),
            'bySymbol' => $bars(Analytics::groupBy($trades, fn ($t) => $t['symbol'])),
            'byStrategy' => $bars(Analytics::groupBy($trades, fn ($t) => $t['strategy_name'] ?: ($t['setup_tag'] ?: 'Unassigned'))),
        ], 'Dashboard', 'dashboard');
    }

    /**
     * Account equity over time including deposits, withdrawals and adjustments (marked as dots), so the curve
     * shows the real capital in the account — not only trading P&L.
     */
    private function capitalCurve(array $trades, ?string $from, ?string $to, callable $label): array
    {
        $tz = new \DateTimeZone($this->tz);
        $utc = fn (string $d, string $t) => (new \DateTimeImmutable($d . ' ' . $t, $tz))->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        $p = ['u' => $this->uid, 'a' => (int) $this->acc['id']];
        $signed = "CASE WHEN type = 'WITHDRAWAL' THEN -ABS(amount) WHEN type = 'DEPOSIT' THEN ABS(amount) ELSE amount END";
        $start = (float) $this->acc['starting_capital'];
        if ($from) {
            $f = $utc($from, '00:00:00');
            $start += (float) Database::value("SELECT COALESCE(SUM(pnl), 0) FROM trades WHERE user_id = :u AND account_id = :a AND status = 'CLOSED' AND executed_at < :f", $p + ['f' => $f]);
            $start += (float) Database::value("SELECT COALESCE(SUM($signed), 0) FROM capital_transactions WHERE user_id = :u AND account_id = :a AND occurred_at < :f", $p + ['f' => $f]);
        }
        $w = '';
        $q = $p;
        if ($from) {
            $w .= ' AND occurred_at >= :f';
            $q['f'] = $utc($from, '00:00:00');
        }
        if ($to) {
            $w .= ' AND occurred_at <= :t';
            $q['t'] = $utc($to, '23:59:59');
        }
        $events = [];
        foreach ($trades as $t) {
            $events[] = [$t['executed_at'], (float) $t['pnl'], null, (int) $t['id']];
        }
        foreach (Database::all("SELECT type, $signed AS amt, occurred_at, id FROM capital_transactions WHERE user_id = :u AND account_id = :a$w", $q) as $c) {
            $events[] = [$c['occurred_at'], (float) $c['amt'], $c['type'], -(int) $c['id']];
        }
        usort($events, fn ($a, $b) => [$a[0], $a[3]] <=> [$b[0], $b[3]]);
        $eq = $start;
        $out = [];
        $names = ['DEPOSIT' => 'Deposit', 'WITHDRAWAL' => 'Withdrawal', 'ADJUSTMENT' => 'Adjustment'];
        foreach ($events as [$at, $amt, $type]) {
            $eq += $amt;
            $pt = ['x' => $label($at), 'y' => round($eq, 2), 'note' => ($type ? $names[$type] . ' ' : '') . ($amt >= 0 ? '+' : '') . number_format($amt, 2)];
            if ($type) {
                $pt['mark'] = $amt >= 0 ? 'in' : 'out';
            }
            $out[] = $pt;
        }
        return ['points' => $out, 'base' => round($start, 2), 'marks' => count(array_filter($out, fn ($x) => isset($x['mark'])))];
    }

    public function calendar(Request $req): never
    {
        $mStr = (string) $req->query('month', '');
        $now = new \DateTimeImmutable('now', new \DateTimeZone($this->tz));
        $month = preg_match('/^\d{4}-\d{2}$/', $mStr) ? new \DateTimeImmutable($mStr . '-01', new \DateTimeZone($this->tz)) : $now->modify('first day of this month')->setTime(0, 0);
        $gridStart = $month->modify('monday this week');
        if ($gridStart > $month) {
            $gridStart = $gridStart->modify('-7 days');
        }
        $gridEnd = $month->modify('last day of this month')->modify('sunday this week');
        $trades = Ledger::forAnalytics($this->uid, (int) $this->acc['id'], $this->tz, $gridStart->format('Y-m-d'), $gridEnd->format('Y-m-d'));
        $days = [];
        foreach ($trades as $t) {
            $d = Analytics::local($t['executed_at'], $this->tz)->format('Y-m-d');
            $days[$d]['pnl'] = ($days[$d]['pnl'] ?? 0) + (float) $t['pnl'];
            $days[$d]['n'] = ($days[$d]['n'] ?? 0) + 1;
            $days[$d]['trades'][] = $t;
        }
        $sel = (string) $req->query('day', '');
        $selTrades = preg_match('/^\d{4}-\d{2}-\d{2}$/', $sel) ? ($days[$sel]['trades'] ?? []) : [];
        $monthTrades = array_filter($trades, fn ($t) => Analytics::local($t['executed_at'], $this->tz)->format('Y-m') === $month->format('Y-m'));
        $this->render('calendar', [
            'month' => $month, 'gridStart' => $gridStart, 'gridEnd' => $gridEnd, 'days' => $days, 'sel' => $sel, 'selTrades' => $selTrades,
            'today' => $now->format('Y-m-d'), 'monthSum' => Analytics::summarize(array_values($monthTrades)),
        ], 'Calendar', 'calendar');
    }
}
