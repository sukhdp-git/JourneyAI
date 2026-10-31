<?php /** Cookie consent (GDPR / DPDP): analytics and ads tags load only after "Accept all". Essential cookies (sign-in, security) need no consent. */ ?>
<div class="cookie-bar" role="dialog" aria-live="polite" aria-label="Cookie consent" data-cookie-bar>
  <p>We use essential cookies to keep you signed in and secure. With your permission we also use analytics cookies to understand how the site is used. <a href="<?= e(url('/privacy-policy')) ?>#cookies">Privacy policy</a></p>
  <div class="cookie-actions">
    <button type="button" class="btn btn-ghost btn-sm" data-consent="essential">Essential only</button>
    <button type="button" class="btn btn-primary btn-sm" data-consent="all">Accept all</button>
  </div>
</div>
<script>
(function () {
  var bar = document.querySelector('[data-cookie-bar]');
  bar.addEventListener('click', function (e) {
    var b = e.target.closest('[data-consent]'); if (!b) return;
    document.cookie = 'jz_consent=' + b.dataset.consent + '; Max-Age=' + 60 * 60 * 24 * 180 + '; Path=/; SameSite=Lax' + (location.protocol === 'https:' ? '; Secure' : '');
    if (b.dataset.consent === 'all') location.reload(); else bar.remove();
  });
})();
</script>
