<?php use App\Core\View; ?>
<?= View::partial('public/partials/page-hero', ['eyebrow' => setting('services_eyebrow'), 'title' => setting('services_heading', 'Services'), 'subtitle' => setting('services_intro'), 'crumbs' => [['Home', '/'], ['Services', '/services']]]) ?>
<section class="section">
  <div class="container">
    <?php if ($services): ?>
      <?= View::partial('public/partials/service-cards', ['services' => $services]) ?>
    <?php else: ?>
      <div class="empty-state"><?= icon('layers', 'icon') ?><p>Services will appear here once they are published.</p></div>
    <?php endif; ?>
  </div>
</section>
<?php if ($process): ?>
<section class="section bg-muted">
  <div class="container">
    <div class="section-head align-center reveal"><p class="eyebrow">How it works</p><h2>A process you can repeat</h2></div>
    <?= View::partial('public/partials/process-steps', ['process' => $process]) ?>
  </div>
</section>
<?php endif; ?>
<?php foreach ($custom as $c) echo View::partial('public/sections/custom', ['c' => $c]); ?>
