<?php
use App\Trading\Domain;
/** @var array $strategies @var array $mine */
$icons = ['SMC' => 'layers', 'ICT' => 'clock', 'ORDER_FLOW' => 'activity', 'VOLUME_PROFILE' => 'bars', 'MEAN_REVERSION' => 'scale', 'SUPPLY_DEMAND' => 'target', 'BREAKOUT' => 'trend-up'];
$colors = ['SMC' => 'c-violet', 'ICT' => 'c-blue', 'ORDER_FLOW' => 'c-cyan', 'VOLUME_PROFILE' => 'c-amber', 'MEAN_REVERSION' => 'c-green', 'SUPPLY_DEMAND' => 'c-pink', 'BREAKOUT' => 'c-red'];
?>
<section class="ex-hero uni-hero">
  <div class="ex-hero-text">
    <span class="ex-kicker"><?= icon('book', 'icon icon-xs') ?> journzey.ai University</span>
    <h2>The institutional intraday playbook</h2>
    <p><?= count($strategies) ?> strategies built on liquidity, volume and order flow. Read the logic, learn the rules, then copy a strategy into your journal and measure it.</p>
  </div>
  <div class="uni-steps">
    <div><b>1</b><span>Read one strategy end-to-end</span></div>
    <div><b>2</b><span>Copy it to <em>My strategies</em></span></div>
    <div><b>3</b><span>Trade it in demo &amp; tag every trade</span></div>
    <div><b>4</b><span>Review its numbers after 20–30 trades</span></div>
  </div>
</section>
<div class="uni-grid">
  <?php foreach ($strategies as $i => $s): $owned = in_array(mb_substr($s['title'], 0, 80), $mine, true); ?>
  <a class="ex-card uni-card <?= $colors[$s['style']] ?? 'c-blue' ?>" href="<?= e(url('/terminal/university/' . $s['slug'])) ?>">
    <header class="ex-head"><span class="ex-icon"><?= icon($icons[$s['style']] ?? 'compass', 'icon icon-sm') ?></span><div class="ex-title"><h2><span class="uni-num"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span> <?= e($s['short_title'] ?: $s['title']) ?></h2><p><?= e(Domain::STRATEGY_STYLES[$s['style']] ?? '') ?><?= $owned ? ' · <b class="up">In your strategies</b>' : '' ?></p></div></header>
    <p class="uni-sum"><?= e($s['summary']) ?></p>
    <div class="ex-tags"><span><small>Assets</small><?= e((string) $s['primary_assets']) ?></span><span><small>TF</small><?= e((string) $s['timeframe']) ?></span><span><small>R:R</small><?= e((string) $s['target_rr']) ?></span></div>
  </a>
  <?php endforeach; ?>
</div>
<p class="ex-disclaimer">Educational content — not financial advice. No strategy wins every time; practise in demo and size every trade to your own risk limits.</p>
