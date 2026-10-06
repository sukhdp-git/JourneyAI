<?php
use App\Core\View;
/** @var array $posts @var ?array $featured @var App\Core\Paginator $pager @var string $q */
$crumbs = [['Home', '/'], ['Blog', '/blog']];
if ($category) $crumbs[] = [$category['name'], '/blog/category/' . $category['slug']];
if ($tag) $crumbs[] = ['#' . $tag['name'], '/blog/tag/' . $tag['slug']];
$search = '<form class="search-form" action="' . e(url('/blog')) . '" method="get" role="search"><label class="sr-only" for="blog-q">Search articles</label>' . icon('search', 'icon icon-sm') . '<input id="blog-q" type="search" name="q" value="' . e($q) . '" placeholder="Search articles" maxlength="100"><button class="btn btn-primary btn-sm" type="submit">Search</button></form>';
?>
<?= View::partial('public/partials/page-hero', ['eyebrow' => ($category || $tag) ? 'Blog' : setting('blog_eyebrow', 'Blog'), 'title' => ($category || $tag) ? $heading : setting('blog_heading', 'Blog'), 'subtitle' => $category['description'] ?? (($category || $tag) ? '' : setting('blog_intro')), 'crumbs' => $crumbs, 'extra' => $search]) ?>
<section class="section section-tight">
  <div class="container">
    <?php if ($categories): ?>
    <nav class="chips reveal" aria-label="Categories">
      <a href="<?= e(url('/blog')) ?>"<?= !$category && !$tag ? ' class="is-active" aria-current="page"' : '' ?>>All</a>
      <?php foreach ($categories as $c): if (!$c['posts']) continue; ?>
      <a href="<?= e(url('/blog/category/' . $c['slug'])) ?>"<?= ($category['slug'] ?? '') === $c['slug'] ? ' class="is-active" aria-current="page"' : '' ?>><?= e($c['name']) ?> <small><?= (int) $c['posts'] ?></small></a>
      <?php endforeach; ?>
    </nav>
    <?php endif; ?>
    <?php if ($q !== ''): ?><p class="results-note"><?= (int) $pager->total ?> result<?= $pager->total === 1 ? '' : 's' ?> for “<?= e($q) ?>” · <a href="<?= e(url('/blog')) ?>">Clear search</a></p><?php endif; ?>
    <?php if ($featured): ?>
    <article class="featured-post reveal">
      <a class="post-thumb" href="<?= e(url('/blog/' . $featured['slug'])) ?>" tabindex="-1" aria-hidden="true"><?php if ($featured['featured_image']): ?><?= cms_image($featured['featured_image'], '', '', true, '(min-width: 900px) 55vw, 100vw') ?><?php else: ?><span class="thumb-fallback"><?= icon('book', 'icon') ?></span><?php endif; ?></a>
      <div class="post-body">
        <p class="post-meta"><span class="badge">Featured</span><?php if ($featured['category_slug']): ?><a href="<?= e(url('/blog/category/' . $featured['category_slug'])) ?>"><?= e($featured['category_name']) ?></a><?php endif; ?><time datetime="<?= e(gmdate('c', strtotime($featured['published_at']))) ?>"><?= e(fmt_date($featured['published_at'])) ?></time></p>
        <h2><a href="<?= e(url('/blog/' . $featured['slug'])) ?>"><?= e($featured['title']) ?></a></h2>
        <?php if ($featured['excerpt']): ?><p class="lead"><?= e($featured['excerpt']) ?></p><?php endif; ?>
        <a class="card-link" href="<?= e(url('/blog/' . $featured['slug'])) ?>">Read article <?= icon('arrow-right', 'icon icon-sm') ?></a>
      </div>
    </article>
    <?php endif; ?>
    <?php if ($posts): ?>
      <div class="post-grid"><?php foreach ($posts as $p) echo View::partial('public/partials/post-card', ['p' => $p]); ?></div>
    <?php elseif (!$featured): ?>
      <div class="empty-state"><?= icon('search', 'icon') ?><p><?= $q !== '' ? 'No articles match your search.' : 'No articles have been published yet.' ?></p></div>
    <?php endif; ?>
    <?= View::partial('public/partials/pagination', ['pager' => $pager]) ?>
  </div>
</section>
