<?php /** Module 12 — Brokers, prop firms & staying safe */ ?>
<section class="cx-sec">
  <h3><span class="n">1</span> Choosing a broker</h3>
  <p class="cx-lead">Your broker holds your money. <strong>Regulation comes before low spreads or big bonuses.</strong></p>
  <div class="cx-cards">
    <div class="cx-card"><h4><?= icon('shield', 'icon icon-sm') ?> Regulated</h4><p>Check the licence number on the regulator's own website, e.g. FCA (UK), ASIC (Australia), CySEC (EU) or the regulator in your country.</p></div>
    <div class="cx-card"><h4><?= icon('scale', 'icon icon-sm') ?> Clear costs</h4><p>Compare spread + commission per round trip, swap rates and withdrawal fees — not just “zero spread” headlines.</p></div>
    <div class="cx-card"><h4><?= icon('download', 'icon icon-sm') ?> Easy withdrawals</h4><p>Test a small withdrawal early. Slow or blocked withdrawals are the biggest warning sign.</p></div>
  </div>
  <div class="cx-warn"><?= icon('alert', 'icon') ?><p><strong>Legal where you live:</strong> rules differ by country. In India, for example, the RBI has warned residents against unauthorised forex trading platforms; residents may trade permitted currency pairs only on recognised Indian exchanges. Check your local rules before opening any account.</p></div>
</section>

<section class="cx-sec">
  <h3><span class="n">2</span> How prop-firm challenges work</h3>
  <p>A proprietary trading (“prop”) firm offers you a funded account if you pass an evaluation that you pay a fee for. It's a <strong>rules test</strong> more than a profit test.</p>
  <div class="cx-tw"><table class="cx-table">
    <thead><tr><th>Rule</th><th>Typical meaning</th></tr></thead>
    <tbody>
      <tr><td><strong>Profit target</strong></td><td>Reach e.g. +8–10% to pass a phase</td></tr>
      <tr><td><strong>Daily loss limit</strong></td><td>Lose more than e.g. 4–5% in one day and the account fails</td></tr>
      <tr><td><strong>Max loss</strong></td><td>Overall limit, e.g. 8–10%. <em>Static</em> = fixed from the start; <em>trailing</em> = moves up with your highest balance</td></tr>
      <tr><td><strong>Minimum days / consistency</strong></td><td>Trade a minimum number of days; no single day may be too large a share of profit</td></tr>
    </tbody></table></div>
  <p>Exact numbers differ between firms and change over time — always read the current rules. journzey's prop-firm account presets track them live on your dashboard.</p>
  <div class="cx-tip"><?= icon('check-circle', 'icon') ?><p>With a 5% daily limit, risking 2% per trade means three losses end your day — or your account. Many traders use <strong>0.25–0.5% per trade</strong> in challenges and stop for the day well before the limit.</p></div>
</section>

<section class="cx-sec">
  <h3><span class="n">3</span> Scam red flags</h3>
  <div class="cx-tw"><table class="cx-table">
    <thead><tr><th>You see…</th><th>What it usually means</th></tr></thead>
    <tbody>
      <tr><td>“Guaranteed” or fixed monthly returns</td><td>Nobody can guarantee trading returns. Walk away.</td></tr>
      <tr><td>An “account manager” who trades for you</td><td>You lose control of your money; often unregulated.</td></tr>
      <tr><td>Paid signal groups showing only wins</td><td>Screenshots are easy to fake; ask for verified, complete records.</td></tr>
      <tr><td>Pressure to deposit more to “unlock” a withdrawal</td><td>A classic sign of fraud.</td></tr>
      <tr><td>“Recovery” services promising to get lost money back</td><td>Often a second scam targeting the same victims.</td></tr>
    </tbody></table></div>
  <figure class="cx-fig">
    <svg viewBox="0 0 640 210" role="img" aria-label="Red flags on the left, signs of a legitimate firm on the right">
      <text class="lbl-dn" x="20" y="24">RED FLAGS</text><text class="lbl-up" x="340" y="24">GREEN FLAGS</text>
      <?php foreach (['“Guaranteed 10% a week”', '“DM me / join my VIP group”', '“Send USDT to unlock withdrawals”', '“Only 3 slots left, decide now”'] as $i => $t): ?>
      <g transform="translate(20 <?= 40 + $i * 40 ?>)"><rect width="290" height="30" rx="9" class="zone-r"/><text class="tb" x="12" y="20"><?= $t ?></text></g>
      <?php endforeach; foreach (['Regulator licence you can look up', 'Clear fees and withdrawal rules', 'Talks about risk, never promises', 'Real support, real company address'] as $i => $t): ?>
      <g transform="translate(340 <?= 40 + $i * 40 ?>)"><rect width="280" height="30" rx="9" class="zone-s"/><text class="tb" x="12" y="20">✓ <?= $t ?></text></g>
      <?php endforeach; ?>
    </svg>
    <figcaption>If someone promises profit or asks you to send money to “unlock” money, walk away.</figcaption>
  </figure>
  <ul class="cx-take">
    <li><?= icon('check', 'icon icon-sm') ?>Regulated, legal where you live, and withdrawals tested early.</li>
    <li><?= icon('check', 'icon icon-sm') ?>Prop challenges: respect the daily and max loss above everything.</li>
    <li><?= icon('check', 'icon icon-sm') ?>Guaranteed returns and fake-looking P&amp;L are red flags.</li>
  </ul>
</section>

<section class="cx-quiz" data-quiz>
  <h3><?= icon('sparkles', 'icon') ?> Quick check</h3>
  <p class="muted small">Pick an answer — you'll see why right away.</p>

  <div class="cx-q" data-a="b"><p>1. What should you check first when choosing a broker?</p>
    <label><input type="radio" name="q1" value="a"> The size of the deposit bonus</label><label><input type="radio" name="q1" value="b"> Its regulation, verified on the regulator's website</label><label><input type="radio" name="q1" value="c"> The highest leverage offered</label>
    <p class="why">Regulation protects your money; bonuses and leverage don't.</p></div>
  <div class="cx-q" data-a="c"><p>2. A challenge has a 5% daily loss limit. Safest risk per trade?</p>
    <label><input type="radio" name="q2" value="a"> 2.5%</label><label><input type="radio" name="q2" value="b"> 5%</label><label><input type="radio" name="q2" value="c"> 0.25–0.5%</label>
    <p class="why">Small risk keeps a normal losing streak far away from the daily limit.</p></div>
  <div class="cx-q" data-a="a"><p>3. Someone promises 10% a month, guaranteed. You…</p>
    <label><input type="radio" name="q3" value="a"> walk away — returns can't be guaranteed</label><label><input type="radio" name="q3" value="b"> start small to test</label><label><input type="radio" name="q3" value="c"> ask for a bigger guarantee</label>
    <p class="why">“Guaranteed” returns are the number-one scam signal.</p></div>
  <p class="cx-score" data-quiz-score aria-live="polite"></p>
</section>
