<?php /** Module 3 — The golden rule: risk management */ ?>
<section class="cx-sec">
  <h3><span class="n">1</span> The golden rule: never risk more than 1–2% per trade</h3>
  <p class="cx-lead">Risk is the money you lose <strong>if your stop loss is hit</strong>. Fix that amount before you enter, and keep it small, and no single trade — or bad week — can knock you out of the game.</p>
  <div class="cx-cards">
    <div class="cx-stat"><b>1%</b><span>A common default for beginners. On a $10,000 account that's $100 per trade.</span></div>
    <div class="cx-stat"><b>2%</b><span>A widely used upper limit. Above it, normal losing streaks start to do serious damage.</span></div>
    <div class="cx-stat"><b>0.5%</b><span>Often used while learning a new strategy or after a drawdown, to slow things down.</span></div>
  </div>
  <p>Why so small? Because losing streaks are normal, even for a good strategy. Here is what <strong>10 losses in a row</strong> leaves of a $10,000 account at different risk levels:</p>
  <?php $streak = [1 => 90.4, 2 => 81.7, 5 => 59.9, 10 => 34.9]; ?>
  <figure class="cx-fig">
    <svg viewBox="0 0 640 230" role="img" aria-label="Account left after 10 straight losses: 90% at 1% risk, 82% at 2%, 60% at 5%, 35% at 10%">
      <line class="ax" x1="70" y1="20" x2="70" y2="190"/><line class="ax" x1="70" y1="190" x2="620" y2="190"/>
      <line class="gl" x1="70" y1="20" x2="620" y2="20"/><line class="gl" x1="70" y1="105" x2="620" y2="105"/>
      <?php $i = 0; foreach ($streak as $r => $left): $x = 110 + $i++ * 128; $h = $left * 1.7; ?>
      <rect class="<?= $r <= 2 ? 'brand' : 'dno' ?>" x="<?= $x ?>" y="<?= 190 - $h ?>" width="64" height="<?= $h ?>" rx="6"/>
      <text class="<?= $r <= 2 ? 'lbl-b' : 'lbl-dn' ?>" x="<?= $x + 32 ?>" y="<?= 182 - $h ?>" text-anchor="middle"><?= $left ?>%</text>
      <text class="tb" x="<?= $x + 32 ?>" y="208" text-anchor="middle"><?= $r ?>% risk</text>
      <text class="ts" x="<?= $x + 32 ?>" y="222" text-anchor="middle">$<?= number_format($left * 100) ?> left</text>
      <?php endforeach; ?>
      <text class="t" x="62" y="24" text-anchor="end">100%</text><text class="t" x="62" y="109" text-anchor="end">50%</text><text class="t" x="62" y="194" text-anchor="end">0</text>
    </svg>
    <figcaption>Balance remaining after 10 consecutive losing trades, risking a fixed % of the current balance each time. Formula: (1 − risk)<sup>10</sup>.</figcaption>
  </figure>
  <div class="cx-tw"><table class="cx-table">
    <thead><tr><th>Risk per trade</th><th class="r">$ risked on $10,000</th><th class="r">After 5 losses</th><th class="r">After 10 losses</th><th class="r">Gain needed to recover from 10</th></tr></thead>
    <tbody>
      <?php foreach ($streak as $r => $left): $five = number_format(pow(1 - $r / 100, 5) * 100, 1); $need = round((100 / $left - 1) * 100, 1); ?>
      <tr<?= $r === 1 ? ' class="hl"' : '' ?>><td><strong><?= $r ?>%</strong></td><td class="r">$<?= number_format(100 * $r) ?></td><td class="r"><?= $five ?>%</td><td class="r <?= $r <= 2 ? 'good' : 'bad' ?>"><?= $left ?>%</td><td class="r">+<?= $need ?>%</td></tr>
      <?php endforeach; ?>
    </tbody></table></div>
  <div class="cx-key"><?= icon('info', 'icon') ?><p>With a 50% win rate, the chance of hitting <strong>at least one streak of 5 losses in a row</strong> somewhere in 100 trades is roughly <strong>81%</strong>. Plan for streaks — they are not a sign your strategy is broken.</p></div>
</section>

<section class="cx-sec">
  <h3><span class="n">2</span> Position size: let the stop decide the lot size</h3>
  <p>Most beginners pick a lot size first and hope. Professionals work backwards from the stop loss:</p>
  <div class="cx-key"><?= icon('scale', 'icon') ?><p><strong>Lot size = Money at risk ÷ (Stop distance × Value of a 1-unit move for 1 lot)</strong><br><span class="small">Money at risk = Account balance × Risk %</span></p></div>
  <div class="cx-cards">
    <div class="cx-card"><h4><?= icon('star', 'icon icon-sm') ?> Example: Gold (XAU/USD)</h4>
      <p>Account $10,000 · risk 1% = <strong>$100</strong><br>Entry 2,650.00 · stop 2,645.00 → distance <strong>$5</strong><br>1 lot = 100 oz, so a $1 move = $100 per lot → $5 stop = <strong>$500 per lot</strong></p>
      <p>Lot size = $100 ÷ $500 = <strong>0.20 lots</strong></p></div>
    <div class="cx-card"><h4><?= icon('globe', 'icon icon-sm') ?> Example: EUR/USD</h4>
      <p>Account $10,000 · risk 1% = <strong>$100</strong><br>Stop 20 pips · 1 standard lot ≈ <strong>$10 per pip</strong> → $200 per lot</p>
      <p>Lot size = $100 ÷ $200 = <strong>0.50 lots</strong></p></div>
    <div class="cx-card"><h4><?= icon('target', 'icon icon-sm') ?> Same risk, different stop</h4>
      <p>Wider stop → smaller position. Tighter stop → bigger position. <strong>The dollar risk stays the same</strong>; only the lot size changes.</p>
      <p>Gold with a $10 stop: $100 ÷ $1,000 = 0.10 lots.</p></div>
  </div>
  <div class="cx-calc" data-calc="size">
    <h4><?= icon('scale', 'icon') ?> Try it: position size calculator</h4>
    <div class="grid-form">
      <div class="f"><label for="cx-bal">Account balance ($)</label><input id="cx-bal" type="number" min="0" step="any" value="10000" data-in="bal"></div>
      <div class="f"><label for="cx-risk">Risk per trade (%)</label><input id="cx-risk" type="number" min="0" max="100" step="0.1" value="1" data-in="risk"></div>
      <div class="f"><label for="cx-inst">Instrument</label><select id="cx-inst" data-in="mult">
        <option value="100">Gold XAU/USD (1 lot = 100 oz)</option>
        <option value="100000">Forex pair, e.g. EUR/USD (1 lot = 100,000)</option>
        <option value="1000">USD/JPY-type pair (≈ $1,000 per 1.00 move — varies with rate)</option>
        <option value="5000">Silver XAG/USD (1 lot = 5,000 oz)</option>
        <option value="1">Index / crypto CFD (1 lot = 1 unit)</option>
      </select></div>
      <div class="f"><label for="cx-entry">Entry price</label><input id="cx-entry" type="number" step="any" value="2650" data-in="entry"></div>
      <div class="f"><label for="cx-stop">Stop loss price</label><input id="cx-stop" type="number" step="any" value="2645" data-in="stop"></div>
      <div class="f"><label for="cx-rr">Target R:R (1 : x)</label><input id="cx-rr" type="number" min="0" step="0.5" value="2" data-in="rr"></div>
    </div>
    <div class="cx-calc-out" aria-live="polite">
      <div class="main"><small>Lot size</small><b data-out="lots">0.20</b></div>
      <div><small>Money at risk</small><b data-out="riskusd">$100</b></div>
      <div><small>Loss per 1.00 lot</small><b data-out="perlot">$500</b></div>
      <div><small>Target price</small><b data-out="target">2,660</b></div>
      <div><small>Profit at target</small><b data-out="reward">$200</b></div>
    </div>
    <p class="small muted" style="margin:10px 0 0">Educational estimate, rounded down to 0.01 lots. Contract sizes differ by broker — check yours. For the full version with your saved accounts, use the <a href="<?= e(url('/terminal/calculator')) ?>">Risk Calculator</a>.</p>
  </div>
</section>

<section class="cx-sec">
  <h3><span class="n">3</span> Risk : reward — what you aim to win for what you risk</h3>
  <p>R:R compares the distance to your <strong>target</strong> with the distance to your <strong>stop</strong>. If you risk $100 to make $200, that's <strong>1 : 2</strong>. Traders call the amount risked “1R”, so a winner at 1:2 is “+2R” and a full loss is “−1R”.</p>
  <figure class="cx-fig">
    <svg viewBox="0 0 640 240" role="img" aria-label="Long trade with entry at 2650, stop at 2645 (risk 1R) and target at 2660 (reward 2R)">
      <rect class="zone-r" x="120" y="150" width="440" height="45" rx="6"/>
      <rect class="zone-s" x="120" y="60" width="440" height="90" rx="6"/>
      <line class="ln-m" x1="80" y1="150" x2="600" y2="150"/>
      
      <polyline class="ln" points="130,150 170,140 200,158 240,128 280,135 330,108 370,116 420,88 470,94 520,62" fill="none"/>
      <text class="lbl-up" x="575" y="56" text-anchor="end">Target 2,660 · +2R (+$200)</text>
      <text class="tb" x="575" y="144" text-anchor="end">Entry 2,650</text>
      <text class="lbl-dn" x="575" y="212" text-anchor="end">Stop 2,645 · −1R (−$100)</text>
      <text class="t" x="100" y="110" text-anchor="end">reward</text><text class="t" x="100" y="177" text-anchor="end">risk</text>
    </svg>
    <figcaption>A 1:2 long trade. The reward zone is twice the height of the risk zone.</figcaption>
  </figure>
  <div class="cx-warn"><?= icon('alert', 'icon') ?><p>A high R:R alone doesn't make money — a 1:5 target that is almost never reached is worse than a 1:1.5 target that is hit often. R:R and win rate only make sense <strong>together</strong>.</p></div>
</section>

<section class="cx-sec">
  <h3><span class="n">4</span> The win-rate table: how often must you be right?</h3>
  <p>For any R:R, there is a <strong>break-even win rate</strong>: win less often than this and you lose money over time (before costs). Formula: <strong>break-even = 1 ÷ (1 + R)</strong>.</p>
  <?php $be = ['1 : 0.5' => .5, '1 : 1' => 1, '1 : 1.5' => 1.5, '1 : 2' => 2, '1 : 2.5' => 2.5, '1 : 3' => 3, '1 : 4' => 4, '1 : 5' => 5]; ?>
  <div class="cx-tw"><table class="cx-table">
    <thead><tr><th>Risk : Reward</th><th>Break-even win rate</th><th class="r">Wins needed out of 10</th></tr></thead>
    <tbody><?php foreach ($be as $lab => $r): $p = 100 / (1 + $r); ?>
      <tr<?= $r == 2 ? ' class="hl"' : '' ?>><td><strong><?= $lab ?></strong></td><td><span class="cx-bar" style="width:<?= round($p * 0.9) ?>%"></span><strong><?= rtrim(rtrim(number_format($p, 1), '0'), '.') ?>%</strong></td><td class="r">more than <?= rtrim(rtrim(number_format($p / 10, 1), '0'), '.') ?></td></tr>
    <?php endforeach; ?></tbody></table></div>
  <p>At 1:2 you can be wrong <strong>two times out of three</strong> and still break even. That is why good traders focus on cutting losers at 1R and letting winners reach their target.</p>

  <h4 style="margin:22px 0 6px">Expectancy: average R per trade</h4>
  <p class="small muted" style="margin-top:0">Expectancy = (win rate × R) − (loss rate × 1). Positive = the strategy made money on average per trade in this model; negative = it lost. Green is better, red loses over time.</p>
  <?php $wr = [30, 40, 50, 60, 70]; $rrs = [1, 1.5, 2, 3]; ?>
  <div class="cx-tw"><table class="cx-table cx-heat">
    <thead><tr><th>Win rate ↓ · R:R →</th><?php foreach ($rrs as $r): ?><th class="r">1 : <?= $r ?></th><?php endforeach; ?></tr></thead>
    <tbody><?php foreach ($wr as $w): ?><tr><td><strong><?= $w ?>%</strong></td><?php foreach ($rrs as $r): $e = round($w / 100 * $r - (1 - $w / 100), 2); $cls = $e < -0.001 ? 'hn' : ($e < 0.001 ? 'h0' : ($e < 0.4 ? 'h1' : ($e < 0.9 ? 'h2' : 'h3'))); ?>
      <td class="v <?= $cls ?>"><?= $e > 0 ? '+' : ($e < 0 ? '−' : '') ?><?= rtrim(rtrim(number_format(abs($e), 2), '0'), '.') ?: '0' ?>R</td>
    <?php endforeach; ?></tr><?php endforeach; ?></tbody></table></div>
  <div class="cx-tip"><?= icon('check-circle', 'icon') ?><p>Example: 40% win rate at 1:2 → (0.4 × 2) − (0.6 × 1) = <strong>+0.2R</strong> per trade. Risking $100 per trade, that's about +$20 per trade on average over many trades — before spreads and commissions. Past results don't guarantee future ones; this is how to read your own statistics, not a promise.</p></div>
</section>

<section class="cx-sec">
  <h3><span class="n">5</span> Five risk rules to write down today</h3>
  <div class="cx-cards">
    <div class="cx-card"><h4><span class="tag">1</span> Stop loss on every trade</h4><p>Placed when you enter, at the price where your idea is proven wrong — never “I'll watch it”.</p></div>
    <div class="cx-card"><h4><span class="tag">2</span> Fixed % risk</h4><p>Same 0.5–2% every trade. Don't size up because a setup “feels sure”.</p></div>
    <div class="cx-card"><h4><span class="tag">3</span> Daily loss limit</h4><p>For example −3R or −3% in a day → you stop and come back tomorrow.</p></div>
    <div class="cx-card"><h4><span class="tag">4</span> Never widen the stop</h4><p>Moving a stop further away turns a planned 1R loss into an unplanned 3R loss.</p></div>
    <div class="cx-card"><h4><span class="tag">5</span> Watch total exposure</h4><p>Three trades on correlated markets (e.g. EUR/USD, GBP/USD, DXY short) are really one big trade.</p></div>
  </div>
  <div class="cx-key"><?= icon('shield', 'icon') ?><p>journzey can hold you to these: set your <a href="<?= e(url('/terminal/settings#risk')) ?>">risk rules and daily loss limit</a>, size trades with the <a href="<?= e(url('/terminal/calculator')) ?>">Risk Calculator</a>, and check your real R:R and win rate on the <a href="<?= e(url('/terminal/dashboard')) ?>">Dashboard</a>.</p></div>
</section>

<section class="cx-sec">
  <h3><span class="n">★</span> Key takeaways</h3>
  <ul class="cx-take">
    <li><?= icon('check', 'icon icon-sm') ?>Risk 1–2% (or less) of your account per trade, decided before you enter.</li>
    <li><?= icon('check', 'icon icon-sm') ?>Lot size = money at risk ÷ (stop distance × value per unit per lot). The stop decides the size.</li>
    <li><?= icon('check', 'icon icon-sm') ?>Break-even win rate = 1 ÷ (1 + R). At 1:2 you need to win only a bit over 33% of trades.</li>
    <li><?= icon('check', 'icon icon-sm') ?>Positive expectancy comes from win rate and R:R together — measure both in your journal.</li>
  </ul>
</section>

<section class="cx-quiz" data-quiz>
  <h3><?= icon('sparkles', 'icon') ?> Quick check</h3>
  <p class="muted small">Pick an answer — you'll see why right away.</p>
  <div class="cx-q" data-a="b"><p>1. $5,000 account, 1% risk, gold entry 2,400 with a stop at 2,396. About how many lots?</p>
    <label><input type="radio" name="q1" value="a"> 1.00 lot</label><label><input type="radio" name="q1" value="b"> 0.12 lots</label><label><input type="radio" name="q1" value="c"> 0.50 lots</label>
    <p class="why">Risk = $50. A $4 stop × $100 per $1 per lot = $400 per lot. $50 ÷ $400 = 0.125 → 0.12 lots (round down).</p></div>
  <div class="cx-q" data-a="a"><p>2. What is the break-even win rate at 1:3?</p>
    <label><input type="radio" name="q2" value="a"> 25%</label><label><input type="radio" name="q2" value="b"> 33%</label><label><input type="radio" name="q2" value="c"> 75%</label>
    <p class="why">1 ÷ (1 + 3) = 0.25. Win more than 1 trade in 4 at a true 1:3 and you are profitable before costs.</p></div>
  <div class="cx-q" data-a="c"><p>3. Your trade is near the stop and you “feel” it will turn. What should you do?</p>
    <label><input type="radio" name="q3" value="a"> Move the stop further away</label><label><input type="radio" name="q3" value="b"> Add to the position</label><label><input type="radio" name="q3" value="c"> Leave the stop where it is</label>
    <p class="why">The stop was set where the idea is wrong. Moving it turns a small planned loss into a large unplanned one.</p></div>
  <div class="cx-q" data-a="b"><p>4. 40% win rate at 1:2 R:R. What is the expectancy?</p>
    <label><input type="radio" name="q4" value="a"> −0.2R</label><label><input type="radio" name="q4" value="b"> +0.2R</label><label><input type="radio" name="q4" value="c"> +0.8R</label>
    <p class="why">(0.4 × 2) − (0.6 × 1) = 0.8 − 0.6 = +0.2R per trade on average.</p></div>
  <p class="cx-score" data-quiz-score aria-live="polite"></p>
</section>
