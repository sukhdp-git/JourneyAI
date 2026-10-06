<?php
/** Public layout. @var array $seo @var string $content */
use App\Core\Seo;
use App\Models\Navigation;

$siteName = setting('site_name', 'journzey.ai');
$favicon = setting('favicon');
$fontH = setting('font_heading', 'Space Grotesk');
$fontB = setting('font_body', 'Inter');
$fonts = array_unique(array_filter([$fontH, $fontB], fn ($f) => $f !== '' && $f !== 'System'));
$current = (new App\Core\Request())->path;
$isHome = $current === '/';
$gtm = setting_on('gtm_enabled') && preg_match('/^GTM-[A-Z0-9]+$/', setting('gtm_id')) ? setting('gtm_id') : '';
$ga = setting_on('ga_enabled') && preg_match('/^(G|UA|AW)-[A-Z0-9\-]+$/', setting('ga_id')) ? setting('ga_id') : '';
$pixel = setting_on('pixel_enabled') && ctype_digit(setting('pixel_id')) ? setting('pixel_id') : '';
?><!doctype html>
<html lang="en" class="no-js">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
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
<?php if ($seo['published']): ?><meta property="article:published_time" content="<?= e(gmdate('c', strtotime($seo['published']))) ?>">
<?php endif; ?>
<?php if (setting_on('gsc_enabled') && setting('gsc_code') !== ''): ?><meta name="google-site-verification" content="<?= e(preg_replace('/^.*content="([^"]+)".*$/s', '$1', setting('gsc_code'))) ?>">
<?php endif; ?>
<meta name="theme-color" content="<?= e(setting('color_bg', '#070a12')) ?>">
<?php if ($favicon): ?><link rel="icon" href="<?= e(media_url($favicon)) ?>"><?php else: ?><link rel="icon" href="<?= e(asset('images/favicon.svg')) ?>" type="image/svg+xml"><?php endif; ?>
<?php if ($fonts): ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?<?= e(implode('&', array_map(fn ($f) => 'family=' . str_replace(' ', '+', $f) . ':wght@400;500;600;700', $fonts))) ?>&display=swap">
<?php endif; ?>
<link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>">
<?= App\Core\View::partial('public/partials/theme') ?>
<?php foreach (Seo::globals() as $schema): ?>
<script type="application/ld+json"><?= Seo::json($schema) ?></script>
<?php endforeach; ?>
<?php if ($gtm): ?>
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer',<?= json_encode($gtm) ?>);</script>
<?php endif; ?>
<?php if ($ga): ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($ga) ?>"></script>
<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config',<?= json_encode($ga) ?>);</script>
<?php endif; ?>
<?php if ($pixel): ?>
<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init',<?= json_encode($pixel) ?>);fbq('track','PageView');</script>
<?php endif; ?>
<script>document.documentElement.classList.replace('no-js','js');</script>
</head>
<body class="<?= $isHome ? 'is-home' : 'is-inner' ?><?= setting_on('animations_enabled', true) ? ' has-motion' : '' ?>">
<?php if ($gtm): ?><noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?= e($gtm) ?>" height="0" width="0" style="display:none;visibility:hidden" title="Google Tag Manager"></iframe></noscript><?php endif; ?>
<a class="skip-link" href="#main">Skip to content</a>
<?= App\Core\View::partial('public/partials/header', ['current' => $current, 'isHome' => $isHome, 'nav' => Navigation::tree('header')]) ?>
<main id="main" tabindex="-1">
<?= $content ?>
</main>
<?= App\Core\View::partial('public/partials/footer', []) ?>
<?= App\Core\View::partial('public/partials/whatsapp', []) ?>
<script src="<?= e(asset('js/site.js')) ?>" defer></script>
</body>
</html>
