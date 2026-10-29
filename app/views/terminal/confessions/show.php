<?php
/** A single confession with its replies (also the share link). @var array $c @var array $comments */
?>
<div class="cf cf-single" data-cf-app data-base="<?= e(url('/terminal/confessions')) ?>">
  <a class="tm-btn tm-btn-sm cf-back" href="<?= e(url('/terminal/confessions')) ?>"><?= icon('arrow-left', 'icon icon-sm') ?> All confessions</a>
  <div class="cf-feed" data-cf-feed>
    <?= App\Core\View::partial('terminal/confessions/card', ['c' => $c, 'me' => (int) $m['id'], 'i' => 0]) ?>
  </div>
  <script type="application/json" data-cf-open="<?= (int) $c['id'] ?>"></script>
</div>
