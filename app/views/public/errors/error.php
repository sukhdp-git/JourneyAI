<?php
$copy = [
    404 => 'The page you are looking for has moved, been removed or never existed.',
    403 => 'You do not have permission to view this page.',
    419 => 'Your session expired before the form was sent. Please go back, reload the page and try again.',
    429 => 'You have made too many requests. Please wait a moment and try again.',
    500 => 'An unexpected error occurred. It has been logged and we will look into it.',
];
$isAdmin = str_starts_with((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), rtrim((string) parse_url(BASE_URL, PHP_URL_PATH), '/') . '/' . ADMIN_PREFIX);
?>
<section class="error-page">
  <span class="hero-glow" aria-hidden="true"></span><span class="hero-grid" aria-hidden="true"></span>
  <div class="error-card">
    <a class="logo" href="<?= e(url('/')) ?>"><?php try { echo App\Core\View::partial('public/partials/logo'); } catch (\Throwable) { echo 'journzey.ai'; } ?></a>
    <p class="error-code"><?= (int) $status ?></p>
    <h1><?= e($title) ?></h1>
    <p class="lead"><?= e($message !== '' && $status !== 500 ? $message : ($copy[$status] ?? $copy[500])) ?></p>
    <div class="actions center">
      <?php if ($isAdmin): ?>
      <a class="btn btn-primary" href="<?= e(admin_url('dashboard')) ?>"><?= icon('dashboard', 'icon icon-sm') ?> Back to dashboard</a>
      <?php else: ?>
      <a class="btn btn-primary" href="<?= e(url('/')) ?>"><?= icon('home', 'icon icon-sm') ?> Go to homepage</a>
      <a class="btn btn-ghost" href="<?= e(url('/contact')) ?>">Contact us</a>
      <?php endif; ?>
    </div>
  </div>
</section>
