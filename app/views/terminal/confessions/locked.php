<?php /** Confessions preview for members without an active plan. */ ?>
<div class="cf cf-locked">
  <?= App\Core\View::partial('terminal/confessions/bg') ?>
  <section class="cf-lock-card">
    <span class="cf-kicker"><?= icon('lock', 'icon icon-xs') ?> Members with a plan</span>
    <h2>Confessions: the trading floor, <em>unfiltered.</em></h2>
    <p class="muted">An anonymous feed where traders post the trades, feelings and lessons nobody puts on Instagram. Experienced traders answer beginners in the replies. Every like is shown as $15 of respect.</p>
    <ul class="cf-rules compact">
      <li>🙊 Your own anonymous @handle</li><li>📈 Attach real trades from your journal</li><li>🛡️ No links, DMs, signals or selling</li><li>💬 Comments, likes and share cards</li>
    </ul>
    <a class="tm-btn tm-btn-primary cf-post" href="<?= e(url('/terminal/billing')) ?>"><?= icon('zap', 'icon icon-sm') ?> Unlock with a plan</a>
  </section>
  <div class="cf-lock-preview" aria-hidden="true">
    <?php foreach ([['🦊', 30, 'calm_scalper', '🙊 Confession', 'Took 6 trades before 10am. Planned 2. Guess which 2 were green.', '$435'], ['🐼', 200, 'zen_candle', '🙋 Question', 'Is 1% risk per trade too small on a 5k account? Everyone online says go bigger.', '$120'], ['🦉', 262, 'night_owl', '🟢 Win', 'First week with zero revenge trades. Small green, but it’s mine.', '$1,080']] as [$e, $h, $n, $t, $b, $r]): ?>
    <div class="cf-card"><span class="cf-ribbon">Example</span><header class="cf-head"><span class="cf-av" style="--h:<?= $h ?>"><?= $e ?></span><div class="cf-who"><b>@<?= $n ?></b><span class="cf-meta"><span>2h</span><span class="cf-tag"><?= $t ?></span></span></div></header><div class="cf-body"><?= e($b) ?></div><footer class="cf-actions"><span class="cf-act cf-like on"><svg class="cf-heart" viewBox="0 0 24 24"><path d="M12 20.5s-7.5-4.6-9.3-9.2C1.4 7.9 3.6 4.5 7 4.5c2 0 3.6 1.1 5 3 1.4-1.9 3-3 5-3 3.4 0 5.6 3.4 4.3 6.8-1.8 4.6-9.3 9.2-9.3 9.2z"/></svg> <?= $r ?></span></footer></div>
    <?php endforeach; ?>
  </div>
</div>
