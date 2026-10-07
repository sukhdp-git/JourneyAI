<?php
use App\Controllers\Terminal\CalculatorController as C;
use App\Trading\Domain;
$byClass = [];
foreach ($instruments as $s => $i) { $byClass[$i['class']][$s] = $i; }
$instSelect = function (string $id) use ($byClass) {
    $h = '<input type="search" class="inst-filter" placeholder="Search instrument (e.g. gold, nas, eurusd)" data-inst-filter="#' . $id . '" aria-label="Search instruments">';
    $h .= '<select id="' . $id . '" name="symbol" data-inst-select>';
    foreach (Domain::ASSET_CLASSES as $cls => $label) {
        if (empty($byClass[$cls])) continue;
        $h .= '<optgroup label="' . e($label) . '">';
        foreach ($byClass[$cls] as $s => $i) {
            $h .= '<option value="' . e($s) . '" data-search="' . e(strtolower($s . ' ' . $i['name'] . ' ' . implode(' ', $i['aliases']))) . '" data-spec="' . e(json_encode(['contract' => $i['contract'], 'tick' => $i['tick'], 'pip' => $i['pip'], 'quote' => $i['quote'], 'step' => $i['lot_step'], 'decimals' => $i['decimals']])) . '"' . ($s === 'XAUUSD' ? ' selected' : '') . '>' . e($s . ' · ' . $i['name']) . '</option>';
        }
        $h .= '</optgroup>';
    }
    return $h . '</select><p class="muted small spec-line" data-spec-out></p>';
};
$sideToggle = fn (string $n) => '<div class="seg side-seg" role="radiogroup" aria-label="Direction"><label><input type="radio" name="side" value="LONG" checked><span>BUY</span></label><label><input type="radio" name="side" value="SHORT"><span>SELL</span></label></div>';
$rateField = fn () => '<div class="f" data-rate-field hidden><label>Conversion rate <span data-rate-label></span></label><input name="rate" type="number" step="any" min="0" placeholder="e.g. 1.08"><span class="f-hint">Needed when the instrument is quoted in another currency. Use your broker\'s current rate — journzey.ai does not fetch live prices.</span></div>';
?>
<div class="seg calc-tabs" role="tablist" aria-label="Calculator mode">
  <button type="button" role="tab" class="on" aria-selected="true" data-calc-tab="size">Risk-based lot size</button>
  <button type="button" role="tab" aria-selected="false" data-calc-tab="pnl">Trade P&amp;L / R calculator</button>
</div>

<div class="grid g-main calc" data-calc="size">
  <form class="panel stack" data-calc-form="size" novalidate>
    <div class="panel-head"><h2>How much should I trade?</h2><span class="muted small">Based on how much you are willing to risk</span></div>
    <div class="f"><label for="cs-acc">Account</label>
      <select id="cs-acc" name="account_id" data-calc-account>
        <?php foreach ($calcAccounts as $a): ?><option value="<?= $a['id'] ?>" data-equity="<?= e((string) $a['equity']) ?>" data-currency="<?= e($a['currency']) ?>"<?= $a['id'] === (int) $acc['id'] ? ' selected' : '' ?>><?= e($a['name'] . ' — ' . ($a['demo'] ? 'Demo account' : (Domain::ACCOUNT_TYPES[$a['type']] ?? $a['type'])) . ' · ' . money($a['equity'], $a['currency'])) ?></option><?php endforeach; ?>
      </select>
      <span class="f-hint">Capital is what you entered for the account plus deposits/withdrawals and closed P&amp;L. <a href="<?= e(url('/terminal/accounts')) ?>">Manage accounts</a></span>
    </div>
    <div class="f"><label for="cs-sym">Instrument</label><?= $instSelect('cs-sym') ?></div>
    <div class="f"><span class="lbl">Direction</span><?= $sideToggle('side') ?></div>
    <div class="f"><span class="lbl">Risk</span>
      <div class="seg" role="radiogroup" aria-label="Risk type"><label><input type="radio" name="risk_mode" value="percent" checked><span>% of capital</span></label><label><input type="radio" name="risk_mode" value="fixed"><span>Fixed amount</span></label></div>
      <div class="chips" data-risk-chips="percent"><?php foreach (C::RISK_PCTS as $p): ?><button type="button" class="chip-btn<?= (float) $p === (float) $m['default_risk_pct'] ? ' on' : '' ?>" data-risk="<?= $p ?>"><?= $p ?>%</button><?php endforeach; ?><button type="button" class="chip-btn" data-risk="custom">Custom</button></div>
      <div class="chips" data-risk-chips="fixed" hidden><?php foreach (C::RISK_FIXED as $p): ?><button type="button" class="chip-btn" data-risk="<?= $p ?>"><?= e(money($p, $acc['currency'])) ?></button><?php endforeach; ?><button type="button" class="chip-btn" data-risk="custom">Custom</button></div>
      <input name="risk_value" type="number" step="any" min="0" value="<?= e((string) (float) $m['default_risk_pct']) ?>" data-risk-value aria-label="Risk value">
    </div>
    <div class="grid-form">
      <div class="f"><label for="cs-e">Entry</label><input id="cs-e" name="entry" type="number" step="any" inputmode="decimal"></div>
      <div class="f"><label for="cs-s">Stop loss</label><input id="cs-s" name="stop" type="number" step="any" inputmode="decimal"></div>
      <div class="f"><label for="cs-t">Take profit</label><input id="cs-t" name="target" type="number" step="any" inputmode="decimal"></div>
    </div>
    <?= $rateField() ?>
    <input type="hidden" name="mode" value="size">
  </form>
  <section class="panel calc-out" data-calc-out="size" aria-live="polite">
    <div class="calc-hero"><small>Recommended position size</small><strong data-o="lots">—</strong><em data-o="lots_note">Enter entry and stop loss</em></div>
    <dl class="calc-dl">
      <dt>Risk amount</dt><dd data-o="risk_actual">—</dd>
      <dt>Stop distance</dt><dd data-o="stop_distance">—</dd>
      <dt>Potential profit</dt><dd data-o="profit" class="up">—</dd>
      <dt>Risk : Reward</dt><dd data-o="rr">—</dd>
      <dt>Potential account gain</dt><dd data-o="gain_pct" class="up">—</dd>
      <dt>Potential account loss</dt><dd data-o="loss_pct" class="down">—</dd>
    </dl>
    <ul class="calc-errors" data-o="errors"></ul>
    <p class="panel-note">Lots are rounded down to the instrument's lot step so the risk never exceeds your budget. Contract sizes follow common CFD conventions (e.g. 1 lot gold = 100 oz, 1 lot FX = 100,000 units, 1 lot index = 1 × point) — check your broker's specification.</p>
  </section>
</div>

<div class="grid g-main calc" data-calc="pnl" hidden>
  <form class="panel stack" data-calc-form="pnl" novalidate>
    <div class="panel-head"><h2>Trade P&amp;L and risk-to-reward</h2></div>
    <div class="f"><label for="cp-sym">Instrument</label><?= $instSelect('cp-sym') ?></div>
    <div class="f"><span class="lbl">Direction</span><?= $sideToggle('side') ?></div>
    <div class="grid-form">
      <div class="f"><label for="cp-e">Entry</label><input id="cp-e" name="entry" type="number" step="any" inputmode="decimal"></div>
      <div class="f"><label for="cp-s">Stop loss</label><input id="cp-s" name="stop" type="number" step="any" inputmode="decimal"></div>
      <div class="f"><label for="cp-k">Price type</label><select id="cp-k" name="target_kind"><option value="tp">Take profit (plan)</option><option value="exit">Exit (finished trade)</option></select></div>
      <div class="f"><label for="cp-t">Exit / take profit</label><input id="cp-t" name="target" type="number" step="any" inputmode="decimal"></div>
      <div class="f"><label for="cp-l">Lot size</label><input id="cp-l" name="lots" type="number" step="any" min="0" value="1"></div>
      <div class="f"><label for="cp-c">Currency</label><select id="cp-c" name="currency"><?php foreach (Domain::CURRENCIES as $c): ?><option<?= $c === $acc['currency'] ? ' selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?></select></div>
    </div>
    <?= $rateField() ?>
    <input type="hidden" name="mode" value="pnl">
  </form>
  <section class="panel calc-out" data-calc-out="pnl" aria-live="polite">
    <div class="calc-hero"><small>Profit / Loss</small><strong data-o="pnl">—</strong><em data-o="pnl_note">Enter entry, stop, exit/TP and lots</em></div>
    <dl class="calc-dl">
      <dt>Risk : Reward</dt><dd data-o="rr">—</dd>
      <dt>Result in R</dt><dd data-o="r_multiple">—</dd>
      <dt>Risk at stop</dt><dd data-o="risk" class="down">—</dd>
      <dt>Stop distance</dt><dd data-o="stop_distance">—</dd>
      <dt>Target distance</dt><dd data-o="target_distance">—</dd>
      <dt>Value per pip/point (this size)</dt><dd data-o="pip_value">—</dd>
    </dl>
    <ul class="calc-errors" data-o="errors"></ul>
    <p class="panel-note">Risk distance = |entry − stop|, reward distance = |target − entry|, R:R = reward ÷ risk. Estimates exclude spread, commission and swap.</p>
  </section>
</div>
