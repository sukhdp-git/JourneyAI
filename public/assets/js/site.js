/* journzey.ai — public site interactions (no dependencies). */
(function () {
  'use strict';
  var d = document, body = d.body;

  // Sticky header scroll state
  var header = d.querySelector('[data-header]');
  if (header) {
    var onScroll = function () { header.classList.toggle('is-scrolled', window.scrollY > 12); };
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
  }

  // Desktop dropdowns (click/keyboard; hover handled in CSS)
  d.querySelectorAll('.dropdown-toggle').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      var item = btn.closest('.has-dropdown'), open = !item.classList.contains('is-open');
      d.querySelectorAll('.has-dropdown.is-open').forEach(function (o) { o.classList.remove('is-open'); o.querySelector('.dropdown-toggle').setAttribute('aria-expanded', 'false'); });
      item.classList.toggle('is-open', open);
      btn.setAttribute('aria-expanded', String(open));
    });
  });
  d.addEventListener('click', function () {
    d.querySelectorAll('.has-dropdown.is-open').forEach(function (o) { o.classList.remove('is-open'); o.querySelector('.dropdown-toggle').setAttribute('aria-expanded', 'false'); });
  });

  // Mobile menu
  var toggle = d.querySelector('[data-menu-toggle]'), menu = d.querySelector('[data-mobile-menu]');
  function setMenu(open) {
    if (!menu) return;
    menu.hidden = !open;
    toggle.setAttribute('aria-expanded', String(open));
    toggle.querySelector('.sr-only').textContent = open ? 'Close menu' : 'Open menu';
    body.classList.toggle('menu-open', open);
    if (open) { var first = menu.querySelector('a, button'); if (first) first.focus(); }
  }
  if (toggle && menu) {
    toggle.addEventListener('click', function () { setMenu(menu.hidden); });
    d.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && !menu.hidden) { setMenu(false); toggle.focus(); }
    });
    menu.querySelectorAll('.m-sub-toggle').forEach(function (b) {
      b.addEventListener('click', function () {
        var sub = d.getElementById(b.getAttribute('aria-controls')), open = sub.hidden;
        sub.hidden = !open; b.setAttribute('aria-expanded', String(open));
      });
    });
    window.addEventListener('resize', function () { if (window.innerWidth >= 900 && !menu.hidden) setMenu(false); });
  }

  // FAQ accordion (accessible: button + region, aria-expanded)
  d.querySelectorAll('[data-accordion] .faq-q button').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var item = btn.closest('.faq-item'), panel = d.getElementById(btn.getAttribute('aria-controls')), open = btn.getAttribute('aria-expanded') !== 'true';
      btn.setAttribute('aria-expanded', String(open));
      panel.hidden = !open;
      item.classList.toggle('is-open', open);
    });
  });

  // Reveal on scroll
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var els = d.querySelectorAll('.reveal');
  if (!reduce && 'IntersectionObserver' in window && body.classList.contains('has-motion')) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) { if (en.isIntersecting) { en.target.classList.add('is-visible'); io.unobserve(en.target); } });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
    els.forEach(function (el) { io.observe(el); });
  } else {
    els.forEach(function (el) { el.classList.add('is-visible'); });
  }

  // Card spotlight follows the pointer
  if (!reduce) {
    d.querySelectorAll('.service-card').forEach(function (c) {
      c.addEventListener('pointermove', function (e) { var r = c.getBoundingClientRect(); c.style.setProperty('--mx', ((e.clientX - r.left) / r.width * 100) + '%'); });
    });
  }

  // Copy link
  d.querySelectorAll('[data-copy]').forEach(function (b) {
    b.addEventListener('click', function () {
      var label = b.querySelector('.copy-label');
      var done = function () { if (label) { label.textContent = 'Copied'; setTimeout(function () { label.textContent = 'Copy link'; }, 1800); } };
      if (navigator.clipboard) navigator.clipboard.writeText(b.getAttribute('data-copy')).then(done); else done();
    });
  });

  // Client-side form validation (convenience only — the server validates everything again)
  d.querySelectorAll('form[data-validate]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      var firstBad = null;
      form.querySelectorAll('input, select, textarea').forEach(function (f) {
        if (f.type === 'hidden' || f.closest('.hp')) return;
        var wrap = f.closest('.field'); if (!wrap) return;
        var old = wrap.querySelector('.field-error.js'); if (old) old.remove();
        if (!f.checkValidity()) {
          wrap.classList.add('has-error'); f.setAttribute('aria-invalid', 'true');
          var p = d.createElement('p'); p.className = 'field-error js'; p.textContent = f.validationMessage; wrap.appendChild(p);
          firstBad = firstBad || f;
        } else { wrap.classList.remove('has-error'); f.removeAttribute('aria-invalid'); }
      });
      if (firstBad) { e.preventDefault(); firstBad.focus(); return; }
      var btn = form.querySelector('[type=submit]');
      if (btn) { btn.classList.add('is-loading'); if (btn.dataset.loadingText) btn.lastChild.textContent = ' ' + btn.dataset.loadingText; }
    });
  });
})();
