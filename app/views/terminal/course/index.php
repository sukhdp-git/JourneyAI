<?php use App\Trading\Course; /** Trading Foundations — course overview */ ?>
<div class="cx" data-course data-course-key="jz-course-<?= (int) $m['id'] ?>">
  <section class="cx-hero">
    <span class="cx-kicker"><?= icon('book', 'icon icon-xs') ?> journzey.ai University · free mini-course</span>
    <h2><?= e(Course::TITLE) ?>: from <em>zero</em> to a trader with a plan</h2>
    <p>Five short modules for new traders: why most people lose, how markets and orders work, the risk rules that keep you in the game, the emotional traps to avoid, and the habits that build real skill.</p>
    <div class="cx-stats">
      <span><?= icon('layers', 'icon icon-sm') ?> 5 modules</span>
      <span><?= icon('clock', 'icon icon-sm') ?> about <?= Course::minutes() ?> minutes</span>
      <span><?= icon('trend-up', 'icon icon-sm') ?> Beginner → intermediate</span>
      <span><?= icon('check-circle', 'icon icon-sm') ?> Quiz in every module</span>
    </div>
    <div class="cx-progress"><div class="bar"><span data-course-bar></span></div><small data-course-label>0 of 5 modules complete</small></div>
  </section>

  <ol class="cx-mods">
    <?php foreach (Course::MODULES as $n => [$slug, $title, $short, $min, $ic, $sum]): ?>
    <li><a class="cx-mod" href="<?= e(url('/terminal/university/course/' . $slug)) ?>" data-course-mod="<?= $n ?>">
      <span class="cx-mod-n" aria-hidden="true"><?= $n ?></span>
      <span><h3>Module <?= $n ?> · <?= e($title) ?></h3><p><?= e($sum) ?></p>
        <span class="cx-mod-meta"><span><?= icon('clock', 'icon icon-xs') ?> <?= $min ?> min</span><span><?= icon($ic, 'icon icon-xs') ?> <?= e($short) ?></span></span></span>
      <span><b class="cx-done-badge"><?= icon('check', 'icon icon-xs') ?> Completed</b><span class="cx-go"><?= icon('arrow-right', 'icon') ?></span></span>
    </a></li>
    <?php endforeach; ?>
  </ol>

  <section class="cx-sec" style="margin-top:18px">
    <h3><span class="n">✓</span> By the end you will be able to</h3>
    <ul class="cx-outcomes">
      <li><?= icon('check', 'icon icon-sm') ?><span>Explain stocks, indices, forex, commodities and crypto, and read any candlestick chart.</span></li>
      <li><?= icon('check', 'icon icon-sm') ?><span>Place the right order type and mark support, resistance and trend.</span></li>
      <li><?= icon('check', 'icon icon-sm') ?><span>Size every position so one loss costs only 1–2% of your account.</span></li>
      <li><?= icon('check', 'icon icon-sm') ?><span>Know the win rate a setup needs for its risk:reward to pay.</span></li>
      <li><?= icon('check', 'icon icon-sm') ?><span>Spot revenge trading, FOMO and tilt — and have a rule ready for each.</span></li>
      <li><?= icon('check', 'icon icon-sm') ?><span>Run a backtest, write a trading plan and review your journal every week.</span></li>
    </ul>
  </section>
  <p class="ex-disclaimer">Educational content — not financial advice. Trading involves substantial risk of loss; past results never guarantee future returns. Practise in demo first.</p>
</div>
