<?php
declare(strict_types=1);

namespace App\Controllers\Terminal;

use App\Core\Database;
use App\Core\Paginator;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Trading\Domain;
use App\Trading\Ledger;
use App\Trading\Members;

/** Trade log: filtered ledger, CRUD, CSV export, screenshots (private storage) and share cards. */
final class TradeController extends TerminalController
{
    private function filters(Request $req): array
    {
        $f = [];
        foreach (['q', 'symbol', 'side', 'session', 'strategy', 'result', 'from', 'to', 'emotion', 'mistake', 'sort', 'dir'] as $k) {
            $v = mb_substr(trim((string) $req->query($k, '')), 0, 60);
            if ($v !== '') {
                $f[$k] = $v;
            }
        }
        return $f;
    }

    public function index(Request $req): never
    {
        $f = $this->filters($req);
        [, $total] = Ledger::trades($this->uid, (int) $this->acc['id'], $f, $this->tz, 1);
        $pg = new Paginator($total, 50, $req->int('page', 1));
        [$rows] = Ledger::trades($this->uid, (int) $this->acc['id'], $f, $this->tz, 50, $pg->offset);
        $symbols = array_column(Database::all('SELECT DISTINCT symbol FROM trades WHERE user_id = :u AND account_id = :a ORDER BY symbol', ['u' => $this->uid, 'a' => $this->acc['id']]), 'symbol');
        $this->render('trades/index', ['rows' => $rows, 'pager' => $pg, 'f' => $f, 'symbols' => $symbols, 'strategies' => Ledger::strategies($this->uid)], 'Trade Log', 'trades');
    }

    public function export(Request $req): never
    {
        [$rows] = Ledger::trades($this->uid, (int) $this->acc['id'], $this->filters($req), $this->tz, 50000);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="JournzeyAI_Trades_' . gmdate('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Date', 'Time', 'Timezone', 'Account', 'Symbol', 'Side', 'Status', 'Entry', 'Exit', 'Stop', 'Take profit', 'Lot size', 'Fees', 'P&L', 'R', 'Strategy', 'Setup', 'Session', 'Emotion', 'Mistake', 'Rules followed', 'Source', 'Notes']);
        $safe = fn ($v) => is_string($v) && preg_match('/^[=+\-@\t\r]/', $v) ? "'" . $v : $v;
        foreach ($rows as $t) {
            fputcsv($out, array_map($safe, [
                fmt_date($t['executed_at'], 'Y-m-d'), fmt_date($t['executed_at'], 'H:i'), $this->tz, $this->acc['name'], $t['symbol'], $t['side'], $t['status'],
                $t['entry_price'], $t['exit_price'], $t['stop_loss'], $t['take_profit'], $t['lot_size'], $t['fees'], $t['pnl'], $t['rr'],
                $t['strategy_name'], $t['setup_tag'], $t['session'], $t['emotion'], $t['mistake_tag'], $t['rules_followed'] ? 'yes' : 'no', $t['source'], $t['notes'],
            ]));
        }
        fclose($out);
        exit;
    }

    private function find(Request $req): array
    {
        return Ledger::trade($this->uid, (int) $req->params['id']) ?? Response::abort(404);
    }

    public function show(Request $req): never
    {
        $t = $this->find($req);
        $acc = Ledger::account($this->uid, (int) $t['account_id']);
        $this->render('trades/show', ['t' => $t, 'tacc' => $acc], $t['symbol'] . ' ' . $t['side'], 'trades');
    }

    public function create(Request $req): never
    {
        $this->requireWritable();
        $this->render('trades/form', ['t' => null, 'strategies' => Ledger::strategies($this->uid, true)], 'New trade', 'trades');
    }

    public function edit(Request $req): never
    {
        $t = $this->find($req);
        $this->requireWritable(Ledger::account($this->uid, (int) $t['account_id']));
        $this->render('trades/form', ['t' => $t, 'strategies' => Ledger::strategies($this->uid)], 'Edit trade', 'trades');
    }

    public function store(Request $req): never
    {
        $this->requireWritable();
        [$errors, $row] = Ledger::validateTrade($this->m, $this->acc, $_POST);
        if ($errors) {
            Session::withErrors($errors, $_POST);
            Response::redirect('/terminal/trades/new');
        }
        $id = Database::insert('trades', $row + ['user_id' => $this->uid, 'account_id' => $this->acc['id'], 'source' => 'MANUAL']);
        $this->back('/terminal/trades/' . $id, 'success', 'Trade saved' . ($row['pnl'] !== null ? ': ' . money($row['pnl'], $this->acc['currency'], true) : ' as OPEN') . '.');
    }

    public function update(Request $req): never
    {
        $t = $this->find($req);
        $acc = Ledger::account($this->uid, (int) $t['account_id']);
        $this->requireWritable($acc);
        [$errors, $row] = Ledger::validateTrade($this->m, $acc, $_POST);
        if ($errors) {
            Session::withErrors($errors, $_POST);
            Response::redirect('/terminal/trades/' . $t['id'] . '/edit');
        }
        Database::update('trades', $row, 'id = :id AND user_id = :u', ['id' => $t['id'], 'u' => $this->uid]);
        $this->back('/terminal/trades/' . $t['id'], 'success', 'Trade updated.');
    }

    public function delete(Request $req): never
    {
        $t = $this->find($req);
        $this->requireWritable(Ledger::account($this->uid, (int) $t['account_id']));
        $this->removeShot($t);
        Database::delete('trades', 'id = :id AND user_id = :u', ['id' => $t['id'], 'u' => $this->uid]);
        Members::audit($this->uid, 'trade_deleted', $t['symbol'] . ' ' . $t['side'] . ' #' . $t['id']);
        $this->back('/terminal/trades', 'success', 'Trade deleted.');
    }

    /**
     * Post-trade runner audit (hypothetical): what if 20% of the position had been kept after the exit with a
     * breakeven stop, for up to 4 hours? Uses real 1-minute history; returns an error instead of inventing prices.
     */
    public function runner(Request $req): never
    {
        $t = $this->find($req);
        if ($t['status'] !== 'CLOSED' || $t['stop_loss'] === null || $t['exit_price'] === null) {
            Response::json(['ok' => false, 'error' => 'The runner audit needs a closed trade with a stop loss.'], 422);
        }
        $from = $t['closed_at'] ?: $t['executed_at'];
        try {
            $candles = \App\Trading\MarketData::candles($t['symbol'], $from, gmdate('Y-m-d H:i:s', strtotime($from . ' UTC') + 4 * 3600));
        } catch (\RuntimeException $e) {
            Response::json(['ok' => false, 'error' => $e->getMessage()], 503);
        }
        $dir = $t['side'] === 'LONG' ? 1 : -1;
        $entry = (float) $t['entry_price'];
        $exit = (float) $t['exit_price'];
        $risk = abs($entry - (float) $t['stop_loss']);
        $best = $exit;
        $out = null;
        foreach ($candles as $c) {
            if (($dir === 1 && $c['l'] <= $entry) || ($dir === -1 && $c['h'] >= $entry)) {
                $out = $entry;
                break;
            }
            $best = $dir === 1 ? max($best, $c['h']) : min($best, $c['l']);
        }
        $runnerExit = $out ?? end($candles)['c'];
        $extraR = $risk > 0 ? 0.2 * ($runnerExit - $exit) * $dir / $risk : 0;
        $extraPnl = $t['risk_amount'] ? $extraR * (float) $t['risk_amount'] : null;
        Response::json(['ok' => true, 'text' => sprintf('Hypothetical 20%% runner: exit %s (%s), %s%.2fR%s. Maximum favourable price after your exit within 4 h: %s. Historical, hypothetical result — not a recommendation.',
            \App\Trading\Instruments::format($t['symbol'], $runnerExit), $out !== null ? 'breakeven stop hit' : 'held 4 hours', $extraR >= 0 ? '+' : '', $extraR,
            $extraPnl !== null ? ' (' . money($extraPnl, Ledger::account($this->uid, (int) $t['account_id'])['currency'], true) . ')' : '', \App\Trading\Instruments::format($t['symbol'], $best))]);
    }

    // --------------------------------------------------------------- screenshots (private, owner-only)
    private function dir(): string
    {
        return STORAGE_PATH . '/private/screenshots/' . $this->uid;
    }

    public function screenshot(Request $req): never
    {
        $t = $this->find($req);
        $path = $t['screenshot_path'] ? $this->dir() . '/' . basename($t['screenshot_path']) : '';
        if (!$path || !is_file($path)) {
            Response::abort(404);
        }
        header('Content-Type: image/webp');
        header('Cache-Control: private, max-age=3600');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    public function uploadScreenshot(Request $req): never
    {
        $t = $this->find($req);
        $this->requireWritable(Ledger::account($this->uid, (int) $t['account_id']));
        $f = $_FILES['screenshot'] ?? null;
        $fail = fn (string $m) => Response::json(['ok' => false, 'error' => $m], 422);
        if (!$f || $f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
            $fail('Choose a PNG, JPEG or WebP image up to 5 MB.');
        }
        if ($f['size'] > 5 * 1024 * 1024) {
            $fail('Screenshots must be 5 MB or smaller.');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
        $ext = strtolower(pathinfo((string) $f['name'], PATHINFO_EXTENSION));
        if (!in_array($mime, ['image/png', 'image/jpeg', 'image/webp'], true) || ($ext !== '' && !in_array($ext, ['png', 'jpg', 'jpeg', 'webp'], true))) {
            $fail('Only PNG, JPEG and WebP images are allowed.');
        }
        $img = @imagecreatefromstring((string) file_get_contents($f['tmp_name']));
        if (!$img) {
            $fail('The image could not be read.');
        }
        if (imagesx($img) > 2400) {
            $img = imagescale($img, 2400);
        }
        if (!is_dir($this->dir())) {
            mkdir($this->dir(), 0750, true);
        }
        $name = 't' . $t['id'] . '-' . bin2hex(random_bytes(6)) . '.webp';
        imagewebp($img, $this->dir() . '/' . $name, 80);
        imagedestroy($img);
        $this->removeShot($t);
        Database::update('trades', ['screenshot_path' => $name], 'id = :id AND user_id = :u', ['id' => $t['id'], 'u' => $this->uid]);
        Response::json(['ok' => true, 'message' => 'Screenshot attached.']);
    }

    public function deleteScreenshot(Request $req): never
    {
        $t = $this->find($req);
        $this->removeShot($t);
        Database::update('trades', ['screenshot_path' => null], 'id = :id AND user_id = :u', ['id' => $t['id'], 'u' => $this->uid]);
        $this->back('/terminal/trades/' . $t['id'], 'success', 'Screenshot removed.');
    }

    private function removeShot(array $t): void
    {
        if ($t['screenshot_path'] && is_file($p = $this->dir() . '/' . basename($t['screenshot_path']))) {
            @unlink($p);
        }
    }
}
