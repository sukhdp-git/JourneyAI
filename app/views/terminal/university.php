<?php
use App\Trading\Domain;
/** @var array $strategies @var array $mine */
$colors = ['SMC' => 'c-violet', 'ICT' => 'c-blue', 'ORDER_FLOW' => 'c-cyan', 'VOLUME_PROFILE' => 'c-amber', 'MEAN_REVERSION' => 'c-green', 'SUPPLY_DEMAND' => 'c-pink', 'BREAKOUT' => 'c-red'];
?>
<div class="uni-wrap">
  <header class="uni-head">
    <span class="uni-kicker"><?= icon('book', 'icon icon-xs') ?> journzey.ai University</span>
    <h2>The institutional intraday playbook</h2>
    <p><?= count($strategies) ?> strategies built on liquidity, volume and order flow. Read one end to end, add it to your strategies, then trade it in demo and review its numbers after 20–30 trades.</p>
  </header>
  <ol class="uni-list">
    <?php foreach ($strategies as $i => $s): $owned = in_array(mb_substr($s['title'], 0, 80), $mine, true); ?>
    <li class="<?= $colors[$s['style']] ?? 'c-blue' ?>">
      <a class="uni-row" href="<?= e(url('/terminal/university/' . $s['slug'])) ?>">
        <span class="uni-n"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
        <span class="uni-body">
          <span class="uni-meta"><i></i><?= e(Domain::STRATEGY_STYLES[$s['style']] ?? 'Strategy') ?><?php if ($owned): ?><b class="uni-owned"><?= icon('check', 'icon icon-xs') ?> In your strategies</b><?php endif; ?></span>
          <strong class="uni-name"><?= e($s['title']) ?></strong>
          <span class="uni-sum"><?= e($s['summary']) ?></span>
          <span class="uni-facts"><?php foreach (array_filter([(string) $s['primary_assets'], (string) $s['timeframe'], $s['target_rr'] ? 'R:R ' . $s['target_rr'] : '']) as $fact): ?><span><?= e($fact) ?></span><?php endforeach; ?></span>
        </span>
        <span class="uni-go" aria-hidden="true"><?= icon('arrow-right', 'icon icon-sm') ?></span>
      </a>
    </li>
    <?php endforeach; ?>
  </ol>
  <p class="ex-disclaimer">Educational content — not financial advice. No strategy wins every time; practise in demo and size every trade to your own risk limits.</p>
</div>
