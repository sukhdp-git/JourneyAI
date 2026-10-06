<?php
use App\Core\View;
/** @var array $post @var string $author @var array $tags @var array $related */
$link = url('/blog/' . $post['slug']);
$share = [
    ['X', 'x-social', 'https://twitter.com/intent/tweet?url=' . rawurlencode($link) . '&text=' . rawurlencode($post['title'])],
    ['LinkedIn', 'linkedin', 'https://www.linkedin.com/sharing/share-offsite/?url=' . rawurlencode($link)],
    ['Facebook', 'facebook', 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode($link)],
    ['WhatsApp', 'whatsapp', 'https://wa.me/?text=' . rawurlencode($post['title'] . ' ' . $link)],
];
$words = str_word_count(strip_tags((string) $post['content']));
$crumbs = [['Home', '/'], ['Blog', '/blog']];
if ($post['category_slug']) $crumbs[] = [$post['category_name'], '/blog/category/' . $post['category_slug']];
$crumbs[] = [$post['title'], '/blog/' . $post['slug']];
$meta = '<p class="article-meta"><span>' . icon('user', 'icon icon-sm') . e($author) . '</span><span>' . icon('calendar', 'icon icon-sm') . '<time datetime="' . e(gmdate('c', strtotime($post['published_at']))) . '">' . e(fmt_date($post['published_at'])) . '</time></span><span>' . icon('clock', 'icon icon-sm') . max(1, (int) round($words / 220)) . ' min read</span></p>';
?>
<?= View::partial('public/partials/page-hero', ['eyebrow' => $post['category_name'] ?: 'Blog', 'title' => $post['title'], 'subtitle' => $post['excerpt'], 'crumbs' => $crumbs, 'extra' => $meta]) ?>
<section class="section section-tight">
  <div class="container article-layout">
    <article class="article">
      <?php if ($post['featured_image']): ?><figure class="article-image reveal"><?= cms_image($post['featured_image'], $post['title'], '', true, '(min-width: 900px) 760px, 100vw') ?></figure><?php endif; ?>
      <div class="prose"><?= $post['content'] ?></div>
      <?php if ($tags): ?>
      <ul class="tag-list" aria-label="Tags"><?php foreach ($tags as $t): ?><li><a href="<?= e(url('/blog/tag/' . $t['slug'])) ?>">#<?= e($t['name']) ?></a></li><?php endforeach; ?></ul>
      <?php endif; ?>
      <div class="share">
        <span>Share</span>
        <?php foreach ($share as [$label, $ic, $href]): ?><a href="<?= e($href) ?>" target="_blank" rel="noopener" aria-label="Share on <?= e($label) ?>"><?= icon($ic, 'icon icon-sm') ?></a><?php endforeach; ?>
        <button type="button" data-copy="<?= e($link) ?>" aria-label="Copy link"><?= icon('link', 'icon icon-sm') ?><span class="copy-label">Copy link</span></button>
      </div>
    </article>
    <aside class="article-aside">
      <div class="aside-card sticky-aside">
        <h2 class="h4"><?= e(setting('footer_cta_heading') ?: 'Want to see the platform?') ?></h2>
        <p><?= e(setting('footer_cta_text') ?: 'Book a walkthrough with our team.') ?></p>
        <?= cms_button(setting('header_cta_label', 'Book a demo'), setting('header_cta_url', '/book-consultation'), 'btn btn-primary btn-block', true) ?>
      </div>
    </aside>
  </div>
</section>
<?php if ($related): ?>
<section class="section bg-muted">
  <div class="container">
    <div class="section-head align-left reveal"><p class="eyebrow">Keep reading</p><h2>Related articles</h2></div>
    <div class="post-grid"><?php foreach ($related as $p) echo View::partial('public/partials/post-card', ['p' => $p]); ?></div>
  </div>
</section>
<?php endif; ?>
