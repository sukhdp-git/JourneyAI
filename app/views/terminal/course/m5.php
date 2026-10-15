<?php /** Module 5 — Habits of successful traders */ ?>
<section class="cx-sec">
  <h3><span class="n">1</span> Process beats prediction</h3>
  <p class="cx-lead">Nobody can predict every trade. Consistent traders don't try — they build a process they repeat every day: <strong>test → plan → execute → journal → review</strong>.</p>
  <figure class="cx-fig">
    <svg viewBox="0 0 640 150" role="img" aria-label="The loop: backtest, plan, execute, journal, review, then back to backtest">
      <?php $loop = [['Backtest', 'prove the edge'], ['Plan', 'write the rules'], ['Execute', 'follow them'], ['Journal', 'log every trade'], ['Review', 'weekly, with data']]; foreach ($loop as $i => [$a, $b]): $x = 70 + $i * 125; ?>
      <?php if ($i < 4): ?><line class="ln" x1="<?= $x + 30 ?>" y1="60" x2="<?= $x + 95 ?>" y2="60"/><?php endif; ?>
      <circle class="pt" cx="<?= $x ?>" cy="60" r="26"/>
      <text class="lbl-b" x="<?= $x ?>" y="65" text-anchor="middle"><?= $i + 1 ?></text>
      <text class="tb" x="<?= $x ?>" y="108" text-anchor="middle"><?= $a ?></text>
      <text class="ts" x="<?= $x ?>" y="124" text-anchor="middle"><?= $b ?></text>
      <?php endforeach; ?>
      <path class="ln-m" d="M570 90 Q570 142 320 142 Q70 142 70 90" fill="none"/>
    </svg>
    <figcaption>The improvement loop. Each week's review feeds back into the plan.</figcaption>
  </figure>
  <div class="cx-cards">
    <div class="cx-card"><h4><?= icon('target', 'icon icon-sm') ?> Outcome goal</h4><p>“Make $1,000 this month.” You don't control it — the market does. Chasing it leads to forcing trades.</p></div>
    <div class="cx-card"><h4><?= icon('check-circle', 'icon icon-sm') ?> Process goal</h4><p>“Follow my plan on every trade and journal all of them this month.” Fully in your control, and it is what produces results over time.</p></div>
  </div>
</section>

<section class="cx-sec">
  <h3><span class="n">2</span> Backtest before you risk real money</h3>
  <p>Backtesting means going back on the chart and applying your exact rules to past price action, trade by trade, to see how they would have performed.</p>
  <ol class="cx-take" style="list-style:none">
    <li><?= icon('edit', 'icon icon-sm') ?><span><strong>Write the rules first</strong> — market, timeframe, entry trigger, stop, target, and when <em>not</em> to trade. If a rule needs judgement, make it more precise.</span></li>
    <li><?= icon('bars', 'icon icon-sm') ?><span><strong>Collect a decent sample</strong> — 50 to 100+ trades across different conditions (trending, ranging, quiet, news weeks). 10 trades proves nothing.</span></li>
    <li><?= icon('journal', 'icon icon-sm') ?><span><strong>Record every result in R</strong> — win, loss, R:R, time, and a screenshot. Count the ugly losses too.</span></li>
    <li><?= icon('scale', 'icon icon-sm') ?><span><strong>Calculate</strong> win rate, average R, expectancy (Module 3) and the worst losing streak.</span></li>
    <li><?= icon('alert', 'icon icon-sm') ?><span><strong>Don't curve-fit</strong> — tweaking rules until the past looks perfect usually fails on new data. Keep rules simple.</span></li>
    <li><?= icon('shield', 'icon icon-sm') ?><span><strong>Forward-test</strong> on a demo or with very small risk for a few weeks before full size. Live execution is always harder than backtest.</span></li>
  </ol>
  <div class="cx-tip"><?= icon('layers', 'icon') ?><p>In journzey, create the setup in <a href="<?= e(url('/terminal/strategies')) ?>">Strategies</a> and link each trade to it — Strategy Analysis then shows that setup's win rate and expectancy automatically.</p></div>
</section>

<section class="cx-sec">
  <h3><span class="n">3</span> Build your trading plan</h3>
  <p>A trading plan is a one-page document that answers every decision <em>before</em> the market opens. Fill this in — it saves in your browser only — then copy it into your Notepad.</p>
  <div class="cx-calc" data-plan>
    <h4><?= icon('file', 'icon') ?> My trading plan</h4>
    <div class="grid-form">
      <div class="f"><label for="pl-mk">Markets I trade</label><input id="pl-mk" data-plan-f="Markets" placeholder="e.g. XAU/USD, NAS100"></div>
      <div class="f"><label for="pl-tm">Sessions / hours</label><input id="pl-tm" data-plan-f="Hours" placeholder="e.g. London open, 13:00–16:00"></div>
      <div class="f"><label for="pl-tf">Timeframes</label><input id="pl-tf" data-plan-f="Timeframes" placeholder="e.g. H1 bias, M15 entry"></div>
      <div class="f"><label for="pl-rk">Risk per trade</label><input id="pl-rk" data-plan-f="Risk per trade" placeholder="e.g. 1% of balance"></div>
      <div class="f"><label for="pl-dl">Daily loss limit</label><input id="pl-dl" data-plan-f="Daily loss limit" placeholder="e.g. −3% or 3 losses"></div>
      <div class="f"><label for="pl-mx">Max trades per day</label><input id="pl-mx" data-plan-f="Max trades per day" placeholder="e.g. 3"></div>
    </div>
    <div class="grid-form" style="grid-template-columns:1fr;margin-top:10px">
      <div class="f"><label for="pl-se">Setup &amp; entry rules</label><textarea id="pl-se" rows="3" data-plan-f="Setup & entry" placeholder="e.g. Uptrend on H1 (higher highs/lows), pullback to support, bullish engulfing on M15"></textarea></div>
      <div class="f"><label for="pl-ex">Stop, target &amp; exit rules</label><textarea id="pl-ex" rows="2" data-plan-f="Stop, target & exit" placeholder="e.g. Stop below the swing low; target at least 1:2; never widen the stop"></textarea></div>
      <div class="f"><label for="pl-no">I do NOT trade when…</label><textarea id="pl-no" rows="2" data-plan-f="No-trade conditions" placeholder="e.g. 15 min before high-impact news, after hitting my daily limit, when tired or angry"></textarea></div>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:12px">
      <button type="button" class="tm-btn tm-btn-sm tm-btn-primary" data-plan-copy><?= icon('copy', 'icon icon-sm') ?> Copy my plan</button>
      <a class="tm-btn tm-btn-sm" href="<?= e(url('/terminal/notepad')) ?>"><?= icon('edit', 'icon icon-sm') ?> Open Notepad</a>
      <span class="small muted" data-plan-msg aria-live="polite" style="align-self:center"></span>
    </div>
  </div>
</section>

<section class="cx-sec">
  <h3><span class="n">4</span> Track every trade in journzey</h3>
  <p>A journal only works if it is complete. Log <strong>every</strong> trade — wins, losses and the ones you're embarrassed about. It takes about 10 seconds with voice.</p>
  <div class="cx-cards">
    <div class="cx-card"><h4><?= icon('mic', 'icon icon-sm') ?> The numbers</h4><p>Symbol, direction, entry, exit, lot size, stop and target. Say it: <em>“Buy gold at 2650, exit 2660, 0.15 lots”</em> and journzey fills it in.</p></div>
    <div class="cx-card"><h4><?= icon('camera', 'icon icon-sm') ?> The chart</h4><p>Attach a screenshot of the entry. In a month, your screenshots show exactly what your best and worst setups look like.</p></div>
    <div class="cx-card"><h4><?= icon('heart', 'icon icon-sm') ?> The emotion</h4><p>Tag how you felt. Patterns like “every FOMO trade lost” only appear when you record them.</p></div>
    <div class="cx-card"><h4><?= icon('check', 'icon icon-sm') ?> Did you follow the plan?</h4><p>Mark the strategy and note any rule you broke. A losing trade that followed the plan is a good trade; a winner that broke it is a warning.</p></div>
  </div>
</section>

<section class="cx-sec">
  <h3><span class="n">5</span> Your daily and weekly routine</h3>
  <figure class="cx-fig">
    <svg viewBox="0 0 640 170" role="img" aria-label="Daily routine timeline: before market, during, after market, and weekly review">
      <line class="ax" x1="40" y1="70" x2="600" y2="70"/>
      <?php $day = [['Before', 'news, levels, plan'], ['During', 'only plan setups'], ['After', 'journal + screenshot'], ['Weekly', 'review the data']]; foreach ($day as $i => [$a, $b]): $x = 90 + $i * 155; ?>
      <circle class="pt" cx="<?= $x ?>" cy="70" r="9"/>
      <text class="lbl-b" x="<?= $x ?>" y="46" text-anchor="middle"><?= $a ?></text>
      <text class="tb" x="<?= $x ?>" y="100" text-anchor="middle"><?= $b ?></text>
      <?php endforeach; ?>
      <rect class="brand" x="40" y="128" width="560" height="6" rx="3"/>
      <text class="ts" x="320" y="156" text-anchor="middle">Same routine, every trading day</text>
    </svg>
  </figure>
  <div class="cx-cards">
    <div class="cx-card"><h4><span class="tag">Before</span> 10–15 min</h4><p>Check the economic calendar, mark key support/resistance, note the trend on your higher timeframe, and confirm your risk for the day.</p></div>
    <div class="cx-card"><h4><span class="tag">During</span></h4><p>Trade only setups from your plan. Size every trade with the calculator. Respect the daily limit.</p></div>
    <div class="cx-card"><h4><span class="tag">After</span> 5 min</h4><p>Log any trade not logged yet, add screenshots and emotions, and write one line: what did I do well, what will I fix?</p></div>
    <div class="cx-card"><h4><span class="tag">Weekly</span> 30 min</h4><p>Open the <a href="<?= e(url('/terminal/dashboard')) ?>">Dashboard</a>, <a href="<?= e(url('/terminal/calendar')) ?>">Calendar</a> and <a href="<?= e(url('/terminal/edge')) ?>">Edge Matrix</a>: best/worst setups, hours and emotions. Change <strong>one</strong> thing for next week.</p></div>
  </div>
  <h4 style="margin:22px 0 8px">Your weekly review questions</h4>
  <div class="cx-tw"><table class="cx-table">
    <thead><tr><th>Question</th><th>Where to find it in journzey</th></tr></thead>
    <tbody>
      <tr><td>Did I follow my risk rules on every trade?</td><td>Trade Log · risk column, Dashboard</td></tr>
      <tr><td>Which setup made money, which lost?</td><td>Strategies · Strategy Analysis</td></tr>
      <tr><td>What time of day / day of week works best?</td><td>Edge Matrix</td></tr>
      <tr><td>Which emotions came with my losses?</td><td>Edge Matrix · emotion tags</td></tr>
      <tr><td>How did the week look overall?</td><td>Calendar · weekly totals, Trade Log · weekly card</td></tr>
    </tbody></table></div>
</section>

<section class="cx-sec">
  <h3><span class="n">6</span> Your first 30 days</h3>
  <div class="cx-cards">
    <div class="cx-card"><h4><span class="tag">Week 1</span> Learn &amp; plan</h4><p>Pick one market and one setup. Write your trading plan above. Set risk rules and the Tilt Circuit Breaker in Settings.</p></div>
    <div class="cx-card"><h4><span class="tag">Week 2</span> Backtest</h4><p>Backtest 50+ examples of your setup. Record them as a strategy and calculate expectancy.</p></div>
    <div class="cx-card"><h4><span class="tag">Week 3</span> Demo / small size</h4><p>Trade the plan on a demo account or at 0.25–0.5% risk. Log 100% of trades with voice.</p></div>
    <div class="cx-card"><h4><span class="tag">Week 4</span> Review</h4><p>Do a full weekly review. Keep what worked, fix one leak, and only then consider normal risk.</p></div>
  </div>
  <div class="cx-cta cx-ready">
    <div><h4>Ready to start?</h4><p class="muted small">Put the course into practice with your first journaled trade.</p></div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <a class="tm-btn tm-btn-primary" href="<?= e(url('/terminal/trades/new')) ?>"><?= icon('plus', 'icon icon-sm') ?> Log a trade</a>
      <a class="tm-btn" href="<?= e(url('/terminal/strategies/new')) ?>"><?= icon('layers', 'icon icon-sm') ?> Create a strategy</a>
    </div>
  </div>
</section>

<section class="cx-sec">
  <h3><span class="n">★</span> Key takeaways</h3>
  <ul class="cx-take">
    <li><?= icon('check', 'icon icon-sm') ?>Set process goals you control: follow the plan and journal every trade.</li>
    <li><?= icon('check', 'icon icon-sm') ?>Backtest 50–100+ trades, then forward-test small before trading full size.</li>
    <li><?= icon('check', 'icon icon-sm') ?>A written plan answers every decision before the market opens.</li>
    <li><?= icon('check', 'icon icon-sm') ?>Review weekly with data and change one thing at a time.</li>
  </ul>
  <p class="small muted">Educational content only — not financial advice. Trading involves substantial risk of loss; past or backtested results do not guarantee future results.</p>
</section>

<section class="cx-quiz" data-quiz>
  <h3><?= icon('sparkles', 'icon') ?> Final check</h3>
  <p class="muted small">Pick an answer — you'll see why right away.</p>
  <div class="cx-q" data-a="c"><p>1. How many trades should a backtest include before you trust it?</p>
    <label><input type="radio" name="q1" value="a"> 5–10</label><label><input type="radio" name="q1" value="b"> About 20</label><label><input type="radio" name="q1" value="c"> 50–100 or more</label>
    <p class="why">Small samples are mostly luck. 50–100+ trades across different conditions give a much fairer picture.</p></div>
  <div class="cx-q" data-a="b"><p>2. Which of these is a process goal?</p>
    <label><input type="radio" name="q2" value="a"> Make 10% this month</label><label><input type="radio" name="q2" value="b"> Journal every trade and follow my stop rules</label><label><input type="radio" name="q2" value="c"> Win 8 trades this week</label>
    <p class="why">Process goals are fully in your control. Profit and win counts depend on the market.</p></div>
  <div class="cx-q" data-a="a"><p>3. A trade followed your plan but hit the stop. How should you see it?</p>
    <label><input type="radio" name="q3" value="a"> A good trade with a normal loss</label><label><input type="radio" name="q3" value="b"> A mistake — change the strategy</label><label><input type="radio" name="q3" value="c"> A reason to win it back now</label>
    <p class="why">Losses are part of every edge. Judge trades by whether you followed the plan, and judge the strategy over many trades.</p></div>
  <p class="cx-score" data-quiz-score aria-live="polite"></p>
</section>
