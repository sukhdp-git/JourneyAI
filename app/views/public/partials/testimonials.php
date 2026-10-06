<div class="testimonial-grid">
  <?php foreach ($testimonials as $t): ?>
  <figure class="testimonial reveal<?= $t['is_demo'] ? ' is-demo' : '' ?>">
    <?php if ($t['is_demo']): ?><span class="demo-badge">Demo content</span><?php endif; ?>
    <?php if ($t['rating'] && !$t['is_demo']): ?><div class="stars" aria-label="Rated <?= (int) $t['rating'] ?> out of 5"><?php for ($i = 1; $i <= 5; $i++): ?><span class="<?= $i <= (int) $t['rating'] ? 'on' : '' ?>"><?= icon('star', 'icon icon-sm') ?></span><?php endfor; ?></div><?php endif; ?>
    <blockquote><p><?= nl2br(e($t['message'])) ?></p></blockquote>
    <figcaption>
      <?php if ($t['photo']): ?><?= cms_image($t['photo'], $t['name'], 'avatar', false, '48px') ?><?php else: ?><span class="avatar avatar-initial" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($t['name'], 0, 1))) ?></span><?php endif; ?>
      <span><strong><?= e($t['name']) ?></strong><?php if ($t['designation'] || $t['company']): ?><small><?= e(trim($t['designation'] . ($t['designation'] && $t['company'] ? ', ' : '') . $t['company'])) ?></small><?php endif; ?></span>
    </figcaption>
  </figure>
  <?php endforeach; ?>
</div>
