<?php
$desktop = setting('logo_desktop');
$mobile = setting('logo_mobile') ?: $desktop;
$name = setting('site_name', 'journzey.ai');
if ($desktop): ?>
<img class="logo-img logo-desktop" src="<?= e(media_url($desktop)) ?>" alt="<?= e($name) ?>" height="36">
<img class="logo-img logo-mobile" src="<?= e(media_url($mobile)) ?>" alt="<?= e($name) ?>" height="32">
<?php else: ?>
<span class="logo-mark" aria-hidden="true"><svg viewBox="0 0 32 32" width="32" height="32"><defs><linearGradient id="lg-<?= $uid = bin2hex(random_bytes(3)) ?>" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="var(--primary)"/><stop offset="1" stop-color="var(--secondary)"/></linearGradient></defs><rect width="32" height="32" rx="9" fill="url(#lg-<?= $uid ?>)"/><path d="M7 21.5l5.5-5.5 4 3.5L25 10" fill="none" stroke="#fff" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/><circle cx="25" cy="10" r="2.2" fill="#fff"/></svg></span>
<span class="logo-text"><?= e($name) ?></span>
<?php endif;
