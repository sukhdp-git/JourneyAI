<?php
/** Terminal shell. @var array $m @var array $acc @var array $accounts @var array $balance @var string $nav @var string $content */
use App\Core\Database;
use App\Trading\Domain;

$theme = isset(Domain::THEMES[$m['theme']]) ? $m['theme'] : Domain::DEFAULT_THEME;
$links = [
    ['home', '/terminal', 'nav.home', 'home'], ['dashboard', '/terminal/dashboard', 'nav.dashboard', 'dashboard'], ['calendar', '/terminal/calendar', 'nav.calendar', 'calendar'],
    ['trades', '/terminal/trades', 'nav.trades', 'list'], ['calculator', '/terminal/calculator', 'nav.calculator', 'scale'], ['strategies', '/terminal/strategies', 'nav.strategies', 'target'], ['edge', '/terminal/edge', 'nav.edge', 'grid'],
    ['notepad', '/terminal/notepad', 'nav.notepad', 'journal'], ['coach', '/terminal/coach', 'nav.coach', 'sparkles'], ['university', '/terminal/university', 'nav.university', 'book'],
];
$initials = mb_strtoupper(mb_substr($m['name'], 0, 1));
?><!doctype html>
<html lang="<?= e($m['language'] ?: 'en') ?>" data-theme="<?= e($theme) ?>" data-voice-lang="<?= e(Domain::VOICE_LANGUAGES[$m['language'] ?: 'en'] ?? 'en-US') ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<meta name="app-base" content="<?= e(url('/terminal')) ?>">
<title><?= e($pageTitle) ?> · <?= e(setting('site_name', 'journzey.ai')) ?></title>
<link rel="icon" href="<?= e(setting('favicon') ? media_url(setting('favicon')) : asset('images/favicon.svg')) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&family=Plus+Jakarta+Sans:wght@600;700&display=swap">
<link rel="stylesheet" href="<?= e(asset('css/terminal.css')) ?>">
</head>
<body class="tm">
<a class="skip-link" href="#tm-main">Skip to content</a>
<div class="tm-shell">
  <aside class="tm-side" id="tm-side" aria-label="Terminal navigation">
    <div class="tm-brand">
      <a href="<?= e(url('/')) ?>" class="tm-logo"><span class="tm-mark" aria-hidden="true">j</span><span><?= e(setting('site_name', 'journzey.ai')) ?></span></a>
      <button class="tm-icon-btn tm-side-close" type="button" data-side-close aria-label="Close menu"><?= icon('x', 'icon') ?></button>
    </div>
    <section class="tm-hud" aria-label="Account equity">
      <form method="post" action="<?= e(url('/terminal/account/switch')) ?>" class="tm-acc-switch">
        <?= csrf_field() ?>
        <label class="sr-only" for="acc-switch">Active account</label>
        <select id="acc-switch" name="account_id" data-autosubmit>
          <?php foreach ($accounts as $a): ?><option value="<?= (int) $a['id'] ?>"<?= (int) $a['id'] === (int) $acc['id'] ? ' selected' : '' ?>><?= e(($a['is_demo'] ? '◇ ' : '◆ ') . $a['name']) ?></option><?php endforeach; ?>
        </select>
      </form>
      <div class="tm-hud-row">
        <span class="mode <?= $acc['is_demo'] ? 'demo' : 'live' ?>"><?= e($acc['is_demo'] ? t('hud.demo') : t('hud.live')) ?></span>
        <?php if ((int) $acc['has_demo_data']): ?><span class="mode demo-data"><?= e(t('common.demo_data')) ?></span><?php endif; ?>
        <?php if (!$acc['writable']): ?><span class="mode locked" title="Upgrade to write to live accounts">READ-ONLY</span><?php endif; ?>
      </div>
      <small><?= e(t('hud.equity')) ?></small>
      <strong class="num"><?= e(money($balance['equity'], $acc['currency'])) ?></strong>
      <span class="num <?= $balance['pnl'] >= 0 ? 'up' : 'down' ?>"><?= $balance['pnl'] >= 0 ? '▲' : '▼' ?> <?= e(money($balance['pnl'], $acc['currency'], true)) ?><?= $balance['return'] !== null ? ' · ' . e(($balance['return'] >= 0 ? '+' : '') . number_format($balance['return'] * 100, 2)) . '%' : '' ?></span>
      <?php if ($acc['writable']): ?><button type="button" class="tm-hud-edit" data-open-modal="#tm-equity"><?= icon('edit', 'icon icon-sm') ?> Edit / adjust equity</button><?php endif; ?>
    </section>
    <button type="button" class="tm-quick-btn" data-open-quick><?= icon('zap', 'icon icon-sm') ?> <?= e(t('nav.quick')) ?> <kbd>/</kbd></button>
    <nav class="tm-nav">
      <ul>
        <?php foreach ($links as [$key, $href, $label, $ic]): ?>
        <li><a href="<?= e(url($href)) ?>"<?= $nav === $key ? ' class="on" aria-current="page"' : '' ?>><?= icon($ic, 'icon') ?><span><?= e(t($label)) ?></span></a></li>
        <?php endforeach; ?>
      </ul>
      <ul class="tm-nav-2">
        <li><a href="<?= e(url('/terminal/accounts')) ?>"<?= $nav === 'accounts' ? ' class="on" aria-current="page"' : '' ?>><?= icon('link', 'icon') ?><span><?= e(t('nav.accounts')) ?></span></a></li>
        <?php if (App\Core\Database::value('SELECT id FROM affiliates WHERE user_id = :u', ['u' => $m['id']])): ?><li><a href="<?= e(url('/terminal/affiliate')) ?>"<?= $nav === 'affiliate' ? ' class="on" aria-current="page"' : '' ?>><?= icon('link', 'icon') ?><span><?= e(t('nav.affiliate')) ?></span></a></li><?php endif; ?>
        <li><a href="<?= e(url('/terminal/billing')) ?>"<?= $nav === 'billing' ? ' class="on" aria-current="page"' : '' ?>><?= icon('star', 'icon') ?><span><?= e(t('nav.billing')) ?></span><?php if (!$m['ent']['paid']): ?><b class="tm-badge">FREE</b><?php endif; ?></a></li>
        <li><a href="<?= e(url('/terminal/settings')) ?>"<?= $nav === 'settings' ? ' class="on" aria-current="page"' : '' ?>><?= icon('settings', 'icon') ?><span><?= e(t('nav.settings')) ?></span></a></li>
      </ul>
    </nav>
    <div class="tm-user" data-menu>
      <button type="button" class="tm-user-btn" data-menu-toggle aria-expanded="false" aria-haspopup="true">
        <?php if ($m['avatar_url']): ?><img src="<?= e($m['avatar_url']) ?>" alt="" class="tm-avatar" referrerpolicy="no-referrer"><?php else: ?><span class="tm-avatar"><?= e($initials) ?></span><?php endif; ?>
        <span class="tm-user-meta"><strong><?= e($m['name']) ?></strong><small><?= e($m['email']) ?></small></span>
        <?= icon('chevron-down', 'icon icon-sm') ?>
      </button>
      <div class="tm-menu" role="menu">
        <a role="menuitem" href="<?= e(url('/terminal/settings')) ?>"><?= icon('user', 'icon icon-sm') ?> Account settings</a>
        <a role="menuitem" href="<?= e(url('/terminal/billing')) ?>"><?= icon('star', 'icon icon-sm') ?> <?= e($m['ent']['plan_name']) ?></a>
        <a role="menuitem" href="<?= e(url('/')) ?>"><?= icon('globe', 'icon icon-sm') ?> Website</a>
        <?php $supportMail = setting('support_email') ?: setting('contact_email'); ?>
        <a role="menuitem" href="<?= e($supportMail ? 'mailto:' . $supportMail . '?subject=' . rawurlencode('Support request — ' . $m['email']) : url('/contact')) ?>"><?= icon('help', 'icon icon-sm') ?> Help &amp; support</a>
        <form method="post" action="<?= e(url('/terminal/theme')) ?>" style="padding:6px 10px"><?= csrf_field() ?><label class="small muted" for="tm-theme">Theme</label>
          <select id="tm-theme" name="theme" data-autosubmit style="margin-top:4px"><?php foreach (Domain::THEMES as $k => $l): ?><option value="<?= e($k) ?>"<?= $theme === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></form>
        <form method="post" action="<?= e(url('/logout')) ?>"><?= csrf_field() ?><button role="menuitem" type="submit"><?= icon('logout', 'icon icon-sm') ?> <?= e(t('nav.signout')) ?></button></form>
      </div>
    </div>
  </aside>
  <div class="tm-overlay" data-side-close></div>
  <div class="tm-main-wrap">
    <header class="tm-top">
      <button class="tm-icon-btn tm-burger" type="button" data-side-open aria-label="Open menu" aria-controls="tm-side"><?= icon('menu', 'icon') ?></button>
      <h1 class="tm-title"><?= e($pageTitle) ?></h1>
      <div class="tm-top-right">
        <span class="mode <?= $acc['is_demo'] ? 'demo' : 'live' ?> tm-top-mode"><?= e($acc['is_demo'] ? t('hud.demo') : t('hud.live')) ?></span>
        <span class="tm-top-acc"><?= e($acc['name']) ?></span>
        <?php if (!$m['ent']['paid']): ?><a class="tm-btn tm-btn-sm tm-btn-accent" href="<?= e(url('/pricing')) ?>"><?= e(t('common.upgrade')) ?></a><?php endif; ?>
        <a class="tm-btn tm-btn-sm tm-btn-primary hide-xs" href="<?= e(url('/terminal/trades/new')) ?>"><?= icon('plus', 'icon icon-sm') ?> <?= e(t('common.new_trade')) ?></a>
      </div>
    </header>
    <main class="tm-main" id="tm-main" tabindex="-1">
      <?php foreach (['daily' => 'DAILY', 'weekly' => 'WEEKLY'] as $lk => $lname): $L = $limits[$lk]; if ($L['reached']): ?>
      <div class="tm-alert tm-alert-error limit-alert" role="alert"><?= icon('alert', 'icon') ?><p><strong><?= $lname ?> RISK LIMIT REACHED</strong> — You have reached your configured <?= strtolower($lname) ?> trading loss limit (<?= e(money(-$L['loss'], $acc['currency'])) ?> of <?= e(money($L['limit'], $acc['currency'])) ?><?= $L['type'] === 'percent' ? ' = ' . e(rtrim(rtrim(number_format((float) $L['value'], 2), '0'), '.')) . '%' : '' ?>). Consider stopping trading for <?= $lk === 'daily' ? 'today' : 'the rest of the week' ?>. <span class="muted">This is a journal warning — journzey.ai cannot block orders at your broker.</span></p></div>
      <?php elseif ($L['warning']): ?>
      <div class="tm-alert tm-alert-warn" role="status"><?= icon('alert', 'icon icon-sm') ?><p><strong><?= ucfirst($lk) ?> limit almost used:</strong> <?= e(money(-$L['loss'], $acc['currency'])) ?> of <?= e(money($L['limit'], $acc['currency'])) ?> (<?= (int) round($L['used'] * 100) ?>%). <?= e(money($L['remaining'], $acc['currency'])) ?> left.</p></div>
      <?php endif; endforeach; ?>
      <?php foreach ($flash as $f): ?><div class="tm-alert tm-alert-<?= e($f['type']) ?>" role="<?= $f['type'] === 'error' ? 'alert' : 'status' ?>"><?= icon($f['type'] === 'error' ? 'alert' : 'check-circle', 'icon icon-sm') ?><p><?= e($f['message']) ?></p></div><?php endforeach; ?>
      <?= $content ?>
    </main>
  </div>
</div>
<nav class="tm-tabs" aria-label="Quick tabs">
  <a href="<?= e(url('/terminal')) ?>"<?= $nav === 'home' ? ' class="on"' : '' ?>><?= icon('home', 'icon') ?><span><?= e(t('nav.home')) ?></span></a>
  <a href="<?= e(url('/terminal/dashboard')) ?>"<?= $nav === 'dashboard' ? ' class="on"' : '' ?>><?= icon('dashboard', 'icon') ?><span><?= e(t('nav.dashboard')) ?></span></a>
  <button type="button" class="tm-tab-quick" data-open-quick aria-label="<?= e(t('nav.quick')) ?>"><?= icon('plus', 'icon') ?></button>
  <a href="<?= e(url('/terminal/trades')) ?>"<?= $nav === 'trades' ? ' class="on"' : '' ?>><?= icon('list', 'icon') ?><span><?= e(t('nav.trades')) ?></span></a>
  <button type="button" data-side-open><?= icon('menu', 'icon') ?><span><?= e(t('nav.more')) ?></span></button>
</nav>
<?= App\Core\View::partial('terminal/partials/quick-modal', ['acc' => $acc, 'm' => $m]) ?>
<div class="tm-modal" id="tm-confirm" role="dialog" aria-modal="true" aria-labelledby="tm-confirm-title" hidden>
  <div class="tm-modal-bg" data-close></div>
  <div class="tm-modal-card sm"><h2 id="tm-confirm-title">Are you sure?</h2><p data-confirm-text></p><div class="tm-modal-actions"><button type="button" class="tm-btn" data-close>Cancel</button><button type="button" class="tm-btn tm-btn-danger" data-confirm-ok>Delete</button></div></div>
</div>
<?= App\Core\View::partial('terminal/partials/equity-modal', ['acc' => $acc, 'balance' => $balance, 'history' => $capitalHistory]) ?>
<?= App\Core\View::partial('terminal/partials/share-modal') ?>
<?= App\Core\View::partial('terminal/partials/flex-modal') ?>
<div class="tm-toasts" aria-live="polite" data-toasts></div>
<script src="<?= e(asset('js/charts.js')) ?>" defer></script>
<script src="<?= e(asset('js/terminal.js')) ?>" defer></script>
<script src="<?= e(asset('js/vendor/qrcode.js')) ?>" defer></script>
<script src="<?= e(asset('js/voice-parse.js')) ?>" defer></script>
<script src="<?= e(asset('js/tools.js')) ?>" defer></script>
</body>
</html>
