<?php
use App\Trading\Course;
/** Public verification of a University certificate. @var array $c */
?>
<section class="section section-tight">
  <div class="container verify-wrap">
    <div class="verify-card is-up cert-verify">
      <div class="verify-badge"><?= icon('check-circle', 'icon') ?> Verified by <?= e(setting('site_name', 'journzey.ai')) ?> University</div>
      <p class="verify-date">Certificate of completion · <?= e(fmt_date($c['issued_at'], 'F j, Y')) ?></p>
      <p class="verify-sym" style="font-size:28px"><strong><?= e($c['name_on_cert']) ?></strong></p>
      <p>completed <strong><?= e(Course::TITLE) ?></strong>: <?= count(Course::MODULES) ?> lessons in <?= count(Course::LEVELS) ?> levels (about <?= round(Course::minutes() / 60, 1) ?> hours), passing the quiz in every lesson.</p>
      <dl class="verify-stats">
        <div><dt>Certificate ID</dt><dd><?= e($c['code']) ?></dd></div>
        <div><dt>Issued</dt><dd><?= e(fmt_date($c['issued_at'], 'M j, Y')) ?></dd></div>
        <div><dt>Course</dt><dd><?= e(Course::TITLE) ?></dd></div>
      </dl>
      <p class="muted small">This confirms the course was completed on journzey.ai. It is an educational certificate, not a professional or regulatory qualification.</p>
    </div>
  </div>
</section>
