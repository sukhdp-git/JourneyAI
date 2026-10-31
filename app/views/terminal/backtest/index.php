<?php
use App\Trading\Backtest;
use App\Trading\BacktestData;
/** Backtester. @var array $data @var array $runs @var int $used @var int $limit @var bool $live */
$cfg = [
    'operands' => Backtest::OPERANDS, 'ops' => Backtest::OPS, 'patterns' => Backtest::PATTERNS, 'sessions' => Backtest::SESSIONS,
    'tfs' => array_map(fn ($s) => $s, Backtest::TIMEFRAMES), 'years' => BacktestData::YEARS,
    'urls' => ['run' => url('/terminal/backtest/run'), 'fetch' => url('/terminal/backtest/fetch'), 'upload' => url('/terminal/backtest/upload'), 'runs' => url('/terminal/backtest/runs'), 'datasets' => url('/terminal/backtest/datasets')],
];
$dsLabel = function (array $d): string {
    if (empty($d['first_ts'])) {
        return 'not loaded yet';
    }
    return number_format((int) ($d['rows_count'] ?? 0)) . ' candles · ' . gmdate('M Y', (int) $d['first_ts']) . ' – ' . gmdate('M Y', (int) $d['last_ts']);
};
$tfName = array_flip(Backtest::TIMEFRAMES);
?>
<div class="bt" data-bt data-cfg="<?= e(json_encode($cfg)) ?>">
  <section class="bt-hero">
    <div>
      <span class="bt-kicker"><?= icon('activity', 'icon icon-xs') ?> Backtester</span>
      <h2>Test your rules on <em>2 years of real candles.</em></h2>
      <p class="muted">Build the strategy from rules, pick the market, and see every trade it would have taken: win rate, R, drawdown, the worst streak and the months that hurt.</p>
    </div>
    <div class="bt-quota"><b data-bt-used><?= $used ?></b><span>of <?= $limit ?> tests used this month</span><div class="bar"><span style="width:<?= min(100, round($used / max(1, $limit) * 100)) ?>%" data-bt-usedbar></span></div></div>
  </section>

  <div class="bt-grid">
    <div class="bt-build">
      <!-- 1 · DATA -->
      <section class="panel bt-step">
        <div class="panel-head"><h2><span class="bt-n">1</span> Price history</h2></div>
        <div class="bt-tabs" role="tablist">
          <button type="button" role="tab" class="on" data-bt-tab="market" aria-selected="true">Market data</button>
          <button type="button" role="tab" data-bt-tab="upload" aria-selected="false">Your CSV <?= $data['own'] ? '(' . count($data['own']) . ')' : '' ?></button>
        </div>
        <div data-bt-pane="market">
          <?php if (!$data['shared']): ?>
          <p class="bt-note"><?= icon('info', 'icon icon-xs') ?> Market data is not connected yet. Upload your own candles in the <b>Your CSV</b> tab (export from MT5 or TradingView).</p>
          <?php else: ?>
          <div class="bt-ds-list">
            <?php foreach ($data['shared'] as $d): $ready = $d['status'] === 'ready' && (int) $d['rows_count']; ?>
            <label class="bt-ds<?= $ready ? '' : ' empty' ?>" data-bt-ds="<?= (int) $d['id'] ?>" data-symbol="<?= e($d['symbol']) ?>" data-tf="<?= (int) $d['tf'] ?>" data-first="<?= (int) $d['first_ts'] ?>" data-last="<?= (int) $d['last_ts'] ?>" data-ready="<?= $ready ? 1 : 0 ?>">
              <input type="radio" name="bt-ds" value="<?= (int) $d['id'] ?>" <?= $ready ? '' : 'disabled' ?>>
              <b><?= e($d['symbol']) ?></b>
              <small data-bt-dsinfo><?= $ready ? e($dsLabel($d)) : ($d['status'] === 'fetching' ? 'partly loaded' : 'not loaded yet') ?></small>
              <?php if ($live): ?><button type="button" class="tm-btn tm-btn-sm" data-bt-load="<?= e($d['symbol']) ?>"><?= icon($ready ? 'refresh' : 'download', 'icon icon-xs') ?> <?= $ready ? 'Update' : 'Load 2 years' ?></button><?php endif; ?>
              <span class="bt-prog" hidden><span></span></span>
            </label>
            <?php endforeach; ?>
          </div>
          <p class="bt-foot">15-minute candles from Twelve Data, shared by all members. Loading 2 years takes about a minute the first time.</p>
          <?php endif; ?>
        </div>
        <div data-bt-pane="upload" hidden>
          <form class="bt-upload" data-bt-upload enctype="multipart/form-data">
            <label class="bt-file"><?= icon('upload', 'icon icon-sm') ?> <span data-bt-filename>Choose a CSV of candles</span><input type="file" name="file" accept=".csv,.txt,.tsv" required></label>
            <div class="bt-row2">
              <label>Symbol<input name="symbol" maxlength="30" placeholder="e.g. XAUUSD" required></label>
              <label>File clock
                <select name="offset">
                  <option value="0">UTC (TradingView default)</option><option value="2">UTC+2 (most MT4/MT5 brokers, winter)</option><option value="3">UTC+3 (most MT4/MT5 brokers, summer)</option>
                  <option value="1">UTC+1</option><option value="5.5">UTC+5:30 (India)</option><option value="-5">UTC−5 (New York, winter)</option><option value="-4">UTC−4 (New York, summer)</option>
                </select></label>
            </div>
            <button class="tm-btn tm-btn-primary" type="submit"><?= icon('upload', 'icon icon-sm') ?> Upload candles</button>
            <p class="bt-foot">Date, time, open, high, low, close. MT5: right-click the chart → <i>Bars</i> → Export. TradingView: <i>Export chart data</i>. 15-minute candles or slower (1- and 5-minute files are combined into 15-minute candles). Up to 12 MB, <?= BacktestData::MAX_UPLOADS ?> files.</p>
          </form>
          <div class="bt-ds-list" data-bt-own>
            <?php foreach ($data['own'] as $d): ?>
            <label class="bt-ds" data-bt-ds="<?= (int) $d['id'] ?>" data-symbol="<?= e($d['symbol']) ?>" data-tf="<?= (int) $d['tf'] ?>" data-first="<?= (int) $d['first_ts'] ?>" data-last="<?= (int) $d['last_ts'] ?>" data-ready="1">
              <input type="radio" name="bt-ds" value="<?= (int) $d['id'] ?>">
              <b><?= e($d['symbol']) ?> <span class="bt-tfpill"><?= e($tfName[(int) $d['tf']] ?? '') ?></span></b>
              <small><?= e($d['name']) ?> · <?= e($dsLabel($d)) ?></small>
              <button type="button" class="tm-btn tm-btn-sm tm-btn-ghost" data-bt-dsdel="<?= (int) $d['id'] ?>" aria-label="Delete <?= e($d['name']) ?>"><?= icon('trash', 'icon icon-xs') ?></button>
            </label>
            <?php endforeach; ?>
          </div>
        </div>
      </section>

      <!-- 2 · RULES -->
      <section class="panel bt-step">
        <div class="panel-head"><h2><span class="bt-n">2</span> Entry rules</h2></div>
        <div class="bt-presets"><span>Start from:</span>
          <button type="button" data-bt-preset="ema">EMA crossover</button><button type="button" data-bt-preset="breakout">20-bar breakout</button>
          <button type="button" data-bt-preset="rsi">RSI pullback</button><button type="button" data-bt-preset="bb">Bollinger reversal</button><button type="button" data-bt-preset="macd">MACD cross</button>
        </div>
        <label class="bt-inline">Trade direction
          <select data-bt-side><option value="long">Buy only</option><option value="short">Sell only</option><option value="both" selected>Buy and sell</option></select></label>
        <div class="bt-conds" data-bt-block="long">
          <h3><span class="bt-dot up"></span> Buy when <small>all are true on a candle close</small></h3>
          <div data-bt-list="long"></div>
          <div class="bt-add"><button type="button" class="tm-btn tm-btn-sm" data-bt-add="long"><?= icon('plus', 'icon icon-xs') ?> Condition</button><button type="button" class="tm-btn tm-btn-sm" data-bt-addp="long"><?= icon('plus', 'icon icon-xs') ?> Candle pattern</button></div>
        </div>
        <div class="bt-conds" data-bt-block="short">
          <h3><span class="bt-dot dn"></span> Sell when <small>all are true on a candle close</small> <button type="button" class="bt-link" data-bt-mirror>Mirror the buy rules</button></h3>
          <div data-bt-list="short"></div>
          <div class="bt-add"><button type="button" class="tm-btn tm-btn-sm" data-bt-add="short"><?= icon('plus', 'icon icon-xs') ?> Condition</button><button type="button" class="tm-btn tm-btn-sm" data-bt-addp="short"><?= icon('plus', 'icon icon-xs') ?> Candle pattern</button></div>
        </div>
        <details class="bt-filters">
          <summary>Only trade in certain sessions or days</summary>
          <div class="bt-chips" data-bt-sessions><?php foreach (Backtest::SESSIONS as $k => $v): ?><label><input type="checkbox" value="<?= $k ?>"> <?= $v ?></label><?php endforeach; ?></div>
          <div class="bt-chips" data-bt-days><?php foreach ([1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri'] as $k => $v): ?><label><input type="checkbox" value="<?= $k ?>"> <?= $v ?></label><?php endforeach; ?></div>
          <p class="bt-foot">Leave everything unticked to trade at any time. Sessions follow London and New York local time (daylight saving included).</p>
        </details>
      </section>

      <!-- 3 · EXITS & RISK -->
      <section class="panel bt-step">
        <div class="panel-head"><h2><span class="bt-n">3</span> Exits and risk</h2></div>
        <div class="bt-form">
          <label>Stop loss<select data-bt-f="sl.type"><option value="atr">ATR multiple</option><option value="swing">Beyond recent swing</option><option value="points">Fixed price points</option><option value="percent">% of price</option></select></label>
          <label><span data-bt-sllbl>× ATR</span><input type="number" step="any" min="0" value="1.5" data-bt-f="sl.value"></label>
          <label data-bt-sllen>Lookback (bars)<input type="number" min="2" max="200" value="14" data-bt-f="sl.len"></label>
          <label>Take profit<select data-bt-f="tp.type"><option value="r">R multiple</option><option value="atr">ATR multiple</option><option value="points">Fixed price points</option><option value="none">None (exit by rules)</option></select></label>
          <label data-bt-tpval>Target<input type="number" step="any" min="0" value="2" data-bt-f="tp.value"></label>
          <label>Move stop to break-even at<input type="number" step="0.1" min="0" placeholder="off" data-bt-f="be_at_r"><small>R</small></label>
          <label>Close after (bars)<input type="number" min="0" placeholder="off" data-bt-f="max_bars"></label>
          <label class="bt-check"><input type="checkbox" data-bt-f="opposite"> Exit on the opposite signal</label>
        </div>
        <div class="bt-form">
          <label>Starting balance ($)<input type="number" min="100" value="10000" data-bt-f="balance"></label>
          <label>Risk per trade (%)<input type="number" step="0.1" min="0.01" max="10" value="1" data-bt-f="risk_pct"></label>
          <label>Spread (price points)<input type="number" step="any" min="0" value="0" data-bt-f="spread"><small>e.g. gold 0.30, EURUSD 0.00010</small></label>
          <label>Max trades a day<input type="number" min="0" max="50" placeholder="no limit" data-bt-f="max_per_day"></label>
        </div>
      </section>

      <!-- 4 · RUN -->
      <section class="panel bt-step bt-runbox">
        <div class="panel-head"><h2><span class="bt-n">4</span> Period</h2></div>
        <div class="bt-form">
          <label>Timeframe<select data-bt-tf><?php foreach (Backtest::TIMEFRAMES as $k => $s): ?><option value="<?= $k ?>"<?= $k === '1h' ? ' selected' : '' ?>><?= ['15min' => '15 minutes', '30min' => '30 minutes', '1h' => '1 hour', '4h' => '4 hours', '1day' => 'Daily'][$k] ?></option><?php endforeach; ?></select></label>
          <label>From<input type="date" data-bt-from></label>
          <label>To<input type="date" data-bt-to></label>
          <label class="bt-wide">Name (optional)<input maxlength="80" placeholder="e.g. Gold London EMA cross" data-bt-name></label>
        </div>
        <button type="button" class="tm-btn tm-btn-primary bt-go" data-bt-run><?= icon('activity', 'icon icon-sm') ?> Run backtest</button>
        <p class="bt-err" data-bt-err role="alert" hidden></p>
      </section>
    </div>

    <aside class="bt-side">
      <section class="panel">
        <div class="panel-head"><h2><?= icon('list', 'icon icon-sm') ?> Your tests</h2></div>
        <ul class="bt-runs" data-bt-runs>
          <?php foreach ($runs as $r): $s = json_decode($r['summary'], true); ?>
          <li><button type="button" data-bt-open="<?= (int) $r['id'] ?>"><b><?= $r['saved'] ? '★ ' : '' ?><?= e($r['name']) ?></b>
            <span><?= e($r['symbol']) ?> · <?= e($r['tf']) ?> · <?= (int) $s['trades'] ?> trades · <em class="<?= $s['net_r'] >= 0 ? 'up' : 'dn' ?>"><?= ($s['net_r'] >= 0 ? '+' : '') . $s['net_r'] ?>R</em></span></button></li>
          <?php endforeach; ?>
          <?php if (!$runs): ?><li class="bt-empty">Your tests will appear here.</li><?php endif; ?>
        </ul>
      </section>
      <section class="panel bt-how">
        <div class="panel-head"><h2><?= icon('info', 'icon icon-sm') ?> How a test is simulated</h2></div>
        <ul>
          <li>Rules are checked when a candle <b>closes</b>; the trade opens at the <b>next candle's open</b>. No peeking ahead.</li>
          <li>If one candle touches both the stop and the target, the <b>stop counts first</b>.</li>
          <li>One trade at a time. Spread is charged on every trade.</li>
          <li>Results are in <b>R</b> (your risk per trade) and in account % with your risk setting.</li>
        </ul>
      </section>
    </aside>
  </div>

  <!-- RESULTS -->
  <section class="bt-results" data-bt-results hidden aria-live="polite"></section>
  <p class="ex-disclaimer">Backtests are hypothetical. They use historical candles, not real fills: slippage, news spikes, swaps and commissions can make live results worse. Past results do not predict future returns. Not financial advice.</p>
</div>
