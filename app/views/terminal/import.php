<?php
/**
 * Import trades from any broker CSV: upload → check the matched columns → preview → import.
 * @var string $stage upload|map
 */
use App\Trading\BrokerImport;

$offsets = ['member' => 'Same as my timezone (' . $tz . ')', '0' => 'UTC / GMT+0', '60' => 'GMT+1', '120' => 'GMT+2 (most MT4/MT5 servers in winter)', '180' => 'GMT+3 (most MT4/MT5 servers in summer)', '-240' => 'GMT−4 (New York, summer)', '-300' => 'GMT−5 (New York, winter)', '330' => 'GMT+5:30 (India)', '240' => 'GMT+4 (Dubai)', '480' => 'GMT+8 (Singapore / Hong Kong)', '540' => 'GMT+9 (Tokyo)', '600' => 'GMT+10 (Sydney)'];
?>
<?php if ($stage === 'upload'): ?>
<section class="panel stack imp">
  <div class="panel-head"><h2><?= icon('upload', 'icon') ?> Import trade history from any broker</h2><a class="tm-btn tm-btn-sm" href="<?= e(url('/terminal/trades')) ?>">← Trade Log</a></div>
  <p class="muted">Upload the CSV your broker or platform exports. journzey matches the columns for you, shows what it found, and lets you correct anything before a single trade is saved. Your dashboard, calendar, Edge Matrix and AI Coach then analyse the imported history.</p>
  <ul class="imp-list small">
    <li><strong>Closed-trade reports</strong> — one row per trade (MT4/MT5 history, cTrader, NinjaTrader, TradeLocker, DXtrade, Match-Trader, prop-firm dashboards…).</li>
    <li><strong>Execution / fill reports</strong> — one row per buy or sell (Zerodha, Upstox, Binance, Bybit, Interactive Brokers, TradingView order history…). Buys and sells are paired first-in-first-out into round trips; what is still open is imported as an open trade.</li>
    <li>Duplicates are skipped by ticket number, so importing the same file twice is safe.</li>
  </ul>
  <?php if (!empty($pending)): ?><p class="small"><?= icon('clock', 'icon icon-xs') ?> You have an unfinished import of <strong><?= e($pending['name']) ?></strong>. <a href="<?= e(url('/terminal/import/map')) ?>">Continue it</a> or upload a new file below.</p><?php endif; ?>
  <?php if (!$targets): ?>
    <p class="muted small">There is no account to import into: the DEMO DATA journal stays separate and live accounts need an active plan. <a href="<?= e(url('/terminal/accounts')) ?>">Add a demo account</a> or <a href="<?= e(url('/pricing')) ?>">see plans</a>.</p>
  <?php else: ?>
  <form method="post" action="<?= e(url('/terminal/import')) ?>" enctype="multipart/form-data" class="stack"><?= csrf_field() ?>
    <div class="grid-form">
      <div class="f"><label for="imp-acc">Import into account</label><select id="imp-acc" name="account_id"><?php foreach ($targets as $a): ?><option value="<?= (int) $a['id'] ?>"<?= (int) $a['id'] === (int) $acc['id'] ? ' selected' : '' ?>><?= e($a['name']) ?> · <?= (int) $a['is_demo'] ? 'DEMO' : 'LIVE' ?> · <?= e($a['currency']) ?></option><?php endforeach; ?></select></div>
      <div class="f"><label for="imp-file">CSV file (max 5 MB)</label><input id="imp-file" type="file" name="file" accept=".csv,.txt,.tsv,text/csv,text/plain" required><span class="f-hint">Excel file? Open it and use File → Save As → CSV.</span></div>
    </div>
    <div><button class="tm-btn tm-btn-primary"><?= icon('upload', 'icon icon-sm') ?> Upload &amp; match columns</button></div>
  </form>
  <?php endif; ?>
</section>
<?php else:
    $h = $det['headers'];
    $selCol = function (string $field) use ($h, $opt): string {
        $cur = $opt['map'][$field] ?? null;
        $o = '<option value="">— not in this file —</option>';
        foreach ($h as $i => $name) {
            $o .= '<option value="' . $i . '"' . ($cur === $i ? ' selected' : '') . '>' . e(($name !== '' ? $name : 'Column ' . ($i + 1))) . '</option>';
        }
        return $o;
    };
    $req = fn (string $f) => BrokerImport::FIELDS[$f][$opt['mode'] === 'fills' ? 2 : 1];
?>
<form method="post" action="<?= e(url('/terminal/import/run')) ?>" class="stack imp" data-import><?= csrf_field() ?>
  <section class="panel stack">
    <div class="panel-head"><h2><?= icon('upload', 'icon') ?> <?= e($imp['name']) ?></h2><span class="muted small"><?= (int) $det['rows'] ?> data row<?= $det['rows'] == 1 ? '' : 's' ?> · into <strong><?= e($imp['acc']['name']) ?></strong> (<?= e($imp['acc']['currency']) ?>)</span></div>
    <p class="muted small">The columns below were matched automatically from the file’s header row. Check them, choose how the file is laid out, then press <strong>Preview</strong>. Nothing is saved until you press <strong>Import</strong>.</p>
    <div class="grid-form">
      <div class="f"><label for="imp-mode">What does each row contain?</label><select id="imp-mode" name="mode"><option value="trades"<?= $opt['mode'] === 'trades' ? ' selected' : '' ?>>One closed (or open) trade per row</option><option value="fills"<?= $opt['mode'] === 'fills' ? ' selected' : '' ?>>One buy or sell execution per row (pair them for me)</option></select></div>
      <div class="f"><label for="imp-do">Date format</label><select id="imp-do" name="date_order"><?php foreach (['dmy' => 'Day first (31/12/2025)', 'mdy' => 'Month first (12/31/2025)', 'ymd' => 'Year first (2025-12-31)'] as $k => $l): ?><option value="<?= $k ?>"<?= $opt['date_order'] === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
      <div class="f"><label for="imp-off">Times in the file are in</label><select id="imp-off" name="offset"><?php foreach ($offsets as $k => $l): ?><option value="<?= e((string) $k) ?>"<?= (string) $opt['offset_key'] === (string) $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select><span class="f-hint">Times with an explicit offset (e.g. 2025-03-04T10:00:00+05:30 or a trailing Z) are converted exactly.</span></div>
      <div class="f"><label for="imp-unit">Size column is in</label><select id="imp-unit" name="size_unit"><?php foreach (['auto' => 'Detect automatically', 'lots' => 'Lots / contracts / quantity', 'units' => 'Units (100,000 = 1 forex lot)'] as $k => $l): ?><option value="<?= $k ?>"<?= $opt['size_unit'] === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select><span class="f-hint">Detect: forex sizes of 1,000 or more are read as units (100,000 = 1 lot).</span></div>
    </div>
    <fieldset class="imp-map"><legend>Columns</legend>
      <div class="grid-form">
      <?php foreach (BrokerImport::FIELDS as $k => [$label]): ?>
        <div class="f"><label for="imp-m-<?= $k ?>"><?= e($label) ?><?= $req($k) ? ' <span class="req" title="Required">*</span>' : '' ?></label><select id="imp-m-<?= $k ?>" name="map_<?= $k ?>"><?= $selCol($k) ?></select><span class="f-hint">Detect: forex sizes of 1,000 or more are read as units (100,000 = 1 lot).</span></div>
      <?php endforeach; ?>
      </div>
      <p class="f-hint">P&amp;L: when a profit column is mapped, the broker’s figure is used (plus commission and swap columns, as statements report them). Without it, P&amp;L is calculated from prices and size with the same formulas as the rest of journzey.</p>
    </fieldset>
    <details class="imp-sample"><summary>First rows of the file</summary>
      <div class="table-wrap"><table class="tbl small"><thead><tr><?php foreach ($h as $i => $name): ?><th><?= e($name !== '' ? $name : '#' . ($i + 1)) ?></th><?php endforeach; ?></tr></thead><tbody>
      <?php foreach ($det['sample'] as $r): ?><tr><?php foreach ($h as $i => $_): ?><td><?= e(mb_strimwidth((string) ($r[$i] ?? ''), 0, 28, '…')) ?></td><?php endforeach; ?></tr><?php endforeach; ?>
      </tbody></table></div>
    </details>
  </section>

  <?php if ($preview !== null): $tr = $preview['trades']; ?>
  <section class="panel stack" aria-live="polite" id="imp-preview">
    <div class="panel-head"><h2>Preview</h2><span class="muted small"><?= count($tr) ?> trade<?= count($tr) == 1 ? '' : 's' ?> ready<?= $preview['open'] ? ' (' . (int) $preview['open'] . ' still open)' : '' ?> · <?= (int) $preview['skipped'] ?> non-trade row<?= $preview['skipped'] == 1 ? '' : 's' ?> skipped · <?= count($preview['errors']) ?> problem<?= count($preview['errors']) == 1 ? '' : 's' ?></span></div>
    <?php if ($preview['errors']): ?>
      <div class="tm-alert tm-alert-<?= $tr ? 'info' : 'error' ?> imp-errs" role="alert"><div><strong><?= $tr ? 'These rows will not be imported:' : 'Nothing can be imported yet:' ?></strong>
        <ul class="small"><?php foreach (array_slice($preview['errors'], 0, 12) as [$where, $why]): ?><li><?= e($where) ?> — <?= e($why) ?></li><?php endforeach; ?><?php if (count($preview['errors']) > 12): ?><li>…and <?= count($preview['errors']) - 12 ?> more.</li><?php endif; ?></ul>
      </div></div>
    <?php endif; ?>
    <?php if ($tr): ?>
    <div class="table-wrap"><table class="tbl cards small"><thead><tr><th>Opened (your time)</th><th>Instrument</th><th>Side</th><th class="r">Size</th><th class="r">Entry</th><th class="r">Exit</th><th class="r">Stop</th><th class="r">Broker P&amp;L</th></tr></thead><tbody>
      <?php foreach (array_slice($tr, 0, 15) as $t): ?><tr>
        <td data-label="Opened"><?= e(fmt_date($t['executed_at'], 'M j, Y H:i')) ?></td><td data-label="Instrument" class="strong"><?= e($t['symbol']) ?></td>
        <td data-label="Side"><span class="badge <?= strtolower($t['side']) ?>"><?= $t['side'] === 'LONG' ? 'BUY' : 'SELL' ?></span></td>
        <td data-label="Size" class="r num"><?= e(rtrim(rtrim(number_format((float) $t['lots'], 4, '.', ''), '0'), '.')) ?></td>
        <td data-label="Entry" class="r num"><?= e((string) $t['entry']) ?></td><td data-label="Exit" class="r num"><?= $t['exit'] !== null ? e((string) $t['exit']) : '<span class="muted">open</span>' ?></td>
        <td data-label="Stop" class="r num"><?= $t['sl'] !== null ? e((string) $t['sl']) : '—' ?></td>
        <td data-label="Broker P&amp;L" class="r num <?= $t['pnl'] === null ? 'muted' : ($t['pnl'] >= 0 ? 'up' : 'down') ?>"><?= $t['pnl'] === null ? 'calculated' : e(money($t['pnl'], $imp['acc']['currency'], true)) ?></td>
      </tr><?php endforeach; ?>
    </tbody></table></div>
    <?php if (count($tr) > 15): ?><p class="muted small">Showing the first 15 of <?= count($tr) ?>.</p><?php endif; ?>
    <?php endif; ?>
  </section>
  <?php endif; ?>

  <div class="imp-actions">
    <button class="tm-btn" name="action" value="cancel" formnovalidate>Cancel &amp; delete file</button>
    <button class="tm-btn" name="action" value="preview"><?= icon('eye', 'icon icon-sm') ?> Preview</button>
    <?php if ($preview !== null && $preview['trades']): ?><button class="tm-btn tm-btn-primary" name="action" value="import" data-import-go><?= icon('check', 'icon icon-sm') ?> Import <?= count($preview['trades']) ?> trade<?= count($preview['trades']) == 1 ? '' : 's' ?></button><?php endif; ?>
  </div>
</form>
<?php endif; ?>
