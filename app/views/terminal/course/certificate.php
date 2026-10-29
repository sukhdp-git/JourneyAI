<?php
use App\Trading\Course;
/** Certificate of completion: locked → claim (name) → issued (download / print / verify). @var array $standing @var ?array $cert @var array $progress */
$st = $standing;
$total = count(Course::MODULES);
$name = $cert ? $cert['name_on_cert'] : trim((string) $m['name']);
$verify = $cert ? url('/certificate/' . $cert['code']) : '';
$date = $cert ? fmt_date($cert['issued_at'], 'F j, Y') : date('F j, Y');
$hours = round(Course::minutes() / 60, 1);
?>
<div class="cx cx-certpage">
  <a class="tm-btn tm-btn-sm" href="<?= e(url('/terminal/university/course')) ?>"><?= icon('arrow-left', 'icon icon-sm') ?> Course overview</a>

  <?php if (!$st['eligible'] && !$cert): ?>
  <section class="cx-hero cx-cert-locked">
    <span class="cx-kicker"><?= icon('lock', 'icon icon-xs') ?> Certificate of completion</span>
    <h2>Finish the course, <em>earn the certificate.</em></h2>
    <p>Pass the quick check (<?= Course::PASS ?>%+) in all <?= $total ?> lessons. You have passed <b><?= (int) $st['done'] ?></b>.</p>
    <div class="cx-progress"><div class="bar"><span style="width:<?= round($st['done'] / $total * 100) ?>%"></span></div><small><?= (int) $st['done'] ?> of <?= $total ?> lessons passed</small></div>
    <ol class="cx-left">
      <?php foreach (Course::order() as $i => $k): if (isset($progress[$k])) continue; [$slug, $title] = Course::MODULES[$k]; ?>
      <li><a href="<?= e(url('/terminal/university/course/' . $slug)) ?>"><span><?= $i + 1 ?></span> <?= e($title) ?> <?= icon('arrow-right', 'icon icon-xs') ?></a></li>
      <?php endforeach; ?>
    </ol>
  </section>
  <?php else: ?>

  <?php if (!$cert): ?>
  <section class="cx-claim">
    <h2>🎓 You did it. <em>Claim your certificate.</em></h2>
    <p class="muted">Type the name to print on it. It cannot be changed after the certificate is issued.</p>
    <form method="post" action="<?= e(url('/terminal/university/certificate')) ?>" class="cx-claim-form">
      <?= csrf_field() ?>
      <label for="cert-name" class="sr-only">Name on the certificate</label>
      <input id="cert-name" name="name" value="<?= e($name) ?>" maxlength="60" required autocomplete="name" data-cert-name>
      <button class="tm-btn tm-btn-primary" type="submit"><?= icon('check', 'icon icon-sm') ?> Issue my certificate</button>
    </form>
  </section>
  <?php else: ?>
  <div class="cx-cert-actions">
    <button type="button" class="tm-btn tm-btn-primary" data-cert-download><?= icon('download', 'icon icon-sm') ?> Download PNG</button>
    <button type="button" class="tm-btn" data-cert-print><?= icon('file', 'icon icon-sm') ?> Print / save as PDF</button>
    <button type="button" class="tm-btn" data-cert-copy="<?= e($verify) ?>"><?= icon('link', 'icon icon-sm') ?> Copy verification link</button>
    <button type="button" class="tm-btn" data-cert-share><?= icon('share', 'icon icon-sm') ?> Share</button>
  </div>
  <?php endif; ?>

  <figure class="cert<?= $cert ? ' issued' : ' preview' ?>" data-cert data-name="<?= e($name) ?>" data-date="<?= e($date) ?>" data-code="<?= e($cert['code'] ?? 'PREVIEW') ?>" data-url="<?= e($verify) ?>" data-course="<?= e(Course::TITLE) ?>" data-meta="<?= e($total . ' lessons · ' . count(Course::LEVELS) . ' levels · about ' . $hours . ' hours') ?>">
    <div class="cert-in">
      <div class="cert-top"><span class="cert-logo">journzey<span>.ai</span></span><span class="cert-uni">University</span></div>
      <p class="cert-kicker">Certificate of completion</p>
      <p class="cert-this">This certifies that</p>
      <p class="cert-name" data-cert-name-out><?= e($name) ?></p>
      <p class="cert-what">has completed <b><?= e(Course::TITLE) ?></b>, passing the quiz in every lesson.</p>
      <p class="cert-meta"><?= $total ?> lessons · <?= count(Course::LEVELS) ?> levels · about <?= $hours ?> hours · <?= implode(' → ', array_map(fn ($l) => e($l[0]), Course::LEVELS)) ?></p>
      <div class="cert-foot">
        <div><span class="cert-line"></span><b>journzey.ai University</b><small>Issued <?= e($date) ?></small></div>
        <div class="cert-seal" aria-hidden="true"><svg viewBox="0 0 100 100"><defs><linearGradient id="cs" x1="0" x2="1"><stop offset="0" stop-color="#6541e9"/><stop offset="1" stop-color="#feb2b4"/></linearGradient></defs><circle cx="50" cy="50" r="46" fill="url(#cs)"/><circle cx="50" cy="50" r="38" fill="none" stroke="#fff" stroke-width="1.5" stroke-dasharray="3 3"/><text x="50" y="47" text-anchor="middle" fill="#fff" font-size="11" font-weight="800">VERIFIED</text><text x="50" y="62" text-anchor="middle" fill="#fff" font-size="9">COMPLETE</text></svg></div>
        <div class="cert-id"><canvas width="84" height="84" data-cert-qr aria-label="QR code to verify this certificate"></canvas><small>ID <?= e($cert['code'] ?? '— preview —') ?><br>Verify at <?= e(preg_replace('#^https?://#', '', $cert ? $verify : url('/certificate/…'))) ?></small></div>
      </div>
    </div>
  </figure>
  <p class="ex-disclaimer">An educational certificate from journzey.ai University. It is not a professional, academic or regulatory qualification.</p>
  <?php endif; ?>
</div>
<script src="<?= e(asset('js/certificate.js')) ?>" defer></script>
