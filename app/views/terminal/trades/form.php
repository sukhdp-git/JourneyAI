<?php
use App\Trading\Domain;
use App\Trading\Instruments;
$v = function (string $k, string $d = '') use ($t, $tz) {
    $old = old($k, null);
    if ($old !== null) return (string) $old;
    if (!$t) return $d;
    if ($k === 'executed_at') return utc_to_local_input($t['executed_at']);
    if ($k === 'pnl') return $t['pnl_override'] ? (string) $t['pnl'] : '';
    $x = $t[$k] ?? '';
    return is_string($x) && preg_match('/^-?\d+\.\d+$/', $x) ? rtrim(rtrim($x, '0'), '.') : (string) $x;
};
$err = fn ($k) => field_error($k);
$action = $t ? url('/terminal/trades/' . $t['id']) : url('/terminal/trades');
?>
<?php if (!$t): ?>
<section class="voice-box" data-voice-trade="#trade-form" aria-labelledby="vt-title">
  <div class="panel-head"><h2 id="vt-title">🎙 Voice trade entry</h2><span class="muted small">Speak or type one sentence — values fill the form for you to check before saving</span></div>
  <div class="voice-row">
    <button type="button" class="tm-btn tm-btn-primary" data-voice-into="#vt-text" data-voice-after="[data-vt-parse]" data-voice-note="#vt-note">🎙 Speak trade</button>
    <label class="sr-only" for="vt-text">Trade sentence</label>
    <input id="vt-text" type="text" maxlength="400" data-vt-text placeholder="e.g. Bought gold at 2645.50, stop loss 2639, take profit 2660, 0.5 lots">
    <button type="button" class="tm-btn" data-vt-parse>Read sentence</button>
  </div>
  <p class="muted small" id="vt-note" hidden>Voice input needs Chrome, Edge or Safari with microphone access. You can still type the sentence and press “Read sentence”.</p>
  <div data-vt-preview aria-live="polite"></div>
</section>
<?php endif; ?>
<form method="post" action="<?= e($action) ?>" class="panel" id="trade-form" novalidate>
  <?= csrf_field() ?>
  <div class="panel-head"><h2><?= $t ? 'Edit ' . e($t['symbol']) . ' trade' : 'Log a trade' ?></h2><span class="muted small">Account: <?= e($acc['name']) ?> (<?= e($acc['currency']) ?>) · times in <?= e($tz) ?></span></div>
  <div class="grid-form">
    <div class="f<?= has_error('symbol') ? ' has-error' : '' ?>"><label for="t-symbol">Instrument *</label><select id="t-symbol" name="symbol" required><?php foreach (Domain::ASSET_CLASSES as $cls => $cl): ?><optgroup label="<?= e($cl) ?>"><?php foreach (Instruments::all() as $s => $i) if ($i['class'] === $cls): ?><option value="<?= e($s) ?>"<?= $v('symbol', 'XAUUSD') === $s ? ' selected' : '' ?>><?= e($s . ' · ' . $i['name']) ?></option><?php endif; ?></optgroup><?php endforeach; ?></select><?= $err('symbol') ?></div>
    <div class="f<?= has_error('side') ? ' has-error' : '' ?>"><label for="t-side">Side *</label><select id="t-side" name="side"><option value="LONG"<?= $v('side', 'LONG') === 'LONG' ? ' selected' : '' ?>>Long (buy)</option><option value="SHORT"<?= $v('side') === 'SHORT' ? ' selected' : '' ?>>Short (sell)</option></select><?= $err('side') ?></div>
    <div class="f<?= has_error('executed_at') ? ' has-error' : '' ?>"><label for="t-exec">Executed at</label><input id="t-exec" type="datetime-local" name="executed_at" value="<?= e($v('executed_at')) ?>"><span class="f-hint">Empty = now</span><?= $err('executed_at') ?></div>
    <div class="f<?= has_error('lot_size') ? ' has-error' : '' ?>"><label for="t-lots">Lot size *</label><input id="t-lots" type="number" step="any" min="0" name="lot_size" value="<?= e($v('lot_size', '1')) ?>" required><?= $err('lot_size') ?></div>
    <div class="f<?= has_error('entry_price') ? ' has-error' : '' ?>"><label for="t-entry">Entry *</label><input id="t-entry" type="number" step="any" name="entry_price" value="<?= e($v('entry_price')) ?>" required><?= $err('entry_price') ?></div>
    <div class="f<?= has_error('stop_loss') ? ' has-error' : '' ?>"><label for="t-stop">Stop loss</label><input id="t-stop" type="number" step="any" name="stop_loss" value="<?= e($v('stop_loss')) ?>"><?= $err('stop_loss') ?></div>
    <div class="f<?= has_error('take_profit') ? ' has-error' : '' ?>"><label for="t-tp">Take profit</label><input id="t-tp" type="number" step="any" name="take_profit" value="<?= e($v('take_profit')) ?>"><?= $err('take_profit') ?></div>
    <div class="f<?= has_error('exit_price') ? ' has-error' : '' ?>"><label for="t-exit">Exit</label><input id="t-exit" type="number" step="any" name="exit_price" value="<?= e($v('exit_price')) ?>"><span class="f-hint">Empty = open trade</span><?= $err('exit_price') ?></div>
    <div class="f<?= has_error('fees') ? ' has-error' : '' ?>"><label for="t-fees">Fees / commission</label><input id="t-fees" type="number" step="any" min="0" name="fees" value="<?= e($v('fees', '0')) ?>"><?= $err('fees') ?></div>
    <div class="f<?= has_error('pnl') ? ' has-error' : '' ?>"><label for="t-pnl">Broker P&amp;L (override)</label><input id="t-pnl" type="number" step="any" name="pnl" value="<?= e($v('pnl')) ?>"><span class="f-hint">Optional — otherwise calculated</span><?= $err('pnl') ?></div>
    <div class="f<?= has_error('strategy_id') ? ' has-error' : '' ?>"><label for="t-strat">Strategy</label><select id="t-strat" name="strategy_id"><option value="">— None —</option><?php foreach ($strategies as $s): ?><option value="<?= (int) $s['id'] ?>"<?= $v('strategy_id') == $s['id'] ? ' selected' : '' ?>><?= e($s['name']) ?></option><?php endforeach; ?></select><?= $err('strategy_id') ?></div>
    <div class="f"><label for="t-setup">Setup tag</label><input id="t-setup" type="text" name="setup_tag" maxlength="120" value="<?= e($v('setup_tag')) ?>" list="setup-list"><datalist id="setup-list"><?php foreach ($strategies as $s) foreach (preg_split('/\R/', (string) ($s['setups'] ?? '')) as $su) if (trim($su) !== ''): ?><option value="<?= e(trim($su)) ?>"><?= e($s['name']) ?></option><?php endif; ?></datalist><span class="f-hint">Sub-setup, e.g. VAL Bounce</span></div>
    <div class="f"><label for="t-emo">Emotion</label><select id="t-emo" name="emotion"><option value="">—</option><?php foreach (Domain::EMOTIONS as $e): ?><option value="<?= e($e) ?>"<?= $v('emotion') === $e ? ' selected' : '' ?>><?= e(ucfirst(strtolower($e))) ?></option><?php endforeach; ?></select></div>
    <div class="f"><label for="t-mis">Mistake</label><select id="t-mis" name="mistake_tag"><?php foreach (Domain::MISTAKES as $k => $l): ?><option value="<?= e($k) ?>"<?= $v('mistake_tag', 'NONE') === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
    <div class="f"><span class="lbl">Rules</span><input type="hidden" name="rules_followed" value="0"><label class="check"><input type="checkbox" name="rules_followed" value="1"<?= $v('rules_followed', '1') === '1' ? ' checked' : '' ?>> Plan &amp; rules followed</label></div>
    <div class="f span-all"><label for="t-notes">Notes</label><textarea id="t-notes" name="notes" rows="4" maxlength="5000"><?= e($v('notes')) ?></textarea></div>
  </div>
  <p class="panel-note">P&amp;L and R are calculated on the server from entry, exit, stop and lot size using standard contract sizes (e.g. 1 lot gold = 100 oz, 1 lot FX = 100,000 units). Enter your broker’s figure in “Broker P&amp;L” if it differs.</p>
  <div class="tm-modal-actions"><a class="tm-btn" href="<?= e($t ? url('/terminal/trades/' . $t['id']) : url('/terminal/trades')) ?>">Cancel</a><button class="tm-btn tm-btn-primary" type="submit"><?= $t ? 'Save changes' : 'Save trade' ?></button></div>
</form>
