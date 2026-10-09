<?php
declare(strict_types=1);

namespace App\Controllers\Terminal;

use App\Core\Database;
use App\Core\Paginator;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Trading\AiCoach;
use App\Trading\Domain;
use App\Trading\Instruments;
use App\Trading\Ledger;
use App\Trading\Members;
use App\Trading\Runner;

/** Trade log: filtered ledger, CRUD, CSV export, screenshots (private storage) and share cards. */
final class TradeController extends TerminalController
{
    private function filters(Request $req): array
    {
        $f = [];
        foreach (['q', 'symbol', 'side', 'session', 'strategy', 'result', 'from', 'to', 'emotion', 'mistake', 'sort', 'dir', 'view'] as $k) {
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
        $per = ($f['view'] ?? '') === 'shots' ? 24 : 50;
        $pg = new Paginator($total, $per, $req->int('page', 1));
        [$rows] = Ledger::trades($this->uid, (int) $this->acc['id'], $f, $this->tz, $per, $pg->offset);
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
        $shotErr = $this->formShot($id);
        $this->back('/terminal/trades/' . $id, $shotErr ? 'info' : 'success', 'Trade saved' . ($row['pnl'] !== null ? ': ' . money($row['pnl'], $this->acc['currency'], true) : ' as OPEN') . '.' . ($shotErr ? ' Screenshot not attached: ' . $shotErr : ''));
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
        Database::delete('trade_runner_audits', 'trade_id = :id AND user_id = :u', ['id' => $t['id'], 'u' => $this->uid]);
        $shotErr = $this->formShot((int) $t['id']);
        $this->back('/terminal/trades/' . $t['id'], $shotErr ? 'info' : 'success', 'Trade updated.' . ($shotErr ? ' Screenshot not attached: ' . $shotErr : ''));
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

    /** Runner audit for one trade (hypothetical 20% runner held 2 hours with a breakeven stop). */
    public function runner(Request $req): never
    {
        $t = $this->find($req);
        if (($why = Runner::ineligible($t)) !== null) {
            Response::json(['ok' => false, 'error' => $why], 422);
        }
        $acc = Ledger::account($this->uid, (int) $t['account_id']);
        $demo = (int) $acc['has_demo_data'] === 1;
        if (!$demo && !\App\Trading\MarketData::configured()) {
            Response::json(['ok' => false, 'error' => 'The runner audit needs market data, which has not been set up on this site yet.'], 503);
        }
        $r = Runner::audit($t, $demo);
        if ($r['status'] !== 'ok') {
            Response::json(['ok' => false, 'error' => $r['message']], 503);
        }
        Response::json(['ok' => true, 'text' => sprintf('%sIf 20%% had stayed open for %d more hours (stop at breakeven): runner exit %s (%s), %s%.2fR%s. Hypothetical, historical result — not a recommendation.',
            $demo ? 'DEMO DATA (simulated prices). ' : '', Runner::HOURS, \App\Trading\Instruments::format($t['symbol'], $r['runner_exit']), $r['stopped'] ? 'breakeven stop hit' : 'held ' . Runner::HOURS . ' hours',
            $r['extra_r'] >= 0 ? '+' : '', $r['extra_r'], $r['extra_pnl'] !== null ? ' (' . money($r['extra_pnl'], $acc['currency'], true) . ')' : '')]);
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

    /** Validates an uploaded image and returns it re-encoded (strips metadata and any embedded payload). */
    private static function readImage(?array $f): array
    {
        if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
            return [null, 'Choose a PNG, JPEG or WebP image up to 5 MB.'];
        }
        if ($f['size'] > 5 * 1024 * 1024) {
            return [null, 'Screenshots must be 5 MB or smaller.'];
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
        $ext = strtolower(pathinfo((string) $f['name'], PATHINFO_EXTENSION));
        if (!in_array($mime, ['image/png', 'image/jpeg', 'image/webp'], true) || ($ext !== '' && !in_array($ext, ['png', 'jpg', 'jpeg', 'webp'], true))) {
            return [null, 'Only PNG, JPEG and WebP images are allowed.'];
        }
        $img = @imagecreatefromstring((string) file_get_contents($f['tmp_name']));
        if (!$img) {
            return [null, 'The image could not be read.'];
        }
        if (imagesx($img) > 2400) {
            $img = imagescale($img, 2400);
        }
        return [$img, null];
    }

    /** Stores the image for the trade (replacing any previous one). */
    private function saveShot(array $t, \GdImage $img): void
    {
        if (!is_dir($this->dir())) {
            mkdir($this->dir(), 0750, true);
        }
        $name = 't' . $t['id'] . '-' . bin2hex(random_bytes(6)) . '.webp';
        imagewebp($img, $this->dir() . '/' . $name, 80);
        imagedestroy($img);
        $this->removeShot($t);
        Database::update('trades', ['screenshot_path' => $name], 'id = :id AND user_id = :u', ['id' => $t['id'], 'u' => $this->uid]);
    }

    /** Screenshot chosen in the log/edit form (optional). Returns a message when it could not be saved. */
    private function formShot(int $tradeId): ?string
    {
        $f = $_FILES['screenshot'] ?? null;
        if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        [$img, $err] = self::readImage($f);
        if ($err) {
            return $err;
        }
        $this->saveShot(Ledger::trade($this->uid, $tradeId), $img);
        return null;
    }

    public function uploadScreenshot(Request $req): never
    {
        $t = $this->find($req);
        $this->requireWritable(Ledger::account($this->uid, (int) $t['account_id']));
        [$img, $err] = self::readImage($_FILES['screenshot'] ?? null);
        if ($err) {
            Response::json(['ok' => false, 'error' => $err], 422);
        }
        $this->saveShot($t, $img);
        Response::json(['ok' => true, 'message' => 'Screenshot attached.']);
    }

    /**
     * Reads a chart screenshot with the configured AI provider and returns the values it can see (instrument,
     * side, entry, stop, target). Nothing is stored; the member checks the filled form before saving.
     */
    public function readChart(Request $req): never
    {
        if (!AiCoach::configured()) {
            Response::json(['ok' => false, 'error' => 'Automatic chart reading is not set up on this site — please type the values.'], 503);
        }
        [$img, $err] = self::readImage($_FILES['screenshot'] ?? null);
        if ($err) {
            Response::json(['ok' => false, 'error' => $err], 422);
        }
        if (imagesx($img) > 1600) {
            $img = imagescale($img, 1600);
        }
        ob_start();
        imagejpeg($img, null, 85);
        $jpeg = (string) ob_get_clean();
        imagedestroy($img);
        $r = AiCoach::readChart($jpeg, 'image/jpeg', array_keys(Instruments::all()));
        if (!$r['ok']) {
            Response::json(['ok' => false, 'error' => $r['error']], 502);
        }
        Response::json(['ok' => true, 'fields' => $r['fields'], 'note' => $r['note']]);
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
