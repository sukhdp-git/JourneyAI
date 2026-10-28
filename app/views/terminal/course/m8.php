<?php /** Module 8 — Indicators: what they really tell you */ ?>
<section class="cx-sec">
  <h3><span class="n">1</span> What an indicator is (and isn't)</h3>
  <p class="cx-lead">An indicator is <strong>a formula applied to past prices</strong> (and sometimes volume). It can summarise what happened. It can't know what happens next, and most of them <strong>lag</strong> price.</p>
  <div class="cx-cards">
    <div class="cx-stat"><b>Trend</b><span>Which way is it going? Moving averages, VWAP.</span></div>
    <div class="cx-stat"><b>Momentum</b><span>How strong is the push? RSI, MACD.</span></div>
    <div class="cx-stat"><b>Volatility</b><span>How far does it usually move? ATR, Bollinger Bands.</span></div>
  </div>
  <div class="cx-tip"><?= icon('check-circle', 'icon') ?><p>Use <strong>one or two indicators that answer different questions</strong>. Five momentum indicators on one chart all say the same thing five times.</p></div>
</section>

<section class="cx-sec">
  <h3><span class="n">2</span> Moving averages</h3>
  <p>The average closing price of the last N candles, drawn as a line. The <strong>SMA</strong> weights every candle equally; the <strong>EMA</strong> reacts faster because it weights recent candles more.</p>
  <figure class="cx-fig">
    <svg viewBox="0 0 640 200" role="img" aria-label="Price above a rising moving average in an up-trend">
      <polyline class="ln-m" points="20,170 80,150 140,140 200,120 260,110 320,95 380,80 440,72 500,60 560,52 620,40"/>
      <polyline class="ln" points="20,160 60,130 100,150 150,110 190,128 240,90 280,105 330,70 370,88 420,55 460,66 510,40 560,48 620,22"/>
      <text class="t" x="560" y="72">moving average</text><text class="lbl-b" x="460" y="36">price</text>
    </svg>
    <figcaption>Illustration. Price staying above a rising average = up-trend; pullbacks to the average are common entry areas.</figcaption>
  </figure>
  <p>Common settings: <strong>20</strong> (short-term), <strong>50</strong> (medium) and <strong>200</strong> (long-term trend). Price above a rising 200 is often treated as a long-term up-trend.</p>
</section>

<section class="cx-sec">
  <h3><span class="n">3</span> RSI and MACD: momentum</h3>
  <div class="cx-cards">
    <div class="cx-card"><h4><?= icon('activity', 'icon icon-sm') ?> RSI (14)</h4><p>A 0–100 score of recent up-moves vs down-moves. Above 70 is called “overbought”, below 30 “oversold”.</p><p class="small"><strong>Trap:</strong> in a strong trend, RSI can stay above 70 for a long time. Overbought is not a sell signal on its own.</p></div>
    <div class="cx-card"><h4><?= icon('bars', 'icon icon-sm') ?> MACD (12, 26, 9)</h4><p>The gap between a fast and a slow EMA, plus a signal line. Crossovers and the histogram show momentum speeding up or fading.</p></div>
    <div class="cx-card"><h4><?= icon('eye', 'icon icon-sm') ?> Divergence</h4><p>Price makes a new high but RSI/MACD makes a lower high: momentum is fading. A warning to tighten up, not an entry by itself.</p></div>
  </div>
</section>

<section class="cx-sec">
  <h3><span class="n">4</span> ATR and Bollinger Bands: volatility</h3>
  <p><strong>ATR (Average True Range)</strong> tells you how far the market typically moves per candle. It's the most practical indicator for beginners because it answers a real question: <em>how wide should my stop be?</em></p>
  <div class="cx-key"><?= icon('scale', 'icon') ?><p><strong>Example:</strong> gold's 15-minute ATR is $4. A stop of 1.5 × ATR = $6 beyond your level gives the trade room to breathe. Then size the position from that $6 stop (Module 3), so your $ risk stays the same.</p></div>
  <p><strong>Bollinger Bands</strong> (20, 2) draw a band about two standard deviations around a 20-period average. Narrow bands = quiet market (a “squeeze” often comes before a big move). Price riding the outer band = strong trend.</p>
</section>

<section class="cx-sec">
  <h3><span class="n">5</span> VWAP and volume</h3>
  <p><strong>VWAP</strong> is the day's average price weighted by volume. Many institutions use it as a benchmark for their fills, so intraday traders watch it as a fair-value line: above it, buyers have had the upper hand today; below it, sellers.</p>
  <p><strong>Volume</strong> confirms moves: a breakout on rising volume is more convincing than one on thin volume. In spot forex there is no central volume, so platforms show <em>tick volume</em> (number of price changes), which is a rough stand-in.</p>
  <ul class="cx-take">
    <li><?= icon('check', 'icon icon-sm') ?>Indicators summarise the past; price and levels come first.</li>
    <li><?= icon('check', 'icon icon-sm') ?>Pick one trend tool and one momentum or volatility tool.</li>
    <li><?= icon('check', 'icon icon-sm') ?>Use ATR to set stops that fit the market, then size from the stop.</li>
  </ul>
</section>

<section class="cx-quiz" data-quiz>
  <h3><?= icon('sparkles', 'icon') ?> Quick check</h3>
  <p class="muted small">Pick an answer — you'll see why right away.</p>
  <div class="cx-q" data-a="b"><p>1. RSI is at 78 in a strong up-trend. What does that mean on its own?</p>
    <label><input type="radio" name="q1" value="a"> Sell now</label><label><input type="radio" name="q1" value="b"> Momentum is strong; not a signal by itself</label><label><input type="radio" name="q1" value="c"> The trend is over</label>
    <p class="why">RSI can stay “overbought” for a long time while a trend continues.</p></div>
  <div class="cx-q" data-a="a"><p>2. Which indicator answers “how wide should my stop be?”</p>
    <label><input type="radio" name="q2" value="a"> ATR</label><label><input type="radio" name="q2" value="b"> MACD</label><label><input type="radio" name="q2" value="c"> RSI</label>
    <p class="why">ATR measures typical movement, so stops can fit the market's volatility.</p></div>
  <div class="cx-q" data-a="c"><p>3. You have RSI, Stochastic and CCI on one chart. The problem?</p>
    <label><input type="radio" name="q3" value="a"> None, more is better</label><label><input type="radio" name="q3" value="b"> They are too slow</label><label><input type="radio" name="q3" value="c"> They all measure momentum, so they repeat each other</label>
    <p class="why">Combine tools that answer different questions: trend, momentum, volatility.</p></div>
  <p class="cx-score" data-quiz-score aria-live="polite"></p>
</section>
