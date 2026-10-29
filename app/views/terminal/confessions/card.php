<?php
use App\Trading\Confessions as C;
/** One confession in the feed. @var array $c @var int $me @var int $i @var bool $fresh */
[$em, $hue] = C::avatar((int) $c['avatar_seed']);
$tag = C::TAGS[$c['tag']] ?? C::TAGS['CONFESSION'];
$mine = (int) $c['user_id'] === $me;
$id = (int) $c['id'];
$trade = '';
if ($c['trade_symbol']) {
    $pnl = (float) $c['trade_pnl'];
    $trade = $c['trade_symbol'] . ' ' . $c['trade_side'] . ' · ' . money($pnl, $c['trade_currency'] ?: 'USD', true) . ($c['trade_r'] !== null ? ' · ' . ((float) $c['trade_r'] > 0 ? '+' : ((float) $c['trade_r'] < 0 ? '−' : '')) . number_format(abs((float) $c['trade_r']), 1) . 'R' : '');
}
?>
<article class="cf-card<?= !empty($fresh) ? ' is-fresh' : '' ?>" data-cf="<?= $id ?>" data-cf-handle="<?= e($c['handle']) ?>" data-cf-tag="<?= e($tag[0] . ' ' . $tag[1]) ?>" data-cf-trade="<?= e($trade) ?>" data-cf-demo="<?= (int) $c['trade_demo'] ?>" data-cf-url="<?= e(url('/terminal/confessions/' . $id)) ?>" style="--i:<?= (int) ($i ?? 0) ?>">
  <header class="cf-head">
    <span class="cf-av" style="--h:<?= $hue ?>" aria-hidden="true"><?= $em ?></span>
    <div class="cf-who">
      <b>@<?= e($c['handle']) ?></b>
      <span class="cf-meta"><a href="<?= e(url('/terminal/confessions/' . $id)) ?>"><time datetime="<?= e($c['created_at']) ?>Z"><?= e(C::ago($c['created_at'])) ?></time></a>
        <span class="cf-tag cf-tag-<?= e(strtolower($c['tag'])) ?>"><?= $tag[0] ?> <?= e($tag[1]) ?></span><?php if ($c['mood']): ?><span class="cf-mood" title="Mood"><?= e($c['mood']) ?></span><?php endif; ?></span>
    </div>
    <div class="cf-menu" data-cf-menu>
      <button type="button" class="cf-more" data-cf-more aria-haspopup="true" aria-expanded="false" aria-label="More options"><?= icon('menu', 'icon icon-sm') ?></button>
      <div class="cf-pop" role="menu" hidden>
        <button type="button" role="menuitem" data-cf-copy><?= icon('link', 'icon icon-xs') ?> Copy link</button>
        <?php if ($mine): ?><button type="button" role="menuitem" class="danger" data-cf-delete><?= icon('trash', 'icon icon-xs') ?> Delete</button>
        <?php else: foreach (C::REPORT_REASONS as $k => $l): ?><button type="button" role="menuitem" data-cf-report="<?= e($k) ?>"><?= icon('flag', 'icon icon-xs') ?> Report: <?= e($l) ?></button><?php endforeach; endif; ?>
      </div>
    </div>
  </header>
  <div class="cf-body" data-cf-body><?= nl2br(e($c['body'])) ?></div>
  <?php if ($trade): ?>
  <div class="cf-trade <?= (float) $c['trade_pnl'] >= 0 ? 'up' : 'down' ?>">
    <span class="cf-sym"><?= e($c['trade_symbol']) ?></span><span class="cf-side"><?= e($c['trade_side']) ?></span>
    <b><?= e(money((float) $c['trade_pnl'], $c['trade_currency'] ?: 'USD', true)) ?></b>
    <?php if ($c['trade_r'] !== null): ?><span class="cf-r"><?= (float) $c['trade_r'] > 0 ? '+' : ((float) $c['trade_r'] < 0 ? '−' : '') ?><?= number_format(abs((float) $c['trade_r']), 1) ?>R</span><?php endif; ?>
    <em title="Attached from the member's own journal"><?= icon('check-circle', 'icon icon-xs') ?> from journal<?= (int) $c['trade_demo'] ? ' · demo account' : '' ?></em>
  </div>
  <?php endif; ?>
  <footer class="cf-actions">
    <button type="button" class="cf-act cf-like<?= $c['liked'] ? ' on' : '' ?>" data-cf-like aria-pressed="<?= $c['liked'] ? 'true' : 'false' ?>" title="Every like = $15 of respect (just for fun, no real money)">
      <svg class="cf-heart" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20.5s-7.5-4.6-9.3-9.2C1.4 7.9 3.6 4.5 7 4.5c2 0 3.6 1.1 5 3 1.4-1.9 3-3 5-3 3.4 0 5.6 3.4 4.3 6.8-1.8 4.6-9.3 9.2-9.3 9.2z"/></svg>
      <span data-cf-respect><?= e(C::respect((int) $c['likes'])) ?></span><span class="sr-only"> respect, <?= (int) $c['likes'] ?> likes</span>
    </button>
    <button type="button" class="cf-act" data-cf-comments aria-expanded="false"><?= icon('message', 'icon icon-sm') ?> <span data-cf-ccount><?= (int) $c['comments'] ?></span></button>
    <button type="button" class="cf-act" data-cf-share><?= icon('share', 'icon icon-sm') ?> <span>Share</span></button>
  </footer>
  <div class="cf-thread" data-cf-thread hidden>
    <div class="cf-clist" data-cf-clist aria-live="polite"></div>
    <form class="cf-cform" data-cf-cform>
      <label class="sr-only" for="cfc-<?= $id ?>">Reply</label>
      <input id="cfc-<?= $id ?>" name="body" maxlength="<?= C::MAX_COMMENT ?>" autocomplete="off" placeholder="Reply with a tip or some support… (no links or DMs)">
      <button type="submit" class="tm-btn tm-btn-sm tm-btn-primary" aria-label="Send reply"><?= icon('send', 'icon icon-sm') ?></button>
    </form>
  </div>
</article>
