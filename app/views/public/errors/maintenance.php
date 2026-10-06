<section class="error-page">
  <span class="hero-glow" aria-hidden="true"></span>
  <div class="error-card">
    <a class="logo" href="<?= e(url('/')) ?>"><?= App\Core\View::partial('public/partials/logo') ?></a>
    <p class="error-code"><?= icon('settings', 'icon') ?></p>
    <h1>We will be back soon</h1>
    <p class="lead"><?= e(setting('maintenance_message', 'We are making improvements and will be back shortly.')) ?></p>
  </div>
</section>
