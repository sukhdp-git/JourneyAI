<?php
use App\Core\View;
$email = setting('contact_email'); $support = setting('support_email'); $phone = setting('contact_phone'); $address = setting('contact_address'); $hours = setting('business_hours');
$wa = preg_replace('/\D/', '', setting('contact_whatsapp'));
$map = setting('map_embed_url');
$mapOk = $map !== '' && preg_match('#^https://(www\.)?google\.[a-z.]+/maps/embed\?#i', $map);
?>
<?= View::partial('public/partials/page-hero', ['eyebrow' => setting('contact_eyebrow', 'Contact'), 'title' => setting('contact_heading', 'Contact us'), 'subtitle' => setting('contact_intro'), 'crumbs' => [['Home', '/'], ['Contact', '/contact']]]) ?>
<section class="section section-tight">
  <div class="container form-layout">
    <div class="form-card reveal" id="form">
      <?= View::partial('public/partials/flash', ['flash' => $flash]) ?>
      <h2 class="h3">Send a message</h2>
      <form method="post" action="<?= e(url('/contact')) ?>" class="form" novalidate data-validate>
        <?= csrf_field() ?>
        <input type="hidden" name="_ts" value="<?= e($formTs) ?>">
        <div class="hp" aria-hidden="true"><label for="c-website">Website</label><input id="c-website" type="text" name="website" tabindex="-1" autocomplete="off"></div>
        <div class="form-row">
          <?= View::partial('public/partials/field', ['name' => 'name', 'label' => 'Full name', 'required' => true, 'autocomplete' => 'name', 'max' => 120]) ?>
          <?= View::partial('public/partials/field', ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true, 'autocomplete' => 'email', 'max' => 190]) ?>
        </div>
        <div class="form-row">
          <?= View::partial('public/partials/field', ['name' => 'phone', 'label' => 'Phone', 'type' => 'tel', 'autocomplete' => 'tel', 'max' => 40, 'hint' => 'Optional']) ?>
          <?= View::partial('public/partials/field', ['name' => 'subject', 'label' => 'Subject', 'max' => 200, 'hint' => 'Optional']) ?>
        </div>
        <?= View::partial('public/partials/field', ['name' => 'message', 'label' => 'Message', 'type' => 'textarea', 'required' => true, 'min' => 10, 'max' => 5000]) ?>
        <p class="form-note">By sending this form you agree to our <a href="<?= e(url('/privacy-policy')) ?>">Privacy Policy</a>.</p>
        <button class="btn btn-primary btn-lg" type="submit" data-loading-text="Sending…"><?= icon('send', 'icon icon-sm') ?> Send message</button>
      </form>
    </div>
    <aside class="contact-aside reveal">
      <?php if ($email || $support || $phone || $address || $hours || $wa): ?>
      <ul class="contact-cards stacked">
        <?php if ($support): ?><li class="support-card"><?= icon('help', 'icon') ?><div><small>Customer support</small><a href="mailto:<?= e($support) ?>?subject=<?= rawurlencode('Support request') ?>"><?= e($support) ?></a><span class="muted">Questions about your account, billing or the terminal.</span></div></li><?php endif; ?>
        <?php if ($email): ?><li><?= icon('mail', 'icon') ?><div><small>Email</small><a href="mailto:<?= e($email) ?>"><?= e($email) ?></a></div></li><?php endif; ?>
        <?php if ($phone): ?><li><?= icon('phone', 'icon') ?><div><small>Phone</small><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $phone)) ?>"><?= e($phone) ?></a></div></li><?php endif; ?>
        <?php if ($wa): ?><li><?= icon('whatsapp', 'icon') ?><div><small>WhatsApp</small><a href="https://wa.me/<?= e($wa) ?>" target="_blank" rel="noopener"><?= e(setting('contact_whatsapp')) ?></a></div></li><?php endif; ?>
        <?php if ($address): ?><li><?= icon('map-pin', 'icon') ?><div><small>Address</small><span><?= nl2br(e($address)) ?></span></div></li><?php endif; ?>
        <?php if ($hours): ?><li><?= icon('clock', 'icon') ?><div><small>Business hours</small><span><?= nl2br(e($hours)) ?></span></div></li><?php endif; ?>
      </ul>
      <?php endif; ?>
      <div class="aside-card">
        <h2 class="h4">Try it yourself</h2>
        <p>Create a free account and explore the full terminal with demo data.</p>
        <?= cms_button('Create a free account', '/signup', 'btn btn-ghost btn-block', true) ?>
      </div>
    </aside>
  </div>
</section>
<?php if ($mapOk): ?>
<section class="section section-tight"><div class="container"><div class="map-frame reveal"><iframe src="<?= e($map) ?>" title="Map" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe></div></div></section>
<?php endif; ?>
<?php foreach ($custom as $c) echo View::partial('public/sections/custom', ['c' => $c]); ?>
