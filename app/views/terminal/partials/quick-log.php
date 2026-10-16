<?php
/**
 * Quick Log — four quick steps: instrument (editable favourites) → Buy/Sell → lot size → prices on a scroll wheel.
 * Date, time and session are filled automatically when the trade is saved. @var string $ctx unique id prefix
 */
use App\Trading\Instruments;
use App\Trading\QuickLog;
use App\Trading\Sessions;

$ctx ??= 'ql';
$member = App\Trading\Members::current();
$accId = (int) ($acc['id'] ?? 0);
$prefs = QuickLog::prefs((int) $member['id'], $accId);
$all = [];
foreach (Instruments::all() as $s => $i) {
    $all[$s] = ['n' => $i['name'], 'c' => $i['class'], 'q' => $i['quote'], 'k' => (float) $i['contract'], 'd' => (int) $i['decimals']];
}
?>
<div class="ql" data-quick-log data-ctx="<?= e($ctx) ?>" data-base="<?= e(url('/terminal')) ?>" data-currency="<?= e($acc['currency']) ?>"
  data-symbols="<?= e(json_encode($prefs['symbols'])) ?>" data-lots="<?= e(json_encode((object) $prefs['lots'])) ?>" data-all="<?= e(json_encode($all)) ?>">
  <ol class="ql-steps" aria-label="Quick Log steps">
    <li data-ql-dot="1" class="on"><span>1</span> Instrument</li><li data-ql-dot="2"><span>2</span> Buy / Sell</li><li data-ql-dot="3"><span>3</span> Lot size</li><li data-ql-dot="4"><span>4</span> Prices</li>
  </ol>

  <section class="ql-step" data-ql-step="1" aria-labelledby="<?= e($ctx) ?>-s1">
    <div class="ql-step-head"><h3 id="<?= e($ctx) ?>-s1">Which instrument?</h3><button type="button" class="ql-link" data-ql-edit aria-expanded="false"><?= icon('edit', 'icon icon-xs') ?> Edit list</button></div>
    <div class="ql-chips" role="radiogroup" aria-label="Instrument" data-ql-favs></div>
    <div class="ql-editor" data-ql-editor hidden>
      <label class="sr-only" for="<?= e($ctx) ?>-find">Find an instrument</label>
      <input id="<?= e($ctx) ?>-find" type="search" placeholder="Search instruments (e.g. gold, nasdaq, eurusd)" data-ql-find autocomplete="off">
      <p class="muted small">Tick up to <?= QuickLog::MAX_SYMBOLS ?> instruments you trade. They appear as quick buttons.</p>
      <div class="ql-all" data-ql-all></div>
      <div class="ql-actions"><button type="button" class="tm-btn tm-btn-sm" data-ql-edit-cancel>Cancel</button><button type="button" class="tm-btn tm-btn-sm tm-btn-primary" data-ql-edit-save>Save my instruments</button></div>
    </div>
  </section>

  <section class="ql-step" data-ql-step="2" hidden aria-labelledby="<?= e($ctx) ?>-s2">
    <div class="ql-step-head"><h3 id="<?= e($ctx) ?>-s2"><span data-ql-sym-label></span> — buy or sell?</h3></div>
    <div class="ql-sides" role="radiogroup" aria-label="Direction">
      <button type="button" class="ql-side ql-buy" data-ql-side="LONG" role="radio" aria-checked="false"><?= icon('trend-up', 'icon') ?> BUY</button>
      <button type="button" class="ql-side ql-sell" data-ql-side="SHORT" role="radio" aria-checked="false"><?= icon('trend-down', 'icon') ?> SELL</button>
    </div>
  </section>

  <section class="ql-step" data-ql-step="3" hidden aria-labelledby="<?= e($ctx) ?>-s3">
    <div class="ql-step-head"><h3 id="<?= e($ctx) ?>-s3">Lot size</h3></div>
    <div class="ql-lots">
      <button type="button" class="ql-pm" data-ql-lot="-1" aria-label="Decrease lot size">−</button>
      <label class="sr-only" for="<?= e($ctx) ?>-lots">Lot size</label>
      <input id="<?= e($ctx) ?>-lots" type="number" inputmode="decimal" step="0.01" min="0.01" value="0.10" data-ql-lots>
      <button type="button" class="ql-pm" data-ql-lot="1" aria-label="Increase lot size">+</button>
    </div>
    <div class="ql-chips ql-lot-chips"><?php foreach (['0.01', '0.05', '0.10', '0.25', '0.50', '1.00'] as $l): ?><button type="button" class="ql-chip" data-ql-lot-set="<?= $l ?>"><?= $l ?></button><?php endforeach; ?></div>
    <div class="ql-actions"><button type="button" class="tm-btn tm-btn-primary" data-ql-next="4">Next: prices →</button></div>
  </section>

  <section class="ql-step" data-ql-step="4" hidden aria-labelledby="<?= e($ctx) ?>-s4">
    <div class="ql-step-head"><h3 id="<?= e($ctx) ?>-s4">Prices</h3><span class="muted small" data-ql-summary></span></div>
    <div class="ql-prices">
      <button type="button" class="ql-price" data-ql-field="entry"><small>Entry</small><b data-ql-val="entry">Tap to set</b></button>
      <button type="button" class="ql-price" data-ql-field="exit"><small>Exit <em>(empty = open trade)</em></small><b data-ql-val="exit">—</b></button>
      <button type="button" class="ql-price" data-ql-field="stop"><small>Stop loss <em>(optional)</em></small><b data-ql-val="stop">—</b></button>
    </div>
    <div class="ql-result" data-ql-result aria-live="polite"></div>
    <p class="muted small ql-auto"><?= icon('clock', 'icon icon-xs') ?> Date, time and session are filled in automatically when you log (now: <span data-ql-session><?= e(App\Trading\Domain::SESSIONS[Sessions::classify(gmdate('Y-m-d H:i:s'))] ?? '') ?></span> session). Add setup, emotions and notes later from the Trade Log.</p>
    <div class="ql-actions"><button type="button" class="tm-btn" data-ql-back>← Back</button><button type="button" class="tm-btn tm-btn-primary" data-ql-save disabled><?= icon('check', 'icon icon-sm') ?> Log trade</button></div>
  </section>

  <div class="ql-done" data-ql-done hidden role="status"></div>

  <!-- Price wheel (one per Quick Log) -->
  <div class="ql-wheel-sheet" data-ql-sheet hidden role="dialog" aria-modal="false" aria-labelledby="<?= e($ctx) ?>-wt">
    <div class="ql-wheel-head"><b id="<?= e($ctx) ?>-wt" data-ql-wtitle>Entry price</b><span class="muted small" data-ql-src></span></div>
    <div class="ql-wheel-wrap">
      <div class="ql-wheel" data-ql-wheel tabindex="0" role="spinbutton" aria-label="Price — scroll, drag or use the arrow keys"></div>
      <div class="ql-wheel-band" aria-hidden="true"></div>
    </div>
    <div class="ql-wheel-tools">
      <label class="sr-only" for="<?= e($ctx) ?>-type">Type a price</label>
      <input id="<?= e($ctx) ?>-type" type="number" step="any" inputmode="decimal" placeholder="Type a price" data-ql-type>
      <span class="ql-stepbox"><button type="button" class="tm-btn tm-btn-sm" data-ql-stepx="0.1" title="Finer steps">÷10</button><span class="small" data-ql-steplbl></span><button type="button" class="tm-btn tm-btn-sm" data-ql-stepx="10" title="Bigger steps">×10</button></span>
    </div>
    <div class="ql-actions"><button type="button" class="tm-btn tm-btn-sm" data-ql-clear>Clear</button><button type="button" class="tm-btn tm-btn-sm" data-ql-wcancel>Cancel</button><button type="button" class="tm-btn tm-btn-sm tm-btn-primary" data-ql-set>Set price</button></div>
  </div>
</div>
