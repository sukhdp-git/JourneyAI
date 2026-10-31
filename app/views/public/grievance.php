<?php
use App\Core\View;
/** Grievance Officer page (IT Rules 2021, Consumer Protection (E-Commerce) Rules 2020, DPDP Act 2023). */
$email = setting('grievance_email') ?: (setting('support_email') ?: setting('contact_email'));
$entity = setting('legal_entity') ?: setting('site_name', 'journzey.ai');
?>
<?= View::partial('public/partials/page-hero', ['eyebrow' => 'Legal', 'title' => 'Grievance Officer', 'subtitle' => 'Raise a complaint about the service, your personal data or something posted on the platform.', 'image' => null, 'crumbs' => [['Home', '/'], ['Grievance Officer', '/grievance-officer']], 'extra' => '']) ?>
<section class="section section-tight">
  <div class="container legal-layout">
    <article class="prose legal">
      <h2>Contact</h2>
      <p><strong><?= e(setting('grievance_name') ?: 'Grievance Officer') ?></strong><br>
        <?= e($entity) ?><?php if (setting('legal_gstin')): ?> · GSTIN <?= e(setting('legal_gstin')) ?><?php endif; ?><br>
        <?php if ($email): ?>Email: <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a><br><?php endif; ?>
        <?php if (setting('grievance_phone')): ?>Phone: <?= e(setting('grievance_phone')) ?><br><?php endif; ?>
        <?php if (setting('grievance_hours')): ?>Hours: <?= e(setting('grievance_hours')) ?><br><?php endif; ?>
        <?php if (setting('contact_address')): ?>Address: <?= nl2br(e(setting('contact_address'))) ?><?php endif; ?></p>
      <h2>How we handle a complaint</h2>
      <ol>
        <li>Email the Grievance Officer with your account email, what happened and, for content, the link to it.</li>
        <li>We acknowledge your complaint within <strong>24 hours</strong>.</li>
        <li>We resolve it within <strong>15 days</strong> (sooner for urgent requests such as removing content that exposes someone's private information).</li>
        <li>If you are not satisfied with the answer about your personal data, you can complain to the Data Protection Board of India.</li>
      </ol>
      <h2>Quick links</h2>
      <ul>
        <li>Download or delete your data: Terminal → Settings.</li>
        <li>Stop autopay: Terminal → Plan &amp; billing → <em>Turn off autopay</em>.</li>
        <li><a href="<?= e(url('/privacy-policy')) ?>">Privacy policy</a> · <a href="<?= e(url('/terms-and-conditions')) ?>">Terms</a> · <a href="<?= e(url('/contact')) ?>">Contact</a></li>
      </ul>
    </article>
  </div>
</section>
