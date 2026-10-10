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

  // Account switcher: "＋ Add account…" opens the dialog instead of switching
  $$('[data-acc-switch]').forEach(function (sel) {
    var cur = sel.value;
    sel.addEventListener('change', function () {
      if (sel.value === 'add') { sel.value = cur; var m = $('#tm-add-account'); if (m) open(m); return; }
      sel.form.submit();
    });
  });
  $$('[data-open-add-account]').forEach(function (b) { b.addEventListener('click', function () { var m = $('#tm-add-account'); if (m) { open(m); if (b.dataset.openAddAccount) { var t = $('[data-aa-kind="' + b.dataset.openAddAccount + '"]', m); if (t) t.click(); } } }); });
  $$('#tm-add-account').forEach(function (m) {
    $$('[data-aa-kind]', m).forEach(function (t) {
      t.addEventListener('click', function () {
        $$('[data-aa-kind]', m).forEach(function (x) { var on = x === t; x.classList.toggle('on', on); x.setAttribute('aria-selected', on ? 'true' : 'false'); });
        $$('[data-aa-form]', m).forEach(function (f) { f.hidden = f.dataset.aaForm !== t.dataset.aaKind; });
        var first = $('[data-aa-form="' + t.dataset.aaKind + '"] input:not([type=hidden])', m); if (first) first.focus();
      });
    });
    var pf = $('[data-aa-form=prop]', m), presets = pf ? JSON.parse(pf.dataset.presets || '{}') : {};
    function fill(key) {
      var p = presets[key]; if (!p) return;
      $$('[data-rule]', pf).forEach(function (inp) { var v = p[inp.dataset.rule]; inp.value = v === null || v === undefined ? (inp.tagName === 'SELECT' ? 'static' : '') : v; });
    }
    var ps = pf && $('[data-aa-preset]', pf);
    if (ps) { ps.addEventListener('change', function () { fill(ps.value); }); fill(ps.value); }
  });

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

  // Tilt cooldown countdown
  $$('[data-countdown]').forEach(function (c) {
    var end = +c.dataset.countdown * 1000;
    (function upd() { var s = Math.max(0, Math.round((end - Date.now()) / 1000)); c.textContent = Math.floor(s / 60) + ':' + ('0' + (s % 60)).slice(-2); if (s > 0) setTimeout(upd, 1000); else location.reload(); })();
  });

  // AI text: plain text with **key phrase** shown highlighted (built with DOM nodes, never innerHTML)
  function setCoachText(el, text) {
    el.textContent = '';
    String(text || '').split(/(\*\*[^*]+\*\*)/).forEach(function (part) {
      if (/^\*\*[^*]+\*\*$/.test(part)) el.appendChild(txt('mark', '', part.slice(2, -2))); else if (part) el.appendChild(d.createTextNode(part));
    });
  }
  // AI Coach chat
  var chat = $('[data-chat-form]');
  if (chat) {
    var log = $('[data-chat-log]'), ta = $('textarea', chat);
    var add = function (role, text, meta) { var m = txt('div', 'msg ' + role); setCoachText(m, text); if (meta) m.appendChild(txt('small', '', meta)); log.appendChild(m); log.scrollTop = log.scrollHeight; return m; };
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
      var out = $('[data-review-out]'); out.hidden = false; b.classList.add('busy'); out.textContent = 'Writing your AI review narrative…';
      post(base + '/coach/review', { period: b.dataset.review }).then(function (r) {
        b.classList.remove('busy'); setCoachText(out, r.ok ? r.text : (r.error || 'Could not generate the review.'));
        $$('[data-review-actions]').forEach(function (a) { a.hidden = !r.ok; });
      });
    });
  });
  $$('[data-copy-target]').forEach(function (b) { b.addEventListener('click', function () { var t = $(b.dataset.copyTarget); if (t && navigator.clipboard) navigator.clipboard.writeText(t.textContent).then(function () { toast('Copied to clipboard.', 'success'); }); }); });
  $$('[data-print]').forEach(function (b) { b.addEventListener('click', function () { window.print(); }); });

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
