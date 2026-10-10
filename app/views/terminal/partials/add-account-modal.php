<?php
/** "Add account": broker account or prop-firm account (with typical rule presets). @var array $m */
use App\Trading\Domain;
use App\Trading\PropRules;
$return = (new App\Core\Request())->path;
$paid = (bool) $m['ent']['live'];
$cur = $m['base_currency'] ?: 'USD';
$curOpts = implode('', array_map(fn ($c) => '<option' . ($c === $cur ? ' selected' : '') . '>' . e($c) . '</option>', Domain::CURRENCIES));
$mode = function (string $id) use ($paid): string {
    return '<div class="aa-mode" role="radiogroup" aria-label="Account mode">'
        . '<label><input type="radio" name="mode" value="live"' . ($paid ? ' checked' : ' disabled') . '><span><b>Live</b><small>' . ($paid ? 'Real money account' : 'Needs a paid plan') . '</small></span></label>'
        . '<label><input type="radio" name="mode" value="demo"' . ($paid ? '' : ' checked') . '><span><b>Practice</b><small>Free · demo mode</small></span></label></div>';
};
?>
<div class="tm-modal" id="tm-add-account" role="dialog" aria-modal="true" aria-labelledby="tm-aa-title" hidden>
  <div class="tm-modal-bg" data-close></div>
  <div class="tm-modal-card aa-card">
    <div class="tm-modal-head"><h2 id="tm-aa-title"><?= icon('plus', 'icon icon-sm') ?> Add account</h2><button type="button" class="tm-icon-btn" data-close aria-label="Close"><?= icon('x', 'icon') ?></button></div>
    <div class="aa-kinds" role="tablist" aria-label="Account type">
      <button type="button" role="tab" class="aa-kind on" data-aa-kind="broker" aria-selected="true"><?= icon('link', 'icon') ?><span><b>Broker account</b><small>Your own account at any broker</small></span></button>
      <button type="button" role="tab" class="aa-kind" data-aa-kind="prop" aria-selected="false"><?= icon('shield', 'icon') ?><span><b>Prop firm account</b><small>Challenge or funded — rules tracked</small></span></button>
    </div>

    <form method="post" action="<?= e(url('/terminal/accounts')) ?>" class="stack aa-form" data-aa-form="broker"><?= csrf_field() ?>
      <input type="hidden" name="kind" value="broker"><input type="hidden" name="return" value="<?= e($return) ?>">
      <div class="grid-form">
        <div class="f"><label for="aa-b-broker">Broker name</label><input id="aa-b-broker" name="broker_name" maxlength="80" placeholder="e.g. IC Markets, Exness, Zerodha" required></div>
        <div class="f"><label for="aa-b-name">Account name <span class="muted">(optional)</span></label><input id="aa-b-name" name="name" maxlength="80" placeholder="e.g. Swing account"></div>
      </div>
      <div class="grid-form">
        <div class="f"><label for="aa-b-eq">Starting equity</label><input id="aa-b-eq" name="starting_capital" type="number" step="0.01" min="0" required inputmode="decimal" placeholder="e.g. 5000"></div>
        <div class="f"><label for="aa-b-cur">Currency</label><select id="aa-b-cur" name="currency"><?= $curOpts ?></select></div>
      </div>
      <?= $mode('b') ?>
      <p class="muted small">You can adjust the equity anytime with <b>Edit / adjust equity</b> under the balance — deposits, withdrawals or a new balance.</p>
      <div class="tm-modal-actions"><button class="tm-btn tm-btn-primary" type="submit">Add broker account</button></div>
    </form>

    <form method="post" action="<?= e(url('/terminal/accounts')) ?>" class="stack aa-form" data-aa-form="prop" data-presets="<?= e(json_encode(PropRules::presetsForJs())) ?>" hidden><?= csrf_field() ?>
      <input type="hidden" name="kind" value="prop"><input type="hidden" name="return" value="<?= e($return) ?>">
      <div class="grid-form">
        <div class="f"><label for="aa-p-firm">Prop firm name</label><input id="aa-p-firm" name="prop_firm" maxlength="80" placeholder="Type the firm's name" required></div>
        <div class="f"><label for="aa-p-name">Account name <span class="muted">(optional)</span></label><input id="aa-p-name" name="name" maxlength="80" placeholder="Auto: firm + size + phase"></div>
      </div>
      <div class="grid-form">
        <div class="f"><label for="aa-p-cap">Account size (capital)</label><input id="aa-p-cap" name="starting_capital" type="number" step="0.01" min="1" required inputmode="decimal" placeholder="e.g. 100000" list="aa-sizes"><datalist id="aa-sizes"><option value="5000"><option value="10000"><option value="25000"><option value="50000"><option value="100000"><option value="200000"></datalist></div>
        <div class="f"><label for="aa-p-cur">Currency</label><select id="aa-p-cur" name="currency"><?= $curOpts ?></select></div>
      </div>
      <div class="f"><label for="aa-p-preset">Challenge type — fills in typical rules</label>
        <select id="aa-p-preset" name="prop_preset" data-aa-preset><?php foreach (PropRules::PRESETS as $k => $p): ?><option value="<?= e($k) ?>"><?= e($p[0]) ?></option><?php endforeach; ?></select></div>
      <fieldset class="aa-rules"><legend>Rules <span class="muted small">— check your firm's current rules and edit any value</span></legend>
        <div class="aa-rule-grid">
          <div class="f"><label for="aa-r-target">Profit target %</label><input id="aa-r-target" name="profit_target_pct" type="number" step="0.01" min="0" data-rule="target" placeholder="none"></div>
          <div class="f"><label for="aa-r-daily">Daily loss limit %</label><input id="aa-r-daily" name="prop_daily_pct" type="number" step="0.01" min="0" data-rule="daily" placeholder="none"></div>
          <div class="f"><label for="aa-r-total">Max overall loss %</label><input id="aa-r-total" name="max_total_loss_pct" type="number" step="0.01" min="0" data-rule="total" placeholder="none"></div>
          <div class="f"><label for="aa-r-mode">Overall loss type</label><select id="aa-r-mode" name="total_loss_mode" data-rule="mode"><option value="static">Static (from starting balance)</option><option value="trailing">Trailing (from highest balance)</option></select></div>
          <div class="f"><label for="aa-r-days">Min trading days</label><input id="aa-r-days" name="min_trading_days" type="number" step="1" min="0" data-rule="days" placeholder="none"></div>
          <div class="f"><label for="aa-r-cons">Consistency: best day ≤ % of profit</label><input id="aa-r-cons" name="consistency_pct" type="number" step="0.01" min="0" data-rule="consistency" placeholder="none"></div>
        </div>
      </fieldset>
      <?= $mode('p') ?>
      <p class="muted small">The Dashboard shows live progress for every rule. Rules are a journal guide — your firm's own dashboard is always the official record.</p>
      <div class="tm-modal-actions"><button class="tm-btn tm-btn-primary" type="submit">Add prop firm account</button></div>
    </form>
  </div>
</div>
