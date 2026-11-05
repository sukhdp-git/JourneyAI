<?php
/** Layout for the new landing page. @var array $seo @var string $content */
use App\Core\Seo;
use App\Core\View;

$siteName = setting('site_name', 'journzey.ai');
$favicon = setting('favicon');
$gtm = setting_on('gtm_enabled') && preg_match('/^GTM-[A-Z0-9]+$/', setting('gtm_id')) ? setting('gtm_id') : '';
$ga = setting_on('ga_enabled') && preg_match('/^(G|UA|AW)-[A-Z0-9\-]+$/', setting('ga_id')) ? setting('ga_id') : '';
$pixel = setting_on('pixel_enabled') && ctype_digit(setting('pixel_id')) ? setting('pixel_id') : '';
$askConsent = setting_on('cookie_banner_enabled', true) && ($gtm || $ga || $pixel);
$track = !$askConsent || ($_COOKIE['jz_consent'] ?? '') === 'all';
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($seo['title']) ?></title>
<meta name="description" content="<?= e($seo['description']) ?>">
<link rel="canonical" href="<?= e($seo['canonical']) ?>">
<meta name="robots" content="<?= e($seo['robots']) ?>">
<meta property="og:site_name" content="<?= e($siteName) ?>">
<meta property="og:type" content="<?= e($seo['og_type']) ?>">
<meta property="og:title" content="<?= e($seo['og_title']) ?>">
<meta property="og:description" content="<?= e(excerpt($seo['og_description'] ?: $seo['description'], 300)) ?>">
<meta property="og:url" content="<?= e($seo['canonical']) ?>">
<?php if ($seo['og_image']): ?><meta property="og:image" content="<?= e($seo['og_image']) ?>">
<?php endif; ?>
<meta name="twitter:card" content="<?= e($seo['twitter_card']) ?>">
<?php if ($seo['twitter_site']): ?><meta name="twitter:site" content="<?= e($seo['twitter_site']) ?>">
<?php endif; ?>
<?php if (setting_on('gsc_enabled') && setting('gsc_code') !== ''): ?><meta name="google-site-verification" content="<?= e(preg_replace('/^.*content="([^"]+)".*$/s', '$1', setting('gsc_code'))) ?>">
<?php endif; ?>
<meta name="theme-color" content="#07060c">
<?php if ($favicon): ?><link rel="icon" href="<?= e(media_url($favicon)) ?>"><?php else: ?><link rel="icon" href="<?= e(asset('images/favicon.svg')) ?>" type="image/svg+xml"><?php endif; ?>
<link rel="preload" href="<?= e(asset('fonts/landing/bricolage.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="<?= e(asset('fonts/landing/geist.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= e(asset('css/landing.css')) ?>">
<noscript><style>.rv{opacity:1!important;transform:none!important;filter:none!important}.man .mw{opacity:1!important}.gal{height:auto!important}.gal-pin{position:static!important;height:auto!important}.proc{height:auto!important}.proc-pin{position:static!important;height:auto!important}</style></noscript>
<?php foreach (Seo::globals() as $schema): ?>
<script type="application/ld+json"><?= Seo::json($schema) ?></script>
<?php endforeach; ?>
<?php if ($gtm && $track): ?>
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer',<?= json_encode($gtm) ?>);</script>
<?php endif; ?>
<?php if ($ga && $track): ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($ga) ?>"></script>
<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config',<?= json_encode($ga) ?>);</script>
<?php endif; ?>
<?php if ($pixel && $track): ?>
<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init',<?= json_encode($pixel) ?>);fbq('track','PageView');</script>
<?php endif; ?>
</head>
<body>
<?php if ($gtm && $track): ?><noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?= e($gtm) ?>" height="0" width="0" style="display:none;visibility:hidden" title="Google Tag Manager"></iframe></noscript><?php endif; ?>
<a class="skip" href="#top">Skip to content</a>
<?= $content ?>
<script>window.JZ_IMG=<?= json_encode(url('/public/assets/images/landing') . '/') ?>;</script>
<script src="<?= e(asset('js/landing.js')) ?>" defer></script>
<?php if ($askConsent && !isset($_COOKIE['jz_consent'])): ?><?= View::partial('public/partials/cookie-consent') ?><?php endif; ?>
</body>
</html>
