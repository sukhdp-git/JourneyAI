<?php
declare(strict_types=1);

namespace App\Controllers\Terminal;

use App\Core\Request;
use App\Core\Response;
use App\Trading\Instruments;
use App\Trading\Ledger;
use App\Trading\TradeMath;

/** Position Size & Risk Calculator: Mode A (P&L and R:R) and Mode B (risk-based lot size). */
final class CalculatorController extends TerminalController
{
    public const RISK_PCTS = ['0.25', '0.5', '1', '1.5', '2'];
    public const RISK_FIXED = ['100', '200', '500', '1000'];

    public function index(Request $req): never
    {
        $accounts = [];
        foreach (Ledger::accounts($this->uid) as $a) {
            $b = Ledger::balance($a);
            $accounts[] = ['id' => (int) $a['id'], 'name' => $a['name'], 'type' => $a['account_type'], 'currency' => $a['currency'], 'equity' => $b['equity'], 'demo' => (int) $a['is_demo'], 'demo_data' => (int) $a['has_demo_data']];
        }
        $this->render('calculator', ['calcAccounts' => $accounts, 'instruments' => Instruments::all()], 'Position & Risk Calculator', 'calculator');
    }

    public function compute(Request $req): never
    {
        $num = fn (string $k) => is_numeric($req->post($k)) ? (float) $req->post($k) : null;
        $sym = Instruments::resolve((string) $req->post('symbol')) ?? '';
        $side = $req->post('side') === 'SHORT' ? 'SHORT' : 'LONG';
        $entry = $num('entry');
        $stop = $num('stop');
        $rate = $num('rate');
        if (!$sym || $entry === null || $stop === null) {
            Response::json(['ok' => false, 'errors' => ['Choose an instrument and enter entry and stop loss.']]);
        }
        if ($req->post('mode') === 'size') {
            $acc = Ledger::account($this->uid, $req->int('account_id'));
            if (!$acc) {
                Response::json(['ok' => false, 'errors' => ['Choose one of your accounts.']]);
            }
            $capital = Ledger::balance($acc)['equity'];
            $value = $num('risk_value');
            if ($value === null || $value <= 0) {
                Response::json(['ok' => false, 'errors' => ['Choose how much to risk.']]);
            }
            $percent = $req->post('risk_mode') !== 'fixed';
            if ($percent && $value > 100) {
                Response::json(['ok' => false, 'errors' => ['Risk percentage must be 100% or less.']]);
            }
            $riskAmount = $percent ? $capital * $value / 100 : $value;
            $r = TradeMath::positionSize($sym, $side, $entry, $stop, $num('target'), $riskAmount, $capital, $acc['currency'], $rate);
            $r['capital'] = $capital;
            $r['currency'] = $acc['currency'];
            Response::json($r);
        }
        $target = $num('target');
        $lots = $num('lots');
        if ($target === null || $lots === null) {
            Response::json(['ok' => false, 'errors' => ['Enter an exit or take-profit price and the lot size.']]);
        }
        $cur = in_array($req->post('currency'), \App\Trading\Domain::CURRENCIES, true) ? (string) $req->post('currency') : $this->acc['currency'];
        $r = TradeMath::pnlCalc($sym, $side, $entry, $stop, $target, $lots, $cur, $rate, $req->post('target_kind') === 'exit' ? 'Exit' : 'Take profit');
        $r['currency'] = $cur;
        Response::json($r);
    }
}
