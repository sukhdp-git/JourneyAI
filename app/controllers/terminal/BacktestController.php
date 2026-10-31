<?php
declare(strict_types=1);

namespace App\Controllers\Terminal;

use App\Core\Database;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Response;
use App\Core\Settings;
use App\Trading\Backtest;
use App\Trading\BacktestData;
use App\Trading\Members;

/**
 * Backtester: build a rule-based strategy, choose 2 years of 15-minute (or slower) candles from Twelve Data or a CSV export,
 * and see every simulated trade with its statistics. Members with a plan only (Control Panel switch), with a monthly run limit.
 */
final class BacktestController extends TerminalController
{
    private const MAX_UPLOAD = 12 * 1024 * 1024;

    private function allowed(): bool
    {
        if (!Settings::bool('backtest_enabled')) {
            Response::abort(404);        // hidden until switched on in Control Panel → Backtester
        }
        return !Settings::bool('backtest_paid_only', true) || Members::entitlements($this->m)['paid'];
    }

    private function gate(): void
    {
        if (!$this->allowed()) {
            Response::json(['ok' => false, 'error' => 'The backtester is for members with an active plan.'], 403);
        }
    }

    private function limit(): int
    {
        return max(1, (int) Settings::get('backtest_monthly_runs', '100'));
    }

    private function usedThisMonth(): int
    {
        return (int) Database::value("SELECT COUNT(*) FROM bt_runs WHERE user_id = :u AND created_at >= DATE_FORMAT(UTC_TIMESTAMP(), '%Y-%m-01')", ['u' => $this->uid]);
    }

    public function index(Request $req): never
    {
        if (!$this->allowed()) {
            $this->render('backtest/locked', [], 'Backtester', 'backtest');
        }
        $this->render('backtest/index', [
            'data' => BacktestData::forMember($this->uid),
            'runs' => Database::all('SELECT id, name, symbol, tf, summary, saved, created_at FROM bt_runs WHERE user_id = :u AND (saved = 1 OR result IS NOT NULL) ORDER BY saved DESC, id DESC LIMIT 20', ['u' => $this->uid]),
            'used' => $this->usedThisMonth(), 'limit' => $this->limit(), 'live' => \App\Trading\MarketData::configured(),
        ], 'Backtester', 'backtest');
    }

    /** One page of Twelve Data history (the browser repeats this until done). */
    public function fetch(Request $req): never
    {
        $this->gate();
        if (!RateLimiter::hit('btfetch:' . $this->uid, 40, 60)) {
            Response::json(['ok' => true, 'done' => false, 'wait' => 30, 'progress' => 0, 'rows' => 0]);
        }
        @set_time_limit(60);
        $r = BacktestData::fetchStep(strtoupper($req->str('symbol', 20)));
        if (!empty($r['id']) && ($ds = BacktestData::find($this->uid, (int) $r['id']))) {
            $r += ['first' => (int) $ds['first_ts'], 'last' => (int) $ds['last_ts']];
        }
        Response::json($r);
    }

    public function upload(Request $req): never
    {
        $this->gate();
        if (!RateLimiter::hit('btup:' . $this->uid, 10, 3600)) {
            Response::json(['ok' => false, 'error' => 'Too many uploads. Try again in an hour.'], 429);
        }
        $f = $_FILES['file'] ?? null;
        if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
            $big = in_array($f['error'] ?? 0, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true);
            Response::json(['ok' => false, 'error' => $big ? 'That file is larger than the server allows (12 MB). Export fewer years or a slower timeframe.' : 'Choose a CSV file with your candles.'], 422);
        }
        $ext = strtolower(pathinfo((string) $f['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['csv', 'txt', 'tsv'], true) || $f['size'] > self::MAX_UPLOAD) {
            Response::json(['ok' => false, 'error' => 'Upload a .csv, .txt or .tsv file up to 12 MB.'], 422);
        }
        $content = (string) file_get_contents($f['tmp_name']);
        if (str_starts_with($content, "PK\x03\x04")) {
            Response::json(['ok' => false, 'error' => 'This looks like an Excel file. Save it as CSV first.'], 422);
        }
        if (str_starts_with($content, "\xFF\xFE") || str_contains(substr($content, 0, 400), "\0")) {
            $content = mb_convert_encoding($content, 'UTF-8', 'UTF-16LE');   // MT5 exports are UTF-16
        } elseif (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        }
        @set_time_limit(120);
        $offset = max(-14, min(14, (float) $req->post('offset', 0)));
        $p = BacktestData::parseCsv($content, $offset);
        if ($p['error']) {
            Response::json(['ok' => false, 'error' => $p['error']], 422);
        }
        $symbol = strtoupper(preg_replace('/[^A-Za-z0-9._\/-]/', '', $req->str('symbol', 30)) ?: 'CUSTOM');
        $name = trim($req->str('name', 80)) ?: $symbol . ' (upload)';
        [$id, $err] = BacktestData::saveUpload($this->uid, $name, $symbol, $p['bars']);
        if ($err) {
            Response::json(['ok' => false, 'error' => $err], 422);
        }
        $ds = BacktestData::find($this->uid, $id);
        Response::json(['ok' => true, 'id' => $id, 'skipped' => $p['skipped'], 'dataset' => $ds,
            'message' => number_format((int) $ds['rows_count']) . ' candles saved (' . gmdate('M j, Y', (int) $ds['first_ts']) . ' – ' . gmdate('M j, Y', (int) $ds['last_ts']) . ')' . ($p['skipped'] ? ', ' . $p['skipped'] . ' unreadable rows skipped' : '') . '.']);
    }

    public function deleteDataset(Request $req): never
    {
        $this->gate();
        Response::json(['ok' => BacktestData::deleteOwn($this->uid, (int) ($req->params['id'] ?? 0))]);
    }

    public function run(Request $req): never
    {
        $this->gate();
        if (!RateLimiter::hit('btrun:' . $this->uid, 12, 60)) {
            Response::json(['ok' => false, 'error' => 'Too many tests in a minute. Wait a moment.'], 429);
        }
        if ($this->usedThisMonth() >= $this->limit()) {
            Response::json(['ok' => false, 'error' => 'You have used all ' . $this->limit() . ' backtests for this month. The count resets on the 1st.'], 429);
        }
        $ds = BacktestData::find($this->uid, $req->int('dataset_id'));
        if (!$ds || $ds['status'] !== 'ready' || !(int) $ds['rows_count']) {
            Response::json(['ok' => false, 'error' => 'Load the price history first.'], 422);
        }
        $tfKey = (string) $req->post('tf', '1h');
        $tf = Backtest::TIMEFRAMES[$tfKey] ?? null;
        if (!$tf || $tf < (int) $ds['tf']) {
            Response::json(['ok' => false, 'error' => 'This dataset has ' . array_flip(Backtest::TIMEFRAMES)[(int) $ds['tf']] . ' candles, so test on that timeframe or slower.'], 422);
        }
        [$rules, $err] = Backtest::normalize((array) json_decode((string) $req->post('rules', '{}'), true));
        if ($err) {
            Response::json(['ok' => false, 'error' => $err], 422);
        }
        $last = (int) $ds['last_ts'];
        $from = BacktestData::parseTime((string) $req->post('from', '')) ?? max((int) $ds['first_ts'], $last - BacktestData::YEARS * 365 * 86400);
        $to = BacktestData::parseTime((string) $req->post('to', '')) ?? $last;
        $to = min($to + 86399, $last);
        $from = max($from, (int) $ds['first_ts']);
        if ($to - $from < 7 * 86400) {
            Response::json(['ok' => false, 'error' => 'Choose a test period of at least a week inside the available history.'], 422);
        }
        if ($to - $from > (BacktestData::YEARS + 1) * 366 * 86400) {
            Response::json(['ok' => false, 'error' => 'Choose a period of up to 3 years.'], 422);
        }
        @set_time_limit(90);
        $bars = BacktestData::bars($ds, $tf, $from, $to);
        if (count($bars) < 200) {
            Response::json(['ok' => false, 'error' => 'Not enough candles in that period (' . count($bars) . '). Choose a longer period.'], 422);
        }
        $res = (new Backtest($bars))->run($rules);
        $name = trim($req->str('name', 80)) ?: $ds['symbol'] . ' ' . $tfKey . ' test';
        $summary = $res['stats'] + ['symbol' => $ds['symbol'], 'tf' => $tfKey, 'source' => $ds['source'], 'dataset' => $ds['name'], 'risk_pct' => $rules['risk']['risk_pct']];
        $trades = array_slice($res['trades'], -3000);
        Database::query('INSERT INTO bt_runs (user_id, name, symbol, tf, dataset_id, rules, summary, result) VALUES (:u, :n, :s, :tf, :d, :r, :sm, :res)', [
            'u' => $this->uid, 'n' => $name, 's' => $ds['symbol'], 'tf' => $tfKey, 'd' => $ds['id'], 'r' => json_encode($rules), 'sm' => json_encode($summary),
            'res' => json_encode(['trades' => $trades, 'equity' => $res['equity'], 'monthly' => $res['monthly'], 'breakdown' => $res['breakdown']]),
        ]);
        $id = (int) Database::pdo()->lastInsertId();
        // keep full results for the 25 newest unsaved runs; older unsaved runs keep only their summary
        Database::query('UPDATE bt_runs SET result = NULL WHERE user_id = :u AND saved = 0 AND id < (SELECT m FROM (SELECT id AS m FROM bt_runs WHERE user_id = :u2 AND saved = 0 ORDER BY id DESC LIMIT 1 OFFSET 24) x)', ['u' => $this->uid, 'u2' => $this->uid]);
        Response::json(['ok' => true, 'id' => $id, 'name' => $name, 'rules' => Backtest::describe($rules), 'summary' => $summary, 'trades' => array_slice($trades, -500),
            'trade_count' => count($res['trades']), 'equity' => $res['equity'], 'monthly' => $res['monthly'], 'breakdown' => $res['breakdown'], 'used' => $this->usedThisMonth(), 'limit' => $this->limit()]);
    }

    private function ownRun(Request $req): array
    {
        $r = Database::one('SELECT * FROM bt_runs WHERE id = :id AND user_id = :u', ['id' => (int) ($req->params['id'] ?? 0), 'u' => $this->uid]);
        if (!$r) {
            Response::json(['ok' => false, 'error' => 'Test not found.'], 404);
        }
        return $r;
    }

    public function show(Request $req): never
    {
        $this->gate();
        $r = $this->ownRun($req);
        $res = $r['result'] ? json_decode($r['result'], true) : null;
        $rules = json_decode($r['rules'], true);
        Response::json(['ok' => true, 'id' => (int) $r['id'], 'name' => $r['name'], 'rules_raw' => $rules, 'rules' => Backtest::describe($rules), 'summary' => json_decode($r['summary'], true), 'dataset_id' => (int) $r['dataset_id'],
            'trades' => $res ? array_slice($res['trades'], -500) : [], 'trade_count' => (int) (json_decode($r['summary'], true)['trades'] ?? 0), 'equity' => $res['equity'] ?? [], 'monthly' => $res['monthly'] ?? [], 'breakdown' => $res['breakdown'] ?? [], 'archived' => !$res]);
    }

    public function save(Request $req): never
    {
        $this->gate();
        $r = $this->ownRun($req);
        $name = trim($req->str('name', 80)) ?: $r['name'];
        Database::query('UPDATE bt_runs SET saved = 1, name = :n WHERE id = :id', ['n' => $name, 'id' => $r['id']]);
        Response::json(['ok' => true, 'name' => $name]);
    }

    public function delete(Request $req): never
    {
        $this->gate();
        $r = $this->ownRun($req);
        Database::query('UPDATE bt_runs SET saved = 0, result = NULL, name = :n WHERE id = :id', ['n' => mb_substr('(deleted) ' . $r['name'], 0, 80), 'id' => $r['id']]);
        Response::json(['ok' => true]);
    }

    /** Every trade of a test as CSV (for Excel / Sheets). */
    public function csv(Request $req): never
    {
        if (!$this->allowed()) {
            Response::abort(403);
        }
        $r = Database::one('SELECT * FROM bt_runs WHERE id = :id AND user_id = :u', ['id' => (int) ($req->params['id'] ?? 0), 'u' => $this->uid]);
        $res = $r && $r['result'] ? json_decode($r['result'], true) : null;
        if (!$res) {
            Response::abort(404, 'This test is no longer stored. Run it again to download the trades.');
        }
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="backtest-' . preg_replace('/[^a-z0-9]+/i', '-', $r['name']) . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['#', 'side', 'entry_time_utc', 'entry', 'stop', 'target', 'exit_time_utc', 'exit', 'exit_reason', 'r', 'pnl', 'balance', 'bars', 'session']);
        foreach ($res['trades'] as $i => $t) {
            fputcsv($out, [$i + 1, $t['side'], gmdate('Y-m-d H:i', $t['entry_ts']), $t['entry'], $t['stop'], $t['target'], gmdate('Y-m-d H:i', $t['exit_ts']), $t['exit'], $t['reason'], $t['r'], $t['pnl'], $t['balance'], $t['bars'], $t['session']]);
        }
        fclose($out);
        exit;
    }
}
