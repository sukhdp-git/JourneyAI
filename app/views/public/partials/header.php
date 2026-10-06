<?php
/** @var array $nav @var string $current @var bool $isHome */
$sticky = setting_on('header_sticky', true);
$transparent = $isHome && setting_on('header_transparent_home', true);
$member = member();
$cta = $member || (setting_on('header_cta_enabled', true) && setting('header_cta_label') !== '');
$ctaLabel = $member ? 'Open terminal' : setting('header_cta_label');
$ctaUrl = $member ? '/terminal' : setting('header_cta_url', '/signup');
?>
<?php if (setting_on('announcement_enabled') && setting('announcement_text') !== ''): ?>
<div class="announcement">
  <div class="container">
    <?php if (setting('announcement_url') !== ''): ?><a href="<?= e(url(setting('announcement_url'))) ?>"><?= e(setting('announcement_text')) ?> <?= icon('arrow-right', 'icon icon-sm') ?></a><?php else: ?><span><?= e(setting('announcement_text')) ?></span><?php endif; ?>
  </div>
</div>
<?php endif; ?>
<header class="site-header<?= $sticky ? ' is-sticky' : '' ?><?= $transparent ? ' is-transparent' : '' ?>" data-header>
  <div class="container header-inner">
    <a class="logo" href="<?= e(url('/')) ?>" aria-label="<?= e(setting('site_name', 'journzey.ai')) ?> — home"><?= App\Core\View::partial('public/partials/logo') ?></a>
    <nav class="main-nav" aria-label="Main">
      <ul class="nav-list">
        <?php foreach ($nav as $i => $item): $active = is_active_path(url($item['url']), $current); ?>
        <li class="nav-item<?= $item['children'] ? ' has-dropdown' : '' ?>">
          <a class="nav-link<?= $active ? ' is-active' : '' ?>" href="<?= e(url($item['url'])) ?>"<?= $item['target'] === '_blank' ? ' target="_blank" rel="noopener"' : '' ?><?= $active ? ' aria-current="page"' : '' ?>><?= e($item['label']) ?></a>
          <?php if ($item['children']): ?>
          <button class="dropdown-toggle" type="button" aria-expanded="false" aria-controls="dd-<?= $i ?>" aria-label="Show <?= e($item['label']) ?> menu"><?= icon('chevron-down', 'icon icon-sm') ?></button>
          <ul class="dropdown" id="dd-<?= $i ?>">
            <?php foreach ($item['children'] as $child): ?>
            <li><a href="<?= e(url($child['url'])) ?>"<?= $child['target'] === '_blank' ? ' target="_blank" rel="noopener"' : '' ?><?= is_active_path(url($child['url']), $current) ? ' aria-current="page" class="is-active"' : '' ?>><?= e($child['label']) ?></a></li>
            <?php endforeach; ?>
          </ul>
          <?php endif; ?>
        </li>
        <?php endforeach; ?>
      </ul>
    </nav>
    <div class="header-actions">
      <?php if (!$member && setting('header_secondary_label') !== '' && setting('header_secondary_url') !== ''): ?>
      <a class="btn btn-ghost btn-sm hide-mobile" href="<?= e(url(setting('header_secondary_url'))) ?>"><?= e(setting('header_secondary_label')) ?></a>
      <?php endif; ?>
      <?php if ($cta): ?>
      <a class="btn btn-primary btn-sm hide-mobile" href="<?= e(url($ctaUrl)) ?>"><?= e($ctaLabel) ?></a>
      <?php endif; ?>
      <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="mobile-menu" data-menu-toggle>
        <span class="sr-only">Open menu</span><span class="burger" aria-hidden="true"><span></span><span></span><span></span></span>
      </button>
    </div>
  </div>
</header>
<div class="mobile-menu" id="mobile-menu" hidden data-mobile-menu>
  <nav aria-label="Mobile">
    <ul>
      <?php foreach ($nav as $i => $item): ?>
      <li>
        <?php if ($item['children']): ?>
        <div class="m-row">
          <a href="<?= e(url($item['url'])) ?>"<?= is_active_path(url($item['url']), $current) ? ' aria-current="page"' : '' ?>><?= e($item['label']) ?></a>
          <button type="button" class="m-sub-toggle" aria-expanded="false" aria-controls="m-sub-<?= $i ?>" aria-label="Show <?= e($item['label']) ?> links"><?= icon('chevron-down', 'icon') ?></button>
        </div>
        <ul class="m-sub" id="m-sub-<?= $i ?>" hidden>
          <?php foreach ($item['children'] as $child): ?>
          <li><a href="<?= e(url($child['url'])) ?>"<?= $child['target'] === '_blank' ? ' target="_blank" rel="noopener"' : '' ?>><?= e($child['label']) ?></a></li>
          <?php endforeach; ?>
        </ul>
        <?php else: ?>
        <a href="<?= e(url($item['url'])) ?>"<?= $item['target'] === '_blank' ? ' target="_blank" rel="noopener"' : '' ?><?= is_active_path(url($item['url']), $current) ? ' aria-current="page"' : '' ?>><?= e($item['label']) ?></a>
        <?php endif; ?>
      </li>
      <?php endforeach; ?>
    </ul>
    <?php if (!$member && setting('header_secondary_label') !== '' && setting('header_secondary_url') !== ''): ?><a class="btn btn-ghost btn-block" href="<?= e(url(setting('header_secondary_url'))) ?>"><?= e(setting('header_secondary_label')) ?></a><?php endif; ?>
    <?php if ($cta): ?><a class="btn btn-primary btn-block" href="<?= e(url($ctaUrl)) ?>"><?= e($ctaLabel) ?></a><?php endif; ?>
  </nav>
</div>
