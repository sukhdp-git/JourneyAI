<?php
use App\Core\View;
$fld = fn (array $f) => View::partial('admin/partials/field', ['f' => $f, 'value' => old($f['name'], setting($f['name']))]);
$secret = function (string $name, string $label, string $help = '', string $placeholder = '') use ($saved, $locked) {
    $isLocked = $locked[$name] ?? false;
    ob_start(); ?>
    <div class="f w-half<?= has_error($name) ? ' has-error' : '' ?>">
      <label for="s-<?= e($name) ?>"><?= e($label) ?></label>
      <?php if ($isLocked): ?><input id="s-<?= e($name) ?>" value="Set in config.php" disabled><?php else: ?>
      <div class="pw-wrap"><input type="password" id="s-<?= e($name) ?>" name="<?= e($name) ?>" value="" autocomplete="new-password" spellcheck="false" placeholder="<?= $saved[$name] ? '•••••••• saved — leave empty to keep' : e($placeholder ?: 'Paste key') ?>"><button type="button" class="pw-toggle" data-pw-toggle aria-label="Show value"><?= icon('eye', 'icon icon-sm') ?></button></div>
      <?php if ($saved[$name]): ?><label class="check small"><input type="checkbox" name="clear_<?= e($name) ?>" value="1"> Remove saved value</label><?php endif; ?>
      <?php endif; ?>
      <?= field_error($name) ?><?php if ($help): ?><p class="help"><?= $help ?></p><?php endif; ?>
    </div>
    <?php return ob_get_clean();
};
$badge = fn (bool $ok, string $on = 'Connected', string $off = 'Not configured') => '<span class="badge ' . ($ok ? 'st-published' : 'st-draft') . '">' . e($ok ? $on : $off) . '</span>';
$test = fn (string $which, string $label = 'Test connection') => '<button type="button" class="btn btn-secondary btn-sm" data-test="' . e($which) . '">' . e($label) . '</button> <span class="small muted" data-test-out="' . e($which) . '" aria-live="polite"></span>';
?>
<div class="page-head"><div><h1>Integrations</h1><p class="muted">API keys are stored on the server only. Secrets are encrypted with your APP_KEY and are never shown again after saving.</p></div></div>
<form method="post" action="<?= e(admin_url('integrations')) ?>" autocomplete="off" data-dirty-check novalidate class="stack-lg">
  <?= csrf_field() ?>

  <section class="card form-card">
    <div class="card-head"><h2 class="card-title"><?= icon('globe', 'icon icon-sm') ?> Google sign-in</h2><?= $badge($status['google']) ?></div>
    <p class="muted small">Google Cloud Console → APIs &amp; Services → Credentials → Create OAuth client ID (Web application). Add this <strong>Authorized redirect URI</strong> exactly:</p>
    <p><code id="redir"><?= e($redirectUri) ?></code> <button type="button" class="btn btn-ghost btn-sm" data-copy="#redir">Copy</button></p>
    <div class="form-grid">
      <?= ($locked['google_client_id'] ?? false) ? '<div class="f w-half"><label>Client ID</label><input value="Set in config.php" disabled></div>' : $fld(['name' => 'google_client_id', 'label' => 'Client ID', 'type' => 'text', 'width' => 'half', 'help' => 'Ends with .apps.googleusercontent.com']) ?>
      <?= $secret('google_client_secret', 'Client secret') ?>
    </div>
    <div class="form-foot"><?= $test('google', 'Check') ?></div>
  </section>

  <section class="card form-card">
    <div class="card-head"><h2 class="card-title"><?= icon('zap', 'icon icon-sm') ?> Payments</h2><?= $badge($status['gateway'] !== 'none', $status['gateway'] === 'none' ? '' : ucfirst($status['gateway']) . ' active') ?></div>
    <p class="muted small">Members pay a one-time amount for a plan period; access is activated only after the gateway confirms the payment (signature-verified). Choose one gateway. Manage prices in <a href="<?= e(admin_url('plans')) ?>">Plans</a>.</p>
    <div class="form-grid">
      <?= $fld(['name' => 'payment_gateway', 'label' => 'Active gateway', 'type' => 'select', 'width' => 'half', 'options' => ['none' => 'None (manual grants only)', 'razorpay' => 'Razorpay (India — INR, cards, UPI)', 'stripe' => 'Stripe (international)']]) ?>
      <?= $fld(['name' => 'payment_currency_note', 'label' => 'Note under pricing (optional)', 'type' => 'text', 'width' => 'half', 'help' => 'e.g. “Prices include GST.”']) ?>
    </div>
    <h3 class="h4">Razorpay</h3>
    <p class="muted small">Dashboard → Account &amp; Settings → API Keys. Webhook (Dashboard → Webhooks): URL <code><?= e(url('/webhooks/razorpay')) ?></code>, events <code>payment.captured</code>, <code>order.paid</code>, <code>payment.failed</code>.</p>
    <div class="form-grid">
      <?= $fld(['name' => 'razorpay_key_id', 'label' => 'Key ID', 'type' => 'text', 'width' => 'half', 'help' => 'rzp_test_… or rzp_live_…']) ?>
      <?= $secret('razorpay_key_secret', 'Key secret') ?>
      <?= $secret('razorpay_webhook_secret', 'Webhook secret', 'The secret you type when creating the webhook.') ?>
    </div>
    <div class="form-foot"><?= $test('razorpay') ?></div>
    <h3 class="h4">Stripe</h3>
    <p class="muted small">Developers → API keys → Secret key. Webhook endpoint: <code><?= e(url('/webhooks/stripe')) ?></code> with events <code>checkout.session.completed</code>, <code>checkout.session.async_payment_succeeded</code>, <code>checkout.session.async_payment_failed</code>, <code>charge.refunded</code>.</p>
    <div class="form-grid">
      <?= $secret('stripe_secret_key', 'Secret key', '', 'sk_live_…') ?>
      <?= $secret('stripe_webhook_secret', 'Webhook signing secret', '', 'whsec_…') ?>
    </div>
    <div class="form-foot"><?= $test('stripe') ?></div>
  </section>

  <section class="card form-card">
    <div class="card-head"><h2 class="card-title"><?= icon('message', 'icon icon-sm') ?> AI Coach</h2><?= $badge($status['ai']) ?></div>
    <p class="muted small">The coach sends only aggregated statistics for the signed-in member to the provider. Without a key, members see “AI Coach requires server configuration.” Anthropic requests use server-side fallbacks, so a declined request is retried on a fallback model automatically.</p>
    <div class="form-grid">
      <?= $fld(['name' => 'ai_provider', 'label' => 'Provider', 'type' => 'select', 'width' => 'half', 'options' => ['none' => 'None (AI Coach off)', 'anthropic' => 'Anthropic Claude', 'gemini' => 'Google Gemini']]) ?>
      <?= $fld(['name' => 'ai_model', 'label' => 'Model (optional)', 'type' => 'text', 'width' => 'half', 'help' => 'Default: claude-opus-5-5 (Anthropic) or gemini-2.5-flash (Gemini).']) ?>
      <?= $secret('ai_api_key', 'API key', 'Anthropic: console.anthropic.com → API keys. Gemini: aistudio.google.com → Get API key.') ?>
    </div>
    <div class="form-foot"><?= $test('ai') ?></div>
  </section>

  <section class="card form-card">
    <div class="card-head"><h2 class="card-title"><?= icon('bars', 'icon icon-sm') ?> Market data</h2><?= $badge($status['market'], 'Live quotes', 'Ticker shows DEMO DATA') ?></div>
    <p class="muted small">Live ticker quotes and the Runner Auditor use <a href="https://twelvedata.com" target="_blank" rel="noopener">Twelve Data</a>. Without a key the ticker shows static levels clearly labelled DEMO DATA and the Runner Auditor is disabled.</p>
    <div class="form-grid"><?= $secret('market_data_api_key', 'Twelve Data API key') ?></div>
    <div class="form-foot"><?= $test('market') ?></div>
  </section>

  <section class="card form-card">
    <h2 class="card-title"><?= icon('users', 'icon icon-sm') ?> Member sign-up &amp; free plan</h2>
    <div class="form-grid">
      <?= $fld(['name' => 'signup_enabled', 'label' => 'Allow new sign-ups', 'type' => 'checkbox']) ?>
      <?= $fld(['name' => 'email_signup_enabled', 'label' => 'Allow email + password sign-up (Google sign-up is controlled by the keys above)', 'type' => 'checkbox']) ?>
      <?= $fld(['name' => 'free_plan_name', 'label' => 'Free plan name', 'type' => 'text', 'width' => 'half']) ?>
      <?= $fld(['name' => 'free_ai_daily_limit', 'label' => 'Free AI Coach messages per day', 'type' => 'number', 'width' => 'half']) ?>
      <?= $fld(['name' => 'free_plan_features', 'label' => 'Free plan features (one per line)', 'type' => 'textarea', 'rows' => 4]) ?>
    </div>
  </section>

  <div class="form-foot sticky-foot"><span class="spacer"></span><button class="btn btn-primary" type="submit"><?= icon('check', 'icon icon-sm') ?> Save integrations</button></div>
</form>
<script>
(function () {
  var csrf = document.querySelector('meta[name=csrf-token]').content;
  document.querySelectorAll('[data-test]').forEach(function (b) {
    b.addEventListener('click', function () {
      var out = document.querySelector('[data-test-out="' + b.dataset.test + '"]'); out.textContent = 'Testing saved settings…'; b.disabled = true;
      var fd = new FormData(); fd.append('_csrf', csrf); fd.append('which', b.dataset.test);
      fetch(<?= json_encode(admin_url('integrations/test')) ?>, { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
        .then(function (r) { return r.json(); }).then(function (r) { out.textContent = (r.ok ? '✓ ' : '✕ ') + r.message; out.style.color = r.ok ? 'var(--a-green)' : 'var(--a-red)'; })
        .catch(function () { out.textContent = 'Request failed.'; }).finally(function () { b.disabled = false; });
    });
  });
  document.querySelectorAll('[data-copy]').forEach(function (b) { b.addEventListener('click', function () { navigator.clipboard && navigator.clipboard.writeText(document.querySelector(b.dataset.copy).textContent); b.textContent = 'Copied'; }); });
})();
</script>
