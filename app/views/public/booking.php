<?php
use App\Core\View;
$points = array_filter(array_map('trim', preg_split('/\r?\n/', setting('booking_points'))));
$opts = ['' => 'Select an option'];
foreach ($services as $s) $opts[(string) $s['id']] = $s['title'];
$opts['other'] = 'Other / not sure';
?>
<?= View::partial('public/partials/page-hero', ['eyebrow' => setting('booking_eyebrow', 'Book a consultation'), 'title' => setting('booking_heading', 'Book a consultation'), 'subtitle' => setting('booking_intro'), 'crumbs' => [['Home', '/'], [setting('booking_eyebrow', 'Book a consultation'), '/book-consultation']]]) ?>
<section class="section section-tight">
  <div class="container form-layout">
    <div class="form-card reveal" id="form">
      <?= View::partial('public/partials/flash', ['flash' => $flash]) ?>
      <h2 class="h3">Your details</h2>
      <form method="post" action="<?= e(url('/book-consultation')) ?>" class="form" novalidate data-validate>
        <?= csrf_field() ?>
        <input type="hidden" name="_ts" value="<?= e($formTs) ?>">
        <div class="hp" aria-hidden="true"><label for="b-website">Website</label><input id="b-website" type="text" name="website" tabindex="-1" autocomplete="off"></div>
        <div class="form-row">
          <?= View::partial('public/partials/field', ['name' => 'name', 'label' => 'Full name', 'required' => true, 'autocomplete' => 'name', 'max' => 120]) ?>
          <?= View::partial('public/partials/field', ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true, 'autocomplete' => 'email', 'max' => 190]) ?>
        </div>
        <div class="form-row">
          <?= View::partial('public/partials/field', ['name' => 'phone', 'label' => 'Phone', 'type' => 'tel', 'required' => true, 'autocomplete' => 'tel', 'max' => 40]) ?>
          <?= View::partial('public/partials/field', ['name' => 'whatsapp', 'label' => 'WhatsApp', 'type' => 'tel', 'max' => 40, 'hint' => 'Optional']) ?>
        </div>
        <div class="form-row">
          <?= View::partial('public/partials/field', ['name' => 'company', 'label' => 'Company / desk', 'max' => 150, 'hint' => 'Optional', 'autocomplete' => 'organization']) ?>
          <?= View::partial('public/partials/field', ['name' => 'service_id', 'label' => 'Interested in', 'type' => 'select', 'options' => $opts]) ?>
        </div>
        <div class="form-row">
          <?= View::partial('public/partials/field', ['name' => 'preferred_date', 'label' => 'Preferred date', 'type' => 'date', 'minDate' => gmdate('Y-m-d'), 'hint' => 'Optional']) ?>
          <?= View::partial('public/partials/field', ['name' => 'preferred_time', 'label' => 'Preferred time', 'type' => 'time', 'hint' => 'Optional · your local time']) ?>
        </div>
        <?= View::partial('public/partials/field', ['name' => 'message', 'label' => 'Anything we should know?', 'type' => 'textarea', 'max' => 5000, 'hint' => 'Optional — markets you trade, number of accounts, goals']) ?>
        <div class="field field-check<?= has_error('consent') ? ' has-error' : '' ?>">
          <label><input type="checkbox" name="consent" value="1" required<?= old('consent') ? ' checked' : '' ?><?= has_error('consent') ? ' aria-invalid="true" aria-describedby="err-consent"' : '' ?>> <span><?= e(setting('booking_consent_text', 'I agree to the Privacy Policy.')) ?> <a href="<?= e(url('/privacy-policy')) ?>">Privacy Policy</a></span></label>
          <?= field_error('consent') ?>
        </div>
        <button class="btn btn-primary btn-lg" type="submit" data-loading-text="Sending…"><?= icon('calendar', 'icon icon-sm') ?> Request my session</button>
      </form>
    </div>
    <aside class="contact-aside reveal">
      <?php if ($points): ?>
      <div class="aside-card">
        <h2 class="h4">What we will cover</h2>
        <ul class="check-list"><?php foreach ($points as $p): ?><li><?= icon('check', 'icon icon-sm') ?><?= e($p) ?></li><?php endforeach; ?></ul>
      </div>
      <?php endif; ?>
      <div class="aside-card subtle">
        <p><?= icon('lock', 'icon icon-sm') ?> Your details are used only to arrange and follow up on your request. See our <a href="<?= e(url('/privacy-policy')) ?>">Privacy Policy</a>.</p>
      </div>
    </aside>
  </div>
</section>
