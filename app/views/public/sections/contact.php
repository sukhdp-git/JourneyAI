<?php
$email = setting('contact_email'); $phone = setting('contact_phone'); $address = setting('contact_address'); $hours = setting('business_hours');
$wa = preg_replace('/\D/', '', setting('contact_whatsapp'));
?>
<section <?= section_attrs($s, 'section-contact') ?>>
  <div class="container contact-band reveal">
    <div>
      <?php if ($s['eyebrow']): ?><p class="eyebrow"><?= e($s['eyebrow']) ?></p><?php endif; ?>
      <h2><?= e($s['heading']) ?></h2>
      <?php if ($s['subheading']): ?><p class="lead"><?= nl2br(e($s['subheading'])) ?></p><?php endif; ?>
      <div class="actions"><?= cms_button($s['cta_label'], $s['cta_url'], 'btn btn-primary', true) ?></div>
    </div>
    <?php if ($email || $phone || $address || $hours || $wa): ?>
    <ul class="contact-cards">
      <?php if ($email): ?><li><?= icon('mail', 'icon') ?><div><small>Email</small><a href="mailto:<?= e($email) ?>"><?= e($email) ?></a></div></li><?php endif; ?>
      <?php if ($phone): ?><li><?= icon('phone', 'icon') ?><div><small>Phone</small><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $phone)) ?>"><?= e($phone) ?></a></div></li><?php endif; ?>
      <?php if ($wa): ?><li><?= icon('whatsapp', 'icon') ?><div><small>WhatsApp</small><a href="https://wa.me/<?= e($wa) ?>" target="_blank" rel="noopener"><?= e(setting('contact_whatsapp')) ?></a></div></li><?php endif; ?>
      <?php if ($address): ?><li><?= icon('map-pin', 'icon') ?><div><small>Address</small><span><?= nl2br(e($address)) ?></span></div></li><?php endif; ?>
      <?php if ($hours): ?><li><?= icon('clock', 'icon') ?><div><small>Hours</small><span><?= nl2br(e($hours)) ?></span></div></li><?php endif; ?>
    </ul>
    <?php endif; ?>
  </div>
</section>
