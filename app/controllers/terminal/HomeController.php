<?php
declare(strict_types=1);

namespace App\Controllers\Terminal;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Trading\Analytics;
use App\Trading\Domain;
use App\Trading\Instruments;
use App\Trading\Ledger;
use App\Trading\MarketData;
use App\Trading\Members;
use App\Trading\QuickTrade;
use App\Trading\Sessions;
use App\Trading\TradeMath;

/** Home Hub: market ticker, world clocks, discipline quote, 9-step checklist, quick trade, tilt breaker. */
final class HomeController extends TerminalController
{
    public function index(Request $req): never
    {
        $today = (new \DateTimeImmutable('now', new \DateTimeZone($this->tz)))->format('Y-m-d');
        $done = array_column(Database::all('SELECT item_key FROM checklist_entries WHERE user_id = :u AND entry_date = :d', ['u' => $this->uid, 'd' => $today]), 'item_key');
        $trades = Ledger::forAnalytics($this->uid, (int) $this->acc['id'], $this->tz, $today, $today);
        $recent = Ledger::forAnalytics($this->uid, (int) $this->acc['id'], $this->tz, (new \DateTimeImmutable('-2 days'))->format('Y-m-d'));
        $tilt = Analytics::tilt($recent, (int) $this->m['tilt_loss_count'], (int) $this->m['tilt_window_minutes'], (int) $this->m['tilt_cooldown_minutes']);
        $todaySum = Analytics::summarize($trades);
        [$latest] = Ledger::trades($this->uid, (int) $this->acc['id'], [], $this->tz, 6);
        $quote = Domain::QUOTES[(int) floor(time() / 3600) % count(Domain::QUOTES)];
        $this->render('home', [
            'market' => MarketData::quotes(), 'done' => $done, 'today' => $today, 'tilt' => $tilt, 'todaySum' => $todaySum, 'latest' => $latest, 'quote' => $quote,
            'budget' => $this->m['max_daily_loss'] !== null ? (float) $this->m['max_daily_loss'] + min(0, (float) $todaySum['net']) : null,
            'hasDemoData' => (bool) Database::value('SELECT id FROM trading_accounts WHERE user_id = :u AND has_demo_data = 1', ['u' => $this->uid]),
        ], 'Home Hub', 'home');
    }

    public function parse(Request $req): never
    {
        $p = QuickTrade::parse(mb_substr((string) $req->post('command', ''), 0, 300));
        if (!$p['errors'] && $p['exit'] !== null) {
            $calc = TradeMath::compute($p['symbol'], $p['side'], $p['entry'], $p['exit'], $p['stop'], $p['lots'], 0, $this->acc['currency']);
            $p['pnl'] = $calc['pnl'];
            $p['pnl_fmt'] = $calc['pnl'] === null ? 'broker P&L needed' : money($calc['pnl'], $this->acc['currency'], true);
        }
        Response::json(['ok' => true, 'parsed' => $p]);
    }

    public function quickTrade(Request $req): never
    {
        $this->requireWritable();
        $tilt = $this->tiltActive();
        if ($tilt) {
            Response::json(['ok' => false, 'error' => 'JOURNZEY TERMINAL LOCK: tilt cooldown is active until ' . fmt_date(gmdate('Y-m-d H:i:s', $tilt), 'H:i') . '. Quick logging is paused; use the full trade form if you must record a trade.'], 423);
        }
        $p = QuickTrade::parse(mb_substr((string) $req->post('command', ''), 0, 300));
        if ($p['errors']) {
            Response::json(['ok' => false, 'error' => implode(' ', $p['errors']), 'parsed' => $p], 422);
        }
        $strategyId = null;
        if ($p['setup']) {
            $strategyId = Database::value('SELECT id FROM strategies WHERE user_id = :u AND name = :n', ['u' => $this->uid, 'n' => $p['setup']]);
        }
        $in = [
            'symbol' => $p['symbol'], 'side' => $p['side'], 'executed_at' => '', 'entry_price' => (string) $p['entry'], 'exit_price' => $p['exit'] === null ? '' : (string) $p['exit'],
            'stop_loss' => $p['stop'] === null ? '' : (string) $p['stop'], 'take_profit' => $p['tp'] === null ? '' : (string) $p['tp'], 'lot_size' => (string) $p['lots'],
            'strategy_id' => $strategyId ?: '', 'setup_tag' => $p['setup'] ?? '', 'emotion' => $p['emotion'] ?? '', 'mistake_tag' => $p['mistake'] ?? 'NONE',
            'rules_followed' => $p['mistake'] ? '0' : '1', 'notes' => 'Quick command: ' . $req->post('command'),
        ];
        [$errors, $row] = Ledger::validateTrade($this->m, $this->acc, $in);
        if ($errors) {
            Response::json(['ok' => false, 'error' => implode(' ', $errors), 'parsed' => $p], 422);
        }
        $id = Database::insert('trades', $row + ['user_id' => $this->uid, 'account_id' => $this->acc['id'], 'source' => 'QUICK_COMMAND']);
        Response::json(['ok' => true, 'id' => $id, 'message' => $row['symbol'] . ' ' . $row['side'] . ' logged' . ($row['pnl'] !== null ? ' · ' . money($row['pnl'], $this->acc['currency'], true) : ' as OPEN') . '.']);
    }

    private function tiltActive(): ?int
    {
        $recent = Ledger::forAnalytics($this->uid, (int) $this->acc['id'], $this->tz, (new \DateTimeImmutable('-2 days'))->format('Y-m-d'));
        $t = Analytics::tilt($recent, (int) $this->m['tilt_loss_count'], (int) $this->m['tilt_window_minutes'], (int) $this->m['tilt_cooldown_minutes']);
        return $t['active'] ? (int) $t['ends_at'] : null;
    }

    public function checklist(Request $req): never
    {
        $item = (string) $req->post('item');
        $all = array_merge(...array_values(array_map('array_keys', Domain::CHECKLIST)));
        $date = (string) $req->post('date');
        if (!in_array($item, $all, true) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            Response::json(['ok' => false, 'error' => 'Invalid checklist item.'], 422);
        }
        if ($req->post('done') === '1') {
            Database::query('INSERT IGNORE INTO checklist_entries (user_id, entry_date, item_key) VALUES (:u, :d, :i)', ['u' => $this->uid, 'd' => $date, 'i' => $item]);
        } else {
            Database::delete('checklist_entries', 'user_id = :u AND entry_date = :d AND item_key = :i', ['u' => $this->uid, 'd' => $date, 'i' => $item]);
        }
        $done = (int) Database::value('SELECT COUNT(*) FROM checklist_entries WHERE user_id = :u AND entry_date = :d', ['u' => $this->uid, 'd' => $date]);
        Response::json(['ok' => true, 'done' => $done, 'total' => count($all), 'pct' => (int) round($done / count($all) * 100)]);
    }

    public function ticker(Request $req): never
    {
        Response::json(MarketData::quotes());
    }

    public function lotSize(Request $req): never
    {
        $sym = Instruments::resolve((string) $req->post('symbol')) ?? '';
        $r = $sym ? TradeMath::lotSize($sym, (float) $req->post('equity'), (float) $req->post('risk'), (float) $req->post('entry'), (float) $req->post('stop'), $this->acc['currency']) : null;
        Response::json($r ? ['ok' => true] + ['lots' => $r['lots'], 'risk_amount' => money($r['risk_amount'], $this->acc['currency']), 'per_lot_risk' => money($r['per_lot_risk'], $this->acc['currency'])] : ['ok' => false, 'error' => 'Check the instrument, entry and stop. Instruments not quoted in ' . $this->acc['currency'] . ' cannot be sized automatically.']);
    }

    public function switchAccount(Request $req): never
    {
        $acc = Ledger::account($this->uid, $req->int('account_id'));
        if ($acc && !(int) $acc['is_archived']) {
            Database::update('user_settings', ['active_account_id' => $acc['id']], 'user_id = :u', ['u' => $this->uid]);
        }
        Response::back('/terminal');
    }

    public function loadDemo(Request $req): never
    {
        Ledger::loadDemo($this->m);
        Members::refresh();
        $this->back('/terminal/dashboard', 'success', 'Demo journal loaded into “Demo Journal (sample data)”. It is marked DEMO DATA and kept separate from your other accounts.');
    }
}
