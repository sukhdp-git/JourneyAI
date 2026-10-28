<?php /** Module 7 — Reading charts like a pro */
// tiny candle helper: x, open, close, high, low (svg y units, smaller = higher)
$k = fn ($x, $o, $c, $h, $l, $w = 14) => '<line class="' . ($c < $o ? 'up' : 'dn') . ' wick" x1="' . $x . '" x2="' . $x . '" y1="' . $h . '" y2="' . $l . '"/><rect class="' . ($c < $o ? 'up' : 'dn') . '" x="' . ($x - $w / 2) . '" y="' . min($o, $c) . '" width="' . $w . '" height="' . max(2, abs($o - $c)) . '" rx="2"/>';
?>
<section class="cx-sec">
  <h3><span class="n">1</span> Top-down: big picture first, entry last</h3>
  <p class="cx-lead">Most bad trades fight the bigger trend without knowing it. Look at <strong>three timeframes, from big to small</strong>.</p>
  <div class="cx-cards">
    <div class="cx-card"><h4><?= icon('compass', 'icon icon-sm') ?> 1. Direction</h4><p>A higher timeframe (e.g. Daily or 4H). Is the market trending up, down or ranging? Where are the big levels?</p></div>
    <div class="cx-card"><h4><?= icon('target', 'icon icon-sm') ?> 2. Setup</h4><p>A middle timeframe (e.g. 1H or 15m). Is your setup forming at one of those levels, in that direction?</p></div>
    <div class="cx-card"><h4><?= icon('zap', 'icon icon-sm') ?> 3. Entry</h4><p>A lower timeframe (e.g. 5m or 1m). The exact trigger and a tight, logical stop.</p></div>
  </div>
  <div class="cx-tip"><?= icon('check-circle', 'icon') ?><p>A common ratio is about 4–6× between timeframes: Daily → 4H → 1H for swing trades, or 1H → 15m → 1m for intraday.</p></div>
</section>

<section class="cx-sec">
  <h3><span class="n">2</span> Structure tells you who's in control</h3>
  <p>An uptrend makes <strong>higher highs (HH) and higher lows (HL)</strong>. A downtrend makes <strong>lower highs (LH) and lower lows (LL)</strong>. When price breaks the last higher low of an uptrend, that <strong>break of structure</strong> is the first warning that the trend may be changing.</p>
  <figure class="cx-fig">
    <svg viewBox="0 0 640 230" role="img" aria-label="Uptrend with higher highs and higher lows, then a break of the last higher low">
      <polyline class="ln" points="20,190 90,120 130,150 210,80 250,115 330,45 380,95 440,170 500,140 600,205"/>
      <g class="t"><text x="84" y="108">HH</text><text x="122" y="168">HL</text><text x="204" y="68">HH</text><text x="242" y="134">HL</text><text x="322" y="34">HH</text><text x="372" y="114">HL</text></g>
      <line class="ln-m" x1="372" x2="620" y1="95" y2="95"/>
      <circle class="pt" cx="404" cy="95" r="7"/><text class="lbl-dn" x="414" y="88">Break of structure</text>
      <text class="lbl-dn" x="508" y="128">LH</text><text class="lbl-dn" x="560" y="218">LL</text>
    </svg>
    <figcaption>Illustration. Up-trend (HH + HL) until price closes below the last higher low; then lower highs and lower lows begin.</figcaption>
  </figure>
  <div class="cx-warn"><?= icon('alert', 'icon') ?><p>One break is a warning, not a guarantee. Many traders wait for the first lower high to confirm a new down-trend before selling.</p></div>
</section>

<section class="cx-sec">
  <h3><span class="n">3</span> Two-candle and three-candle signals</h3>
  <p>Single candles were in Module 2. These combinations add context:</p>
  <div class="cx-figs">
    <figure class="cx-fig"><svg viewBox="0 0 200 150" role="img" aria-label="Bullish engulfing"><?= $k(80, 60, 95, 52, 104, 22) ?><?= $k(120, 100, 40, 32, 110, 30) ?></svg><figcaption><strong>Bullish engulfing</strong> — a green body swallows the previous red one. Strongest at support.</figcaption></figure>
    <figure class="cx-fig"><svg viewBox="0 0 200 150" role="img" aria-label="Inside bar"><?= $k(80, 30, 120, 22, 130, 30) ?><?= $k(120, 90, 62, 55, 98, 18) ?></svg><figcaption><strong>Inside bar</strong> — the whole candle fits inside the previous one. A pause before a breakout.</figcaption></figure>
    <figure class="cx-fig"><svg viewBox="0 0 200 150" role="img" aria-label="Morning star"><?= $k(55, 40, 100, 35, 106, 22) ?><?= $k(100, 112, 106, 98, 124, 12) ?><?= $k(145, 100, 48, 42, 106, 22) ?></svg><figcaption><strong>Morning star</strong> — big red, small pause, big green. A possible bottom after a fall.</figcaption></figure>
  </div>
  <div class="cx-tip"><?= icon('check-circle', 'icon') ?><p>A pattern means something <strong>at a level</strong> (support, resistance, a trend line). The same candle in the middle of nowhere is usually noise.</p></div>
</section>

<section class="cx-sec">
  <h3><span class="n">4</span> Chart patterns worth knowing</h3>
  <div class="cx-tw"><table class="cx-table">
    <thead><tr><th>Pattern</th><th>What it shows</th><th>Usual confirmation</th></tr></thead>
    <tbody>
      <tr><td><strong>Double top / bottom</strong></td><td>Price fails twice at the same level</td><td>Close beyond the middle low (or high)</td></tr>
      <tr><td><strong>Head &amp; shoulders</strong></td><td>A trend runs out of strength: higher peak, then a lower one</td><td>Close through the “neckline”</td></tr>
      <tr><td><strong>Flag / pennant</strong></td><td>A short pause inside a strong trend</td><td>Breakout in the trend's direction</td></tr>
      <tr><td><strong>Triangle</strong></td><td>Ranges get tighter; pressure builds</td><td>Close outside the triangle</td></tr>
      <tr><td><strong>Range</strong></td><td>Price bounces between clear support and resistance</td><td>Trade the edges, or wait for a break</td></tr>
    </tbody></table></div>
  <div class="cx-warn"><?= icon('alert', 'icon') ?><p>Patterns are probabilities, not promises. Test any pattern in your own backtest (Module 10) before trusting it with money.</p></div>
</section>

<section class="cx-sec">
  <h3><span class="n">5</span> Breakout or fakeout?</h3>
  <p>Price pokes through a level, traders jump in… and it snaps back. That's a <strong>fakeout</strong> (or liquidity sweep): the move took out stop orders sitting just beyond the level, then reversed.</p>
  <figure class="cx-fig">
    <svg viewBox="0 0 640 210" role="img" aria-label="Fakeout above resistance versus a real breakout with retest">
      <line class="ln-m" x1="20" x2="300" y1="70" y2="70"/><text class="t" x="24" y="62">Resistance</text>
      <polyline class="ln" points="30,150 80,90 120,130 170,78 210,120 250,40 280,140 300,180"/>
      <text class="lbl-dn" x="240" y="30">Fakeout: wick above, close back below</text>
      <line class="ln-m" x1="340" x2="620" y1="110" y2="110"/><text class="t" x="344" y="102">Resistance</text>
      <polyline class="ln" points="350,180 400,125 440,160 490,90 520,115 560,70 600,40"/>
      <circle class="pt" cx="520" cy="112" r="7"/><text class="lbl-up" x="530" y="140">Close above, retest holds</text>
    </svg>
    <figcaption>Illustration. Left: price spikes through and closes back inside — a trap. Right: a close beyond the level, then a retest from the other side.</figcaption>
  </figure>
  <div class="cx-cards">
    <div class="cx-card"><h4><?= icon('check', 'icon icon-sm') ?> Wait for the close</h4><p>A candle that only wicks through is not a breakout.</p></div>
    <div class="cx-card"><h4><?= icon('check', 'icon icon-sm') ?> Or wait for the retest</h4><p>Old resistance becoming support gives a cleaner entry and stop.</p></div>
    <div class="cx-card"><h4><?= icon('check', 'icon icon-sm') ?> Check the session</h4><p>Breakouts in quiet hours fail more often than in busy ones.</p></div>
  </div>
  <ul class="cx-take">
    <li><?= icon('check', 'icon icon-sm') ?>Direction from the big timeframe, entry from the small one.</li>
    <li><?= icon('check', 'icon icon-sm') ?>HH/HL = up, LH/LL = down; a break of structure is a warning.</li>
    <li><?= icon('check', 'icon icon-sm') ?>Patterns matter at levels, and only after your own testing.</li>
  </ul>
</section>

<section class="cx-quiz" data-quiz>
  <h3><?= icon('sparkles', 'icon') ?> Quick check</h3>
  <p class="muted small">Pick an answer — you'll see why right away.</p>
  <div class="cx-q" data-a="b"><p>1. The Daily chart is in a strong down-trend. Your 5-minute chart shows a buy signal. Best reading?</p>
    <label><input type="radio" name="q1" value="a"> Buy with a bigger size</label><label><input type="radio" name="q1" value="b"> You'd be fighting the bigger trend; be careful or skip</label><label><input type="radio" name="q1" value="c"> Timeframes don't matter</label>
    <p class="why">Top-down: the bigger timeframe sets the direction you trade with.</p></div>
  <div class="cx-q" data-a="c"><p>2. An up-trend just broke below its last higher low. This is…</p>
    <label><input type="radio" name="q2" value="a"> a guaranteed reversal</label><label><input type="radio" name="q2" value="b"> irrelevant</label><label><input type="radio" name="q2" value="c"> a warning that the trend may be changing</label>
    <p class="why">A break of structure is the first sign, not proof. Many wait for a lower high.</p></div>
  <div class="cx-q" data-a="a"><p>3. Price wicks above resistance and closes back below. That's most likely…</p>
    <label><input type="radio" name="q3" value="a"> a fakeout</label><label><input type="radio" name="q3" value="b"> a confirmed breakout</label><label><input type="radio" name="q3" value="c"> a gap</label>
    <p class="why">No close beyond the level = no confirmed breakout.</p></div>
  <p class="cx-score" data-quiz-score aria-live="polite"></p>
</section>
