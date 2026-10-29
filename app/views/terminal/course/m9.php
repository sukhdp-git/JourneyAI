<?php /** Module 9 — News, central banks & the economic calendar */ ?>
<section class="cx-sec">
  <h3><span class="n">1</span> Why news moves price</h3>
  <p class="cx-lead">Prices move when <strong>expectations change</strong>. A release that matches the forecast often does little; a big <strong>surprise</strong> versus the forecast can move a market in seconds.</p>
  <figure class="cx-fig">
    <svg viewBox="0 0 640 210" role="img" aria-label="Two examples: a release in line with the forecast moves price a little; a surprise moves it a lot">
      <text class="tb" x="20" y="22">In line with the forecast</text><text class="ts" x="20" y="38">Forecast 3.1% · Actual 3.1%</text>
      <path class="ln-m" d="M20 120 H130"/><path class="ln" d="M130 120 L150 112 L168 124 L190 116 L210 122 L240 118 L290 120"/>
      <line class="gl" x1="130" x2="130" y1="50" y2="190"/><text class="ts" x="130" y="204" text-anchor="middle">release</text>
      <text class="lbl-b" x="230" y="96">small move</text>
      <text class="tb" x="340" y="22">Surprise</text><text class="ts" x="340" y="38">Forecast 3.1% · Actual 3.6%</text>
      <path class="ln-m" d="M340 80 H450"/><path d="M450 80 L458 84 L466 150 L476 128 L490 168 L510 160 L540 176 L620 182" fill="none" stroke="var(--neg)" stroke-width="3" stroke-linejoin="round"/>
      <line class="gl" x1="450" x2="450" y1="50" y2="190"/><text class="ts" x="450" y="204" text-anchor="middle">release</text>
      <rect class="zone-r" x="452" y="74" width="30" height="104" rx="6"/><text class="lbl-dn" x="560" y="150">big move + wide spread</text>
    </svg>
    <figcaption>Illustration. Price reacts to the gap between the forecast and the actual number, not to the number itself.</figcaption>
  </figure>
  <div class="cx-key"><?= icon('info', 'icon') ?><p>Every economic calendar shows three numbers: <strong>Previous</strong>, <strong>Forecast</strong> (what economists expect) and <strong>Actual</strong>. The market reacts mostly to Actual vs Forecast.</p></div>
</section>

<section class="cx-sec">
  <h3><span class="n">2</span> Interest rates: the biggest driver</h3>
  <p>Central banks set interest rates: the <strong>Fed</strong> (US), <strong>ECB</strong> (Eurozone), <strong>Bank of England</strong>, <strong>Bank of Japan</strong>, the <strong>RBI</strong> (India) and others. In general, when a country's rates are expected to go <strong>up</strong>, its currency tends to get <strong>stronger</strong>, because holding it pays more.</p>
  <div class="cx-cards">
    <div class="cx-card"><h4><?= icon('globe', 'icon icon-sm') ?> Currencies</h4><p>Expectations of higher rates usually support the currency; cuts usually weigh on it.</p></div>
    <div class="cx-card"><h4><?= icon('star', 'icon icon-sm') ?> Gold</h4><p>Pays no interest, so it often struggles when real interest rates and the US dollar rise, and does well when they fall. Often, not always.</p></div>
    <div class="cx-card"><h4><?= icon('trend-up', 'icon icon-sm') ?> Stock indices</h4><p>React to rates (cheaper money helps) and to company earnings season.</p></div>
  </div>
  <div class="cx-warn"><?= icon('alert', 'icon') ?><p>These are tendencies, not laws. Risk sentiment, war, elections and surprises can override them on any day.</p></div>
</section>

<section class="cx-sec">
  <h3><span class="n">3</span> The releases to know</h3>
  <div class="cx-tw"><table class="cx-table">
    <thead><tr><th>Release</th><th>What it measures</th><th>When</th></tr></thead>
    <tbody>
      <tr><td><strong>Rate decision</strong> + press conference</td><td>The interest rate and the bank's outlook</td><td>About every 6–8 weeks per bank</td></tr>
      <tr><td><strong>CPI</strong> (inflation)</td><td>How fast prices are rising — drives rate expectations</td><td>Monthly</td></tr>
      <tr><td><strong>NFP</strong> (US jobs report)</td><td>US jobs added, unemployment, wages</td><td>Usually the first Friday of the month</td></tr>
      <tr><td><strong>GDP</strong></td><td>Size and growth of the economy</td><td>Quarterly</td></tr>
      <tr><td><strong>PMI</strong></td><td>Business surveys — an early read on the economy</td><td>Monthly</td></tr>
    </tbody></table></div>
  <p>Calendars rate events by impact (often colour-coded). Focus on the <strong>high-impact</strong> ones for the currencies you trade — for gold and US indices, that means US data.</p>
</section>

<section class="cx-sec">
  <h3><span class="n">4</span> Trading around news safely</h3>
  <div class="cx-cards">
    <div class="cx-stat"><b>Spreads</b><span>widen sharply just before and after big releases.</span></div>
    <div class="cx-stat"><b>Slippage</b><span>stops and orders can fill far from your price in a fast spike.</span></div>
    <div class="cx-stat"><b>Whipsaw</b><span>price often spikes one way, then the other, within minutes.</span></div>
  </div>
  <p>Three common approaches — pick one and write it in your plan:</p>
  <ol class="cx-rules">
    <li><strong>Stay flat:</strong> no new trades from about 15 minutes before to 15 minutes after a high-impact release. The simplest and the safest for beginners.</li>
    <li><strong>Reduce:</strong> close or shrink open trades before the release.</li>
    <li><strong>Trade the aftermath:</strong> wait for the first spike to settle, then trade your normal setup with the new levels.</li>
  </ol>
  <div class="cx-tip"><?= icon('check-circle', 'icon') ?><p>Make checking the calendar part of your pre-session routine. journzey's Markets tab shows the economic calendar.</p></div>
  <ul class="cx-take">
    <li><?= icon('check', 'icon icon-sm') ?>Surprises versus forecast move markets, not the headline itself.</li>
    <li><?= icon('check', 'icon icon-sm') ?>Rates and inflation drive currencies over time.</li>
    <li><?= icon('check', 'icon icon-sm') ?>Have a written news rule. “Stay flat” is a perfectly good one.</li>
  </ul>
</section>

<section class="cx-quiz" data-quiz>
  <h3><?= icon('sparkles', 'icon') ?> Quick check</h3>
  <p class="muted small">Pick an answer — you'll see why right away.</p>
  <div class="cx-q" data-a="c"><p>1. CPI comes out exactly as forecast. The usual reaction?</p>
    <label><input type="radio" name="q1" value="a"> A huge move, always</label><label><input type="radio" name="q1" value="b"> The market closes</label><label><input type="radio" name="q1" value="c"> Often small — the surprise is what moves price</label>
    <p class="why">Markets price in the forecast; reactions come from the gap between actual and forecast.</p></div>
  <div class="cx-q" data-a="a"><p>2. Markets start expecting higher rates in a country. Its currency usually…</p>
    <label><input type="radio" name="q2" value="a"> strengthens</label><label><input type="radio" name="q2" value="b"> weakens</label><label><input type="radio" name="q2" value="c"> is unaffected</label>
    <p class="why">Higher expected rates make holding the currency more attractive — a tendency, not a law.</p></div>
  <div class="cx-q" data-a="b"><p>3. Your stop is 10 pips away when NFP is released. The risk?</p>
    <label><input type="radio" name="q3" value="a"> None, stops are guaranteed</label><label><input type="radio" name="q3" value="b"> Slippage: it may fill much further away</label><label><input type="radio" name="q3" value="c"> The broker cancels it</label>
    <p class="why">In fast markets a stop becomes a market order and fills at the next available price.</p></div>
  <p class="cx-score" data-quiz-score aria-live="polite"></p>
</section>
