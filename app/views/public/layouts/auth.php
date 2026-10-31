<?php
/** Split-screen layout for sign-in, sign-up, password reset and onboarding. */
$siteName = setting('site_name', 'journzey.ai');
?><!doctype html>
<html lang="en" class="no-js">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?= e(($title ?? 'Sign in') . ' | ' . $siteName) ?></title>
<link rel="icon" href="<?= e(setting('favicon') ? media_url(setting('favicon')) : asset('images/favicon.svg')) ?>">
<link rel="preload" href="<?= e(url('/public/assets/fonts/inter.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= e(asset('fonts/fonts.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/auth.css')) ?>">
<?= App\Core\View::partial('public/partials/theme') ?>
</head>
<body class="auth-page">
<div class="auth-split">
  <aside class="auth-brand" aria-label="About <?= e($siteName) ?>">
    <div class="auth-market" aria-hidden="true"><span class="am-grid"></span><span class="am-glow"></span></div>
    <a class="logo" href="<?= e(url('/')) ?>"><?= App\Core\View::partial('public/partials/logo') ?></a>
    <div class="auth-pitch">
      <p class="auth-kicker">Trading journal · discipline terminal</p>
      <h1>Trade the plan. Journal the execution. <em>Eliminate the leak.</em></h1>
      <?php // Illustration only — no prices or results. Candles and the equity line draw in once.
      $cd = [[40, 52], [52, 47], [47, 58], [58, 55], [55, 66], [66, 61], [61, 70], [70, 78], [78, 73], [73, 84], [84, 80], [80, 91]]; ?>
      <figure class="auth-visual" aria-hidden="true">
        <svg viewBox="0 0 320 150" focusable="false">
          <defs><linearGradient id="av-brand" gradientUnits="userSpaceOnUse" x1="0" y1="0" x2="320" y2="0"><stop offset="0" stop-color="var(--primary)"/><stop offset="1" stop-color="var(--secondary)"/></linearGradient></defs>
          <g class="av-grid"><line x1="0" x2="320" y1="37" y2="37"/><line x1="0" x2="320" y1="75" y2="75"/><line x1="0" x2="320" y1="113" y2="113"/></g>
          <?php foreach ($cd as $i => [$o, $c]): $x = 22 + $i * 25; $y = fn ($v) => 150 - $v * 1.45; $up = $c >= $o; ?>
          <g class="av-c <?= $up ? 'up' : 'dn' ?>" style="--i:<?= $i ?>"><line x1="<?= $x ?>" x2="<?= $x ?>" y1="<?= round($y(max($o, $c) + 4), 1) ?>" y2="<?= round($y(min($o, $c) - 4), 1) ?>"/><rect x="<?= $x - 5 ?>" y="<?= round($y(max($o, $c)), 1) ?>" width="10" height="<?= round(abs($c - $o) * 1.45, 1) ?>" rx="1.5"/></g>
          <?php endforeach; ?>
          <path class="av-eq" d="M10 108 C 60 104, 80 96, 120 88 S 190 70, 230 52 S 290 30, 312 22" pathLength="1"/>
        </svg>
        <figcaption>Illustration</figcaption>
      </figure>
      <ul>
        <li><?= icon('bars', 'icon icon-sm') ?><span><strong>Execution analytics</strong> — expectancy, R, drawdown and sessions from your own trades.</span></li>
        <li><?= icon('brain', 'icon icon-sm') ?><span><strong>Psychology</strong> — emotions, mistakes and a voice-dictated daily notepad.</span></li>
        <li><?= icon('shield', 'icon icon-sm') ?><span><strong>Risk</strong> — drawdown odds, tilt detection and your rules, measured.</span></li>
        <li><?= icon('sparkles', 'icon icon-sm') ?><span><strong>Mind tab</strong> — next-day roasts, a Death Clock and revenge-trade alerts. Never signals or promises.</span></li>
      </ul>
    </div>
    <p class="auth-small">Trading involves risk. <?= e($siteName) ?> is journal and analytics software, not investment advice.</p>
  </aside>
  <main class="auth-main" id="main">
    <a class="logo auth-mobile-logo" href="<?= e(url('/')) ?>"><?= App\Core\View::partial('public/partials/logo') ?></a>
    <?= $content ?>
    <p class="auth-small auth-mobile-risk">Trading involves risk. <?= e($siteName) ?> is journal and analytics software, not investment advice.</p>
  </main>
</div>
<script src="<?= e(asset('js/auth.js')) ?>" defer></script>
</body>
</html>
