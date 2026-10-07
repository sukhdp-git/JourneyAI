<?php
declare(strict_types=1);

namespace App\Controllers\Terminal;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Trading\Domain;
use App\Trading\Ingest;
use App\Trading\Ledger;
use App\Trading\Members;

/** Trading accounts, capital flows, CSV statement import and signed webhook connections. */
final class AccountsController extends TerminalController
{
    public function index(Request $req): never
    {
        $accounts = Ledger::accounts($this->uid, true);
        foreach ($accounts as &$a) {
            $a['bal'] = Ledger::balance($a);
            $a['trades'] = (int) Database::value('SELECT COUNT(*) FROM trades WHERE account_id = :a', ['a' => $a['id']]);
        }
        unset($a);
        $this->render('accounts', [
            'list' => $accounts,
            'flows' => Database::all('SELECT * FROM capital_transactions WHERE user_id = :u AND account_id = :a ORDER BY occurred_at DESC, id DESC LIMIT 50', ['u' => $this->uid, 'a' => $this->acc['id']]),
            'liveCount' => Ledger::liveCount($this->uid),
        ], 'Accounts', 'accounts');
    }

    /** Only same-site terminal paths are accepted as a return target. */
    private function safeBack(string $to): string
    {
        return preg_match('#^/terminal(/[a-z0-9/\-]*)?(\?[a-z0-9=&_\-]*)?$#i', $to) ? $to : '/terminal/accounts';
    }

    private function own(Request $req): array
    {
        return Ledger::account($this->uid, (int) $req->params['id']) ?? Response::abort(404);
    }

    private function input(Request $req): array
    {
        $cap = $req->post('starting_capital');
        return [
            'name' => mb_substr(trim((string) $req->post('name', '')), 0, 80),
            'broker_name' => mb_substr(trim((string) $req->post('broker_name', '')), 0, 80) ?: null,
            'account_type' => isset(Domain::ACCOUNT_TYPES[$req->post('account_type')]) ? $req->post('account_type') : 'PERSONAL',
            'currency' => in_array($req->post('currency'), Domain::CURRENCIES, true) ? $req->post('currency') : 'USD',
            'starting_capital' => is_numeric($cap) && (float) $cap >= 0 && (float) $cap < 1e12 ? round((float) $cap, 2) : null,
        ];
    }

    public function store(Request $req): never
    {
        $d = $this->input($req);
        $live = $req->post('mode') === 'live';
        if ($d['name'] === '' || $d['starting_capital'] === null) {
            $this->back('/terminal/accounts', 'error', 'Give the account a name and a starting capital of zero or more.');
        }
        if ($live) {
            if (!$this->m['ent']['live']) {
                $this->back('/terminal/billing', 'error', 'Live accounts need an active paid plan. Demo accounts are free.');
            }
            if (Ledger::liveCount($this->uid) >= (int) $this->m['ent']['max_live']) {
                $this->back('/terminal/accounts', 'error', 'Your plan allows ' . (int) $this->m['ent']['max_live'] . ' live account(s). Archive one or upgrade your plan.');
            }
        }
        if ((int) Database::value('SELECT COUNT(*) FROM trading_accounts WHERE user_id = :u', ['u' => $this->uid]) >= 25) {
            $this->back('/terminal/accounts', 'error', 'You can keep up to 25 accounts. Archive or delete unused ones first.');
        }
        $id = Database::insert('trading_accounts', $d + ['user_id' => $this->uid, 'is_demo' => $live ? 0 : 1]);
        Database::update('user_settings', ['active_account_id' => $id], 'user_id = :u', ['u' => $this->uid]);
        Members::audit($this->uid, 'account_created', ($live ? 'Live' : 'Demo') . ' account #' . $id);
        $this->back('/terminal/accounts', 'success', 'Account created and selected.');
    }

    public function update(Request $req): never
    {
        $acc = $this->own($req);
        $d = $this->input($req);
        if ($d['name'] === '' || $d['starting_capital'] === null) {
            $this->back('/terminal/accounts', 'error', 'Give the account a name and a starting capital of zero or more.');
        }
        if ((int) $acc['has_demo_data']) {
            unset($d['currency']); // demo dataset prices are in USD
        }
        Database::update('trading_accounts', $d, 'id = :id AND user_id = :u', ['id' => $acc['id'], 'u' => $this->uid]);
        $this->back('/terminal/accounts', 'success', 'Account updated.');
    }

    public function archive(Request $req): never
    {
        $acc = $this->own($req);
        if ($req->post('action') === 'delete') {
            if (trim((string) $req->post('confirm_name')) !== $acc['name']) {
                $this->back('/terminal/accounts', 'error', 'Type the account name exactly to delete it.');
            }
            Database::delete('trading_accounts', 'id = :id AND user_id = :u', ['id' => $acc['id'], 'u' => $this->uid]);
            Members::audit($this->uid, 'account_deleted', 'Account #' . $acc['id']);
            $this->back('/terminal/accounts', 'success', 'Account and its trades deleted.');
        }
        $restore = (int) $acc['is_archived'] === 1;
        if ($restore && !(int) $acc['is_demo'] && Ledger::liveCount($this->uid) >= (int) $this->m['ent']['max_live']) {
            $this->back('/terminal/accounts', 'error', 'Your plan does not allow another active live account.');
        }
        Database::update('trading_accounts', ['is_archived' => $restore ? 0 : 1], 'id = :id AND user_id = :u', ['id' => $acc['id'], 'u' => $this->uid]);
        $this->back('/terminal/accounts', 'success', $restore ? 'Account restored.' : 'Account archived. Its trades are kept.');
    }

    public function capital(Request $req): never
    {
        $acc = $this->own($req);
        $this->requireWritable($acc);
        $to = $this->safeBack((string) $req->post('return', ''));
        $type = in_array($req->post('type'), ['DEPOSIT', 'WITHDRAWAL', 'ADJUSTMENT', 'SET'], true) ? $req->post('type') : null;
        $amt = $req->post('amount');
        $when = local_to_utc((string) $req->post('occurred_at', '')) ?? gmdate('Y-m-d H:i:s');
        if (!$type || !is_numeric($amt) || abs((float) $amt) > 1e11) {
            $this->back($to, 'error', 'Choose a type and enter an amount.');
        }
        $amount = round((float) $amt, 2);
        $note = mb_substr(trim((string) $req->post('note', '')), 0, 255) ?: null;
        if ($type === 'SET') {
            // "Edit equity": record the difference as an adjustment. Historical trade P&L is never rewritten.
            $current = Ledger::balance($acc)['equity'];
            if ($amount < 0) {
                $this->back($to, 'error', 'Equity cannot be negative.');
            }
            $diff = round($amount - $current, 2);
            if ($diff == 0.0) {
                $this->back($to, 'info', 'Equity is already ' . money($amount, $acc['currency']) . '.');
            }
            Database::insert('capital_transactions', ['user_id' => $this->uid, 'account_id' => $acc['id'], 'type' => 'ADJUSTMENT', 'amount' => $diff,
                'note' => $note ?? 'Equity set to ' . money($amount, $acc['currency']), 'occurred_at' => $when]);
            $this->back($to, 'success', 'Equity updated to ' . money($amount, $acc['currency']) . ' (adjustment ' . money($diff, $acc['currency'], true) . '). Trade history is unchanged.');
        }
        if ($amount == 0.0 || ($type !== 'ADJUSTMENT' && $amount < 0)) {
            $this->back($to, 'error', 'Enter a positive amount.');
        }
        Database::insert('capital_transactions', ['user_id' => $this->uid, 'account_id' => $acc['id'], 'type' => $type, 'amount' => $amount, 'note' => $note, 'occurred_at' => $when]);
        $this->back($to, 'success', ucfirst(strtolower($type)) . ' of ' . money(abs($amount), $acc['currency']) . ' recorded.');
    }

    public function import(Request $req): never
    {
        $acc = $this->own($req);
        $this->requireWritable($acc);
        if ((int) $acc['has_demo_data']) {
            $this->back('/terminal/accounts', 'error', 'Import into your own account, not the DEMO DATA journal.');
        }
        $f = $_FILES['file'] ?? null;
        if (!$f || $f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
            $this->back('/terminal/accounts', 'error', 'Choose a CSV file to import.');
        }
        $ext = strtolower(pathinfo((string) $f['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['csv', 'txt'], true) || $f['size'] > 5 * 1024 * 1024) {
            $this->back('/terminal/accounts', 'error', 'Upload a .csv file up to 5 MB.');
        }
        $content = (string) file_get_contents($f['tmp_name']);
        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'UTF-16LE,Windows-1252');
        }
        $offset = max(-720, min(840, (int) $req->post('offset_minutes', 0)));
        $parsed = Ingest::parseCsv($content, $offset);
        $n = ['imported' => 0, 'duplicate' => 0];
        $errors = $parsed['errors'];
        Database::transaction(function () use ($parsed, $acc, &$n, &$errors) {
            foreach ($parsed['trades'] as $i => $t) {
                $r = Ingest::insert($this->uid, $acc, $t, 'CSV_IMPORT');
                isset($n[$r]) ? $n[$r]++ : $errors[] = ['trade ' . ($i + 1), $r];
            }
        });
        Members::audit($this->uid, 'csv_import', sprintf('Account #%d: %d imported, %d duplicates, %d errors', $acc['id'], $n['imported'], $n['duplicate'], count($errors)));
        $msg = sprintf('Import finished: %d imported, %d duplicates skipped, %d non-trade rows skipped, %d errors.', $n['imported'], $n['duplicate'], $parsed['skipped'], count($errors));
        if ($errors) {
            $msg .= ' First issues: ' . implode('; ', array_map(fn ($e) => $e[0] . ': ' . $e[1], array_slice($errors, 0, 3)));
        }
        Database::update('user_settings', ['active_account_id' => $acc['id']], 'user_id = :u', ['u' => $this->uid]);
        $this->back('/terminal/accounts', $n['imported'] || !$errors ? 'success' : 'error', $msg);
    }
}
