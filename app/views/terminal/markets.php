<?php
use App\Core\View;
/** @var string $tab @var array $tabs */
$w = fn (string $type, int $h, string $label, array $opt = []) => View::partial('terminal/partials/tv-widget', ['type' => $type, 'm' => $m, 'height' => $h, 'label' => $label, 'opt' => $opt]);
?>
<div class="mk-tabs" role="tablist" aria-label="Market widgets" data-mk-tabs>
  <?php foreach ($tabs as $k => [$label, $ic]): ?><button type="button" role="tab" data-mk-tab="<?= e($k) ?>" aria-selected="<?= $k === $tab ? 'true' : 'false' ?>"<?= $k === $tab ? ' class="on"' : '' ?>><?= icon($ic, 'icon icon-sm') ?><?= e($label) ?></button><?php endforeach; ?>
</div>
<section class="panel mk-panel mk-chart" data-mk-panel="chart"<?= $tab === 'chart' ? '' : ' hidden' ?>>
  <div class="panel-head"><h2>Live chart</h2>
    <span class="mk-syms" role="group" aria-label="Quick symbols"><?php foreach (['OANDA:XAUUSD' => 'Gold', 'FX:EURUSD' => 'EUR/USD', 'FX:GBPUSD' => 'GBP/USD', 'OANDA:NAS100USD' => 'Nasdaq', 'OANDA:SPX500USD' => 'S&P 500', 'BITSTAMP:BTCUSD' => 'Bitcoin'] as $sym => $lbl): ?><button type="button" class="lt-chip<?= $sym === 'OANDA:XAUUSD' ? ' on' : '' ?>" data-mk-symbol="<?= e($sym) ?>"><?= e($lbl) ?></button><?php endforeach; ?></span></div>
  <?= $w('chart', 680, 'Live chart', ['tz' => $tz]) ?>
  <p class="muted small mk-note">Full TradingView chart: indicators, timeframes and the drawing tools on the left (trend lines, Fibonacci, rectangles, measure…). Search any symbol in the top bar. Drawings are not saved when you leave the page.</p>
</section>
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
