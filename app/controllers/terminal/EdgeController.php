<?php
declare(strict_types=1);

namespace App\Controllers\Terminal;

use App\Core\Request;
use App\Core\Response;
use App\Trading\Analytics;
use App\Trading\Domain;
use App\Trading\Ledger;
use App\Trading\MarketData;

/** Edge Matrix: historical edge breakdowns, best trading window, discipline leak, Monte Carlo risk, tilt and runner audit. */
final class EdgeController extends TerminalController
{
    public function index(Request $req): never
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone($this->tz));
        $pmFrom = $now->modify('first day of last month')->format('Y-m-d');
        $pmTo = $now->modify('last day of last month')->format('Y-m-d');
        $all = Ledger::forAnalytics($this->uid, (int) $this->acc['id'], $this->tz);
        $prev = Ledger::forAnalytics($this->uid, (int) $this->acc['id'], $this->tz, $pmFrom, $pmTo);
        // Accounts with only historical data (e.g. the demo journal) analyse their most recent month instead.
        $basis = 'last calendar month (' . $now->modify('first day of last month')->format('F Y') . ')';
        if (!$prev && $all) {
            $lastT = Analytics::local(end($all)['executed_at'], $this->tz);
            $prev = Ledger::forAnalytics($this->uid, (int) $this->acc['id'], $this->tz, $lastT->format('Y-m-01'), $lastT->format('Y-m-t'));
            $basis = 'most recent month with trades (' . $lastT->format('F Y') . ')';
        }
        $strategy = fn ($t) => $t['strategy_name'] ?: ($t['setup_tag'] ?: null);
        $mistake = fn ($t) => $t['mistake_tag'] !== 'NONE' ? (Domain::MISTAKES[$t['mistake_tag']] ?? $t['mistake_tag']) : null;
        $sessions = Analytics::groupBy($all, fn ($t) => Domain::SESSIONS[$t['session']] ?? null);
        $hours = Analytics::byHour($all, $this->tz);
        $symbols = Analytics::groupBy($all, fn ($t) => $t['symbol']);
        $setups = Analytics::groupBy($all, $strategy);
        $weekdays = Analytics::byWeekday($all, $this->tz);
        $sum = Analytics::summarize($all);
        $this->render('edge', [
            'basis' => $basis,
            'prev' => [
                'strategy' => Analytics::groupBy($prev, $strategy), 'session' => Analytics::groupBy($prev, fn ($t) => Domain::SESSIONS[$t['session']] ?? null),
                'instrument' => Analytics::groupBy($prev, fn ($t) => $t['symbol']), 'hour' => Analytics::byHour($prev, $this->tz), 'mistake' => Analytics::groupBy($prev, $mistake),
            ],
            'best' => ['hour' => Analytics::bestByExpectancy($hours), 'session' => Analytics::bestByExpectancy($sessions), 'instrument' => Analytics::bestByExpectancy($symbols), 'setup' => Analytics::bestByExpectancy($setups), 'weekday' => Analytics::bestByExpectancy($weekdays)],
            'leak' => Analytics::disciplineLeak($all), 'evd' => Analytics::emotionalVsDisciplined($all), 'sum' => $sum,
            'mc' => $this->simulate($all, (float) $this->m['default_risk_pct']), 'risk' => (float) $this->m['default_risk_pct'],
            'tiltRule' => [(int) $this->m['tilt_loss_count'], (int) $this->m['tilt_window_minutes'], (int) $this->m['tilt_cooldown_minutes']],
            'tiltHistory' => $this->tiltEpisodes($all), 'marketData' => MarketData::configured(),
        ], 'Edge Matrix', 'edge');
    }

    /** Monte Carlo from historical win rate and average win/loss in R. */
    private function simulate(array $trades, float $riskPct): ?array
    {
        $withR = array_values(array_filter(Analytics::closed($trades), fn ($t) => $t['rr'] !== null));
        if (count($withR) < 10) {
            return null;
        }
        $wins = array_filter($withR, fn ($t) => (float) $t['rr'] > 0);
        $losses = array_filter($withR, fn ($t) => (float) $t['rr'] <= 0);
        $avgWin = $wins ? array_sum(array_map(fn ($t) => (float) $t['rr'], $wins)) / count($wins) : 0;
        $avgLoss = $losses ? abs(array_sum(array_map(fn ($t) => (float) $t['rr'], $losses)) / count($losses)) : 1;
        $first = strtotime($withR[0]['executed_at'] . ' UTC');
        $last = strtotime(end($withR)['executed_at'] . ' UTC');
        $perMonth = count($withR) / max(1, ($last - $first) / (30 * 86400));
        $n = (int) max(20, min(250, round($perMonth * 6)));
        $r = Analytics::monteCarlo(count($wins) / count($withR), $avgWin, max(0.01, $avgLoss), max(0.0025, min(0.05, $riskPct / 100)), $n, 1000);
        return $r + ['win_rate' => count($wins) / count($withR), 'avg_win_r' => round($avgWin, 2), 'avg_loss_r' => round($avgLoss, 2), 'per_month' => round($perMonth, 1)];
    }

    public function monteCarlo(Request $req): never
    {
        $risk = max(0.25, min(5.0, (float) $req->post('risk', '1')));
        $r = $this->simulate(Ledger::forAnalytics($this->uid, (int) $this->acc['id'], $this->tz), $risk);
        if (!$r) {
            Response::json(['ok' => false, 'error' => 'At least 10 closed trades with a stop loss are needed for a simulation.'], 422);
        }
        Response::json(['ok' => true] + $r);
    }

    private function tiltEpisodes(array $all): int
    {
        [$n, $w] = [(int) $this->m['tilt_loss_count'], (int) $this->m['tilt_window_minutes']];
        $losses = array_values(array_filter(Analytics::closed($all), fn ($t) => (float) $t['pnl'] < 0));
        $count = 0;
        for ($i = $n - 1; $i < count($losses); $i++) {
            if (strtotime($losses[$i]['executed_at']) - strtotime($losses[$i - $n + 1]['executed_at']) <= $w * 60) {
                $count++;
                $i += $n - 1;
            }
        }
        return $count;
    }
}
