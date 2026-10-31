<?php /** Backtester preview for members without an active plan. */ ?>
<div class="bt bt-locked">
  <section class="bt-hero">
    <div>
      <span class="bt-kicker"><?= icon('lock', 'icon icon-xs') ?> Members with a plan</span>
      <h2>Test your rules on <em>2 years of real candles.</em></h2>
      <p class="muted">Build a strategy from rules (moving averages, RSI, breakouts, Bollinger Bands, MACD, candle patterns, sessions), choose the market and see every trade it would have taken: win rate, R, drawdown and the worst losing streak.</p>
      <ul class="bt-lock-list">
        <li>📈 Gold, indices, forex and crypto, or your own MT5 / TradingView export</li>
        <li>🧱 No code: pick the rules from menus</li>
        <li>🔍 Every trade listed, with a CSV download</li>
      </ul>
      <a class="tm-btn tm-btn-primary" href="<?= e(url('/terminal/billing')) ?>"><?= icon('zap', 'icon icon-sm') ?> Unlock with a plan</a>
    </div>
  </section>
</div>
