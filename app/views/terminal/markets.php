<?php
use App\Core\View;
/** @var string $tab @var array $tabs */
$w = fn (string $type, int $h, string $label, array $opt = []) => View::partial('terminal/partials/tv-widget', ['type' => $type, 'm' => $m, 'height' => $h, 'label' => $label, 'opt' => $opt]);
?>
<div class="mk-tabs" role="tablist" aria-label="Market widgets" data-mk-tabs>
  <?php foreach ($tabs as $k => [$label, $ic]): ?><button type="button" role="tab" data-mk-tab="<?= e($k) ?>" aria-selected="<?= $k === $tab ? 'true' : 'false' ?>"<?= $k === $tab ? ' class="on"' : '' ?>><?= icon($ic, 'icon icon-sm') ?><?= e($label) ?></button><?php endforeach; ?>
</div>
<section class="panel mk-panel" data-mk-panel="stocks"<?= $tab === 'stocks' ? '' : ' hidden' ?>>
  <div class="panel-head"><h2>Stock heatmap</h2><label class="sr-only" for="mk-src">Index</label><select id="mk-src" data-mk-source><option value="SPX500">S&amp;P 500</option><option value="NASDAQ100">Nasdaq 100</option></select></div>
  <?= $w('stocks', 600, 'Stock heatmap') ?>
</section>
<section class="panel mk-panel" data-mk-panel="crypto"<?= $tab === 'crypto' ? '' : ' hidden' ?>>
  <div class="panel-head"><h2>Crypto heatmap</h2><span class="muted small">Size = market cap · colour = 24h change</span></div>
  <?= $w('crypto', 600, 'Crypto heatmap') ?>
</section>
<section class="panel mk-panel" data-mk-panel="news"<?= $tab === 'news' ? '' : ' hidden' ?>>
  <div class="panel-head"><h2>Market news</h2><span class="muted small">Top stories across markets</span></div>
  <?= $w('news', 640, 'Market news') ?>
</section>
<section class="panel mk-panel" data-mk-panel="calendar"<?= $tab === 'calendar' ? '' : ' hidden' ?>>
  <div class="panel-head"><h2>Economic calendar</h2><span class="muted small">Medium &amp; high-impact events · your device's time zone</span></div>
  <?= $w('calendar', 640, 'Economic calendar') ?>
</section>
<p class="panel-note mk-note">Market data, news and calendar are provided by TradingView and shown as-is; some exchanges are delayed (TradingView marks this in the widget). journzey.ai does not store or use these prices. For information only — not financial advice.</p>
