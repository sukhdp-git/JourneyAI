<?php
use App\Models\Content;
use App\Models\Navigation;

$socials = ['social_x' => ['x-social', 'X'], 'social_linkedin' => ['linkedin', 'LinkedIn'], 'social_youtube' => ['youtube', 'YouTube'], 'social_instagram' => ['instagram', 'Instagram'], 'social_facebook' => ['facebook', 'Facebook'], 'social_discord' => ['discord', 'Discord'], 'social_telegram' => ['telegram', 'Telegram'], 'social_github' => ['github', 'GitHub']];
$col1 = setting_on('footer_show_services', true) ? array_map(fn ($s) => ['label' => $s['title'], 'url' => '/services/' . $s['slug'], 'target' => '_self'], Content::services(8)) : Navigation::tree('footer_1');
$cols = [[setting('footer_col1_title', 'Platform'), $col1], [setting('footer_col2_title', 'Company'), Navigation::tree('footer_2')], [setting('footer_col3_title', 'Legal'), Navigation::tree('footer_3')]];
$phone = setting('contact_phone');
$email = setting('contact_email');
$address = setting('contact_address');
$hours = setting('business_hours');
$hasContact = setting_on('footer_show_contact', true) && ($phone || $email || $address || $hours);
$copyright = str_replace('{year}', gmdate('Y'), setting('copyright_text', '© {year} ' . setting('site_name')));
?>
<footer class="site-footer">
  <?php if (setting_on('footer_cta_enabled', true) && setting('footer_cta_heading') !== ''): ?>
  <div class="container">
    <div class="footer-cta reveal">
      <div>
        <h2><?= e(setting('footer_cta_heading')) ?></h2>
        <?php if (setting('footer_cta_text')): ?><p><?= e(setting('footer_cta_text')) ?></p><?php endif; ?>
      </div>
      <?php if (setting('footer_cta_label')): ?><a class="btn btn-primary btn-lg" href="<?= e(url(setting('footer_cta_url', '/book-consultation'))) ?>"><?= e(setting('footer_cta_label')) ?> <?= icon('arrow-right', 'icon icon-sm') ?></a><?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
  <div class="container footer-grid">
    <div class="footer-brand">
      <a class="logo" href="<?= e(url('/')) ?>"><?= App\Core\View::partial('public/partials/logo') ?></a>
      <?php if (setting('footer_about')): ?><p><?= e(setting('footer_about')) ?></p><?php endif; ?>
      <?php if (setting_on('footer_show_social', true)): ?>
      <ul class="social" aria-label="Social media">
        <?php foreach ($socials as $key => [$ic, $label]): if (setting($key) === '') continue; ?>
        <li><a href="<?= e(setting($key)) ?>" target="_blank" rel="noopener" aria-label="<?= e($label) ?>"><?= icon($ic, 'icon') ?></a></li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>
    <?php foreach ($cols as [$title, $links]): if (!$links) continue; ?>
    <nav class="footer-col" aria-label="<?= e($title) ?>">
      <h3><?= e($title) ?></h3>
      <ul>
        <?php foreach ($links as $l): ?>
        <li><a href="<?= e(url($l['url'])) ?>"<?= ($l['target'] ?? '_self') === '_blank' ? ' target="_blank" rel="noopener"' : '' ?>><?= e($l['label']) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </nav>
    <?php endforeach; ?>
    <?php if ($hasContact): ?>
    <div class="footer-col">
      <h3>Contact</h3>
      <ul class="contact-list">
        <?php if ($email): ?><li><?= icon('mail', 'icon icon-sm') ?><a href="mailto:<?= e($email) ?>"><?= e($email) ?></a></li><?php endif; ?>
        <?php if ($phone): ?><li><?= icon('phone', 'icon icon-sm') ?><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $phone)) ?>"><?= e($phone) ?></a></li><?php endif; ?>
        <?php if ($address): ?><li><?= icon('map-pin', 'icon icon-sm') ?><span><?= nl2br(e($address)) ?></span></li><?php endif; ?>
        <?php if ($hours): ?><li><?= icon('clock', 'icon icon-sm') ?><span><?= nl2br(e($hours)) ?></span></li><?php endif; ?>
      </ul>
    </div>
    <?php endif; ?>
  </div>
  <div class="container footer-bottom">
    <p><?= e($copyright) ?></p>
    <?php if (setting('footer_disclaimer')): ?><p class="disclaimer"><?= e(setting('footer_disclaimer')) ?></p><?php endif; ?>
  </div>
</footer>
