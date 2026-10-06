<article class="post-card reveal">
  <a class="post-thumb" href="<?= e(url('/blog/' . $p['slug'])) ?>" tabindex="-1" aria-hidden="true">
    <?php if ($p['featured_image']): ?><?= cms_image($p['featured_image'], '', '', false, '(min-width: 900px) 33vw, 100vw') ?><?php else: ?><span class="thumb-fallback"><?= icon('book', 'icon') ?></span><?php endif; ?>
  </a>
  <div class="post-body">
    <p class="post-meta">
      <?php if ($p['category_slug']): ?><a href="<?= e(url('/blog/category/' . $p['category_slug'])) ?>"><?= e($p['category_name']) ?></a><span aria-hidden="true">·</span><?php endif; ?>
      <time datetime="<?= e(gmdate('c', strtotime($p['published_at']))) ?>"><?= e(fmt_date($p['published_at'])) ?></time>
    </p>
    <h3><a href="<?= e(url('/blog/' . $p['slug'])) ?>"><?= e($p['title']) ?></a></h3>
    <?php if ($p['excerpt']): ?><p><?= e(excerpt($p['excerpt'], 150)) ?></p><?php endif; ?>
  </div>
</article>
