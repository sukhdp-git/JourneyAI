<?php if ($pager->pages > 1): ?>
<nav class="pager" aria-label="Pagination">
  <span class="muted small">Page <?= $pager->page ?> of <?= $pager->pages ?> · <?= number_format($pager->total) ?> total</span>
  <div>
    <?php if ($pager->page > 1): ?><a class="btn btn-secondary btn-sm" href="<?= e($pager->url($pager->page - 1)) ?>" rel="prev"><?= icon('chevron-left', 'icon icon-sm') ?> Prev</a><?php endif; ?>
    <?php for ($i = max(1, $pager->page - 2); $i <= min($pager->pages, $pager->page + 2); $i++): ?>
      <?php if ($i === $pager->page): ?><span class="btn btn-sm is-current" aria-current="page"><?= $i ?></span><?php else: ?><a class="btn btn-ghost btn-sm" href="<?= e($pager->url($i)) ?>"><?= $i ?></a><?php endif; ?>
    <?php endfor; ?>
    <?php if ($pager->page < $pager->pages): ?><a class="btn btn-secondary btn-sm" href="<?= e($pager->url($pager->page + 1)) ?>" rel="next">Next <?= icon('chevron-right', 'icon icon-sm') ?></a><?php endif; ?>
  </div>
</nav>
<?php endif; ?>
