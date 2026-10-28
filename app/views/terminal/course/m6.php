<?php /** Module 6 — How markets move: sessions, spreads & leverage */
$sess = [['Sydney', 22, 31, '#b17ace'], ['Tokyo', 24, 33, '#d996c1'], ['London', 32, 41, '#8a5ddc'], ['New York', 37, 46, '#6541e9']]; // hours on a 48h strip (24 = 00:00 UTC)
?>
<section class="cx-sec">
  <h3><span class="n">1</span> The market has a daily rhythm</h3>
  <p class="cx-lead">Forex trades 24 hours a day, five days a week, passing from one financial centre to the next. <strong>How much it moves depends on who is awake.</strong></p>
  <figure class="cx-fig">
    <svg viewBox="0 0 640 200" role="img" aria-label="Trading sessions in UTC: Sydney 22–07, Tokyo 00–09, London 08–17, New York 13–22; London and New York overlap 13–17">
      <?php foreach ($sess as $i => [$name, $a, $b, $col]): $y = 18 + $i * 38; ?>
      <text class="tb" x="8" y="<?= $y + 20 ?>"><?= $name ?></text>
      <rect x="<?= 110 + ($a - 22) * 20 ?>" y="<?= $y ?>" width="<?= ($b - $a) * 20 ?>" height="28" rx="8" fill="<?= $col ?>" opacity=".85"/>
      <?php endforeach; ?>
      <rect class="zone-r" x="<?= 110 + (37 - 22) * 20 ?>" y="10" width="<?= 4 * 20 ?>" height="160" rx="8"/>
      <text class="lbl-dn" x="<?= 110 + (39 - 22) * 20 ?>" y="186" text-anchor="middle">Overlap: busiest hours</text>
      <?php foreach ([22, 26, 30, 34, 38, 42, 46] as $h): ?><text class="ts" x="<?= 110 + ($h - 22) * 20 ?>" y="198" text-anchor="middle"><?= sprintf('%02d', $h % 24) ?></text><?php endforeach; ?>
    </svg>
    <figcaption>Session hours in UTC (winter). London and New York open one hour earlier in UTC during their summer time. In India add 5:30: the overlap is about 18:30–22:30 IST.</figcaption>
  </figure>
  <div class="cx-cards">
    <div class="cx-card"><h4><?= icon('globe', 'icon icon-sm') ?> Asian session</h4><p>Usually calmer. Ranges are tighter; JPY, AUD and NZD pairs move the most.</p></div>
    <div class="cx-card"><h4><?= icon('clock', 'icon icon-sm') ?> London open</h4><p>Volume arrives fast. Many of the day's big moves in EUR, GBP and gold start here.</p></div>
    <div class="cx-card"><h4><?= icon('zap', 'icon icon-sm') ?> New York + overlap</h4><p>US data at the open, the most liquid hours of the day. Biggest moves, tightest spreads.</p></div>
  </div>
  <div class="cx-tip"><?= icon('check-circle', 'icon') ?><p>Pick <strong>one or two sessions</strong> that fit your life and your instrument, and trade only those. Your journal will later show which one actually pays you.</p></div>
</section>

<section class="cx-sec">
  <h3><span class="n">2</span> Pips, points and what one move is worth</h3>
  <p>A <strong>pip</strong> is the standard small step of a currency price: 0.0001 for most pairs and 0.01 for JPY pairs. A <strong>lot</strong> is the trade size. What a move is worth depends on both.</p>
  <div class="cx-tw"><table class="cx-table">
    <thead><tr><th>Instrument</th><th>1 standard lot</th><th class="r">Value of the move</th></tr></thead>
    <tbody>
      <tr><td><strong>EUR/USD</strong> (and other …/USD pairs)</td><td>100,000 units</td><td class="r">≈ $10 per pip</td></tr>
      <tr><td><strong>USD/JPY</strong></td><td>100,000 units</td><td class="r">≈ ¥1,000 per pip (≈ $6–7, varies with the rate)</td></tr>
      <tr><td><strong>Gold XAU/USD</strong></td><td>100 oz</td><td class="r">$100 per $1 move</td></tr>
      <tr><td><strong>Indices</strong> (US30, NAS100…)</td><td>set by your broker</td><td class="r">often $1 per point per lot — check the contract</td></tr>
    </tbody></table></div>
  <div class="cx-warn"><?= icon('alert', 'icon') ?><p>Contract sizes differ between brokers, especially for indices and crypto. Always check the <strong>contract specification</strong> before you size a trade. journzey's Risk Calculator uses each instrument's specs for you.</p></div>
</section>

<section class="cx-sec">
  <h3><span class="n">3</span> What every trade costs you</h3>
  <div class="cx-cards">
    <div class="cx-stat"><b>Spread</b><span>The gap between the buy (ask) and sell (bid) price. You pay it on every trade, the moment you enter.</span></div>
    <div class="cx-stat"><b>Commission</b><span>A fixed fee per lot on “raw spread” accounts. Standard accounts usually build it into a wider spread instead.</span></div>
    <div class="cx-stat"><b>Swap</b><span>An overnight financing charge (or credit) for positions held past the daily rollover — often tripled once a week.</span></div>
    <div class="cx-stat"><b>Slippage</b><span>Getting filled at a worse price than you asked for, usually in fast markets and around news.</span></div>
  </div>
  <p>Costs look tiny per trade, but they add up. A trader paying about $7 in total costs per round trip, taking 5 trades a day, pays around <strong>$35 a day</strong> — roughly <strong>$700 a month</strong> over 20 trading days — before making a cent. That's why overtrading is expensive even when you break even on price.</p>
  <div class="cx-tip"><?= icon('check-circle', 'icon') ?><p>Spreads are widest around the daily rollover (late New York evening) and during big news. If your setup is small, those hours can eat it completely.</p></div>
</section>

<section class="cx-sec">
  <h3><span class="n">4</span> Leverage and margin, without the myths</h3>
  <p class="cx-lead"><strong>Leverage</strong> lets you control a big position with a small deposit called <strong>margin</strong>. It changes how much money is locked up — <strong>not</strong> how much you can lose. Your lot size and your stop decide that.</p>
  <div class="cx-key"><?= icon('scale', 'icon') ?><p><strong>Margin = Position value ÷ Leverage</strong><br><span class="small">1 lot of EUR/USD at 1.0800 = $108,000 position. At 1:100 leverage the margin is $1,080; at 1:500 it is $216. Either way, a 20-pip loss on 1 lot costs about $200.</span></p></div>
  <div class="cx-tw"><table class="cx-table">
    <thead><tr><th>Term</th><th>What it means</th></tr></thead>
    <tbody>
      <tr><td><strong>Equity</strong></td><td>Balance plus or minus your open profit/loss.</td></tr>
      <tr><td><strong>Free margin</strong></td><td>Equity minus the margin already used — what's left for new trades.</td></tr>
      <tr><td><strong>Margin level</strong></td><td>Equity ÷ used margin × 100%.</td></tr>
      <tr><td><strong>Margin call / stop-out</strong></td><td>When margin level falls to the broker's limits, you get a warning, then the broker starts closing your positions automatically. The levels differ by broker — look them up.</td></tr>
    </tbody></table></div>
  <div class="cx-warn"><?= icon('alert', 'icon') ?><p>A stop-out is the market closing your trade for you at the worst moment. If you size every trade from your stop (Module 3), you should never get close to one.</p></div>
</section>

<section class="cx-sec">
  <h3><span class="n">5</span> Gaps and weekends</h3>
  <p>Markets close for the weekend, but news doesn't. On Monday, price can <strong>open far away</strong> from Friday's close — a gap. A stop loss can't protect you inside a gap: it fills at the first available price, which may be much worse.</p>
  <div class="cx-tip"><?= icon('check-circle', 'icon') ?><p>Holding over the weekend is a decision, not a default. If you do, size smaller and know your worst case.</p></div>
  <ul class="cx-take">
    <li><?= icon('check', 'icon icon-sm') ?>Trade the session that fits your instrument and your life.</li>
    <li><?= icon('check', 'icon icon-sm') ?>Know your costs: spread, commission, swap and slippage.</li>
    <li><?= icon('check', 'icon icon-sm') ?>Leverage changes margin, not risk. Your stop and size decide the risk.</li>
  </ul>
</section>

<section class="cx-quiz" data-quiz>
  <h3><?= icon('sparkles', 'icon') ?> Quick check</h3>
  <p class="muted small">Pick an answer — you'll see why right away.</p>
  <div class="cx-q" data-a="c"><p>1. When are forex markets usually the most liquid?</p>
    <label><input type="radio" name="q1" value="a"> Late New York evening</label><label><input type="radio" name="q1" value="b"> Sunday open</label><label><input type="radio" name="q1" value="c"> The London–New York overlap</label>
    <p class="why">Both big centres are open at once, so volume is highest and spreads are usually tightest.</p></div>
  <div class="cx-q" data-a="a"><p>2. You switch from 1:100 to 1:500 leverage and keep the same lot size and stop. Your loss if the stop is hit…</p>
    <label><input type="radio" name="q2" value="a"> stays the same</label><label><input type="radio" name="q2" value="b"> becomes five times smaller</label><label><input type="radio" name="q2" value="c"> becomes five times bigger</label>
    <p class="why">Leverage only changes the margin locked up. Lot size × stop distance decides the loss.</p></div>
  <div class="cx-q" data-a="b"><p>3. What is swap?</p>
    <label><input type="radio" name="q3" value="a"> The gap between bid and ask</label><label><input type="radio" name="q3" value="b"> An overnight financing charge or credit</label><label><input type="radio" name="q3" value="c"> A fee for closing a trade early</label>
    <p class="why">Swap applies to positions held past the daily rollover.</p></div>
  <p class="cx-score" data-quiz-score aria-live="polite"></p>
</section>
