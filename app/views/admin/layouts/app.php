<?php
/** Control Panel layout. @var string $content @var string $pageTitle @var array $crumbs @var array $flash @var array $me */
use App\Core\Database;

$path = (new App\Core\Request())->path;
$rel = trim(substr($path, strlen('/' . ADMIN_PREFIX)), '/');
$newLeads = can('leads') ? (int) Database::value("SELECT COUNT(*) FROM leads WHERE status = 'new'") : 0;
$newMsgs = can('messages') ? (int) Database::value("SELECT COUNT(*) FROM contact_messages WHERE status = 'new'") : 0;
$nav = [
    '' => [['dashboard', 'Dashboard', 'dashboard', null]],
    'Website' => [
        ['settings', 'General settings', 'settings', 'settings.general'], ['homepage', 'Homepage', 'home', 'homepage'], ['header', 'Header', 'menu', 'settings.general'],
        ['footer', 'Footer', 'section', 'settings.general'], ['navigation', 'Navigation', 'navigation', 'navigation'], ['seo', 'SEO', 'search', 'settings.seo'],
        ['social', 'Social links', 'globe', 'settings.general'], ['contact-details', 'Contact details', 'phone', 'settings.general'],
        ['whatsapp', 'WhatsApp', 'whatsapp', 'settings.whatsapp'], ['analytics', 'Analytics', 'bars', 'settings.analytics'],
    ],
    'Content' => [
        ['pages', 'Pages', 'file', 'pages'], ['services', 'Services', 'layers', 'services'], ['blog', 'Blog', 'book', 'blog'],
        ['testimonials', 'Testimonials', 'quote', 'testimonials'], ['faqs', 'FAQs', 'help', 'faqs'], ['process-steps', 'Process steps', 'activity', 'process'],
        ['sections', 'Custom sections', 'section', 'sections'],
    ],
    'Leads' => [['leads', 'Consultation leads', 'inbox', 'leads', $newLeads], ['messages', 'Contact messages', 'message', 'messages', $newMsgs]],
    'Media' => [['media', 'Media library', 'image', 'media']],
    'Email' => [['email/smtp', 'SMTP settings', 'server', 'email.smtp'], ['email/templates', 'Email templates', 'mail', 'email.templates'], ['email/test', 'Send test email', 'send', 'email.smtp'], ['email/logs', 'Delivery log', 'list', 'email.templates']],
    'Appearance' => [['appearance/colors', 'Colours', 'palette', 'appearance'], ['appearance/typography', 'Typography', 'type', 'appearance'], ['appearance/buttons', 'Buttons', 'zap', 'appearance'], ['appearance/layout', 'Layout options', 'grid', 'appearance'], ['appearance/custom-css', 'Custom CSS', 'code', 'custom_css']],
    'System' => [['admin-users', 'Admin users', 'users', 'users'], ['roles', 'Roles', 'key', 'roles'], ['activity-logs', 'Activity logs', 'activity', 'logs'], ['system', 'System information', 'info', 'system']],
];
$isActive = function (string $href) use ($rel): bool {
    if ($href === 'blog') {
        return $rel === 'blog' || str_starts_with($rel, 'blog/');
    }
    return $rel === $href || str_starts_with($rel, $href . '/');
};
$initials = mb_strtoupper(implode('', array_map(fn ($w) => mb_substr($w, 0, 1), array_slice(preg_split('/\s+/', trim($me['name'])), 0, 2))));
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<meta name="admin-base" content="<?= e(admin_url('')) ?>">
<title><?= e($pageTitle) ?> · Control Panel · <?= e(setting('site_name', 'journzey.ai')) ?></title>
<link rel="icon" href="<?= e(asset('images/favicon.svg')) ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= e(asset('admin/admin.css')) ?>">
</head>
<body class="cp">
<a class="skip-link" href="#cp-main">Skip to content</a>
<div class="cp-shell">
  <aside class="cp-sidebar" id="cp-sidebar" aria-label="Control Panel navigation">
    <div class="cp-brand">
      <span class="brand-mark">j</span>
      <div><strong><?= e(setting('site_name', 'journzey.ai')) ?></strong><small>Control Panel</small></div>
      <button class="icon-btn cp-sidebar-close" type="button" data-sidebar-close aria-label="Close menu"><?= icon('x', 'icon') ?></button>
    </div>
    <nav class="cp-nav">
      <?php foreach ($nav as $group => $items):
          $visible = array_filter($items, fn ($i) => $i[3] === null || can($i[3]));
          if (!$visible) continue; ?>
      <?php if ($group !== ''): ?><p class="cp-nav-group"><?= e($group) ?></p><?php endif; ?>
      <ul>
        <?php foreach ($visible as $i): $active = $isActive($i[0]); ?>
        <li><a href="<?= e(admin_url($i[0])) ?>" class="<?= $active ? 'is-active' : '' ?>"<?= $active ? ' aria-current="page"' : '' ?>><?= icon($i[2], 'icon') ?><span><?= e($i[1]) ?></span><?php if (!empty($i[4])): ?><b class="nav-badge"><?= (int) $i[4] ?></b><?php endif; ?></a></li>
        <?php endforeach; ?>
      </ul>
      <?php endforeach; ?>
    </nav>
    <div class="cp-sidebar-foot">
      <a href="<?= e(url('/')) ?>" target="_blank" rel="noopener"><?= icon('external', 'icon icon-sm') ?> View website</a>
    </div>
  </aside>
  <div class="cp-overlay" data-sidebar-close></div>
  <div class="cp-main">
    <header class="cp-topbar">
      <button class="icon-btn cp-menu-btn" type="button" data-sidebar-open aria-label="Open menu" aria-controls="cp-sidebar"><?= icon('menu', 'icon') ?></button>
      <nav class="cp-crumbs" aria-label="Breadcrumb">
        <ol>
          <li><a href="<?= e(admin_url('dashboard')) ?>"><?= icon('home', 'icon icon-sm') ?><span class="sr-only">Dashboard</span></a></li>
          <?php foreach ($crumbs as $i => [$label, $href]): ?>
          <li><?= icon('chevron-right', 'icon icon-xs') ?><?php if ($href !== null && $i < count($crumbs) - 1): ?><a href="<?= e(admin_url($href)) ?>"><?= e($label) ?></a><?php else: ?><span<?= $i === count($crumbs) - 1 ? ' aria-current="page"' : '' ?>><?= e($label) ?></span><?php endif; ?></li>
          <?php endforeach; ?>
        </ol>
      </nav>
      <div class="cp-top-actions">
        <a class="icon-btn hide-sm" href="<?= e(url('/')) ?>" target="_blank" rel="noopener" title="View website" aria-label="View website"><?= icon('globe', 'icon') ?></a>
        <?php if (can('leads')): ?><a class="icon-btn has-dot<?= $newLeads ? ' dot-on' : '' ?>" href="<?= e(admin_url('leads?status=new')) ?>" title="New leads" aria-label="<?= $newLeads ?> new leads"><?= icon('bell', 'icon') ?></a><?php endif; ?>
        <div class="cp-user" data-dropdown>
          <button type="button" class="cp-user-btn" aria-expanded="false" aria-haspopup="true" data-dropdown-toggle>
            <span class="avatar"><?= e($initials) ?></span>
            <span class="cp-user-meta hide-sm"><strong><?= e($me['name']) ?></strong><small><?= e($me['role_name']) ?></small></span>
            <?= icon('chevron-down', 'icon icon-sm') ?>
          </button>
          <div class="cp-menu" role="menu">
            <a role="menuitem" href="<?= e(admin_url('profile')) ?>"><?= icon('user', 'icon icon-sm') ?> My profile</a>
            <a role="menuitem" href="<?= e(url('/')) ?>" target="_blank" rel="noopener"><?= icon('external', 'icon icon-sm') ?> View website</a>
            <form method="post" action="<?= e(admin_url('logout')) ?>"><?= csrf_field() ?><button role="menuitem" type="submit"><?= icon('logout', 'icon icon-sm') ?> Sign out</button></form>
          </div>
        </div>
      </div>
    </header>
    <main class="cp-content" id="cp-main" tabindex="-1">
      <?= $content ?>
    </main>
  </div>
</div>
<div class="toasts" aria-live="polite" aria-atomic="false" data-toasts>
  <?php foreach ($flash as $f): ?>
  <div class="toast toast-<?= e($f['type']) ?>" role="<?= $f['type'] === 'error' ? 'alert' : 'status' ?>"><?= icon($f['type'] === 'error' ? 'alert' : 'check-circle', 'icon') ?><p><?= e($f['message']) ?></p><button type="button" class="toast-x" aria-label="Dismiss"><?= icon('x', 'icon icon-sm') ?></button></div>
  <?php endforeach; ?>
</div>
<?= App\Core\View::partial('admin/partials/modals') ?>
<script src="<?= e(asset('admin/admin.js')) ?>" defer></script>
</body>
</html>
