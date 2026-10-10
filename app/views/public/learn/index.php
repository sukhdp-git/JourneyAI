<?php
use App\Core\View;
use App\Trading\Domain;
/** @var array $free @var array $locked @var int $total @var ?array $member */
$styleIcon = ['SMC' => 'layers', 'ICT' => 'clock', 'ORDER_FLOW' => 'activity', 'VOLUME_PROFILE' => 'bars', 'MEAN_REVERSION' => 'scale', 'SUPPLY_DEMAND' => 'target', 'BREAKOUT' => 'trend-up'];
$all = array_merge($free, $locked);
usort($all, fn ($x, $y) => [(int) $x['sort_order'], (int) $x['id']] <=> [(int) $y['sort_order'], (int) $y['id']]);
$num = [];
foreach ($all as $i => $s) {
    $num[$s['id']] = $i + 1;
}
$unlockUrl = $member ? url('/terminal/university') : url('/signup');
$unlockLabel = $member ? 'Open the University' : 'Sign up free to unlock all ' . $total;
$glossary = [
    ['Liquidity (buy-side / sell-side)', 'Clusters of resting stop and limit orders above highs (buy-side) and below lows (sell-side). A “sweep” or “raid” is a quick move through one of these levels.'],
    ['Market Structure Shift (MSS) / Break of Structure (BOS)', 'A BOS continues the trend by breaking the last swing in its direction. An MSS breaks the last swing against the trend — an early sign the direction may be changing.'],
    ['Displacement', 'A fast, large-bodied candle or series of candles that moves price decisively, usually leaving an imbalance behind.'],
    ['Fair Value Gap (FVG)', 'A three-candle imbalance: the wicks of candles one and three do not overlap, leaving a gap that price often revisits. Its 50% level is called Consequent Encroachment.'],
    ['Order Block (OB) / Breaker Block', 'An Order Block is the last opposing candle before a strong move. When price later breaks straight through it, the failed block is called a Breaker Block and often flips role.'],
    ['Volume Profile: POC, VAH, VAL', 'Volume traded at each price. The Point of Control (POC) is the busiest price; the Value Area High and Low bound the range where roughly 70% of volume traded.'],
    ['VWAP and standard deviation bands', 'The Volume-Weighted Average Price for the session, with bands at multiples of the standard deviation to show how stretched price is from its average.'],
    ['Delta, CVD and footprint charts', 'Delta is aggressive buying minus aggressive selling. Cumulative Volume Delta (CVD) adds it up over time; footprint charts show it inside every candle.'],
    ['ATR and RVOL', 'Average True Range measures typical candle range, used to size stops. Relative Volume compares current volume with the average for that time of day.'],
    ['R:R (risk-to-reward)', 'Potential reward divided by the amount risked. 1:3 means the target is three times the distance to the stop.'],
];
?>
<?= View::partial('public/partials/page-hero', ['eyebrow' => 'Learn', 'title' => 'The institutional intraday strategy playbook', 'subtitle' => count($free) . ' free intraday strategies built around liquidity, volume and order flow — with logic, setup rules, entry, stop and target. ' . ($total - count($free)) . ' more are inside the journzey.ai University for members.', 'crumbs' => [['Home', '/'], ['Learn', '/learn']], 'extra' => '<div class="hero-actions"><a class="btn btn-primary btn-lg" href="#playbook">Read the free strategies</a><a class="btn btn-ghost btn-lg" href="' . e($unlockUrl) . '">' . e($unlockLabel) . '</a></div>']) ?>

<section class="section section-tight" id="playbook">
  <div class="container">
    <div class="learn-disclaimer reveal" role="note"><?= icon('info', 'icon') ?><p><strong>Educational content only — not financial advice.</strong> These strategies describe how some traders approach the market. No strategy wins every time and none guarantees a profit; target R:R values are planning targets, not expected results. Trading leveraged products such as CFDs and futures carries a high risk of losing money. Test any idea in a demo account and size every position to your own risk limits.</p></div>


    <?php if ($free): ?>
    <div class="card-grid learn-grid">
      <?php foreach ($free as $i => $s): ?>
      <a class="service-card learn-card reveal" style="--d:<?= $i % 3 ?>" href="<?= e(url('/learn/' . $s['slug'])) ?>">
        <div class="learn-card-top">
          <span class="learn-num" aria-hidden="true"><?= str_pad((string) $num[$s['id']], 2, '0', STR_PAD_LEFT) ?></span>
          <?php if ($s['style'] && isset(Domain::STRATEGY_STYLES[$s['style']])): ?><span class="learn-style"><?= icon($styleIcon[$s['style']] ?? 'compass', 'icon icon-xs') ?><?= e(Domain::STRATEGY_STYLES[$s['style']]) ?></span><?php endif; ?>
        </div>
        <h3><span class="sr-only">Strategy <?= $num[$s['id']] ?>: </span><?= e($s['title']) ?></h3>
        <p><?= e($s['summary']) ?></p>
        <dl class="learn-facts">
          <?php if ($s['primary_assets']): ?><div><dt>Assets</dt><dd><?= e($s['primary_assets']) ?></dd></div><?php endif; ?>
          <?php if ($s['session_window']): ?><div><dt>Session</dt><dd><?= e($s['session_window']) ?></dd></div><?php endif; ?>
          <?php if ($s['timeframe']): ?><div><dt>Timeframe</dt><dd><?= e($s['timeframe']) ?></dd></div><?php endif; ?>
          <?php if ($s['target_rr']): ?><div><dt>Target R:R</dt><dd><?= e($s['target_rr']) ?></dd></div><?php endif; ?>
        </dl>
        <span class="card-link">Read the playbook <?= icon('arrow-right', 'icon icon-sm') ?></span>
      </a>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
      <div class="empty-state"><?= icon('book', 'icon') ?><p>Strategies will appear here once they are published.</p></div>
    <?php endif; ?>
    <?php if ($locked): ?>
    <div class="learn-locked-head reveal"><h2 class="h3"><?= icon('lock', 'icon') ?> <?= count($locked) ?> more strategies in the University</h2><p class="muted">Order blocks, Judas swings, footprint absorption, opening-range breakouts and more — explained step by step inside the journzey.ai terminal.</p></div>
    <div class="card-grid learn-grid">
      <?php foreach ($locked as $i => $s): ?>
      <a class="service-card learn-card is-locked reveal" style="--d:<?= $i % 3 ?>" href="<?= e($member ? url('/terminal/university/' . $s['slug']) : url('/signup')) ?>">
        <div class="learn-card-top"><span class="learn-num" aria-hidden="true"><?= str_pad((string) $num[$s['id']], 2, '0', STR_PAD_LEFT) ?></span><span class="learn-lock"><?= icon('lock', 'icon icon-xs') ?> Members</span></div>
        <h3><?= e($s['title']) ?></h3>
        <p class="learn-blur" aria-hidden="true">Setup rules, entry trigger, stop loss and take profit are available to members.</p>
        <span class="card-link"><?= $member ? 'Open in University' : 'Sign up free to unlock' ?> <?= icon('arrow-right', 'icon icon-sm') ?></span>
      </a>
      <?php endforeach; ?>
    </div>
    <div class="cta-panel learn-unlock reveal">
      <h2>Get all <?= (int) $total ?> strategies</h2>
      <p class="lead">Create a free account to open the journzey.ai University: every strategy explained, copy any of them into your journal with one click, and track how each performs for you.</p>
      <div class="center-actions"><a class="btn btn-light btn-lg" href="<?= e($unlockUrl) ?>"><?= e($unlockLabel) ?></a></div>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php if ($free): ?>
<section class="section bg-muted" id="matrix">
  <div class="container">
    <div class="section-head reveal"><p class="eyebrow">At a glance</p><h2>Strategy summary matrix</h2><p class="lead">Primary assets, ideal session, setup timeframe and planned risk-to-reward.</p></div>
    <div class="learn-table-wrap reveal" tabindex="0" role="region" aria-label="Strategy summary matrix">
      <table class="learn-table">
        <thead><tr><th scope="col">#</th><th scope="col">Strategy</th><th scope="col">Primary assets</th><th scope="col">Ideal session</th><th scope="col">Setup timeframe</th><th scope="col">Target R:R</th></tr></thead>
        <tbody>
          <?php foreach ($free as $s): ?>
          <tr>
            <td class="learn-td-num"><?= $num[$s['id']] ?></td>
            <th scope="row"><a href="<?= e(url('/learn/' . $s['slug'])) ?>"><?= e($s['short_title'] ?: $s['title']) ?></a></th>
            <td><?= e((string) $s['primary_assets']) ?></td>
            <td><?= e((string) $s['session_window']) ?></td>
            <td><?= e((string) $s['timeframe']) ?></td>
            <td class="learn-td-rr"><?= e((string) $s['target_rr']) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if ($locked): ?><tr class="learn-tr-locked"><td class="learn-td-num"><?= icon('lock', 'icon icon-xs') ?></td><th scope="row" colspan="5"><a href="<?= e($unlockUrl) ?>"><?= count($locked) ?> more strategies — <?= $member ? 'open the University' : 'sign up free to unlock' ?> →</a></th></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
    <p class="muted learn-footnote">Session times are shown as written in the playbook. London and New York opening times in UTC move by one hour when daylight saving time starts or ends — the journzey.ai terminal’s session clock handles this automatically.</p>
  </div>
</section>
<?php endif; ?>

<section class="section">
  <div class="container learn-two">
    <div class="reveal">
      <div class="section-head"><p class="eyebrow">Glossary</p><h2>Key terms used in the playbook</h2></div>
      <dl class="learn-glossary">
        <?php foreach ($glossary as [$term, $def]): ?><div><dt><?= e($term) ?></dt><dd><?= e($def) ?></dd></div><?php endforeach; ?>
      </dl>
    </div>
    <aside class="reveal">
      <div class="aside-card">
        <span class="card-icon"><?= icon('journal', 'icon') ?></span>
        <h2 class="h4">How to practise a strategy</h2>
        <ol class="learn-steps">
          <li>Pick <strong>one</strong> strategy and read its rules until you can explain them without notes.</li>
          <li>Backtest it on past charts and write down every example — winners and losers.</li>
          <li>Trade it in a demo account with a fixed risk per trade, using the Risk Calculator to size positions.</li>
          <li>Tag every trade with the strategy in your journal so its win rate, average R and profit factor build up.</li>
          <li>Review the numbers after at least 20–30 trades before deciding whether it suits you.</li>
        </ol>
        <?php if (member()): ?>
          <a class="btn btn-primary btn-block" href="<?= e(url('/terminal/strategies')) ?>">Open my strategies</a>
        <?php else: ?>
          <a class="btn btn-primary btn-block" href="<?= e(url('/signup')) ?>">Start a free journal</a>
        <?php endif; ?>
      </div>
    </aside>
  </div>
</section>
