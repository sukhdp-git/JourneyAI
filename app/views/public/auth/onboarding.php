<?php use App\Trading\Domain; $o = fn ($k, $d = '') => old($k, $d);
$GLOBALS['__old'] += ['starting_capital' => '10000', 'currency' => $m['base_currency'] ?: 'USD', 'risk_pct' => '1', 'target_rr' => '2', 'timezone' => $m['timezone'] ?: 'UTC', '_tz_auto' => '1']; ?>
<div class="auth-card wide onboarding" data-stepper>
  <ol class="steps-bar" aria-hidden="true"><li class="on">Welcome</li><li>Markets</li><li>Account</li><li>Risk</li><li>Timezone</li><li>Finish</li></ol>
  <?= App\Core\View::partial('public/partials/flash', ['flash' => $flash]) ?>
  <form method="post" action="<?= e(url('/onboarding')) ?>" class="form" novalidate>
    <?= csrf_field() ?>
    <section class="ob-step" data-step="0">
      <h2>Welcome to <?= e(setting('site_name', 'journzey.ai')) ?>, <?= e(explode(' ', $m['name'])[0]) ?></h2>
      <p class="muted">Six quick steps set up your terminal. You can change everything later in Settings.</p>
      <p>Your first account is a <strong>demo account</strong> — log test trades freely. Live accounts are available on a paid plan.</p>
    </section>
    <section class="ob-step" data-step="1">
      <h2>Which markets do you trade?</h2>
      <div class="chip-select">
        <?php foreach (Domain::ASSET_CLASSES as $k => $l): ?><label><input type="checkbox" name="markets[]" value="<?= e($k) ?>"<?= in_array($k, (array) $o('markets', ['METALS', 'FOREX']), true) ? ' checked' : '' ?>><span><?= e($l) ?></span></label><?php endforeach; ?>
      </div>
    </section>
    <section class="ob-step" data-step="2">
      <h2>Your demo account</h2>
      <p class="muted">You start with one demo account. Add your broker and prop-firm accounts anytime from the account menu → <strong>＋ Add account</strong>.</p>
      <div class="form-row">
        <?= App\Core\View::partial('public/partials/field', ['name' => 'starting_capital', 'label' => 'Starting capital', 'type' => 'number', 'required' => true]) ?>
        <?= App\Core\View::partial('public/partials/field', ['name' => 'currency', 'label' => 'Currency', 'type' => 'select', 'options' => array_combine(Domain::CURRENCIES, Domain::CURRENCIES)]) ?>
      </div>
      <p class="muted small">Used for the empty demo account. If you load the demo journal (last step) it uses its own $25,000 sample balance.</p>
    </section>
    <section class="ob-step" data-step="3">
      <h2>Risk settings</h2>
      <div class="form-row">
        <?= App\Core\View::partial('public/partials/field', ['name' => 'risk_pct', 'label' => 'Risk per trade (%)', 'type' => 'number', 'required' => true]) ?>
        <?= App\Core\View::partial('public/partials/field', ['name' => 'target_rr', 'label' => 'Target R', 'type' => 'number']) ?>
      </div>
      <div class="form-row">
        <?= App\Core\View::partial('public/partials/field', ['name' => 'max_daily_loss', 'label' => 'Max daily loss (amount)', 'type' => 'number', 'hint' => 'Optional']) ?>
        <?= App\Core\View::partial('public/partials/field', ['name' => 'max_weekly_loss', 'label' => 'Max weekly loss (amount)', 'type' => 'number', 'hint' => 'Optional']) ?>
      </div>
    </section>
    <section class="ob-step" data-step="4">
      <h2>Your timezone</h2>
      <p class="muted">Daily P&amp;L, the calendar and journals use this timezone. We detected your browser’s timezone — change it if you trade on another clock.</p>
      <?= App\Core\View::partial('public/partials/field', ['name' => 'timezone', 'label' => 'Timezone', 'type' => 'select', 'options' => array_combine(DateTimeZone::listIdentifiers(), DateTimeZone::listIdentifiers())]) ?>
    </section>
    <section class="ob-step" data-step="5">
      <h2>Finish setup</h2>
      <label class="ob-demo"><input type="checkbox" name="load_demo" value="1" checked> <span><strong>Load the demo journal</strong> — 42 sample trades and 13 journal entries (Aug–Oct 2026), clearly marked <em>DEMO DATA</em>, as your demo account. Great for exploring the analytics.</span></label>
      <p class="muted small">Untick to start with an empty account.</p>
    </section>
    <div class="ob-nav">
      <button type="button" class="btn btn-ghost" data-prev>Back</button>
      <button type="button" class="btn btn-primary" data-next>Continue</button>
      <button type="submit" class="btn btn-primary" data-finish>Open my terminal</button>
    </div>
  </form>
</div>
