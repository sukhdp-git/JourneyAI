<?php /** Module 11 — Trade management: stops, targets & exits */ ?>
<section class="cx-sec">
  <h3><span class="n">1</span> Where a stop really belongs</h3>
  <p class="cx-lead">Your stop goes where <strong>your trade idea is proven wrong</strong> — not where the loss “feels OK”. Then the position size makes that distance cost exactly your planned risk.</p>
  <div class="cx-cards">
    <div class="cx-card"><h4><?= icon('layers', 'icon icon-sm') ?> Structure stop</h4><p>Just beyond the swing low (for a buy) or swing high (for a sell) that your setup depends on.</p></div>
    <div class="cx-card"><h4><?= icon('activity', 'icon icon-sm') ?> ATR buffer</h4><p>Add a fraction of ATR beyond that level so normal noise doesn't hit it.</p></div>
    <div class="cx-card"><h4><?= icon('alert', 'icon icon-sm') ?> Avoid obvious spots</h4><p>Exactly at round numbers or exactly on the swing point is where many stops cluster — and get swept.</p></div>
  </div>
  <div class="cx-warn"><?= icon('alert', 'icon') ?><p><strong>The one rule:</strong> a stop only ever moves <strong>towards profit</strong>, never further away. Widening a stop turns a planned 1R loss into a 3R disaster.</p></div>
</section>

<section class="cx-sec">
  <h3><span class="n">2</span> Targets: decide before you enter</h3>
  <p>Common ways to set a target — pick the one your backtest supports:</p>
  <ul>
    <li><strong>Fixed R:</strong> e.g. 2R. Simple and easy to test.</li>
    <li><strong>Next level:</strong> the next support/resistance or the previous high/low.</li>
    <li><strong>Trailing:</strong> no fixed target; ride the trend until the trail is hit.</li>
  </ul>
  <p>Check that the target is at least as far away as your plan requires <em>before</em> entering. If the next level is only 0.8R away, the trade doesn't fit a 2R plan — skip it.</p>
</section>

<section class="cx-sec">
  <h3><span class="n">3</span> Break-even, partials and trailing</h3>
  <div class="cx-tw"><table class="cx-table">
    <thead><tr><th>Technique</th><th>Helps</th><th>Costs</th></tr></thead>
    <tbody>
      <tr><td><strong>Move to break-even</strong> (e.g. at +1R)</td><td>Removes the risk on the trade</td><td>Normal pullbacks stop you out of trades that would have won</td></tr>
      <tr><td><strong>Partial profit</strong> (e.g. half at 1R)</td><td>Locks in some profit, calmer to hold the rest</td><td>Reduces the size of your best winners</td></tr>
      <tr><td><strong>Trailing stop</strong> (structure, ATR or MA)</td><td>Lets big trends pay you</td><td>Gives back part of the move before exiting</td></tr>
    </tbody></table></div>
  <div class="cx-tip"><?= icon('check-circle', 'icon') ?><p>None of these is right or wrong — but changing your mind <strong>during</strong> a trade usually is. Decide your management rule in advance and test it like the entry.</p>
  <figure class="cx-fig">
    <svg viewBox="0 0 640 220" role="img" aria-label="A long trade: the stop starts below entry, moves to break-even at 1R, then trails below each higher low">
      <path d="M30 170 L90 150 L120 160 L180 120 L210 132 L280 92 L310 104 L380 64 L410 76 L480 40 L610 30" fill="none" stroke="var(--strong)" stroke-width="2.5" stroke-linejoin="round"/>
      <line x1="30" x2="610" y1="150" y2="150" stroke="#8b6bff" stroke-width="2"/><text class="tb" x="34" y="144">entry</text>
      <path d="M30 186 H180 V150 H280 V132 H380 V104 H480 V76 H610" fill="none" stroke="var(--neg)" stroke-width="2.5" stroke-dasharray="7 5"/>
      <text class="lbl-dn" x="60" y="204">initial stop (−1R)</text><text class="lbl-b" x="190" y="172">break-even at +1R</text><text class="lbl-up" x="470" y="96">trailing under each higher low</text>
    </svg>
    <figcaption>Illustration. Move the stop only toward profit, by rule: to break-even at +1R, then below each new higher low.</figcaption>
  </figure></div>
</section>

<section class="cx-sec">
  <h3><span class="n">4</span> When to exit early (and when not to)</h3>
  <div class="cx-cards">
    <div class="cx-card"><h4><?= icon('check', 'icon icon-sm') ?> Valid reasons</h4><p>Your plan's invalidation happened (e.g. structure broke against you), a high-impact release is about to hit, or a time stop in your plan ran out.</p></div>
    <div class="cx-card"><h4><?= icon('x', 'icon icon-sm') ?> Not valid</h4><p>“It feels scary”, “I want to lock something in”, “I've had enough losses today”. Those are emotions, not rules.</p></div>
  </div>
  <p>Tag early exits in your journal (journzey has an <strong>Early exit</strong> mistake tag). After 20 trades you'll see in money what it costs you.</p>
  <ul class="cx-take">
    <li><?= icon('check', 'icon icon-sm') ?>Stops go where the idea is wrong; size from the stop.</li>
    <li><?= icon('check', 'icon icon-sm') ?>Never move a stop away from price.</li>
    <li><?= icon('check', 'icon icon-sm') ?>Choose break-even, partial and trailing rules before entering.</li>
  </ul>
</section>

<section class="cx-quiz" data-quiz>
  <h3><?= icon('sparkles', 'icon') ?> Quick check</h3>
  <p class="muted small">Pick an answer — you'll see why right away.</p>
  <div class="cx-q" data-a="c"><p>1. Price is near your stop and you feel it will turn. You…</p>
    <label><input type="radio" name="q1" value="a"> move the stop further away</label><label><input type="radio" name="q1" value="b"> remove the stop</label><label><input type="radio" name="q1" value="c"> leave it — it only moves towards profit</label>
    <p class="why">Widening stops turns planned small losses into big ones.</p></div>
  <div class="cx-q" data-a="a"><p>2. Your plan needs 2R, but the next resistance is 0.8R away. Best action?</p>
    <label><input type="radio" name="q2" value="a"> Skip the trade</label><label><input type="radio" name="q2" value="b"> Take it with a 2R target anyway</label><label><input type="radio" name="q2" value="c"> Double the size</label>
    <p class="why">If the target doesn't fit the plan before entry, the trade doesn't fit the plan.</p></div>
  <div class="cx-q" data-a="b"><p>3. A downside of moving the stop to break-even too early?</p>
    <label><input type="radio" name="q3" value="a"> None</label><label><input type="radio" name="q3" value="b"> Normal pullbacks stop out trades that would have won</label><label><input type="radio" name="q3" value="c"> It increases the risk</label>
    <p class="why">Break-even removes risk but also cuts some winners. Test it.</p></div>
  <p class="cx-score" data-quiz-score aria-live="polite"></p>
</section>
