<?php
/** One lazily loaded TradingView widget. @var string $type @var array $m @var int $height @var string $label @var array $opt */
$w = App\Trading\MarketWidgets::widget($type, (string) ($m['theme'] ?? ''), (string) ($m['language'] ?? 'en'), $opt ?? []);
?>
<div class="tv-widget tv-<?= e($type) ?>" style="--tv-h:<?= (int) $height ?>px" data-tv-src="<?= e($w['src']) ?>" data-tv-config="<?= e(json_encode($w['config'], JSON_UNESCAPED_SLASHES)) ?>" role="region" aria-label="<?= e($label) ?>">
  <div class="tv-loading"><span></span><?= e($label) ?> loading…</div>
  <noscript><p class="muted small">Enable JavaScript to see the <?= e($label) ?>.</p></noscript>
</div>
<p class="tv-credit"><a href="https://www.tradingview.com/" rel="noopener nofollow" target="_blank"><?= e($label) ?> by TradingView</a></p>
