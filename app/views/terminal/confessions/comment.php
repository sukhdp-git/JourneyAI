<?php
use App\Trading\Confessions as C;
/** One reply under a confession. @var array $m @var int $me @var bool $fresh */
[$em, $hue] = C::avatar((int) $m['avatar_seed']);
?>
<div class="cf-c<?= !empty($fresh) ? ' is-fresh' : '' ?>" data-cfc="<?= (int) $m['id'] ?>">
  <span class="cf-av sm" style="--h:<?= $hue ?>" aria-hidden="true"><?= $em ?></span>
  <div class="cf-cbody">
    <div class="cf-chead"><b>@<?= e($m['handle']) ?></b><?php if ((int) $m['by_author']): ?><span class="cf-op">author</span><?php endif; ?><span class="muted small"><?= e(C::ago($m['created_at'])) ?></span></div>
    <p><?= nl2br(e($m['body'])) ?></p>
    <div class="cf-cact">
      <button type="button" class="cf-clike<?= $m['liked'] ? ' on' : '' ?>" data-cfc-like aria-pressed="<?= $m['liked'] ? 'true' : 'false' ?>" title="Every like = $15 of respect"><svg class="cf-heart" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20.5s-7.5-4.6-9.3-9.2C1.4 7.9 3.6 4.5 7 4.5c2 0 3.6 1.1 5 3 1.4-1.9 3-3 5-3 3.4 0 5.6 3.4 4.3 6.8-1.8 4.6-9.3 9.2-9.3 9.2z"/></svg> <span data-cfc-respect><?= e(C::respect((int) $m['likes'])) ?></span></button>
      <?php if ((int) $m['user_id'] === $me): ?><button type="button" class="cf-clink" data-cfc-delete>Delete</button><?php else: ?><button type="button" class="cf-clink" data-cfc-report>Report</button><?php endif; ?>
    </div>
  </div>
</div>
