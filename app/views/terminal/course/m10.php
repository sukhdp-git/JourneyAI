<?php /** Module 10 — Build and test your own strategy */ ?>
<section class="cx-sec">
  <h3><span class="n">1</span> A strategy is a set of if-then rules</h3>
  <p class="cx-lead">If two people can't follow your rules and take the same trade, it isn't a strategy yet — it's a feeling. Write every part down.</p>
  <div class="cx-tw"><table class="cx-table">
    <thead><tr><th>Part</th><th>Example (write your own)</th></tr></thead>
    <tbody>
      <tr><td><strong>Market &amp; time</strong></td><td>XAUUSD, London session 08:00–11:00 UTC only</td></tr>
      <tr><td><strong>Direction filter</strong></td><td>Only buy if 1H makes higher highs and higher lows</td></tr>
      <tr><td><strong>Setup</strong></td><td>Price pulls back to the last 15m higher low / support</td></tr>
      <tr><td><strong>Trigger</strong></td><td>A 5m bullish engulfing candle closes at that level</td></tr>
      <tr><td><strong>Stop</strong></td><td>1 × ATR(14) below the engulfing low</td></tr>
      <tr><td><strong>Target &amp; management</strong></td><td>2R target; move stop to break-even at +1R</td></tr>
      <tr><td><strong>Risk</strong></td><td>1% per trade; max 2 trades a day; stop after 2 losses</td></tr>
    </tbody></table></div>
  <div class="cx-tip"><?= icon('check-circle', 'icon') ?><p>Save it in journzey's <strong>Strategy builder</strong>. Every trade you log can then be linked to it, and Strategy Analysis compares your strategies automatically.</p></div>
</section>

<section class="cx-sec">
  <h3><span class="n">2</span> Backtest it honestly</h3>
  <ol class="cx-rules">
    <li><strong>Pick a period</strong> you haven't studied, ideally 6–12 months, with different market conditions.</li>
    <li><strong>Scroll forward candle by candle</strong> (bar replay) so you can't see the future.</li>
    <li><strong>Take every valid setup</strong> exactly by the rules — including the ones you “don't like”.</li>
    <li><strong>Record each trade:</strong> date, time, entry, stop, exit, result in R, notes.</li>
    <li><strong>Include costs:</strong> spread and commission make a real difference to small targets.</li>
    <li><strong>Collect at least 100 trades</strong> before drawing conclusions.</li>
  </ol>
  <div class="cx-warn"><?= icon('alert', 'icon') ?><p>With only 20 trades, luck dominates. A coin flipped 20 times can easily land 14–6. Small samples mislead in both directions.</p></div>
</section>

<section class="cx-sec">
  <h3><span class="n">3</span> Read the results</h3>
  <div class="cx-cards">
    <div class="cx-stat"><b>Expectancy</b><span>Average R per trade = (win% × avg win R) − (loss% × avg loss R). Must be clearly above zero after costs.</span></div>
    <div class="cx-stat"><b>Max drawdown</b><span>The worst peak-to-low drop. Could you sit through it with real money?</span></div>
    <div class="cx-stat"><b>Losing streak</b><span>The longest run of losses. Plan your risk so twice that streak is survivable.</span></div>
    <div class="cx-stat"><b>Trades per week</b><span>Enough opportunities to matter, few enough to execute well.</span></div>
  </div>
  <div class="cx-tip"><?= icon('check-circle', 'icon') ?><p>Example: 100 trades, 45 wins averaging 2R, 55 losses at −1R → (0.45 × 2) − (0.55 × 1) = <strong>+0.35R per trade</strong> before costs. That's an edge worth forward-testing. A backtest is evidence, not a promise.</p></div>
</section>

<section class="cx-sec">
  <h3><span class="n">4</span> Four ways backtests lie</h3>
  <div class="cx-tw"><table class="cx-table">
    <thead><tr><th>Trap</th><th>What happens</th><th>Fix</th></tr></thead>
    <tbody>
      <tr><td><strong>Overfitting</strong></td><td>Rules tuned until the past looks perfect</td><td>Keep rules simple; test on a different period</td></tr>
      <tr><td><strong>Hindsight</strong></td><td>You “see” the setup because you know what came next</td><td>Bar replay; decide before the next candle</td></tr>
      <tr><td><strong>Cherry-picking</strong></td><td>Skipping setups that would have lost</td><td>Log every valid setup</td></tr>
      <tr><td><strong>No costs</strong></td><td>Small edges disappear after spread and commission</td><td>Subtract real costs from every trade</td></tr>
    </tbody></table></div>
</section>

<section class="cx-sec">
  <h3><span class="n">5</span> From backtest to real money</h3>
  <ol class="cx-rules">
    <li><strong>Forward-test on demo</strong> for 4–8 weeks, in real time, logging every trade in your journal.</li>
    <li><strong>Compare</strong> the live results with the backtest. Similar? Continue. Much worse? Find out why first.</li>
    <li><strong>Go live small:</strong> 0.25–0.5% risk per trade for the first 30–50 trades.</li>
    <li><strong>Increase slowly</strong> only when your journal shows you execute the rules and the edge holds (Module 13).</li>
  </ol>
  <ul class="cx-take">
    <li><?= icon('check', 'icon icon-sm') ?>Write if-then rules for every part of the trade.</li>
    <li><?= icon('check', 'icon icon-sm') ?>Backtest 100+ trades with costs and without hindsight.</li>
    <li><?= icon('check', 'icon icon-sm') ?>Demo, then small live, then size up slowly.</li>
  </ul>
</section>

<section class="cx-quiz" data-quiz>
  <h3><?= icon('sparkles', 'icon') ?> Quick check</h3>
  <p class="muted small">Pick an answer — you'll see why right away.</p>
  <div class="cx-q" data-a="b"><p>1. Your strategy won 15 of its first 20 backtest trades. Conclusion?</p>
    <label><input type="radio" name="q1" value="a"> It's proven — go live big</label><label><input type="radio" name="q1" value="b"> Promising, but the sample is too small</label><label><input type="radio" name="q1" value="c"> It must be overfitted</label>
    <p class="why">20 trades is mostly luck. Keep testing to 100+.</p></div>
  <div class="cx-q" data-a="a"><p>2. 50 wins at +1.5R and 50 losses at −1R. Expectancy per trade?</p>
    <label><input type="radio" name="q2" value="a"> +0.25R</label><label><input type="radio" name="q2" value="b"> +0.5R</label><label><input type="radio" name="q2" value="c"> 0R</label>
    <p class="why">(0.5 × 1.5) − (0.5 × 1) = 0.75 − 0.5 = +0.25R, before costs.</p></div>
  <div class="cx-q" data-a="c"><p>3. You skip backtest setups you “wouldn't have taken”. That's…</p>
    <label><input type="radio" name="q3" value="a"> good discipline</label><label><input type="radio" name="q3" value="b"> a way to save time</label><label><input type="radio" name="q3" value="c"> cherry-picking: it inflates the results</label>
    <p class="why">Every valid setup must count, or the test measures your hindsight.</p></div>
  <p class="cx-score" data-quiz-score aria-live="polite"></p>
</section>
