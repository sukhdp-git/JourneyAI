<?php
declare(strict_types=1);

namespace App\Controllers\Terminal;

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
        $equity = array_map(fn ($p) => ['x' => $label($p['t']), 'y' => $p['equity'], 'note' => ($p['pnl'] >= 0 ? '+' : '') . number_format($p['pnl'], 2)], $eq['points']);
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
