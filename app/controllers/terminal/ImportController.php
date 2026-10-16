<?php
declare(strict_types=1);

namespace App\Controllers\Terminal;

use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Trading\BrokerImport;
use App\Trading\Ingest;
use App\Trading\Ledger;
use App\Trading\Members;

/**
 * Import trade history from any broker's CSV export:
 *   upload → the columns are matched automatically and shown for checking → preview → import → dashboard.
 * The uploaded file is kept privately (storage/private/imports) only until the import finishes or is cancelled,
 * and for at most a day.
 */
final class ImportController extends TerminalController
{
    private const DIR = STORAGE_PATH . '/private/imports';
    private const MAX_BYTES = 5 * 1024 * 1024;

    /** Own accounts trades can be imported into: not the DEMO DATA journal, not read-only live accounts. */
    private function targets(): array
    {
        return array_values(array_filter(Ledger::accounts($this->uid), fn ($a) => !(int) $a['has_demo_data'] && Ledger::writable($this->m, $a)));
    }

    private function file(): ?array
    {
        $s = Session::get('import');
        if (!is_array($s) || !preg_match('/^[a-f0-9]{32}$/', (string) ($s['token'] ?? ''))) {
            return null;
        }
        $path = self::DIR . '/' . $this->uid . '-' . $s['token'] . '.csv';
        $acc = Ledger::account($this->uid, (int) $s['account']);
        if (!is_file($path) || !$acc || (int) $acc['has_demo_data'] || !Ledger::writable($this->m, $acc)) {
            return null;
        }
        return $s + ['path' => $path, 'acc' => $acc];
    }

    private function discard(?array $f): void
    {
        if ($f && is_file($f['path'])) {
            @unlink($f['path']);
        }
        Session::forget('import');
    }

    public function index(Request $req): never
    {
        $this->render('import', ['stage' => 'upload', 'targets' => $this->targets(), 'pending' => $this->file()], 'Import trades', 'trades');
    }

    public function upload(Request $req): never
    {
        $acc = Ledger::account($this->uid, (int) $req->post('account_id', $this->acc['id']));
        if (!$acc || (int) $acc['has_demo_data']) {
            $this->back('/terminal/import', 'error', 'Choose one of your own accounts — the DEMO DATA journal stays separate.');
        }
        $this->requireWritable($acc);
        $f = $_FILES['file'] ?? null;
        if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
            $this->back('/terminal/import', 'error', ($f['error'] ?? 0) === UPLOAD_ERR_INI_SIZE || ($f['error'] ?? 0) === UPLOAD_ERR_FORM_SIZE ? 'That file is larger than 5 MB.' : 'Choose a CSV file to import.');
        }
        $ext = strtolower(pathinfo((string) $f['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['csv', 'txt', 'tsv'], true) || $f['size'] > self::MAX_BYTES) {
            $this->back('/terminal/import', 'error', 'Upload a .csv (or .txt/.tsv) file up to 5 MB. Excel files: use File → Save As → CSV first.');
        }
        $content = (string) file_get_contents($f['tmp_name']);
        if (str_starts_with($content, "PK\x03\x04") || str_contains(substr($content, 0, 2048), "\0\0")) {
            $this->back('/terminal/import', 'error', 'This looks like an Excel or binary file. Open it and use File → Save As → CSV, then upload the CSV.');
        }
        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', str_starts_with($content, "\xFF\xFE") ? 'UTF-16LE' : 'Windows-1252');
        }
        $det = BrokerImport::detect($content);
        if (!$det['ok']) {
            $this->back('/terminal/import', 'error', $det['error']);
        }
        $this->discard($this->file());
        if (!is_dir(self::DIR)) {
            @mkdir(self::DIR, 0750, true);
            @file_put_contents(self::DIR . '/.htaccess', "Require all denied\n");
        }
        foreach (glob(self::DIR . '/*.csv') ?: [] as $old) {
            if (filemtime($old) < time() - 86400) {
                @unlink($old); // abandoned uploads
            }
        }
        $token = bin2hex(random_bytes(16));
        file_put_contents(self::DIR . '/' . $this->uid . '-' . $token . '.csv', $content);
        Session::set('import', ['token' => $token, 'account' => (int) $acc['id'], 'name' => mb_substr(basename((string) $f['name']), 0, 80)]);
        \App\Core\Response::redirect('/terminal/import/map');
    }

    /** Mapping options from the form, falling back to the detected ones. */
    private function options(Request $req, array $det): array
    {
        $map = [];
        foreach (array_keys(BrokerImport::FIELDS) as $k) {
            $v = $req->post('map_' . $k, null);
            $map[$k] = $v === null ? ($det['mapping'][$k] ?? null) : ($v === '' || !ctype_digit((string) $v) || (int) $v >= count($det['headers']) ? null : (int) $v);
        }
        $offset = (string) $req->post('offset', 'member');
        $minutes = $offset === 'member'
            ? (int) round((new \DateTimeZone($this->tz))->getOffset(new \DateTime('now', new \DateTimeZone('UTC'))) / 60)
            : max(-720, min(840, (int) $offset));
        return [
            'map' => $map,
            'mode' => $req->post('mode', $det['mode']) === 'fills' ? 'fills' : 'trades',
            'date_order' => in_array($req->post('date_order'), ['dmy', 'mdy', 'ymd'], true) ? $req->post('date_order') : $det['date_order'],
            'offset_key' => $offset, 'offset' => $minutes,
            'size_unit' => in_array($req->post('size_unit'), ['lots', 'units'], true) ? $req->post('size_unit') : 'auto',
        ];
    }

    public function map(Request $req): never
    {
        $f = $this->file() ?? $this->back('/terminal/import', 'error', 'Upload a file to start an import.');
        $det = BrokerImport::detect((string) file_get_contents($f['path']));
        if (!$det['ok']) {
            $this->discard($f);
            $this->back('/terminal/import', 'error', $det['error']);
        }
        $this->render('import', ['stage' => 'map', 'imp' => $f, 'det' => $det, 'opt' => $this->options($req, $det), 'preview' => null], 'Import trades', 'trades');
    }

    public function run(Request $req): never
    {
        $f = $this->file() ?? $this->back('/terminal/import', 'error', 'The upload expired. Please upload the file again.');
        if ($req->post('action') === 'cancel') {
            $this->discard($f);
            $this->back('/terminal/import', 'success', 'Import cancelled. The uploaded file was deleted.');
        }
        $content = (string) file_get_contents($f['path']);
        $det = BrokerImport::detect($content);
        if (!$det['ok']) {
            $this->discard($f);
            $this->back('/terminal/import', 'error', $det['error']);
        }
        $opt = $this->options($req, $det);
        $parsed = BrokerImport::parse($content, $opt['map'], $opt);
        if ($req->post('action') !== 'import' || !$parsed['trades']) {
            $this->render('import', ['stage' => 'map', 'imp' => $f, 'det' => $det, 'opt' => $opt, 'preview' => $parsed], 'Import trades', 'trades');
        }
        $acc = $f['acc'];
        $this->requireWritable($acc);
        $n = ['imported' => 0, 'duplicate' => 0];
        $errors = $parsed['errors'];
        Database::transaction(function () use ($parsed, $acc, &$n, &$errors) {
            foreach ($parsed['trades'] as $i => $t) {
                $r = Ingest::insert($this->uid, $acc, $t, 'CSV_IMPORT');
                isset($n[$r]) ? $n[$r]++ : $errors[] = [$t['symbol'] . ' ' . substr($t['executed_at'], 0, 10), $r];
            }
        });
        $this->discard($f);
        Members::audit($this->uid, 'csv_import', sprintf('Account #%d (%s): %d imported, %d duplicates, %d skipped, %d errors', $acc['id'], $f['name'], $n['imported'], $n['duplicate'], $parsed['skipped'], count($errors)));
        Database::update('user_settings', ['active_account_id' => $acc['id']], 'user_id = :u', ['u' => $this->uid]);
        $msg = sprintf('Import into %s finished: %d trade%s imported', $acc['name'], $n['imported'], $n['imported'] === 1 ? '' : 's');
        $msg .= $parsed['open'] ? sprintf(' (%d still open)', $parsed['open']) : '';
        $msg .= sprintf(', %d duplicate%s skipped, %d non-trade row%s skipped, %d row%s with problems.', $n['duplicate'], $n['duplicate'] === 1 ? '' : 's', $parsed['skipped'], $parsed['skipped'] === 1 ? '' : 's', count($errors), count($errors) === 1 ? '' : 's');
        if ($errors) {
            $msg .= ' First: ' . implode('; ', array_map(fn ($e) => $e[0] . ' — ' . $e[1], array_slice($errors, 0, 3)));
        }
        $msg .= $n['imported'] ? ' Your dashboard below now includes this history.' : '';
        $this->back($n['imported'] ? '/terminal/dashboard?range=all' : '/terminal/import', $n['imported'] || !$errors ? 'success' : 'error', $msg);
    }
}
