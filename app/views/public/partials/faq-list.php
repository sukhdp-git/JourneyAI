<div class="faq-list" data-accordion>
  <?php foreach ($faqs as $i => $f): $fid = 'faq-' . e($id ?? 'x') . '-' . $i; ?>
  <div class="faq-item reveal">
    <h3 class="faq-q">
      <button type="button" aria-expanded="false" aria-controls="<?= $fid ?>" id="<?= $fid ?>-btn">
        <span><?= e($f['question']) ?></span><span class="faq-icon" aria-hidden="true"><?= icon('plus', 'icon icon-sm') ?></span>
      </button>
    </h3>
    <div class="faq-a" id="<?= $fid ?>" role="region" aria-labelledby="<?= $fid ?>-btn" hidden>
      <div class="faq-a-inner"><?= nl2br(e($f['answer'])) ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
