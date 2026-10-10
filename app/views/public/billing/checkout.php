<?php use App\Core\View; ?>
<?= View::partial('public/partials/page-hero', ['eyebrow' => 'Checkout', 'title' => $plan['name'], 'subtitle' => $plan['tagline'] ?? '', 'crumbs' => [['Home', '/'], ['Pricing', '/pricing'], ['Checkout', '/checkout/' . $plan['slug']]]]) ?>
<section class="section section-tight">
  <div class="container" style="max-width:720px">
    <?= View::partial('public/partials/flash', ['flash' => $flash]) ?>
    <?php if ($cancelled): ?><div class="alert alert-info" role="status">Checkout was cancelled. You have not been charged.</div><?php endif; ?>
    <div class="form-card">
      <h2 class="h3">Order summary</h2>
      <dl class="summary">
        <dt>Plan</dt><dd><?= e($plan['name']) ?></dd>
        <dt>Access</dt><dd><?= (int) $plan['interval_days'] ?> days<?= $extends ? ' — added to your current access (until ' . e(fmt_date($m['plan_expires_at'], 'M j, Y')) . ')' : ' from today' ?></dd>
        <dt>Account</dt><dd><?= e($m['email']) ?></dd>
        <dt>Total</dt><dd class="total"><?= e(money($plan['price'], $plan['currency'])) ?></dd>
        <?php if ($referred): ?><dt>Partner code</dt><dd>Applied ✓</dd><?php endif; ?>
      </dl>
      <?php if ($gateway === 'none'): ?>
        <div class="alert alert-info" role="status">Online payments are not switched on yet. <a href="<?= e(url('/contact')) ?>">Contact us</a> to upgrade — an administrator can activate your plan manually.</div>
        <?php if (!$referred): ?><form method="post" action="<?= e(url('/checkout/' . $plan['slug'] . '/coupon')) ?>" class="coupon-form"><?= csrf_field() ?><div class="field"><label for="co-coupon">Coupon / affiliate code <span class="muted">(optional)</span></label><div class="coupon-row"><input id="co-coupon" name="coupon" maxlength="32" autocomplete="off" value="<?= e(preg_replace('/[^A-Za-z0-9]/', '', $coupon)) ?>" placeholder="e.g. ARJUN25" style="text-transform:uppercase"><button class="btn btn-secondary" type="submit">Apply</button></div></div></form><?php endif; ?>
      <?php else: ?>
        <form method="post" action="<?= e(url('/checkout/' . $plan['slug'])) ?>" data-checkout="<?= e($gateway) ?>">
          <?= csrf_field() ?>
          <?php if (!$referred): ?><div class="field"><label for="co-coupon">Coupon / affiliate code <span class="muted">(optional)</span></label><input id="co-coupon" name="coupon" maxlength="32" autocomplete="off" value="<?= e(preg_replace('/[^A-Za-z0-9]/', '', $coupon)) ?>" placeholder="e.g. ARJUN25" style="text-transform:uppercase"></div><?php endif; ?>
          <label class="check"><input type="checkbox" name="terms" value="1" required> I agree to the <a href="<?= e(url('/terms-and-conditions')) ?>" target="_blank">Terms</a> and understand journzey.ai is a journal and analytics tool, not financial advice.</label>
          <button class="btn btn-primary btn-lg btn-block" type="submit" data-loading-text="Starting secure checkout…">Pay <?= e(money($plan['price'], $plan['currency'])) ?> securely with <?= $gateway === 'razorpay' ? 'Razorpay' : 'Stripe' ?></button>
          <p class="form-note">Payment is processed by <?= $gateway === 'razorpay' ? 'Razorpay' : 'Stripe' ?>. journzey.ai never sees or stores your card details. One-time payment, no auto-renewal.</p>
        </form>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php if ($gateway === 'razorpay'): ?>
<script src="https://checkout.razorpay.com/v1/checkout.js" defer></script>
<script>
(function () {
  var form = document.querySelector('[data-checkout=razorpay]'); if (!form) return;
  form.addEventListener('submit', function (e) {
    e.preventDefault();
    if (!form.reportValidity()) return;
    var btn = form.querySelector('[type=submit]'); btn.disabled = true;
    fetch(form.action, { method: 'POST', body: new FormData(form), headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, credentials: 'same-origin' })
      .then(function (r) { return r.json(); }).then(function (o) {
        if (!o.ok) { alert(o.error || 'Could not start checkout.'); btn.disabled = false; return; }
        var rz = new Razorpay({ key: o.key_id, order_id: o.order_id, amount: o.amount, currency: o.currency, name: <?= json_encode(setting('site_name', 'journzey.ai')) ?>,
          description: <?= json_encode($plan['name']) ?>, prefill: { email: <?= json_encode($m['email']) ?>, name: <?= json_encode($m['name']) ?> },
          handler: function (resp) {
            var f = document.createElement('form'); f.method = 'post'; f.action = <?= json_encode(url('/checkout/razorpay/verify')) ?>;
            var add = function (k, v) { var i = document.createElement('input'); i.type = 'hidden'; i.name = k; i.value = v; f.appendChild(i); };
            add('_csrf', form.querySelector('[name=_csrf]').value); add('razorpay_order_id', resp.razorpay_order_id); add('razorpay_payment_id', resp.razorpay_payment_id); add('razorpay_signature', resp.razorpay_signature);
            document.body.appendChild(f); f.submit();
          },
          modal: { ondismiss: function () { btn.disabled = false; } } });
        rz.open();
      }).catch(function () { alert('Network error. Please try again.'); btn.disabled = false; });
  });
})();
</script>
<?php endif; ?>
