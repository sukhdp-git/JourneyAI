<?php
use App\Trading\AiCoach;
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
$nowLocal = (new DateTimeImmutable('now', new DateTimeZone($tz)))->format('Y-m-d\TH:i');
$fc = fn ($k) => 'f' . (has_error($k) ? ' has-error' : '');
$ai = AiCoach::configured();
?>
<form method="post" action="<?= e($action) ?>" class="panel trade-form" id="trade-form" enctype="multipart/form-data" novalidate data-voice-guide data-currency="<?= e($acc['currency']) ?>">
  <?= csrf_field() ?>
  <div class="panel-head"><h2><?= $t ? 'Edit ' . e($t['symbol']) . ' trade' : 'Log a trade' ?></h2><span class="muted small"><?= e($acc['name']) ?> · <?= e($acc['currency']) ?> · <?= e($tz) ?></span></div>

  <div class="vg-bar" data-vg-bar>
    <button type="button" class="tm-btn tm-btn-primary vg-start" data-vg-start><?= icon('mic', 'icon icon-sm') ?> <span data-vg-label>Voice log</span></button>
    <div class="vg-status" aria-live="polite"><strong data-vg-prompt>Tap “Voice log” and answer one question at a time.</strong><span class="muted small" data-vg-heard>Say “entry 4000”, “skip” or “stop”. The form moves to the next box by itself.</span></div>
    <button type="button" class="tm-btn tm-btn-sm" data-vg-stop hidden>Stop</button>
  </div>
  <p class="muted small" data-vg-unsupported hidden>Voice needs Chrome, Edge or Safari with microphone access — you can still type everything.</p>

  <div class="grid-form">
    <div class="<?= $fc('symbol') ?>" data-vg-step="symbol" data-vg-ask="Which instrument?"><label for="t-symbol">Instrument *</label><select id="t-symbol" name="symbol" required><?php foreach (Domain::ASSET_CLASSES as $cls => $cl): ?><optgroup label="<?= e($cl) ?>"><?php foreach (Instruments::all() as $s => $i) if ($i['class'] === $cls): ?><option value="<?= e($s) ?>" data-quote="<?= e($i['quote']) ?>" data-base="<?= e($i['base']) ?>" data-search="<?= e(strtolower($s . ' ' . $i['name'] . ' ' . implode(' ', $i['aliases']))) ?>"<?= $v('symbol', 'XAUUSD') === $s ? ' selected' : '' ?>><?= e($s . ' · ' . $i['name']) ?></option><?php endif; ?></optgroup><?php endforeach; ?></select><?= $err('symbol') ?></div>
    <div class="<?= $fc('side') ?>" data-vg-step="side" data-vg-ask="Buy or sell?"><label for="t-side">Side *</label><select id="t-side" name="side"><option value="LONG"<?= $v('side', 'LONG') === 'LONG' ? ' selected' : '' ?>>Long (buy)</option><option value="SHORT"<?= $v('side') === 'SHORT' ? ' selected' : '' ?>>Short (sell)</option></select><?= $err('side') ?></div>
    <div class="<?= $fc('entry_price') ?>" data-vg-step="number" data-vg-ask="Entry price?"><label for="t-entry">Entry *</label><input id="t-entry" type="number" step="any" name="entry_price" inputmode="decimal" value="<?= e($v('entry_price')) ?>" required><?= $err('entry_price') ?></div>
    <div class="<?= $fc('stop_loss') ?>" data-vg-step="number" data-vg-ask="Stop loss?"><label for="t-stop">Stop loss</label><input id="t-stop" type="number" step="any" name="stop_loss" inputmode="decimal" value="<?= e($v('stop_loss')) ?>"><?= $err('stop_loss') ?></div>
    <div class="<?= $fc('take_profit') ?>" data-vg-step="number" data-vg-ask="Take profit?"><label for="t-tp">Take profit</label><input id="t-tp" type="number" step="any" name="take_profit" inputmode="decimal" value="<?= e($v('take_profit')) ?>"><?= $err('take_profit') ?></div>
    <div class="<?= $fc('exit_price') ?>" data-vg-step="number" data-vg-ask="Exit price? Say skip if the trade is still open."><label for="t-exit">Exit</label><input id="t-exit" type="number" step="any" name="exit_price" inputmode="decimal" value="<?= e($v('exit_price')) ?>"><span class="f-hint">Empty = open trade</span><?= $err('exit_price') ?></div>
    <div class="<?= $fc('lot_size') ?>" data-vg-step="number" data-vg-ask="Lot size?"><label for="t-lots">Lot size *</label><input id="t-lots" type="number" step="any" min="0" name="lot_size" inputmode="decimal" value="<?= e($v('lot_size', '1')) ?>" required><?= $err('lot_size') ?></div>
    <div class="<?= $fc('executed_at') ?>"><label for="t-exec">Date &amp; time</label><input id="t-exec" type="datetime-local" name="executed_at" value="<?= e($v('executed_at', $nowLocal)) ?>"><span class="f-hint">Today by default</span><?= $err('executed_at') ?></div>
    <div class="<?= $fc('rate') ?>" data-rate-wrap<?= has_error('rate') ? '' : ' hidden' ?>><label for="t-rate">Conversion rate <span data-rate-pair></span></label><input id="t-rate" type="number" step="any" min="0" name="rate" inputmode="decimal" value="<?= e((string) old('rate', '')) ?>"><span class="f-hint">Only for instruments priced in another currency</span><?= $err('rate') ?></div>
    <div class="<?= $fc('strategy_id') ?>"><label for="t-strat">Strategy</label><select id="t-strat" name="strategy_id"><option value="">— None —</option><?php foreach ($strategies as $s): ?><option value="<?= (int) $s['id'] ?>"<?= $v('strategy_id') == $s['id'] ? ' selected' : '' ?>><?= e($s['name']) ?></option><?php endforeach; ?></select><?= $err('strategy_id') ?></div>
    <div class="f"><label for="t-setup">Setup tag</label><input id="t-setup" type="text" name="setup_tag" maxlength="120" value="<?= e($v('setup_tag')) ?>" list="setup-list"><datalist id="setup-list"><?php foreach ($strategies as $s) foreach (preg_split('/\R/', (string) ($s['setups'] ?? '')) as $su) if (trim($su) !== ''): ?><option value="<?= e(trim($su)) ?>"><?= e($s['name']) ?></option><?php endif; ?></datalist></div>
    <div class="f" data-vg-step="emotion" data-vg-ask="How did you feel? Say calm, confident, anxious, fearful, greedy or frustrated — or skip."><label for="t-emo">Emotion</label><select id="t-emo" name="emotion"><option value="">—</option><?php foreach (Domain::EMOTIONS as $em): ?><option value="<?= e($em) ?>"<?= $v('emotion') === $em ? ' selected' : '' ?>><?= e(ucfirst(strtolower($em))) ?></option><?php endforeach; ?></select></div>
    <div class="f"><label for="t-mis">Mistake</label><select id="t-mis" name="mistake_tag"><?php foreach (Domain::MISTAKES as $k => $l): ?><option value="<?= e($k) ?>"<?= $v('mistake_tag', 'NONE') === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
    <div class="f"><span class="lbl">Rules</span><input type="hidden" name="rules_followed" value="0"><label class="check"><input type="checkbox" name="rules_followed" value="1"<?= $v('rules_followed', '1') === '1' ? ' checked' : '' ?>> Plan &amp; rules followed</label></div>
    <div class="f span-all" data-vg-step="text" data-vg-ask="Any notes? Speak them, or say skip."><label for="t-notes">Notes</label><textarea id="t-notes" name="notes" rows="3" maxlength="5000"><?= e($v('notes')) ?></textarea></div>
  </div>

  <div class="shot-drop" data-shot-box>
    <div class="shot-drop-text">
      <strong><?= icon('camera', 'icon icon-sm') ?> Chart screenshot</strong>
      <span class="muted small"><?= $t && $t['screenshot_path'] ? 'A screenshot is attached — choose a new one to replace it.' : 'PNG, JPEG or WebP up to 5 MB. Saved privately with the trade.' ?><?= $ai ? ' With a long/short position tool on the chart, “Read chart” fills instrument, side, entry, stop and target for you.' : '' ?></span>
    </div>
    <div class="shot-drop-actions">
      <label class="tm-btn tm-btn-sm"><input type="file" name="screenshot" accept="image/png,image/jpeg,image/webp" data-shot-input hidden> Choose image</label>
      <?php if ($ai): ?><button type="button" class="tm-btn tm-btn-sm tm-btn-primary" data-shot-read="<?= e(url('/terminal/trades/read-chart')) ?>" disabled><?= icon('sparkles', 'icon icon-sm') ?> Read chart</button><?php endif; ?>
    </div>
    <img alt="Selected screenshot preview" data-shot-preview hidden>
    <p class="small" data-shot-msg aria-live="polite"><?= $err('screenshot') ?></p>
  </div>

  <?php if ($t): // values imported from a broker statement are kept unchanged ?>
    <input type="hidden" name="fees" value="<?= e((string) (float) $t['fees']) ?>">
    <?php if ($t['pnl_override']): ?><input type="hidden" name="pnl" value="<?= e((string) $t['pnl']) ?>"><?php endif; ?>
  <?php endif; ?>
  <p class="panel-note">P&amp;L and R are calculated from entry, exit, stop and lot size (e.g. 1 lot gold = 100 oz, 1 lot FX = 100,000 units).</p>
  <div class="tm-modal-actions"><a class="tm-btn" href="<?= e($t ? url('/terminal/trades/' . $t['id']) : url('/terminal/trades')) ?>">Cancel</a><button class="tm-btn tm-btn-primary" type="submit"><?= $t ? 'Save changes' : 'Save trade' ?></button></div>
</form>
