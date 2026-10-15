<?php /** Module 4 — Trading psychology & emotional traps */ ?>
<section class="cx-sec">
  <h3><span class="n">1</span> Your brain was not built for trading</h3>
  <p class="cx-lead">Strategy is the easy part. The hard part is following it when real money is moving. These traps are well documented in behavioural finance research — knowing their names is the first step to catching them.</p>
  <div class="cx-cards">
    <div class="cx-stat"><b>≈2×</b><span>Losses feel roughly twice as painful as equal gains feel good (“loss aversion”).</span><cite>Kahneman &amp; Tversky, Prospect Theory (1979); Tversky &amp; Kahneman (1992)</cite></div>
    <div class="cx-stat"><b>Sell winners,<br>hold losers</b><span>Investors tend to close winning positions too early and keep losing ones too long (“disposition effect”).</span><cite>Shefrin &amp; Statman (1985); Odean (1998)</cite></div>
    <div class="cx-stat"><b>More trades,<br>lower returns</b><span>In a large study of brokerage accounts, the households that traded most earned the lowest net returns.</span><cite>Barber &amp; Odean (2000)</cite></div>
  </div>
</section>

<section class="cx-sec">
  <h3><span class="n">2</span> The emotional cycle of a losing streak</h3>
  <p>Most blown accounts follow the same loop. Each step feels reasonable in the moment:</p>
  <figure class="cx-fig">
    <svg viewBox="0 0 640 300" role="img" aria-label="Cycle: a loss, frustration, revenge trade bigger, bigger loss, panic or freeze, break rules again">
      <?php $steps = ['A normal loss', 'Frustration: “I need it back”', 'Revenge trade, bigger size', 'Bigger loss', 'Panic: move stop / freeze', 'Rules abandoned']; $cx = 320; $cy = 150; $rx = 230; $ry = 108;
      foreach ($steps as $i => $s): $a = -M_PI / 2 + $i * 2 * M_PI / 6; $x = round($cx + $rx * cos($a)); $y = round($cy + $ry * sin($a)); $a2 = $a + 2 * M_PI / 6; $x2 = round($cx + $rx * cos($a2)); $y2 = round($cy + $ry * sin($a2)); ?>
      <line class="ln-m" x1="<?= $x ?>" y1="<?= $y ?>" x2="<?= $x2 ?>" y2="<?= $y2 ?>"/>
      <?php endforeach; foreach ($steps as $i => $s): $a = -M_PI / 2 + $i * 2 * M_PI / 6; $x = round($cx + $rx * cos($a)); $y = round($cy + $ry * sin($a)); ?>
      <circle class="pt" cx="<?= $x ?>" cy="<?= $y ?>" r="7"/>
      <text class="<?= $i >= 2 ? 'lbl-dn' : 'tb' ?>" x="<?= $x + ($x > $cx + 10 ? 14 : ($x < $cx - 10 ? -14 : 0)) ?>" y="<?= $y + ($y < $cy - 60 ? -14 : ($y > $cy + 60 ? 24 : 4)) ?>" text-anchor="<?= $x > $cx + 10 ? 'start' : ($x < $cx - 10 ? 'end' : 'middle') ?>"><?= e($s) ?></text>
      <?php endforeach; ?>
      <text class="lbl-b" x="<?= $cx ?>" y="<?= $cy - 4 ?>" text-anchor="middle">Break the loop here:</text>
      <text class="tb" x="<?= $cx ?>" y="<?= $cy + 14 ?>" text-anchor="middle">pause after a loss · daily limit</text>
    </svg>
    <figcaption>The tilt cycle. The fix is a rule that triggers <em>before</em> step 3 — a pause, not willpower.</figcaption>
  </figure>
</section>

<section class="cx-sec">
  <h3><span class="n">3</span> The eight emotional traps (and the rule that beats each)</h3>
  <?php $traps = [
    ['flag', 'Revenge trading', 'Trading again straight after a loss, often bigger, to “win it back”.', 'After 2 losses in a row, stop for 30 minutes. After the daily limit, stop for the day.'],
    ['zap', 'FOMO', 'Jumping into a move that already ran because you are afraid to miss it. You buy the top of the candle.', 'No setup, no trade. If you missed it, write “missed” in the journal and wait for the next one.'],
    ['scissors', 'Cutting winners early', 'Closing at +0.5R because the fear of giving back profit is louder than the plan.', 'Set the target when you enter. Only exit early for a reason written in your plan.'],
    ['anchor', 'Letting losers run', 'Hoping instead of exiting — the disposition effect in action. Often comes with moving the stop.', 'The stop is never moved further away. Ever.'],
    ['repeat', 'Overtrading', 'Taking trades out of boredom or to “make the day count”. Costs and mistakes pile up.', 'Set a maximum number of trades per day (for example 3).'],
    ['crown', 'Overconfidence', 'After a winning streak you size up and skip checks. One big loss erases the streak.', 'Risk stays the same after wins. Raise risk only after a written review, in small steps.'],
    ['eye', 'Confirmation bias', 'Only noticing the news and signals that agree with your position.', 'Before entering, write one reason the trade could fail.'],
    ['pause', 'Hesitation / fear', 'After losses, you skip valid setups — then watch them work. The edge only pays if you take every valid trade.', 'If it meets the plan, take it at normal (or reduced) size. The plan decides, not the last result.'],
  ]; $ic = ['scissors' => 'x', 'anchor' => 'trend-down', 'repeat' => 'refresh', 'crown' => 'star', 'pause' => 'clock']; ?>
  <div class="cx-cards">
    <?php foreach ($traps as [$i, $t, $what, $rule]): ?>
    <div class="cx-card"><h4><?= icon($ic[$i] ?? $i, 'icon icon-sm') ?> <?= e($t) ?></h4><p><?= e($what) ?></p><p><span class="tag">Rule</span> <?= e($rule) ?></p></div>
    <?php endforeach; ?>
  </div>
</section>

<section class="cx-sec">
  <h3><span class="n">4</span> Self-check: which traps do you fall into?</h3>
  <p>Be honest — nobody else sees this. Tick every one that has happened to you in the last month, and we'll turn it into rules for your trading plan.</p>
  <div data-selfcheck>
    <div class="cx-checks">
      <label><input type="checkbox" value="After 2 losses in a row I stop trading for at least 30 minutes."> I traded again right after a loss to win it back.</label>
      <label><input type="checkbox" value="I only enter when my written setup is present. Missed moves are logged, not chased."> I entered late because a move was running without me.</label>
      <label><input type="checkbox" value="My target is set at entry; I only exit early for a reason in my plan."> I closed a winning trade early out of fear.</label>
      <label><input type="checkbox" value="I never move my stop loss further away from entry."> I moved my stop further away to avoid being stopped out.</label>
      <label><input type="checkbox" value="Maximum 3 trades per day; when I hit it, I close the platform."> I took trades out of boredom or took too many in a day.</label>
      <label><input type="checkbox" value="My risk per trade stays fixed after a winning streak."> I increased my size after a few wins.</label>
      <label><input type="checkbox" value="Before each trade I write one reason it could fail."> I ignored signs my trade was wrong.</label>
      <label><input type="checkbox" value="When my daily loss limit is hit, I stop for the day — no exceptions."> I kept trading after a bad day had already gone past my limit.</label>
    </div>
    <div class="cx-key" data-selfcheck-out hidden><?= icon('list', 'icon') ?><div><p><strong>Your personal rules</strong> — copy them into your trading plan (Module 5) or Notepad:</p><ol class="cx-rules" data-selfcheck-list></ol><button type="button" class="tm-btn tm-btn-ghost tm-btn-sm" data-selfcheck-copy><?= icon('copy', 'icon icon-sm') ?> Copy rules</button></div></div>
  </div>
</section>

<section class="cx-sec">
  <h3><span class="n">5</span> Tools that do the willpower for you</h3>
  <div class="cx-cards">
    <div class="cx-card"><h4><?= icon('shield', 'icon icon-sm') ?> Tilt Circuit Breaker</h4><p>Set how many losses in how many minutes should trigger a cool-down. journzey warns you before the revenge trade.</p><p><a href="<?= e(url('/terminal/settings')) ?>">Set it in Settings →</a></p></div>
    <div class="cx-card"><h4><?= icon('heart', 'icon icon-sm') ?> Emotion tags</h4><p>Tag each trade with how you felt (calm, FOMO, revenge…). After 20–30 trades, the numbers show which emotions cost you money.</p><p><a href="<?= e(url('/terminal/trades/new')) ?>">Log a trade →</a></p></div>
    <div class="cx-card"><h4><?= icon('brain', 'icon icon-sm') ?> Weekly review</h4><p>Your dashboard and Edge Matrix turn feelings into facts: best hours, best setups, mistakes that repeat.</p><p><a href="<?= e(url('/terminal/edge')) ?>">Open Edge Matrix →</a></p></div>
  </div>
  <div class="cx-tip"><?= icon('check-circle', 'icon') ?><p><strong>The 10-second reset:</strong> after any loss, take your hands off the mouse, breathe out slowly, and say what happened in one sentence (“stop hit, setup was valid”). Log it. Only then look at the chart again.</p></div>
</section>

<section class="cx-sec">
  <h3><span class="n">★</span> Key takeaways</h3>
  <ul class="cx-take">
    <li><?= icon('check', 'icon icon-sm') ?>Losses hurt about twice as much as gains feel good — so we hold losers and cut winners unless rules stop us.</li>
    <li><?= icon('check', 'icon icon-sm') ?>Revenge trading, FOMO and moving stops are the fastest ways to break the 1–2% rule.</li>
    <li><?= icon('check', 'icon icon-sm') ?>Fight emotions with pre-written rules and automatic limits, not willpower in the moment.</li>
    <li><?= icon('check', 'icon icon-sm') ?>Tag emotions in your journal; your own data will show which ones cost you.</li>
  </ul>
</section>

<section class="cx-quiz" data-quiz>
  <h3><?= icon('sparkles', 'icon') ?> Quick check</h3>
  <p class="muted small">Pick an answer — you'll see why right away.</p>
  <div class="cx-q" data-a="b"><p>1. You've just taken two losses in 15 minutes and feel angry. Best next step?</p>
    <label><input type="radio" name="q1" value="a"> Double the size on the next setup</label><label><input type="radio" name="q1" value="b"> Step away and log the trades</label><label><input type="radio" name="q1" value="c"> Switch to a faster timeframe</label>
    <p class="why">That's the start of the tilt cycle. A pause breaks it before the revenge trade.</p></div>
  <div class="cx-q" data-a="c"><p>2. Price shot up 2% in five minutes without you. What is FOMO telling you to do?</p>
    <label><input type="radio" name="q2" value="a"> Wait for your setup</label><label><input type="radio" name="q2" value="b"> Write it in the journal as “missed”</label><label><input type="radio" name="q2" value="c"> Buy now before it goes higher</label>
    <p class="why">FOMO pushes you to chase. Waiting and logging the missed move are the disciplined answers.</p></div>
  <div class="cx-q" data-a="a"><p>3. “Selling winners too early and holding losers too long” is called…</p>
    <label><input type="radio" name="q3" value="a"> The disposition effect</label><label><input type="radio" name="q3" value="b"> Confirmation bias</label><label><input type="radio" name="q3" value="c"> Overtrading</label>
    <p class="why">Named by Shefrin &amp; Statman (1985) and measured in real accounts by Odean (1998).</p></div>
  <p class="cx-score" data-quiz-score aria-live="polite"></p>
</section>
