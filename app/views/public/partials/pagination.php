<?php /** @var App\Core\Paginator $pager */ if ($pager->pages <= 1) return; ?>
<nav class="pagination" aria-label="Pagination">
  <?php if ($pager->page > 1): ?><a href="<?= e($pager->url($pager->page - 1)) ?>" rel="prev"><?= icon('chevron-left', 'icon icon-sm') ?> Previous</a><?php endif; ?>
  <ol>
    <?php for ($i = 1; $i <= $pager->pages; $i++): if ($pager->pages > 7 && abs($i - $pager->page) > 2 && $i !== 1 && $i !== $pager->pages) { if ($i === 2 || $i === $pager->pages - 1) echo '<li class="gap">…</li>'; continue; } ?>
    <li><?php if ($i === $pager->page): ?><span aria-current="page"><?= $i ?></span><?php else: ?><a href="<?= e($pager->url($i)) ?>"><?= $i ?></a><?php endif; ?></li>
    <?php endfor; ?>
  </ol>
  <?php if ($pager->page < $pager->pages): ?><a href="<?= e($pager->url($pager->page + 1)) ?>" rel="next">Next <?= icon('chevron-right', 'icon icon-sm') ?></a><?php endif; ?>
</nav>
