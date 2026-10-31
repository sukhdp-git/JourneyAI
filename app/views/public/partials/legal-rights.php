<?php
/** Rights, retention, cookies and grievance contact appended to the privacy policy (DPDP Act 2023, IT Rules 2021, GDPR). */
$gEmail = setting('grievance_email') ?: (setting('support_email') ?: setting('contact_email'));
$gName = setting('grievance_name') ?: 'Grievance Officer';
?>
<section class="legal-rights" id="your-rights">
  <h2>Your rights and how to use them</h2>
  <p>Under India's Digital Personal Data Protection Act, 2023 (and, where it applies to you, the EU/UK GDPR), you can:</p>
  <ul>
    <li><strong>Access and download</strong> your data at any time: Terminal → Settings → <em>Export my data</em> (JSON and CSV).</li>
    <li><strong>Correct</strong> your profile, trades and journal entries directly in the terminal.</li>
    <li><strong>Delete your account</strong> and all of its data: Terminal → Settings → <em>Delete account</em>. Payment records are kept, without your name or email, only as long as tax and accounting law requires.</li>
    <li><strong>Withdraw consent</strong> for analytics cookies with <em>Cookie settings</em> in the footer, and stop autopay at any time in Plan &amp; billing.</li>
    <li><strong>Nominate</strong> another person to exercise these rights in case of death or incapacity, by writing to the Grievance Officer.</li>
    <li><strong>Complain</strong> to the Grievance Officer below, and if you are not satisfied, to the Data Protection Board of India.</li>
  </ul>
  <h3 id="cookies">Cookies</h3>
  <p>Essential cookies keep you signed in and protect forms against forgery; they cannot be switched off. Analytics and advertising cookies (Google Analytics, Google Tag Manager, Meta Pixel) are loaded only if you choose <em>Accept all</em>.</p>
  <h3>Age</h3>
  <p>The service is only for people aged 18 or over. We do not knowingly collect data from children.</p>
  <h3>Security incidents</h3>
  <p>If a personal-data breach affects you, we will tell you and the Data Protection Board of India, and report cyber-security incidents to CERT-In as the law requires.</p>
  <h3>Grievance Officer</h3>
  <p><?= e($gName) ?><?php if ($gEmail): ?> · <a href="mailto:<?= e($gEmail) ?>"><?= e($gEmail) ?></a><?php endif; ?> · <a href="<?= e(url('/grievance-officer')) ?>">How complaints are handled</a></p>
</section>
