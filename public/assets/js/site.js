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
    // Content is only hidden once the observer is running, so a failed script never leaves sections invisible.
    d.documentElement.classList.add('motion-ready');
    els.forEach(function (el) { io.observe(el); });
  } else {
    els.forEach(function (el) { el.classList.add('is-visible'); });
  }

  // Hero product shot: gentle 3D tilt that follows the pointer
  if (!reduce) d.querySelectorAll('[data-tilt]').forEach(function (el) {
    el.addEventListener('pointermove', function (e) { var r = el.getBoundingClientRect(), x = (e.clientX - r.left) / r.width - 0.5, y = (e.clientY - r.top) / r.height - 0.5; el.style.setProperty('--ry', (x * 8).toFixed(2) + 'deg'); el.style.setProperty('--rx', (-y * 6).toFixed(2) + 'deg'); });
    el.addEventListener('pointerleave', function () { el.style.setProperty('--ry', '0deg'); el.style.setProperty('--rx', '0deg'); });
  });
  // Showcase stage: the tilted screen straightens and the cards slide in as it scrolls into view
  var stages = d.querySelectorAll('[data-sc-stage]');
  if (stages.length) {
    var ticking = false, upd = function () {
      ticking = false;
      stages.forEach(function (st) { var r = st.getBoundingClientRect(), vh = window.innerHeight; var p = reduce ? 1 : Math.max(0, Math.min(1, (vh - r.top) / (vh * 0.75))); st.style.setProperty('--p', p.toFixed(3)); });
    };
    window.addEventListener('scroll', function () { if (!ticking) { ticking = true; requestAnimationFrame(upd); } }, { passive: true });
    window.addEventListener('resize', upd); upd();
  }
  // Product tour: auto-plays through the tabs; pauses on hover/focus; click to jump
  d.querySelectorAll('[data-sc-tour]').forEach(function (tour) {
    var tabs = tour.querySelectorAll('[data-sc-tab]'), panels = tour.querySelectorAll('.sc-panel'), cur = 0, timer = null, DUR = 7000, paused = false;
    tour.style.setProperty('--sc-dur', DUR / 1000 + 's');
    function show(i, focus) {
      cur = (i + tabs.length) % tabs.length;
      tabs.forEach(function (t, k) { var on = k === cur; t.classList.toggle('on', on); t.setAttribute('aria-selected', on ? 'true' : 'false'); t.tabIndex = on ? 0 : -1; var pr = t.querySelector('.sc-progress'); if (pr) { pr.style.animation = 'none'; void pr.offsetWidth; pr.style.animation = ''; } });
      panels.forEach(function (p, k) { p.hidden = k !== cur; p.classList.toggle('on', k === cur); });
      if (focus) tabs[cur].focus();
      schedule();
    }
    function schedule() { clearTimeout(timer); if (!reduce && !paused) timer = setTimeout(function () { show(cur + 1); }, DUR); }
    tabs.forEach(function (t, k) {
      t.addEventListener('click', function () { show(k); });
      t.addEventListener('keydown', function (e) { if (e.key === 'ArrowDown' || e.key === 'ArrowRight') { e.preventDefault(); show(cur + 1, true); } if (e.key === 'ArrowUp' || e.key === 'ArrowLeft') { e.preventDefault(); show(cur - 1, true); } });
    });
    tour.addEventListener('pointerenter', function () { paused = true; tour.classList.add('paused'); clearTimeout(timer); });
    tour.addEventListener('pointerleave', function () { paused = false; tour.classList.remove('paused'); schedule(); });
    if (!reduce) {
      if ('IntersectionObserver' in window) { var tio = new IntersectionObserver(function (es) { es.forEach(function (e) { if (e.isIntersecting) { tour.classList.add('playing'); show(cur); } else { clearTimeout(timer); } }); }, { threshold: 0.3 }); tio.observe(tour); }
      else { tour.classList.add('playing'); schedule(); }
    }
  });

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

/* P&L scroll story + 3D tilt cards */
(function () {
  var d = document, reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  // 3D tilt: rotate towards the cursor, measured from the card centre, with a glare that follows it
  if (!reduce && window.matchMedia('(hover: hover)').matches) {
    d.querySelectorAll('.service-card, .price-card, .plan-card').forEach(function (el) { el.setAttribute('data-tilt3d', ''); });
    d.querySelectorAll('[data-tilt3d]').forEach(function (el) {
      var raf = 0;
      el.addEventListener('pointermove', function (e) {
        cancelAnimationFrame(raf);
        raf = requestAnimationFrame(function () {
          var r = el.getBoundingClientRect(), x = (e.clientX - r.left - r.width / 2) / (r.width / 2), y = (e.clientY - r.top - r.height / 2) / (r.height / 2);
          el.classList.add('tilting');
          el.style.setProperty('--ry', (x * 9).toFixed(2) + 'deg'); el.style.setProperty('--rx', (-y * 7).toFixed(2) + 'deg');
          el.style.setProperty('--gx', ((x + 1) * 50).toFixed(1) + '%'); el.style.setProperty('--gy', ((y + 1) * 50).toFixed(1) + '%');
        });
      });
      el.addEventListener('pointerleave', function () { cancelAnimationFrame(raf); el.classList.remove('tilting'); el.style.setProperty('--ry', '0deg'); el.style.setProperty('--rx', '0deg'); });
    });
  }
  // Scroll story: P&L moves red -> breakeven -> profit as the section scrolls
  d.querySelectorAll('[data-pnl-story]').forEach(function (sec) {
    var num = sec.querySelector('[data-ps-num]'), vals = JSON.parse(num.getAttribute('data-values')), steps = sec.querySelectorAll('[data-ps-step]'),
        tags = sec.querySelectorAll('[data-ps-tag]'), pill = sec.querySelector('[data-ps-pill]'), card = sec.querySelector('.ps-card'),
        line = sec.querySelector('[data-ps-line]'), area = sec.querySelector('[data-ps-area]'), dot = sec.querySelector('[data-ps-dot]');
    var W = 600, Z = 150, N = 40, seed = 3, rnd = function () { seed = seed * 16807 % 2147483647; return seed / 2147483647; }, noise = [];
    for (var i = 0; i <= N; i++) noise.push((rnd() - .5) * 26);
    var fmt = function (v) { var a = Math.abs(Math.round(v)).toLocaleString('en-US'); return (v < -0.5 ? '−$' : v > 0.5 ? '+$' : '$') + a; };
    var col = function (v) { return v < -300 ? 'var(--dn)' : v < 300 ? 'var(--flat)' : 'var(--up)'; };
    function draw(p) {
      var seg = p * (vals.length - 1), k = Math.min(vals.length - 2, Math.floor(seg)), f = seg - k, ease = f * f * (3 - 2 * f), v = vals[k] + (vals[k + 1] - vals[k]) * ease;
      num.textContent = fmt(v); card.style.setProperty('--c', col(v));
      var step = Math.min(vals.length - 1, Math.round(seg));
      steps.forEach(function (s, j) { s.classList.toggle('on', j === step); });
      tags.forEach(function (t, j) { t.classList.toggle('on', j <= step); });
      pill.textContent = steps[step].querySelector('.ps-week').textContent;
      // curve: dips first, then climbs to the current value; drawn up to the current progress
      var upto = Math.max(2, Math.round(N * (0.25 + 0.75 * p))), pts = [];
      for (var i = 0; i <= upto; i++) {
        var t = i / N, base = t < .3 ? -Math.sin(t / .3 * Math.PI / 2) * 95 : -95 + (t - .3) / .7 * (95 + v / 2180 * 120);
        pts.push([i / N * W, Z - base - noise[i] * (0.6 + 0.4 * (1 - p))]);
      }
      var dpath = pts.map(function (q, j) { return (j ? 'L' : 'M') + q[0].toFixed(1) + ' ' + q[1].toFixed(1); }).join(' ');
      line.setAttribute('d', dpath); area.setAttribute('d', dpath + ' L' + pts[pts.length - 1][0].toFixed(1) + ' 260 L0 260 Z');
      dot.setAttribute('cx', pts[pts.length - 1][0]); dot.setAttribute('cy', pts[pts.length - 1][1]);
    }
    if (reduce) { draw(1); return; }
    var ticking = false;
    function upd() { ticking = false; var r = sec.getBoundingClientRect(), total = r.height - window.innerHeight; draw(Math.max(0, Math.min(1, -r.top / (total || 1)))); }
    window.addEventListener('scroll', function () { if (!ticking) { ticking = true; requestAnimationFrame(upd); } }, { passive: true });
    window.addEventListener('resize', upd); upd();
  });
})();
