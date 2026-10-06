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
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap">
<link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/auth.css')) ?>">
<?= App\Core\View::partial('public/partials/theme') ?>
</head>
<body class="auth-page">
<div class="auth-split">
  <aside class="auth-brand">
    <span class="hero-glow" aria-hidden="true"></span><span class="hero-grid" aria-hidden="true"></span>
    <a class="logo" href="<?= e(url('/')) ?>"><?= App\Core\View::partial('public/partials/logo') ?></a>
    <div class="auth-pitch">
      <h1>Trade the plan. Journal the execution. Eliminate the leak.</h1>
      <ul>
        <li><?= icon('bars', 'icon icon-sm') ?><span><strong>Execution analytics</strong> — expectancy, R-multiples, drawdown and sessions from your own trades.</span></li>
        <li><?= icon('brain', 'icon icon-sm') ?><span><strong>Psychology tracking</strong> — emotions, mistakes and a daily notepad with voice dictation.</span></li>
        <li><?= icon('shield', 'icon icon-sm') ?><span><strong>Risk analysis</strong> — Monte Carlo drawdown odds, tilt detection and the discipline leak mirror.</span></li>
        <li><?= icon('sparkles', 'icon icon-sm') ?><span><strong>AI coaching</strong> — feedback grounded in your journal, never signals or promises.</span></li>
      </ul>
    </div>
    <p class="auth-small">Trading involves risk. journzey.ai is journal and analytics software, not investment advice.</p>
  </aside>
  <main class="auth-main" id="main">
    <?= $content ?>
  </main>
</div>
<script src="<?= e(asset('js/auth.js')) ?>" defer></script>
</body>
</html>
