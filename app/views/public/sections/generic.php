<section <?= section_attrs($s) ?>>
  <div class="container">
    <?= App\Core\View::partial('public/partials/section-head', ['s' => $s]) ?>
    <?php if ($s['body']): ?><div class="prose narrow reveal"><?= $s['body'] ?></div><?php endif; ?>
  </div>
</section>
