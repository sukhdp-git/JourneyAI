<?php /** Module 1 — The harsh reality of trading */ ?>
<section class="cx-sec">
  <h3><span class="n">1</span> What the numbers say</h3>
  <p class="cx-lead">Before learning a single chart pattern, look at the scoreboard. Regulators publish data from real broker accounts, and it is sobering.</p>
  <div class="cx-cards">
    <div class="cx-stat"><b>74–89%</b><span>of retail investor accounts lose money when trading CFDs.</span><cite>European Securities and Markets Authority (ESMA), 2018 review of broker data</cite></div>
    <div class="cx-stat"><b>89%</b><span>of individual traders in India's equity futures &amp; options lost money in FY 2021-22.</span><cite>SEBI study, January 2023</cite></div>
    <div class="cx-stat"><b>93%</b><span>of more than one crore individual F&amp;O traders lost money over FY 2022–FY 2024.</span><cite>SEBI study, September 2024</cite></div>
  </div>
  <p>That is where the famous “90% of traders lose” comes from. It is not a law of nature — it is what usually happens when people trade <strong>without a tested plan, with too much leverage and without reviewing their own trades</strong>. Each of those three causes can be fixed, and this course shows you how.</p>
  <div class="cx-key"><?= icon('info', 'icon') ?><p>Your first goal is not to get rich. It is to <strong>survive long enough to get good</strong>: small losses, a written plan, and a journal that shows you the truth.</p></div>
</section>

<section class="cx-sec">
  <h3><span class="n">2</span> The three reasons most traders lose</h3>
  <div class="cx-cards">
    <div class="cx-card"><h4><?= icon('heart', 'icon icon-sm') ?> 1. Emotional trading</h4><p>Decisions are made in the moment: chasing a move that already happened (FOMO), trading bigger to “win it back” after a loss (revenge), or closing a good trade too early out of fear.</p><p>Emotions turn a plan into improvisation — and improvisation is where accounts get hurt.</p></div>
    <div class="cx-card"><h4><?= icon('zap', 'icon icon-sm') ?> 2. Over-leveraging</h4><p>Leverage lets a small deposit control a large position. It magnifies gains <em>and</em> losses equally. Traders size by hope (“this one will work”) instead of by how much they can afford to lose.</p><p>One oversized loss can erase weeks of good trading.</p></div>
    <div class="cx-card"><h4><?= icon('journal', 'icon icon-sm') ?> 3. No journaling</h4><p>Without a record you cannot tell skill from luck. You repeat the same mistake for months because you never see the pattern: the same hour, the same setup, the same emotion.</p><p>Professionals review their data; most retail traders rely on memory — which forgets losses.</p></div>
  </div>
  <p>Other common leaks: <strong>strategy hopping</strong> (switching systems after every loss), <strong>ignoring costs</strong> (spreads, commissions and fees add up when you trade often) and <strong>unrealistic expectations</strong> (trying to double an account in a month).</p>
</section>

<section class="cx-sec">
  <h3><span class="n">3</span> How leverage turns a small move into a big loss</h3>
  <p>Imagine a <strong>$1,000</strong> account. The market moves just <strong>1% against you</strong>. Here is what that costs at different leverage, if you use the full amount the leverage allows:</p>
  <figure class="cx-fig">
    <svg viewBox="0 0 640 230" role="img" aria-label="Loss from a 1% move: 1% at 1:1, 10% at 1:10, 30% at 1:30, 100% at 1:100">
      <line class="ax" x1="70" y1="20" x2="70" y2="190"/><line class="ax" x1="70" y1="190" x2="620" y2="190"/>
      <?php foreach ([['1:1', 1], ['1:10', 10], ['1:30', 30], ['1:100', 100]] as $i => [$lev, $pct]): $x = 110 + $i * 128; $h = max(3, $pct * 1.6); ?>
      <rect class="dno" x="<?= $x ?>" y="<?= 190 - $h ?>" width="64" height="<?= $h ?>" rx="6"/>
      <text class="lbl-dn" x="<?= $x + 32 ?>" y="<?= 182 - $h ?>" text-anchor="middle">−<?= $pct ?>%</text>
      <text class="tb" x="<?= $x + 32 ?>" y="208" text-anchor="middle"><?= $lev ?></text>
      <text class="ts" x="<?= $x + 32 ?>" y="222" text-anchor="middle">−$<?= number_format(10 * $pct) ?></text>
      <?php endforeach; ?>
      <text class="t" x="62" y="26" text-anchor="end">100%</text><text class="t" x="62" y="194" text-anchor="end">0</text>
    </svg>
    <figcaption>Account loss from a 1% price move against a fully leveraged position on a $1,000 account.</figcaption>
  </figure>
  <div class="cx-tw"><table class="cx-table">
    <thead><tr><th>Leverage used</th><th class="r">Position size</th><th class="r">1% move against you</th><th class="r">Share of account lost</th></tr></thead>
    <tbody>
      <tr><td>1:1</td><td class="r">$1,000</td><td class="r bad">−$10</td><td class="r">1%</td></tr>
      <tr><td>1:10</td><td class="r">$10,000</td><td class="r bad">−$100</td><td class="r">10%</td></tr>
      <tr><td>1:30</td><td class="r">$30,000</td><td class="r bad">−$300</td><td class="r">30%</td></tr>
      <tr class="hl"><td>1:100</td><td class="r">$100,000</td><td class="r bad">−$1,000</td><td class="r"><strong>100% — account gone</strong></td></tr>
    </tbody></table></div>
  <div class="cx-warn"><?= icon('alert', 'icon') ?><p>Leverage available is not leverage you should use. In Module 3 you will size every trade from the <strong>stop loss</strong>, so the loss is fixed in advance no matter what leverage the broker offers.</p></div>
</section>

<section class="cx-sec">
  <h3><span class="n">4</span> The recovery trap: losses are harder to win back</h3>
  <p>If you lose 50% of your account, you need a <strong>100%</strong> gain just to get back to where you started. The deeper the hole, the steeper the climb:</p>
  <?php $rec = [10 => 11.1, 20 => 25, 30 => 42.9, 40 => 66.7, 50 => 100, 60 => 150, 75 => 300, 90 => 900]; ?>
  <div class="cx-tw"><table class="cx-table">
    <thead><tr><th>You lose</th><th>Gain needed to break even</th></tr></thead>
    <tbody><?php foreach ($rec as $loss => $gain): ?>
      <tr<?= $loss === 50 ? ' class="hl"' : '' ?>><td class="bad">−<?= $loss ?>%</td><td><span class="cx-bar" style="width:<?= min(100, max(2, $gain / 9)) ?>%"></span><strong>+<?= $gain ?>%</strong></td></tr>
    <?php endforeach; ?></tbody></table></div>
  <p class="small muted">Formula: gain needed = loss ÷ (1 − loss). A 10% loss needs 0.10 ÷ 0.90 = 11.1%.</p>
  <div class="cx-key"><?= icon('shield', 'icon') ?><p>This is why professionals obsess over <strong>defence</strong>. Small, controlled losses are easy to recover from; big ones can end a trading career.</p></div>
</section>

<section class="cx-sec">
  <h3><span class="n">5</span> What the consistent minority does differently</h3>
  <div class="cx-cards">
    <div class="cx-card"><h4><?= icon('scale', 'icon icon-sm') ?> Small, fixed risk</h4><p>They risk a small, fixed share of the account per trade — usually 0.5–2% — and know the exact loss before entering.</p></div>
    <div class="cx-card"><h4><?= icon('target', 'icon icon-sm') ?> A tested edge</h4><p>They trade a few setups they have backtested, with clear entry, stop and target rules.</p></div>
    <div class="cx-card"><h4><?= icon('journal', 'icon icon-sm') ?> A journal and a review</h4><p>Every trade is recorded and reviewed weekly. Decisions are based on their own statistics, not on feelings.</p></div>
    <div class="cx-card"><h4><?= icon('clock', 'icon icon-sm') ?> Patience</h4><p>No trade is a valid decision. They wait for their setup and stop after hitting a daily loss limit.</p></div>
  </div>
  <div class="cx-tip"><?= icon('check-circle', 'icon') ?><p><strong>Try it now:</strong> before your next trade, answer three questions out loud — <em>Where is my stop? How much do I lose if it is hit? Is this setup in my plan?</em> If any answer is “I don't know”, skip the trade.</p></div>
</section>

<section class="cx-sec">
  <h3><span class="n">★</span> Key takeaways</h3>
  <ul class="cx-take">
    <li><?= icon('check', 'icon icon-sm') ?>Regulator data shows most retail traders lose — mainly through emotions, leverage and no journal.</li>
    <li><?= icon('check', 'icon icon-sm') ?>Leverage multiplies losses as much as gains; size from your stop, not from the leverage offered.</li>
    <li><?= icon('check', 'icon icon-sm') ?>A 50% loss needs a 100% gain to recover — protect capital first.</li>
    <li><?= icon('check', 'icon icon-sm') ?>The consistent minority risks little per trade, follows a plan and reviews a journal.</li>
  </ul>
</section>

<section class="cx-quiz" data-quiz>
  <h3><?= icon('sparkles', 'icon') ?> Quick check</h3>
  <p class="muted small">Pick an answer — you'll see why right away.</p>
  <div class="cx-q" data-a="c"><p>1. You lose 50% of your account. What gain do you need to get back to break-even?</p>
    <label><input type="radio" name="q1" value="a"> 50%</label><label><input type="radio" name="q1" value="b"> 75%</label><label><input type="radio" name="q1" value="c"> 100%</label>
    <p class="why">Half the money has to double: $1,000 → $500 needs +$500, which is +100% of $500.</p></div>
  <div class="cx-q" data-a="c"><p>2. At 1:100 leverage with the full position, a 1% move against you costs about…</p>
    <label><input type="radio" name="q2" value="a"> 1% of the account</label><label><input type="radio" name="q2" value="b"> 10% of the account</label><label><input type="radio" name="q2" value="c"> The whole account</label>
    <p class="why">100× leverage turns a 1% move into a 100% change in your account — which is why you size from the stop instead.</p></div>
  <div class="cx-q" data-a="b"><p>3. Which habit most separates consistent traders from the rest?</p>
    <label><input type="radio" name="q3" value="a"> Using more indicators</label><label><input type="radio" name="q3" value="b"> Recording and reviewing every trade</label><label><input type="radio" name="q3" value="c"> Trading bigger after a loss</label>
    <p class="why">A journal turns feelings into data, so you can keep what works and cut what doesn't.</p></div>
  <p class="cx-score" data-quiz-score aria-live="polite"></p>
</section>
