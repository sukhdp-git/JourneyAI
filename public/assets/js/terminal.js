/* journzey.ai terminal — vanilla JS. All data is saved by the server; nothing authoritative lives in the browser. */
(function () {
  'use strict';
  var d = document, body = d.body;
  var csrf = (d.querySelector('meta[name="csrf-token"]') || {}).content || '';
  var base = (d.querySelector('meta[name="app-base"]') || {}).content || '/terminal';
  function $(s, c) { return (c || d).querySelector(s); }
  function $$(s, c) { return Array.prototype.slice.call((c || d).querySelectorAll(s)); }
  function txt(tag, cls, t) { var n = d.createElement(tag); if (cls) n.className = cls; if (t !== undefined) n.textContent = t; return n; }
  function toast(msg, type) { var box = $('[data-toasts]'); if (!box) return; var t = txt('div', 'tm-toast ' + (type || ''), msg); t.setAttribute('role', type === 'error' ? 'alert' : 'status'); box.appendChild(t); setTimeout(function () { t.remove(); }, type === 'error' ? 8000 : 4000); }
  window.tmToast = toast;
  function post(url, data) {
    var fd = data instanceof FormData ? data : new FormData();
    if (!(data instanceof FormData)) Object.keys(data || {}).forEach(function (k) { fd.append(k, data[k]); });
    fd.append('_csrf', csrf);
    return fetch(url, { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'X-CSRF-Token': csrf, 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' } })
      .then(function (r) { return r.json().catch(function () { return { ok: false, error: 'Unexpected server response (' + r.status + ').' }; }).then(function (j) { if (r.status === 401) location.href = '/login'; return j; }); });
  }
  window.tmPost = post;

  // Sidebar, user menu
  $$('[data-side-open]').forEach(function (b) { b.addEventListener('click', function () { body.classList.add('side-open'); }); });
  $$('[data-side-close]').forEach(function (b) { b.addEventListener('click', function () { body.classList.remove('side-open'); }); });
  $$('[data-menu]').forEach(function (m) {
    var b = $('[data-menu-toggle]', m);
    b.addEventListener('click', function (e) { e.stopPropagation(); var o = !m.classList.contains('open'); m.classList.toggle('open', o); b.setAttribute('aria-expanded', String(o)); });
    d.addEventListener('click', function () { m.classList.remove('open'); b.setAttribute('aria-expanded', 'false'); });
  });
  $$('[data-autosubmit]').forEach(function (s) { s.addEventListener('change', function () { s.form.submit(); }); });

  // Modals
  var lastFocus;
  function open(m) { lastFocus = d.activeElement; m.hidden = false; var f = $('input, textarea, select, button:not([data-close])', m); if (f) f.focus(); }
  function close(m) { m.hidden = true; if (lastFocus && lastFocus.focus) lastFocus.focus(); }
  $$('.tm-modal').forEach(function (m) { $$('[data-close]', m).forEach(function (b) { b.addEventListener('click', function () { close(m); }); }); });
  d.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') { var m = $('.tm-modal:not([hidden])'); if (m) close(m); body.classList.remove('side-open'); }
    if (e.key === '/' && !/INPUT|TEXTAREA|SELECT/.test((d.activeElement || {}).tagName || '') && $('#tm-quick')) { e.preventDefault(); open($('#tm-quick')); }
  });
  window.tmOpen = open; window.tmClose = close;

  // Confirm dialogs for destructive forms: <form data-confirm="…">
  var cm = $('#tm-confirm'), pending = null;
  d.addEventListener('submit', function (e) {
    var f = e.target; if (!f.matches || !f.matches('form[data-confirm]') || f.dataset.confirmed) return;
    e.preventDefault(); pending = f; $('[data-confirm-text]', cm).textContent = f.dataset.confirm; open(cm);
  }, true);
  if (cm) $('[data-confirm-ok]', cm).addEventListener('click', function () { if (pending) { pending.dataset.confirmed = '1'; close(cm); pending.submit(); } });

  // Quick trade command
  $$('[data-open-quick]').forEach(function (b) { b.addEventListener('click', function () { body.classList.remove('side-open'); open($('#tm-quick')); }); });
  $$('[data-quick-form]').forEach(function (form) {
    var input = $('[data-quick-input]', form), prev = $('[data-quick-preview]', form), timer, last = null;
    function chip(t, cls) { prev.appendChild(txt('span', 'chip' + (cls ? ' ' + cls : ''), t)); }
    function render(p) {
      prev.textContent = '';
      if (p.symbol) chip(p.symbol); if (p.side) chip(p.side, p.side.toLowerCase());
      if (p.entry !== null) chip('Entry ' + p.entry); if (p.stop !== null) chip('SL ' + p.stop); if (p.tp !== null) chip('TP ' + p.tp);
      if (p.exit !== null) chip('Exit ' + p.exit); if (p.rr !== null) chip((p.rr > 0 ? '+' : '') + p.rr + 'R'); chip(Number(p.lots).toFixed(2) + ' Lot');
      if (p.setup) chip(p.setup); if (p.mistake) chip('#' + p.mistake); if (p.emotion) chip(p.emotion); if (p.pnl !== null && p.pnl !== undefined) chip('P&L ' + p.pnl_fmt);
      (p.errors || []).forEach(function (m) { prev.appendChild(txt('span', 'err', '✕ ' + m)); });
      (p.warnings || []).forEach(function (m) { prev.appendChild(txt('span', 'warn', '! ' + m)); });
    }
    input.addEventListener('input', function () {
      clearTimeout(timer);
      if (!input.value.trim()) { prev.textContent = ''; return; }
      timer = setTimeout(function () { post(base + '/quick-trade/parse', { command: input.value }).then(function (r) { if (r.parsed) { last = r.parsed; render(r.parsed); } else if (r.error) { prev.textContent = r.error; } }); }, 180);
    });
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var btn = $('[type=submit]', form); btn.classList.add('busy');
      post(base + '/quick-trade', { command: input.value }).then(function (r) {
        btn.classList.remove('busy');
        if (r.ok) { toast(r.message, 'success'); input.value = ''; prev.textContent = ''; setTimeout(function () { location.reload(); }, 600); }
        else { toast(r.error || 'Could not log the trade.', 'error'); if (r.parsed) render(r.parsed); }
      });
    });
  });

  // Discipline checklist (persisted server-side)
  $$('[data-checklist] input[type=checkbox]').forEach(function (c) {
    c.addEventListener('change', function () {
      post(base + '/checklist', { item: c.value, done: c.checked ? '1' : '0', date: c.dataset.date }).then(function (r) {
        if (!r.ok) { c.checked = !c.checked; toast(r.error || 'Could not save.', 'error'); return; }
        var bar = $('[data-check-progress]'); if (bar) { bar.style.width = r.pct + '%'; bar.parentNode.setAttribute('aria-valuenow', r.pct); }
        var lbl = $('[data-check-count]'); if (lbl) lbl.textContent = r.done + ' / ' + r.total;
      });
    });
  });

  // World clocks
  var clocks = $$('[data-clock]');
  function tick() {
    var now = new Date();
    clocks.forEach(function (c) {
      var tz = c.dataset.clock, parts = new Intl.DateTimeFormat('en-GB', { timeZone: tz, hour: '2-digit', minute: '2-digit', second: '2-digit', weekday: 'short', hourCycle: 'h23' }).formatToParts(now);
      var g = function (t) { return (parts.find(function (p) { return p.type === t; }) || {}).value; };
      var mins = +g('hour') * 60 + +g('minute'), wd = g('weekday'), open = wd !== 'Sat' && wd !== 'Sun' && mins >= +c.dataset.open && mins < +c.dataset.close;
      $('strong', c).textContent = g('hour') + ':' + g('minute') + ':' + g('second');
      var st = $('.st', c); st.textContent = open ? 'OPEN' : 'CLOSED'; st.className = 'st ' + (open ? 'open' : 'closed');
    });
  }
  if (clocks.length) { tick(); setInterval(tick, 1000); }

  // Market ticker refresh
  var ticker = $('[data-ticker]');
  if (ticker && ticker.dataset.live === '1') setInterval(function () {
    fetch(base + '/ticker', { credentials: 'same-origin', headers: { Accept: 'application/json' } }).then(function (r) { return r.json(); }).then(function (j) {
      (j.quotes || []).forEach(function (q) { var it = ticker.querySelector('[data-sym="' + q.symbol + '"]'); if (!it) return; $('strong', it).textContent = q.price; var em = $('em', it); em.textContent = q.change; em.className = q.up ? 'up' : 'down'; });
    }).catch(function () {});
  }, 60000);

  // Lot size calculator
  $$('[data-lot-form]').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      e.preventDefault();
      post(base + '/lot-size', new FormData(f)).then(function (r) { var out = $('[data-lot-out]', f); out.textContent = r.ok ? r.lots + ' lots · risk ' + r.risk_amount + ' · ' + r.per_lot_risk + ' per lot' : (r.error || 'Cannot calculate.'); });
    });
  });

  // Tilt cooldown countdown
  $$('[data-countdown]').forEach(function (c) {
    var end = +c.dataset.countdown * 1000;
    (function upd() { var s = Math.max(0, Math.round((end - Date.now()) / 1000)); c.textContent = Math.floor(s / 60) + ':' + ('0' + (s % 60)).slice(-2); if (s > 0) setTimeout(upd, 1000); else location.reload(); })();
  });

  // Monte Carlo risk slider
  var mc = $('[data-mc]');
  if (mc) {
    var slider = $('input[type=range]', mc), out = $('[data-mc-risk]', mc), t2;
    var run = function () {
      out.textContent = Number(slider.value).toFixed(2) + '%';
      clearTimeout(t2);
      t2 = setTimeout(function () {
        mc.classList.add('busy');
        post(base + '/edge/monte-carlo', { risk: slider.value }).then(function (r) {
          mc.classList.remove('busy');
          if (!r.ok) { toast(r.error, 'error'); return; }
          r.prob.forEach(function (p) { var e = mc.querySelector('[data-p="' + p.dd + '"]'); if (e) e.textContent = (p.p * 100).toFixed(1) + '%'; });
          $('[data-mc-median]', mc).textContent = (r.median_return * 100).toFixed(1) + '%';
          $('[data-mc-range]', mc).textContent = (r.p5_return * 100).toFixed(1) + '% to ' + (r.p95_return * 100).toFixed(1) + '%';
          $('[data-mc-dd]', mc).textContent = (r.median_max_dd * 100).toFixed(1) + '%';
          var ch = $('.chart', mc); ch.dataset.json = JSON.stringify(r.bands); window.jzChart(ch);
        });
      }, 250);
    };
    slider.addEventListener('input', run);
  }

  // AI Coach chat
  var chat = $('[data-chat-form]');
  if (chat) {
    var log = $('[data-chat-log]'), ta = $('textarea', chat);
    var add = function (role, text, meta) { var m = txt('div', 'msg ' + role, text); if (meta) m.appendChild(txt('small', '', meta)); log.appendChild(m); log.scrollTop = log.scrollHeight; return m; };
    $$('[data-prompt]').forEach(function (b) { b.addEventListener('click', function () { ta.value = b.dataset.prompt; ta.focus(); }); });
    ta.addEventListener('keydown', function (e) { if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) chat.requestSubmit(); });
    chat.addEventListener('submit', function (e) {
      e.preventDefault();
      var q = ta.value.trim(); if (!q) return;
      add('user', q); ta.value = '';
      var wait = add('assistant', 'Analysing your journal…'); var btn = $('[type=submit]', chat); btn.classList.add('busy');
      post(base + '/coach/send', { message: q, conversation_id: chat.dataset.conversation || '', lang: ($('[name=lang]', chat) || {}).value || 'en' }).then(function (r) {
        btn.classList.remove('busy'); wait.remove();
        if (!r.ok) { add('assistant', r.error || 'AI Coach is unavailable right now. Your journal and analytics remain available.'); return; }
        add('assistant', r.reply, r.meta); if (r.conversation_id && !chat.dataset.conversation) { chat.dataset.conversation = r.conversation_id; history.replaceState(null, '', base + '/coach/' + r.conversation_id); }
        if (r.remaining !== undefined) { var rem = $('[data-ai-remaining]'); if (rem) rem.textContent = r.remaining; }
      });
    });
  }
  $$('[data-review]').forEach(function (b) {
    b.addEventListener('click', function () {
      var out = $('[data-review-out]'); b.classList.add('busy'); out.textContent = 'Generating ' + b.dataset.review + ' review…';
      post(base + '/coach/review', { period: b.dataset.review }).then(function (r) {
        b.classList.remove('busy'); out.textContent = r.ok ? r.text : (r.error || 'Could not generate the review.');
        $$('[data-review-actions]').forEach(function (a) { a.hidden = !r.ok; });
      });
    });
  });
  $$('[data-copy-target]').forEach(function (b) { b.addEventListener('click', function () { var t = $(b.dataset.copyTarget); if (t && navigator.clipboard) navigator.clipboard.writeText(t.textContent).then(function () { toast('Copied to clipboard.', 'success'); }); }); });
  $$('[data-print]').forEach(function (b) { b.addEventListener('click', function () { window.print(); }); });

  // Voice dictation (Web Speech API) for the Daily Notepad
  $$('[data-dictate]').forEach(function (b) {
    var SR = window.SpeechRecognition || window.webkitSpeechRecognition, target = $(b.dataset.dictate);
    if (!SR) { b.disabled = true; b.title = 'Voice dictation is not supported in this browser (try Chrome or Edge).'; var n = $('[data-dictate-note]'); if (n) n.hidden = false; return; }
    var rec = null;
    b.addEventListener('click', function () {
      if (rec) { rec.stop(); return; }
      rec = new SR(); rec.lang = ($('[data-voice-lang]') || {}).value || 'en-US'; rec.continuous = true; rec.interimResults = false;
      rec.onresult = function (ev) { for (var i = ev.resultIndex; i < ev.results.length; i++) if (ev.results[i].isFinal) target.value += (target.value && !/\s$/.test(target.value) ? ' ' : '') + ev.results[i][0].transcript.trim(); target.dispatchEvent(new Event('input')); };
      rec.onerror = function (ev) { toast('Dictation error: ' + ev.error, 'error'); };
      rec.onend = function () { rec = null; b.classList.remove('rec'); b.textContent = '🎙 Dictate'; };
      rec.start(); b.classList.add('rec'); b.textContent = '■ Stop';
    });
  });
  // Local draft cache for the notepad (prevents accidental loss; the server copy is authoritative)
  $$('[data-draft-key]').forEach(function (ta) {
    var k = 'jz-draft-' + ta.dataset.draftKey;
    try { var v = localStorage.getItem(k); if (v && !ta.value) { ta.value = v; toast('Restored an unsaved draft.', ''); } } catch (e) {}
    ta.addEventListener('input', function () { try { localStorage.setItem(k, ta.value); } catch (e) {} });
    if (ta.form) ta.form.addEventListener('submit', function () { try { localStorage.removeItem(k); } catch (e) {} });
  });

  // Screenshot upload: picker, drag & drop and clipboard paste
  $$('[data-shot-form]').forEach(function (f) {
    var input = $('input[type=file]', f);
    var send = function (file) {
      if (!file) return; if (file.size > 5 * 1024 * 1024) { toast('Screenshots must be 5 MB or smaller.', 'error'); return; }
      var fd = new FormData(); fd.append('screenshot', file, file.name || 'screenshot.png');
      toast('Uploading screenshot…'); post(f.action, fd).then(function (r) { toast(r.message || r.error, r.ok ? 'success' : 'error'); if (r.ok) setTimeout(function () { location.reload(); }, 500); });
    };
    input.addEventListener('change', function () { send(input.files[0]); });
    ['dragover', 'dragenter'].forEach(function (ev) { f.addEventListener(ev, function (e) { e.preventDefault(); f.classList.add('over'); }); });
    ['dragleave', 'drop'].forEach(function (ev) { f.addEventListener(ev, function (e) { e.preventDefault(); f.classList.remove('over'); }); });
    f.addEventListener('drop', function (e) { send(e.dataTransfer.files[0]); });
    d.addEventListener('paste', function (e) { var it = Array.prototype.find.call((e.clipboardData || {}).items || [], function (i) { return i.type.indexOf('image') === 0; }); if (it) send(it.getAsFile()); });
  });

  // Share cards (canvas → PNG). No account identifiers are drawn.
  var THEMES = { neon: ['#03121a', '#22d3ee', '#a5f3fc'], matrix: ['#020d06', '#22c55e', '#bbf7d0'], amethyst: ['#12061f', '#a855f7', '#e9d5ff'], gold: ['#120d02', '#f0b429', '#fde68a'] };
  var card = $('[data-share]');
  if (card) {
    var data = JSON.parse(card.dataset.share), canvas = $('canvas', card), cx = canvas.getContext('2d');
    var draw = function (theme) {
      var c = THEMES[theme] || THEMES.neon, W = canvas.width, H = canvas.height;
      cx.fillStyle = c[0]; cx.fillRect(0, 0, W, H);
      var g = cx.createLinearGradient(0, 0, W, H); g.addColorStop(0, c[1] + '33'); g.addColorStop(1, 'transparent'); cx.fillStyle = g; cx.fillRect(0, 0, W, H);
      cx.strokeStyle = c[1] + '55'; cx.lineWidth = 1; for (var x = 0; x < W; x += 40) { cx.beginPath(); cx.moveTo(x, 0); cx.lineTo(x, H); cx.stroke(); } for (var y = 0; y < H; y += 40) { cx.beginPath(); cx.moveTo(0, y); cx.lineTo(W, y); cx.stroke(); }
      cx.strokeStyle = c[1]; cx.lineWidth = 4; cx.strokeRect(14, 14, W - 28, H - 28);
      cx.fillStyle = c[2]; cx.font = '600 28px "JetBrains Mono", monospace'; cx.fillText(data.symbol, 50, 86);
      cx.fillStyle = data.side === 'LONG' ? '#22c55e' : '#f05252'; cx.fillText(data.side, 50 + cx.measureText(data.symbol + '  ').width, 86);
      cx.fillStyle = '#ffffff'; cx.font = '700 84px "JetBrains Mono", monospace'; cx.fillText(data.pnl, 50, 200);
      cx.fillStyle = c[1]; cx.font = '600 40px "JetBrains Mono", monospace'; cx.fillText(data.r, 50, 256);
      cx.font = '400 22px "JetBrains Mono", monospace'; cx.fillStyle = c[2];
      [['ENTRY', data.entry], ['EXIT', data.exit], ['STOP', data.stop], ['LOTS', data.lots], ['STRATEGY', data.strategy], ['SESSION', data.session]].forEach(function (row, i) {
        var xx = 50 + (i % 3) * 330, yy = 330 + Math.floor(i / 3) * 70; cx.globalAlpha = .65; cx.fillText(row[0], xx, yy); cx.globalAlpha = 1; cx.fillText(String(row[1]).slice(0, 18), xx, yy + 30);
      });
      cx.fillStyle = c[1]; cx.font = '700 24px "Plus Jakarta Sans", sans-serif'; cx.fillText(data.brand, 50, H - 50);
      if (data.demo) { cx.fillStyle = '#fab219'; cx.font = '700 20px "JetBrains Mono", monospace'; cx.fillText('DEMO DATA', W - 200, H - 50); }
    };
    var sel = $('[name=card_theme]:checked', card) ? $('[name=card_theme]:checked', card).value : 'neon';
    draw(sel);
    $$('[name=card_theme]', card).forEach(function (r) { r.addEventListener('change', function () { draw(r.value); }); });
    $('[data-card-download]', card).addEventListener('click', function () { var a = d.createElement('a'); a.download = 'journzey-trade-' + data.symbol + '.png'; a.href = canvas.toDataURL('image/png'); a.click(); });
    var cp = $('[data-card-copy]', card);
    if (!window.ClipboardItem || !navigator.clipboard || !navigator.clipboard.write) cp.disabled = true;
    else cp.addEventListener('click', function () { canvas.toBlob(function (b) { navigator.clipboard.write([new ClipboardItem({ 'image/png': b })]).then(function () { toast('Card copied to clipboard.', 'success'); }, function () { toast('Your browser blocked clipboard access.', 'error'); }); }); });
  }

  $$('[data-runner]').forEach(function (b) {
    b.addEventListener('click', function () { var out = $('[data-runner-out]'); b.classList.add('busy'); out.textContent = 'Fetching price history…';
      post(b.dataset.runner).then(function (r) { b.classList.remove('busy'); out.textContent = r.ok ? r.text : r.error; }); });
  });

  // Generic PNG export of an SVG/HTML panel (Discipline Leak Mirror)
  $$('[data-png-export]').forEach(function (b) {
    b.addEventListener('click', function () {
      var src = $(b.dataset.pngExport), data = JSON.parse(src.dataset.leak), c = d.createElement('canvas'); c.width = 1000; c.height = 520; var x = c.getContext('2d');
      x.fillStyle = '#0a0c10'; x.fillRect(0, 0, 1000, 520); x.fillStyle = '#8790a2'; x.font = '600 20px Inter, sans-serif'; x.fillText('DISCIPLINE LEAK MIRROR · ' + data.period, 40, 60);
      x.fillStyle = '#f2f4f8'; x.font = '700 64px "JetBrains Mono", monospace'; x.fillText(data.leak, 40, 150);
      x.font = '400 22px Inter, sans-serif'; x.fillStyle = '#c9cfda'; x.fillText('Estimated cost of execution mistakes (hypothetical, conservative).', 40, 195);
      x.fillText('Actual P&L: ' + data.actual + '    Rule-compliant P&L: ' + data.flawless + '    Violations: ' + data.violations, 40, 245);
      data.rows.slice(0, 6).forEach(function (r, i) { x.fillText(r, 40, 300 + i * 34); });
      x.fillStyle = '#8790a2'; x.font = '400 16px Inter, sans-serif'; x.fillText('Hypothetical estimate from historical data — not a guarantee. journzey.ai', 40, 495);
      var a = d.createElement('a'); a.download = 'discipline-leak.png'; a.href = c.toDataURL('image/png'); a.click();
    });
  });
})();
