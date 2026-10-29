<?php /** Module 13 — Running your trading like a business */ ?>
<section class="cx-sec">
  <h3><span class="n">1</span> The daily routine</h3>
  <p class="cx-lead">Professional results come from a boring, repeatable process. Three blocks a day:</p>
  <div class="cx-cards">
    <div class="cx-card"><h4><?= icon('calendar', 'icon icon-sm') ?> Before (15 min)</h4><p>Check the news calendar, mark key levels, note the trend, set today's max loss and max trades.</p></div>
    <div class="cx-card"><h4><?= icon('target', 'icon icon-sm') ?> During</h4><p>Only your setups, only your session. Log each trade right after closing it (Quick Log takes 10 seconds).</p></div>
    <div class="cx-card"><h4><?= icon('book', 'icon icon-sm') ?> After (10 min)</h4><p>Tag emotions and mistakes, write one lesson in the Daily notepad, close the platform.</p></div>
  </div>
</section>

<section class="cx-sec">
  <h3><span class="n">2</span> The weekly and monthly review</h3>
  <ol class="cx-rules">
    <li><strong>Weekly (20 min):</strong> best trade, worst trade, rules followed %, costliest mistake, best and worst hour. Pick <strong>one</strong> thing to fix next week.</li>
    <li><strong>Monthly (45 min):</strong> expectancy, profit factor, drawdown and each strategy's results. Keep what works, cut what doesn't.</li>
  </ol>
  <div class="cx-tip"><?= icon('check-circle', 'icon') ?><p>journzey's Performance review and Edge Matrix produce these numbers for you; your job is to read them and choose the one change.</p>
  <figure class="cx-fig">
    <svg viewBox="0 0 640 220" role="img" aria-label="The improvement loop: plan, trade, journal, review, adjust, then plan again">
      <?php $steps = ['Plan', 'Trade', 'Journal', 'Review', 'Adjust']; foreach ($steps as $i => $t): $a = -M_PI / 2 + $i * 2 * M_PI / 5; $x = 320 + cos($a) * 150; $y = 112 + sin($a) * 78; ?>
      <circle cx="<?= round($x) ?>" cy="<?= round($y) ?>" r="30" class="brand" opacity=".9"/><text x="<?= round($x) ?>" y="<?= round($y) + 4 ?>" text-anchor="middle" fill="#fff" font-size="12" font-weight="700"><?= $t ?></text>
      <?php endforeach; ?>
      <ellipse cx="320" cy="112" rx="150" ry="78" fill="none" class="ln-m"/>
      <text class="tb" x="320" y="110" text-anchor="middle">every week</text><text class="ts" x="320" y="126" text-anchor="middle">one change at a time</text>
    </svg>
    <figcaption>The loop that turns a hobby into a business: change one thing per week, and let the journal show whether it helped.</figcaption>
  </figure></div>
</section>

<section class="cx-sec">
  <h3><span class="n">3</span> When to size up — and when to size down</h3>
  <div class="cx-tw"><table class="cx-table">
    <thead><tr><th>Situation</th><th>Action</th></tr></thead>
    <tbody>
      <tr><td>50+ trades, positive expectancy, rules followed 80%+</td><td>Increase risk one small step (e.g. 0.5% → 0.75%)</td></tr>
      <tr class="hl"><td>Account down 5% from its peak</td><td>Halve your risk per trade</td></tr>
      <tr class="hl"><td>Account down 10% from its peak</td><td>Stop live trading; review the journal; demo until the cause is clear</td></tr>
      <tr><td>A big winning streak</td><td>Change nothing. Overconfidence is a risk too</td></tr>
    </tbody></table></div>
  <p class="small muted">These are example thresholds — write your own into your plan before you need them.</p>
</section>

<section class="cx-sec">
  <h3><span class="n">4</span> Records, taxes and your head</h3>
  <div class="cx-cards">
    <div class="cx-card"><h4><?= icon('file', 'icon icon-sm') ?> Keep records</h4><p>Your journal plus broker statements are your record of every trade. Export them regularly.</p></div>
    <div class="cx-card"><h4><?= icon('scale', 'icon icon-sm') ?> Taxes</h4><p>Trading profits are usually taxable, and the rules differ by country. Ask a qualified tax professional where you live.</p></div>
    <div class="cx-card"><h4><?= icon('heart', 'icon icon-sm') ?> Your head</h4><p>Sleep, breaks and a life outside the charts are part of the edge. If losses start hurting your mood or relationships, take a real break.</p></div>
  </div>
  <div class="cx-key"><?= icon('info', 'icon') ?><p>Never trade money you need for rent, food or bills. Treat trading capital as money you can afford to lose.</p></div>
  <ul class="cx-take">
    <li><?= icon('check', 'icon icon-sm') ?>Before, during, after: the same routine every day.</li>
    <li><?= icon('check', 'icon icon-sm') ?>Review weekly, fix one thing at a time.</li>
    <li><?= icon('check', 'icon icon-sm') ?>A written plan for sizing up and for drawdowns.</li>
  </ul>
</section>

<section class="cx-quiz" data-quiz>
  <h3><?= icon('sparkles', 'icon') ?> Quick check</h3>
  <p class="muted small">Pick an answer — you'll see why right away.</p>
  <div class="cx-q" data-a="a"><p>1. Your account is 10% below its peak. A solid plan says…</p>
    <label><input type="radio" name="q1" value="a"> stop live trading and review</label><label><input type="radio" name="q1" value="b"> double the size to recover</label><label><input type="radio" name="q1" value="c"> try a new strategy today</label>
    <p class="why">Recovering by sizing up is how drawdowns become blown accounts.</p></div>
  <div class="cx-q" data-a="c"><p>2. In your weekly review you find three problems. You should…</p>
    <label><input type="radio" name="q2" value="a"> fix all three at once</label><label><input type="radio" name="q2" value="b"> ignore them until month-end</label><label><input type="radio" name="q2" value="c"> fix the costliest one next week</label>
    <p class="why">One change at a time — so you know what worked.</p></div>
  <div class="cx-q" data-a="b"><p>3. When is it reasonable to increase risk per trade?</p>
    <label><input type="radio" name="q3" value="a"> After one great day</label><label><input type="radio" name="q3" value="b"> After a proven sample with positive expectancy and good discipline</label><label><input type="radio" name="q3" value="c"> When you feel confident</label>
    <p class="why">Size follows evidence from your journal, not feelings.</p></div>
  <p class="cx-score" data-quiz-score aria-live="polite"></p>
</section>
