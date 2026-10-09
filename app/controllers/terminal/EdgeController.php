<?php
declare(strict_types=1);

namespace App\Controllers\Terminal;

use App\Core\Request;
use App\Core\Response;
use App\Trading\MarketData;
use App\Trading\Runner;
use App\Trading\Analytics;
use App\Trading\Edge;
use App\Trading\Ledger;

/**
 * Edge Matrix: Last Month Audit, Best Trading Window (+ A+ setup), Anti-Window, Ruin Probability Radar,
 * Discipline Leak counter and the tilt rule. Historical/statistical analysis only.
 */
final class EdgeController extends TerminalController
{
    /** "Blown" = the account falls this far from its peak. */
    public const THRESHOLDS = ['5' => '−5% (prop daily limit)', '10' => '−10% (prop max loss)', '20' => '−20%', '50' => '−50% (account ruined)'];

    public function index(Request $req): never
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone($this->tz));
        $all = Ledger::forAnalytics($this->uid, (int) $this->acc['id'], $this->tz);
        $period = function (string $from, string $to) use ($all) {
            return array_values(array_filter($all, fn ($t) => ($d = Analytics::local($t['executed_at'], $this->tz)->format('Y-m-d')) >= $from && $d <= $to));
        };
        // Last Month Audit: previous calendar month; accounts with only older data use their latest month with trades.
        $pm = $now->modify('first day of last month');
        $prev = $period($pm->format('Y-m-01'), $pm->format('Y-m-t'));
        $auditLabel = $pm->format('F Y');
        if (!$prev && $all) {
            $last = Analytics::local(end($all)['executed_at'], $this->tz);
            $prev = $period($last->format('Y-m-01'), $last->format('Y-m-t'));
            $auditLabel = $last->format('F Y') . ' (most recent month with trades)';
        }
        // Discipline leak: this month, falling back to the most recent month with trades.
        $month = $period($now->format('Y-m-01'), $now->format('Y-m-d'));
        $leakLabel = 'this month';
        if (!$month && $all) {
            $last = Analytics::local(end($all)['executed_at'], $this->tz);
            $month = $period($last->format('Y-m-01'), $last->format('Y-m-t'));
            $leakLabel = 'in ' . $last->format('F Y');
        }
        $defaultTh = str_starts_with((string) $this->acc['account_type'], 'PROP') ? '10' : '20';
        $th = array_key_exists((string) $req->query('dd'), self::THRESHOLDS) ? (string) $req->query('dd') : $defaultTh;
        $last30 = array_values(array_filter($all, fn ($t) => strtotime($t['executed_at'] . ' UTC') >= time() - 30 * 86400));
        $best = Edge::bestWindow($all);
        $balance = Ledger::balance($this->acc);
        $this->render('edge', [
            'sum' => Analytics::summarize($all), 'audit' => Edge::audit($prev, $this->tz), 'auditLabel' => $auditLabel,
            'best' => $best, 'aplus' => Edge::aPlus($best), 'dims' => Edge::bestDimensions($all, $this->tz), 'anti' => Edge::antiWindow($all),
            'radar' => Edge::ruinRadar($all, (float) $balance['equity'], (float) $this->m['default_risk_pct'], (int) $th / 100), 'th' => $th,
            'leak' => Edge::leak($month, $this->tz), 'leakLabel' => $leakLabel,
            'last30' => Analytics::summarize($last30),
            'runner' => Runner::summary($this->uid, (int) $this->acc['id']), 'runnerPending' => Runner::pendingCount($this->uid, (int) $this->acc['id']),
            'runnerReady' => (int) $this->acc['has_demo_data'] === 1 || MarketData::configured(),
            'tiltRule' => [(int) $this->m['tilt_loss_count'], (int) $this->m['tilt_window_minutes'], (int) $this->m['tilt_cooldown_minutes']],
        ], 'Edge Matrix', 'edge');
    }

    /** Audits the next batch of eligible trades for the 20% runner analysis (JSON). */
    public function runner(Request $req): never
    {
        $demo = (int) $this->acc['has_demo_data'] === 1;
        if (!$demo && !MarketData::configured()) {
            Response::json(['ok' => false, 'error' => 'The runner audit needs market data. The site owner can add a Twelve Data key in Control Panel → Integrations.'], 503);
        }
        $done = 0;
        $failed = null;
        foreach (Runner::pending($this->uid, (int) $this->acc['id']) as $t) {
            $r = Runner::audit($t, $demo);
            if ($r['status'] === 'ok') {
                $done++;
            } else {
                $failed = $r['message'];
            }
        }
        Response::json(['ok' => $done > 0 || $failed === null, 'done' => $done, 'remaining' => Runner::pendingCount($this->uid, (int) $this->acc['id']), 'error' => $done === 0 ? $failed : null]);
    }
}
