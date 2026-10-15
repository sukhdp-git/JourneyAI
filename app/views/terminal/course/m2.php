<?php /** Module 2 — Market fundamentals */
// small helper for candles in diagrams: x, open, close, high, low (y in SVG units, smaller = higher price)
$c = function (float $x, float $o, float $cl, float $hi, float $lo, float $w = 18): string {
    $up = $cl < $o; $cls = $up ? 'up' : 'dn';
    return '<line class="' . $cls . ' wick" x1="' . $x . '" x2="' . $x . '" y1="' . $hi . '" y2="' . $lo . '"/><rect class="' . $cls . '" x="' . ($x - $w / 2) . '" y="' . min($o, $cl) . '" width="' . $w . '" height="' . max(2, abs($cl - $o)) . '" rx="2"/>';
}; ?>
<section class="cx-sec">
  <h3><span class="n">1</span> What you can trade</h3>
  <p class="cx-lead">Every market has a price that moves because buyers and sellers disagree. These are the five you will meet most often.</p>
  <div class="cx-cards">
    <div class="cx-card"><h4><?= icon('trend-up', 'icon icon-sm') ?> Stocks</h4><p>A share is a small piece of ownership in a company, bought and sold on an exchange (NSE, BSE, NYSE, Nasdaq…). Prices move on earnings, news, interest rates and the economy.</p><p><span class="tag">Exchange hours</span><span class="tag">Shares, futures, options, CFDs</span></p></div>
    <div class="cx-card"><h4><?= icon('bars', 'icon icon-sm') ?> Indices</h4><p>An index tracks a basket of stocks to measure a whole market: Nifty 50, S&amp;P 500 (US500), Nasdaq 100 (NAS100), Dow 30 (US30), DAX (GER40). You can't buy an index itself — you trade it through futures, options, ETFs or CFDs.</p><p><span class="tag">Measured in points</span></p></div>
    <div class="cx-card"><h4><?= icon('globe', 'icon icon-sm') ?> Forex</h4><p>Currencies trade in pairs. EUR/USD = 1.0850 means 1 euro costs 1.0850 US dollars. The first currency is the <em>base</em>, the second the <em>quote</em>. The global market runs about 24 hours, Monday to Friday.</p><p><span class="tag">1 pip = 0.0001 (0.01 on JPY pairs)</span><span class="tag">1 standard lot = 100,000 units</span></p></div>
    <div class="cx-card"><h4><?= icon('star', 'icon icon-sm') ?> Commodities</h4><p>Raw materials such as gold, silver, crude oil and natural gas. Gold (XAU/USD) is quoted in dollars per troy ounce; on most CFD platforms 1 lot = 100 ounces. Driven by supply and demand, the US dollar, interest rates and geopolitics.</p><p><span class="tag">Futures, CFDs, ETFs</span></p></div>
    <div class="cx-card"><h4><?= icon('activity', 'icon icon-sm') ?> Crypto</h4><p>Digital assets such as Bitcoin and Ethereum, traded 24/7 on crypto exchanges. Very volatile: double-digit moves in a day are common. Traded as spot coins or derivatives (futures, perpetuals).</p><p><span class="tag">24/7</span><span class="tag">High volatility</span></p></div>
  </div>
  <div class="cx-warn"><?= icon('alert', 'icon') ?><p>Rules differ by country. In India, for example, residents may trade currency derivatives only on recognised exchanges under RBI/SEBI rules. Always check what is legal where you live and use a regulated broker.</p></div>
</section>

<section class="cx-sec">
  <h3><span class="n">2</span> Price basics: bid, ask, spread and size</h3>
  <div class="cx-tw"><table class="cx-table">
    <thead><tr><th>Term</th><th>What it means</th><th>Example</th></tr></thead>
    <tbody>
      <tr><td><strong>Bid</strong></td><td>The price you can <strong>sell</strong> at right now</td><td>EUR/USD bid 1.0850</td></tr>
      <tr><td><strong>Ask</strong></td><td>The price you can <strong>buy</strong> at right now</td><td>EUR/USD ask 1.0851</td></tr>
      <tr><td><strong>Spread</strong></td><td>Ask − bid: a cost you pay on every trade</td><td>1 pip</td></tr>
      <tr><td><strong>Pip / point</strong></td><td>The standard unit of price movement</td><td>1.0850 → 1.0860 = 10 pips · Nasdaq 20,000 → 20,050 = 50 points</td></tr>
      <tr><td><strong>Lot</strong></td><td>The standard trade size</td><td>1.00 lot forex = 100,000 units · 0.10 = mini · 0.01 = micro</td></tr>
      <tr><td><strong>Leverage &amp; margin</strong></td><td>Margin is the deposit the broker holds; leverage is the position size it allows for that deposit</td><td>1:30 → $1,000 margin controls $30,000</td></tr>
    </tbody></table></div>
</section>

<section class="cx-sec">
  <h3><span class="n">3</span> Reading a candlestick</h3>
  <p>Each candle shows what price did during one period — one minute, one hour or one day, depending on the timeframe you choose. Four prices make one candle: <strong>open, high, low and close</strong>.</p>
  <figure class="cx-fig">
    <svg viewBox="0 0 640 250" role="img" aria-label="Anatomy of a bullish (green) and a bearish (red) candle">
      <line class="up wick" x1="190" x2="190" y1="30" y2="225"/><rect class="up" x="160" y="70" width="60" height="110" rx="4"/>
      <line class="dn wick" x1="450" x2="450" y1="30" y2="225"/><rect class="dn" x="420" y="70" width="60" height="110" rx="4"/>
      <g class="t"><text x="232" y="34">High</text><text x="232" y="75">Close</text><text x="232" y="183">Open</text><text x="232" y="228">Low</text>
      <text x="492" y="34">High</text><text x="492" y="75">Open</text><text x="492" y="183">Close</text><text x="492" y="228">Low</text></g>
      <text class="lbl-b" x="100" y="128" text-anchor="end">Body</text><line class="brand-s" x1="104" y1="124" x2="156" y2="124"/>
      <text class="lbl-b" x="100" y="52" text-anchor="end">Wick</text><line class="brand-s" x1="104" y1="48" x2="186" y2="48"/>
      <text class="tb" x="190" y="248" text-anchor="middle">Bullish — closed higher</text><text class="tb" x="450" y="248" text-anchor="middle">Bearish — closed lower</text>
    </svg>
    <figcaption>The body spans open → close; the wicks show the highest and lowest prices traded in the period.</figcaption>
  </figure>
  <p>Long wicks show <strong>rejection</strong>: price went there but could not stay. A big body shows <strong>conviction</strong>: one side controlled the whole period.</p>
  <div class="cx-figs">
    <figure class="cx-fig"><svg viewBox="0 0 200 150" role="img" aria-label="Doji"><?= $c(100, 75, 76, 15, 135, 34) ?></svg><figcaption><strong>Doji</strong> — open ≈ close. Indecision; matters most after a long move.</figcaption></figure>
    <figure class="cx-fig"><svg viewBox="0 0 200 150" role="img" aria-label="Hammer"><?= $c(100, 50, 32, 28, 135, 34) ?></svg><figcaption><strong>Hammer</strong> — long lower wick. Sellers pushed down, buyers pushed back.</figcaption></figure>
    <figure class="cx-fig"><svg viewBox="0 0 200 150" role="img" aria-label="Bullish engulfing"><?= $c(80, 50, 80, 42, 92, 26) ?><?= $c(122, 88, 36, 30, 96, 30) ?></svg><figcaption><strong>Bullish engulfing</strong> — a green body swallows the previous red one.</figcaption></figure>
    <figure class="cx-fig"><svg viewBox="0 0 200 150" role="img" aria-label="Shooting star"><?= $c(100, 98, 116, 15, 122, 34) ?></svg><figcaption><strong>Shooting star</strong> — long upper wick after a rise. Buyers were rejected.</figcaption></figure>
  </div>
  <div class="cx-key"><?= icon('info', 'icon') ?><p>No candle pattern works on its own. Patterns mean much more <strong>at a key level</strong> (support, resistance, a prior high or low) and in the direction of the bigger trend.</p></div>
</section>

<section class="cx-sec">
  <h3><span class="n">4</span> Market structure: trend or range?</h3>
  <p>Markets move in waves. Joining the swing highs and lows tells you who is in control.</p>
  <div class="cx-figs">
    <figure class="cx-fig"><svg viewBox="0 0 260 170" role="img" aria-label="Uptrend: higher highs and higher lows">
      <polyline class="ln" points="15,150 60,95 85,120 135,60 160,88 210,28 245,45"/>
      <g class="lbl-up"><text x="56" y="86">HH</text><text x="80" y="136">HL</text><text x="130" y="51">HH</text><text x="155" y="104">HL</text><text x="203" y="20">HH</text></g></svg>
      <figcaption><strong>Uptrend</strong> — higher highs (HH) and higher lows (HL). Look to buy pullbacks.</figcaption></figure>
    <figure class="cx-fig"><svg viewBox="0 0 260 170" role="img" aria-label="Downtrend: lower highs and lower lows">
      <polyline class="ln" points="15,20 60,75 85,50 135,110 160,82 210,142 245,125"/>
      <g class="lbl-dn"><text x="55" y="92">LL</text><text x="78" y="42">LH</text><text x="130" y="127">LL</text><text x="153" y="74">LH</text><text x="204" y="160">LL</text></g></svg>
      <figcaption><strong>Downtrend</strong> — lower highs (LH) and lower lows (LL). Look to sell rallies.</figcaption></figure>
    <figure class="cx-fig"><svg viewBox="0 0 260 170" role="img" aria-label="Range between support and resistance">
      <rect class="zone-r" x="10" y="26" width="240" height="18" rx="4"/><rect class="zone-s" x="10" y="122" width="240" height="18" rx="4"/>
      <polyline class="ln" points="15,128 50,36 85,130 125,34 160,128 200,38 240,124"/></svg>
      <figcaption><strong>Range</strong> — price bounces between a floor and a ceiling. Trade the edges, not the middle.</figcaption></figure>
  </div>
  <p>A <strong>break of structure</strong> happens when price makes a lower low in an uptrend (or a higher high in a downtrend). It is an early warning that control may be changing hands.</p>
</section>

<section class="cx-sec">
  <h3><span class="n">5</span> Support and resistance</h3>
  <p><strong>Support</strong> is an area where buying has stopped falls before; <strong>resistance</strong> is an area where selling has stopped rallies. They exist because traders remember prices — and place orders there.</p>
  <figure class="cx-fig">
    <svg viewBox="0 0 640 230" role="img" aria-label="Resistance breaks and then acts as support">
      <rect class="zone-r" x="20" y="88" width="300" height="22" rx="5"/><rect class="zone-s" x="320" y="88" width="300" height="22" rx="5"/>
      <polyline class="ln" points="25,190 80,96 120,160 175,94 215,150 265,92 300,130 345,60 385,96 430,92 470,40 515,98 560,60 615,24"/>
      <circle class="pt" cx="80" cy="96" r="5"/><circle class="pt" cx="175" cy="94" r="5"/><circle class="pt" cx="265" cy="92" r="5"/><circle class="pt" cx="385" cy="96" r="5"/><circle class="pt" cx="515" cy="98" r="5"/>
      <text class="lbl-dn" x="28" y="80">Resistance — rejected 3 times</text><text class="lbl-up" x="400" y="132">Old resistance becomes support</text>
    </svg>
    <figcaption>“Role reversal”: once a level breaks, it often flips — old resistance becomes new support.</figcaption>
  </figure>
  <ul>
    <li>Draw <strong>zones, not thin lines</strong> — price rarely turns at an exact number.</li>
    <li>The more clean touches, and the higher the timeframe, the more traders are watching it.</li>
    <li>Round numbers (2,700 on gold, 1.1000 on EUR/USD, 20,000 on Nasdaq) often act as levels.</li>
    <li>Put stops <strong>beyond</strong> the zone, not exactly on it.</li>
  </ul>
</section>

<section class="cx-sec">
  <h3><span class="n">6</span> Order types — how you actually get in and out</h3>
  <div class="cx-tw"><table class="cx-table">
    <thead><tr><th>Order</th><th>What it does</th><th>Use it when</th></tr></thead>
    <tbody>
      <tr><td><strong>Market</strong></td><td>Fills immediately at the best available price (may differ slightly from what you see — “slippage”)</td><td>You need in or out now</td></tr>
      <tr><td><strong>Buy limit</strong></td><td>Buy only at your price or lower — placed <em>below</em> the current price</td><td>Buying a pullback into support</td></tr>
      <tr><td><strong>Sell limit</strong></td><td>Sell only at your price or higher — placed <em>above</em> the current price</td><td>Selling a rally into resistance</td></tr>
      <tr><td><strong>Buy stop</strong></td><td>Becomes a buy once price rises to your level — placed <em>above</em></td><td>Joining a breakout</td></tr>
      <tr><td><strong>Sell stop</strong></td><td>Becomes a sell once price falls to your level — placed <em>below</em></td><td>Joining a breakdown</td></tr>
      <tr><td><strong>Stop loss</strong></td><td>Closes a losing trade at a set price</td><td>Every trade, before you enter</td></tr>
      <tr><td><strong>Take profit</strong></td><td>Closes a winning trade at your target</td><td>Locking in a planned reward</td></tr>
      <tr><td><strong>Trailing stop</strong></td><td>A stop that follows price by a set distance</td><td>Letting a winner run while protecting profit</td></tr>
    </tbody></table></div>
  <figure class="cx-fig">
    <svg viewBox="0 0 640 230" role="img" aria-label="Where each pending order sits relative to the current price">
      <line class="ax" x1="40" y1="115" x2="600" y2="115"/><text class="tb" x="44" y="108">Current price</text>
      <rect class="upo" x="120" y="30" width="180" height="32" rx="8"/><text class="lbl-up" x="210" y="51" text-anchor="middle">Buy STOP · breakout</text>
      <rect class="dno" x="340" y="30" width="180" height="32" rx="8"/><text class="lbl-dn" x="430" y="51" text-anchor="middle">Sell LIMIT · fade a rally</text>
      <rect class="upo" x="120" y="168" width="180" height="32" rx="8"/><text class="lbl-up" x="210" y="189" text-anchor="middle">Buy LIMIT · pullback</text>
      <rect class="dno" x="340" y="168" width="180" height="32" rx="8"/><text class="lbl-dn" x="430" y="189" text-anchor="middle">Sell STOP · breakdown</text>
      <text class="t" x="610" y="50" text-anchor="end">above</text><text class="t" x="610" y="189" text-anchor="end">below</text>
    </svg>
    <figcaption>Limit orders wait for a better price; stop orders wait for confirmation that price is moving.</figcaption>
  </figure>
</section>

<section class="cx-sec">
  <h3><span class="n">7</span> When markets move: sessions and timeframes</h3>
  <div class="cx-cards">
    <div class="cx-card"><h4><?= icon('clock', 'icon icon-sm') ?> Asian session</h4><p>Often quieter for EUR and GBP pairs and gold; JPY and AUD are more active. Ranges frequently form here.</p></div>
    <div class="cx-card"><h4><?= icon('clock', 'icon icon-sm') ?> London session</h4><p>The largest forex volume; Asian ranges are often broken at the London open.</p></div>
    <div class="cx-card"><h4><?= icon('clock', 'icon icon-sm') ?> New York session</h4><p>US stocks and indices open; major US data comes out. The London–New York overlap is usually the most liquid period.</p></div>
  </div>
  <p><strong>Top-down analysis:</strong> decide direction on a higher timeframe (daily or 4-hour), find your levels on a middle one (1-hour), and time entries on a lower one (15- or 5-minute). Your Home Hub shows which sessions are open right now in your own timezone.</p>
</section>

<section class="cx-sec">
  <h3><span class="n">★</span> Key takeaways</h3>
  <ul class="cx-take">
    <li><?= icon('check', 'icon icon-sm') ?>Stocks, indices, forex, commodities and crypto all move on supply and demand — but have different hours, units and volatility.</li>
    <li><?= icon('check', 'icon icon-sm') ?>A candle shows open, high, low and close; wicks show rejection, bodies show conviction.</li>
    <li><?= icon('check', 'icon icon-sm') ?>Higher highs and lows = uptrend; lower highs and lows = downtrend; otherwise it's a range.</li>
    <li><?= icon('check', 'icon icon-sm') ?>Support and resistance are zones; broken levels often flip roles.</li>
    <li><?= icon('check', 'icon icon-sm') ?>Limits wait for a better price, stops wait for momentum — and every trade needs a stop loss.</li>
  </ul>
</section>

<section class="cx-quiz" data-quiz>
  <h3><?= icon('sparkles', 'icon') ?> Quick check</h3>
  <div class="cx-q" data-a="b"><p>1. EUR/USD moves from 1.0850 to 1.0870. How many pips is that?</p>
    <label><input type="radio" name="q1" value="a"> 2 pips</label><label><input type="radio" name="q1" value="b"> 20 pips</label><label><input type="radio" name="q1" value="c"> 200 pips</label>
    <p class="why">One pip is 0.0001, so 0.0020 = 20 pips.</p></div>
  <div class="cx-q" data-a="a"><p>2. You want to buy gold if it pulls back down to support below the current price. Which order?</p>
    <label><input type="radio" name="q2" value="a"> Buy limit</label><label><input type="radio" name="q2" value="b"> Buy stop</label><label><input type="radio" name="q2" value="c"> Sell stop</label>
    <p class="why">A buy limit waits below the market to buy at your price or better.</p></div>
  <div class="cx-q" data-a="c"><p>3. Price makes higher highs and higher lows. What is the structure?</p>
    <label><input type="radio" name="q3" value="a"> Downtrend</label><label><input type="radio" name="q3" value="b"> Range</label><label><input type="radio" name="q3" value="c"> Uptrend</label>
    <p class="why">HH + HL means buyers keep winning the swings.</p></div>
  <div class="cx-q" data-a="b"><p>4. A candle has a tiny body and a very long lower wick at support. What does it suggest?</p>
    <label><input type="radio" name="q4" value="a"> Sellers are in full control</label><label><input type="radio" name="q4" value="b"> Lower prices were rejected</label><label><input type="radio" name="q4" value="c"> Nothing at all</label>
    <p class="why">The long lower wick shows sellers pushed down but buyers pushed price back up — a possible hammer at a key level.</p></div>
  <p class="cx-score" data-quiz-score aria-live="polite"></p>
</section>
