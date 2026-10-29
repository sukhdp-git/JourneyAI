/* journzey.ai — Confessions feed: post, like ($15 of respect each), comment, share as an image, report.
 * The server decides everything (spam shield, limits, counts); this file only renders the answers. */
(function () {
  'use strict';
  var d = document, app = d.querySelector('[data-cf-app]');
  var onboard = d.querySelector('[data-cf-handle-form]');
  if (onboard) {
    var hi = onboard.querySelector('[data-cf-hinput]'), hint = onboard.querySelector('[data-cf-hhint]');
    hi.addEventListener('input', function () {
      hi.value = hi.value.toLowerCase().replace(/[^a-z0-9_.]/g, '');
      var ok = /^[a-z0-9_.]{3,20}$/.test(hi.value) && !/^[._]|[._]$|\.\./.test(hi.value);
      hint.textContent = ok ? 'Looks good: you will appear as @' + hi.value : '3–20 characters: lowercase letters, numbers, dot or underscore.';
      hint.classList.toggle('ok', ok);
    });
  }
  if (!app) return;
  var base = app.dataset.base, reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
  function $(s, c) { return (c || d).querySelector(s); }
  function $$(s, c) { return Array.prototype.slice.call((c || d).querySelectorAll(s)); }
  function toast(m, t) { if (window.tmToast) window.tmToast(m, t); }
  function post(url, data) { return window.tmPost(url, data); }
  function get(url) { return fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }).then(function (r) { return r.json(); }).catch(function () { return { ok: false, error: 'Connection problem. Try again.' }; }); }
  function frag(html) { var t = d.createElement('template'); t.innerHTML = html; return t.content; }

  /* ---------------------------------------------------------------- composer */
  var form = $('[data-cf-compose]');
  if (form) {
    var ta = $('[data-cf-text]', form), count = $('[data-cf-count]', form), shield = $('[data-cf-shield]', form), btn = $('[data-cf-post]', form);
    var max = +ta.getAttribute('maxlength') || 600;
    // Light preview of the server's spam shield, so people see the rule before they press Confess
    var RULES = [
      [/(https?:\/\/|www\.|\b[a-z0-9-]{2,}\.(com|net|org|io|me|co|in|xyz|ly|gg|app|link|site|online|live|vip)\b|t\.me|wa\.me)/i, 'Links are not allowed.'],
      [/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}/i, 'Email addresses are not allowed.'],
      [/(\+?\d[\d\s().-]{8,}\d)/, 'Phone numbers are not allowed.'],
      [/\b(dm|inbox|pm)\s*(me|for)\b|\b(telegram|whats ?app|discord)\b|\b(vip|free|paid)\s+(group|channel|signals?)\b|\bsignals?\s+(group|channel|service)\b|\baccount\s+management\b|\bguaranteed\s+profit\b|\blink\s+in\s+bio\b/i, 'Selling, signals, DMs and groups are blocked.']
    ];
    var prompts = []; try { prompts = JSON.parse(ta.dataset.prompts || '[]'); } catch (e) {}
    var pi = 0; if (prompts.length && !reduce) setInterval(function () { if (!ta.value && d.activeElement !== ta) { pi = (pi + 1) % prompts.length; ta.placeholder = prompts[pi]; } }, 4200);
    var check = function () {
      var v = ta.value, hit = null;
      count.textContent = v.length + ' / ' + max; count.classList.toggle('warn', v.length > max - 40);
      RULES.some(function (r) { if (r[0].test(v)) { hit = r[1]; return true; } return false; });
      shield.hidden = !hit; if (hit) shield.textContent = '🛡️ Spam shield: ' + hit + ' This post would be blocked.';
      ta.style.height = 'auto'; ta.style.height = Math.min(320, ta.scrollHeight + 2) + 'px';
    };
    ta.addEventListener('input', check);
    $$('[data-cf-prompt]').forEach(function (b) { b.addEventListener('click', function () { ta.value = b.dataset.cfPrompt + ' '; check(); ta.focus(); ta.setSelectionRange(ta.value.length, ta.value.length); window.scrollTo({ top: form.getBoundingClientRect().top + scrollY - 90, behavior: reduce ? 'auto' : 'smooth' }); }); });
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      if (ta.value.trim().length < 8) { toast('Write at least a sentence.', 'error'); ta.focus(); return; }
      btn.disabled = true; btn.classList.add('busy');
      post(base, new FormData(form)).then(function (r) {
        btn.disabled = false; btn.classList.remove('busy');
        if (!r.ok) { shield.hidden = false; shield.textContent = '🛡️ ' + (r.error || 'Could not post. Try again.'); shield.classList.add('blocked'); return; }
        shield.hidden = true; shield.classList.remove('blocked');
        var feed = $('[data-cf-feed]'), empty = $('[data-cf-empty]'); if (empty) empty.remove();
        feed.insertBefore(frag(r.html), feed.firstChild);
        form.reset(); check(); toast('Confessed. 🙊', 'success');
        burst($('[data-cf-post]'), '🙊');
      });
    });
    check();
  }

  /* ---------------------------------------------------------------- effects */
  function coin(el, text) {
    if (reduce) return;
    var r = el.getBoundingClientRect(), c = d.createElement('span');
    c.className = 'cf-coin'; c.textContent = text; c.style.left = (r.left + r.width / 2) + 'px'; c.style.top = (r.top + scrollY) + 'px';
    d.body.appendChild(c); setTimeout(function () { c.remove(); }, 1200);
  }
  function burst(el, emo) {
    if (reduce || !el) return;
    var r = el.getBoundingClientRect();
    for (var i = 0; i < 10; i++) {
      var p = d.createElement('span'); p.className = 'cf-spark'; p.textContent = emo || '✦';
      var a = (i / 10) * Math.PI * 2, dist = 40 + Math.random() * 30;
      p.style.left = (r.left + r.width / 2) + 'px'; p.style.top = (r.top + scrollY + r.height / 2) + 'px';
      p.style.setProperty('--dx', Math.cos(a) * dist + 'px'); p.style.setProperty('--dy', Math.sin(a) * dist + 'px');
      d.body.appendChild(p); setTimeout(function (x) { x.remove(); }.bind(null, p), 900);
    }
  }

  /* ---------------------------------------------------------------- card actions (delegated) */
  function cardOf(el) { return el.closest('[data-cf]'); }
  d.addEventListener('click', function (e) {
    var t = e.target.closest('button'); if (!t || !app.contains(t)) { closeMenus(); return; }
    var card = cardOf(t), id = card && card.dataset.cf;
    if (t.hasAttribute('data-cf-like')) {
      var on = !t.classList.contains('on'); t.classList.toggle('on', on); t.setAttribute('aria-pressed', String(on));
      if (on) { coin(t, '+$15'); t.classList.remove('pop'); void t.offsetWidth; t.classList.add('pop'); }
      post(base + '/' + id + '/like', {}).then(function (r) {
        if (!r.ok) { t.classList.toggle('on', !on); toast(r.error || 'Could not like.', 'error'); return; }
        t.classList.toggle('on', r.liked); $('[data-cf-respect]', t).textContent = r.respect;
      });
    } else if (t.hasAttribute('data-cf-comments')) {
      toggleThread(card, t);
    } else if (t.hasAttribute('data-cfc-like')) {
      var c = t.closest('[data-cfc]'), on2 = !t.classList.contains('on'); t.classList.toggle('on', on2);
      if (on2) coin(t, '+$15');
      post(base + '/comments/' + c.dataset.cfc + '/like', {}).then(function (r) { if (r.ok) { t.classList.toggle('on', r.liked); $('[data-cfc-respect]', t).textContent = r.respect; } else t.classList.toggle('on', !on2); });
    } else if (t.hasAttribute('data-cfc-report') || t.hasAttribute('data-cfc-delete')) {
      var cm = t.closest('[data-cfc]'), del = t.hasAttribute('data-cfc-delete');
      if (del && !confirm('Delete this reply?')) return;
      post(base + '/' + cm.dataset.cfc + (del ? '/delete' : '/report'), del ? { type: 'comment' } : { type: 'comment', reason: 'spam' }).then(function (r) {
        if (del && r.ok) { cm.remove(); var n = $('[data-cf-ccount]', card); n.textContent = Math.max(0, +n.textContent - 1); }
        else toast(r.message || (r.ok ? 'Done.' : 'Could not do that.'), r.ok ? 'success' : 'error');
      });
    } else if (t.hasAttribute('data-cf-more')) {
      e.stopPropagation(); var pop = t.nextElementSibling, open = pop.hidden; closeMenus(); pop.hidden = !open; t.setAttribute('aria-expanded', String(open));
    } else if (t.hasAttribute('data-cf-report')) {
      closeMenus();
      post(base + '/' + id + '/report', { type: 'post', reason: t.dataset.cfReport }).then(function (r) { toast(r.message || 'Thanks.', r.ok ? 'success' : 'error'); });
    } else if (t.hasAttribute('data-cf-delete')) {
      closeMenus(); if (!confirm('Delete this confession? This cannot be undone.')) return;
      post(base + '/' + id + '/delete', { type: 'post' }).then(function (r) { if (r.ok) { card.classList.add('gone'); setTimeout(function () { card.remove(); }, 300); } });
    } else if (t.hasAttribute('data-cf-copy')) {
      closeMenus(); copy(location.origin + card.dataset.cfUrl.replace(location.origin, ''));
    } else if (t.hasAttribute('data-cf-share')) {
      share(card, t);
    }
  });
  function closeMenus() { $$('.cf-pop', app).forEach(function (p) { p.hidden = true; }); $$('[data-cf-more]', app).forEach(function (b) { b.setAttribute('aria-expanded', 'false'); }); }
  function copy(text) {
    var done = function () { toast('Link copied. Only members can open it.', 'success'); };
    if (navigator.clipboard) navigator.clipboard.writeText(text).then(done, function () { prompt('Copy this link', text); }); else prompt('Copy this link', text);
  }

  /* ---------------------------------------------------------------- comments */
  function toggleThread(card, btn) {
    var th = $('[data-cf-thread]', card), open = th.hidden;
    th.hidden = !open; btn.setAttribute('aria-expanded', String(open));
    if (!open || th.dataset.loaded) { if (open) $('input', th).focus(); return; }
    var list = $('[data-cf-clist]', th); list.innerHTML = '<p class="muted small cf-loading">Loading replies…</p>';
    get(base + '/' + card.dataset.cf + '/comments').then(function (r) {
      th.dataset.loaded = '1';
      list.innerHTML = r.ok && r.html ? r.html : '<p class="muted small">No replies yet. Be the trader who helps.</p>';
      $('input', th).focus();
    });
  }
  d.addEventListener('submit', function (e) {
    var f = e.target.closest('[data-cf-cform]'); if (!f) return;
    e.preventDefault();
    var card = cardOf(f), inp = $('input', f), v = inp.value.trim(); if (v.length < 2) return;
    var b = $('button', f); b.disabled = true;
    post(base + '/' + card.dataset.cf + '/comments', { body: v }).then(function (r) {
      b.disabled = false;
      if (!r.ok) { toast('🛡️ ' + (r.error || 'Could not reply.'), 'error'); return; }
      var list = $('[data-cf-clist]', card), p = $('p.muted', list); if (p) p.remove();
      list.appendChild(frag(r.html)); inp.value = '';
      var n = $('[data-cf-ccount]', card); n.textContent = +n.textContent + 1;
    });
  });

  /* ---------------------------------------------------------------- share as an image (1080×1350) */
  function wrap(ctx, text, maxW) {
    var out = []; String(text).split(/\n/).forEach(function (para) {
      var line = ''; para.split(/\s+/).forEach(function (w) { var t = line ? line + ' ' + w : w; if (ctx.measureText(t).width > maxW && line) { out.push(line); line = w; } else line = t; });
      out.push(line);
    }); return out;
  }
  function render(card) {
    var W = 1080, H = 1350, c = d.createElement('canvas'); c.width = W; c.height = H; var x = c.getContext('2d');
    var g = x.createLinearGradient(0, 0, W, H); g.addColorStop(0, '#0b0820'); g.addColorStop(1, '#1a0f2e'); x.fillStyle = g; x.fillRect(0, 0, W, H);
    var r1 = x.createRadialGradient(220, 160, 10, 220, 160, 600); r1.addColorStop(0, 'rgba(124,92,255,.45)'); r1.addColorStop(1, 'rgba(124,92,255,0)'); x.fillStyle = r1; x.fillRect(0, 0, W, H);
    var r2 = x.createRadialGradient(900, 1200, 10, 900, 1200, 600); r2.addColorStop(0, 'rgba(254,178,180,.35)'); r2.addColorStop(1, 'rgba(254,178,180,0)'); x.fillStyle = r2; x.fillRect(0, 0, W, H);
    x.fillStyle = 'rgba(255,255,255,.06)'; x.strokeStyle = 'rgba(255,255,255,.14)'; x.lineWidth = 2;
    x.beginPath(); x.roundRect ? x.roundRect(70, 230, W - 140, H - 430, 44) : x.rect(70, 230, W - 140, H - 430); x.fill(); x.stroke();
    x.font = '700 30px Inter, sans-serif'; x.fillStyle = '#c4b5ff'; x.fillText('CONFESSION #' + card.dataset.cf, 110, 150);
    var tg = x.createLinearGradient(110, 0, 600, 0); tg.addColorStop(0, '#8b6bff'); tg.addColorStop(1, '#feb2b4'); x.fillStyle = tg; x.font = '800 54px Inter, sans-serif'; x.fillText('journzey', W - 110 - x.measureText('journzey').width, 160);
    x.font = '600 34px Inter, sans-serif'; x.fillStyle = '#f3f1ff'; x.fillText('@' + card.dataset.cfHandle, 120, 310); x.fillStyle = '#b9b5d6'; x.font = '600 30px Inter, sans-serif';
    var tag = card.dataset.cfTag || ''; x.fillText(tag, W - 120 - x.measureText(tag).width, 310);
    var body = ($('[data-cf-body]', card).innerText || '').trim(), size = body.length > 380 ? 40 : body.length > 220 ? 48 : body.length > 120 ? 56 : 64;
    x.font = '600 ' + size + 'px Inter, sans-serif'; x.fillStyle = '#ffffff';
    var lines = wrap(x, '“' + body + '”', W - 260), lh = size * 1.32, maxL = Math.floor((H - 760) / lh);
    if (lines.length > maxL) { lines = lines.slice(0, maxL); lines[maxL - 1] += '…'; }
    lines.forEach(function (l, i) { x.fillText(l, 120, 410 + i * lh); });
    if (card.dataset.cfTrade) {
      var up = /\+/.test(card.dataset.cfTrade.split('·')[1] || ''); x.font = '700 36px "JetBrains Mono", monospace';
      var tt = card.dataset.cfTrade + (card.dataset.cfDemo === '1' ? ' · demo' : ''), tw = x.measureText(tt).width + 60;
      x.fillStyle = up ? 'rgba(52,211,153,.16)' : 'rgba(248,113,113,.16)'; x.beginPath(); x.roundRect ? x.roundRect(110, H - 330, tw, 74, 37) : x.rect(110, H - 330, tw, 74); x.fill();
      x.fillStyle = up ? '#34d399' : '#f87171'; x.fillText(tt, 140, H - 280);
    }
    var rs = ($('[data-cf-respect]', card) || {}).textContent || '$0';
    x.font = '800 46px Inter, sans-serif'; x.fillStyle = '#ff6b8b'; x.fillText('♥ ' + rs + ' of respect', 110, H - 120);
    x.font = '500 26px Inter, sans-serif'; x.fillStyle = '#9d9abb'; x.fillText('Anonymous trader confessions · journzey.ai', 110, H - 68);
    return c;
  }
  function share(card, btn) {
    btn.disabled = true;
    var c = render(card);
    c.toBlob(function (blob) {
      btn.disabled = false;
      post(base + '/' + card.dataset.cf + '/share', {});
      var file = new File([blob], 'journzey-confession-' + card.dataset.cf + '.png', { type: 'image/png' });
      if (navigator.canShare && navigator.canShare({ files: [file] })) {
        navigator.share({ files: [file], title: 'A trader confession', text: 'Trader confessions on journzey' }).catch(function () {});
      } else {
        var a = d.createElement('a'); a.href = URL.createObjectURL(blob); a.download = file.name; d.body.appendChild(a); a.click(); setTimeout(function () { URL.revokeObjectURL(a.href); a.remove(); }, 400);
        toast('Share card downloaded. Post it to your story.', 'success');
      }
    }, 'image/png');
  }

  /* ---------------------------------------------------------------- load more */
  var more = $('[data-cf-loadmore]');
  if (more) {
    var loading = false, load = function () {
      if (loading || more.hidden) return; loading = true; more.textContent = 'Loading…';
      var q = new URLSearchParams({ tab: app.dataset.tab || 'latest', before: more.dataset.next }); if (app.dataset.tagf) q.set('tag', app.dataset.tagf);
      get(base + '/more?' + q.toString()).then(function (r) {
        loading = false; more.textContent = 'Load more';
        if (!r.ok) return;
        $('[data-cf-feed]').appendChild(frag(r.html)); more.dataset.next = r.next; more.hidden = !r.more;
      });
    };
    more.addEventListener('click', load);
    if ('IntersectionObserver' in window) new IntersectionObserver(function (es) { if (es[0].isIntersecting) load(); }, { rootMargin: '300px' }).observe(more);
  }

  // single-post page: open the replies straight away
  var open = $('[data-cf-open]');
  if (open) { var cd = $('[data-cf="' + open.dataset.cfOpen + '"]'); if (cd) toggleThread(cd, $('[data-cf-comments]', cd)); }
})();
