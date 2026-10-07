/* journzey.ai terminal tools — calculator, clock & sessions, voice input, share cards, strategy builder.
 * Uses the helpers exposed by terminal.js (tmPost, tmToast, tmOpen, tmClose). All maths runs on the server. */
(function () {
  'use strict';
  var d = document;
  var base = (d.querySelector('meta[name="app-base"]') || {}).content || '/terminal';
  function $(s, c) { return (c || d).querySelector(s); }
  function $$(s, c) { return Array.prototype.slice.call((c || d).querySelectorAll(s)); }
  function txt(tag, cls, t) { var n = d.createElement(tag); if (cls) n.className = cls; if (t !== undefined) n.textContent = t; return n; }
  var post = function (u, data) { return window.tmPost(u, data); }, toast = function (m, t) { window.tmToast(m, t); };
  var SR = window.SpeechRecognition || window.webkitSpeechRecognition;
  function curSym(c) { return { USD: '$', EUR: '€', GBP: '£', JPY: '¥', INR: '₹', AUD: 'A$', CAD: 'C$', SGD: 'S$' }[c] || (c + ' '); }
  function money(v, c) { if (v === null || v === undefined) return '—'; return (v < 0 ? '−' : '') + curSym(c) + Math.abs(v).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
  function signedMoney(v, c) { return (v > 0 ? '+' : '') + money(v, c); }

  /* ---------------------------------------------------------------- Position Size & Risk Calculator */
  $$('[data-calc-tab]').forEach(function (b) {
    b.addEventListener('click', function () {
      $$('[data-calc-tab]').forEach(function (x) { x.classList.toggle('on', x === b); x.setAttribute('aria-selected', String(x === b)); });
      $$('[data-calc]').forEach(function (p) { p.hidden = p.dataset.calc !== b.dataset.calcTab; });
    });
  });
  $$('[data-inst-filter]').forEach(function (inp) {
    var sel = $(inp.dataset.instFilter);
    inp.addEventListener('input', function () {
      var q = inp.value.trim().toLowerCase(), first = null;
      $$('option', sel).forEach(function (o) { var hit = !q || o.dataset.search.indexOf(q) >= 0; o.hidden = !hit; o.disabled = !hit; if (hit && !first) first = o; });
      if (first && (!sel.selectedOptions[0] || sel.selectedOptions[0].hidden)) { sel.value = first.value; sel.dispatchEvent(new Event('change', { bubbles: true })); }
    });
  });
  $$('[data-calc-form]').forEach(function (f) {
    var mode = f.dataset.calcForm, out = $('[data-calc-out="' + mode + '"]'), timer;
    var o = function (k) { return $('[data-o="' + k + '"]', out); };
    var sel = $('[data-inst-select]', f), specOut = $('[data-spec-out]', f);
    var showSpec = function () { var sp = JSON.parse(sel.selectedOptions[0].dataset.spec); specOut.textContent = 'Contract ' + sp.contract.toLocaleString() + ' · tick ' + sp.tick + ' · pip/point ' + sp.pip + ' · quoted in ' + sp.quote + ' · lot step ' + sp.step; };
    var reset = function () { $$('dd', out).forEach(function (dd) { dd.textContent = '—'; }); o(mode === 'size' ? 'lots' : 'pnl').textContent = '—'; };
    var run = function () {
      clearTimeout(timer);
      timer = setTimeout(function () {
        var fd = new FormData(f);
        if (!fd.get('entry') || !fd.get('stop')) return;
        post(base + '/calculator/compute', fd).then(function (r) {
          var errs = o('errors'); errs.textContent = '';
          var rateF = $('[data-rate-field]', f);
          if (r.needs_rate) { rateF.hidden = false; $('[data-rate-label]', f).textContent = '(1 ' + r.quote + ' = ? ' + (mode === 'size' ? $('[data-calc-account]', f).selectedOptions[0].dataset.currency : fd.get('currency')) + ')'; }
          if (!r.ok) { (r.errors || [r.error || 'Cannot calculate.']).forEach(function (m) { errs.appendChild(txt('li', '', m)); }); reset(); return; }
          var c = r.currency;
          if (mode === 'size') {
            var dec = String(r.lot_step).indexOf('.') >= 0 ? String(r.lot_step).split('.')[1].length : 0;
            o('lots').textContent = r.lots.toFixed(dec) + ' lots';
            o('lots_note').textContent = r.below_min ? 'Below the minimum lot (' + r.min_lot + ') — this risk budget is too small for this stop distance.' : 'Exact size ' + r.raw_lots + ' lots · ' + money(r.per_lot_risk, c) + ' risk per lot';
            o('risk_actual').textContent = money(r.risk_actual, c) + ' (budget ' + money(r.risk_budget, c) + ')';
            o('stop_distance').textContent = r.stop_distance + ' ' + r.unit;
            o('profit').textContent = r.profit === null ? 'Add a take profit' : signedMoney(r.profit, c);
            o('rr').textContent = r.rr === null ? '—' : '1 : ' + r.rr;
            o('gain_pct').textContent = r.gain_pct === null ? '—' : '+' + r.gain_pct + '%';
            o('loss_pct').textContent = r.loss_pct === null ? '—' : '−' + r.loss_pct + '%';
          } else {
            var pnl = o('pnl'); pnl.textContent = signedMoney(r.pnl, c); pnl.className = r.pnl >= 0 ? 'up' : 'down';
            o('pnl_note').textContent = r.pnl >= 0 ? 'Estimated profit' : 'Estimated loss';
            o('rr').textContent = r.rr === null ? '—' : '1 : ' + r.rr;
            var rm = o('r_multiple'); rm.textContent = (r.r_multiple > 0 ? '+' : '') + r.r_multiple + 'R'; rm.className = r.r_multiple >= 0 ? 'up' : 'down';
            o('risk').textContent = money(-r.risk, c);
            o('stop_distance').textContent = r.stop_distance + ' ' + r.unit; o('target_distance').textContent = r.target_distance + ' ' + r.unit;
            o('pip_value').textContent = money(r.pip_value * Number(fd.get('lots') || 0), c);
          }
        });
      }, 220);
    };
    showSpec(); sel.addEventListener('change', showSpec);
    if (mode === 'size') {
      var rv = $('[data-risk-value]', f);
      var syncChips = function () { var m = $('[name=risk_mode]:checked', f).value; $$('[data-risk-chips]', f).forEach(function (c) { c.hidden = c.dataset.riskChips !== m; }); };
      $$('[name=risk_mode]', f).forEach(function (r) { r.addEventListener('change', function () { syncChips(); var c = $('[data-risk-chips="' + r.value + '"] .chip-btn', f); if (c) c.click(); }); });
      $$('[data-risk]', f).forEach(function (c) {
        c.addEventListener('click', function () {
          $$('.chip-btn', c.parentNode).forEach(function (x) { x.classList.toggle('on', x === c); });
          if (c.dataset.risk === 'custom') { rv.focus(); rv.select(); } else { rv.value = c.dataset.risk; }
          run();
        });
      });
      syncChips();
    }
    f.addEventListener('input', run); f.addEventListener('change', run);
    f.addEventListener('submit', function (e) { e.preventDefault(); run(); });
  });

  /* ---------------------------------------------------------------- Clock & trading sessions */
  var clock = $('[data-clock]');
  if (clock) {
    var tz = clock.dataset.clock, sess = $$('[data-session]');
    var tick = function () {
      var now = new Date();
      clock.querySelector('[data-clock-time]').textContent = new Intl.DateTimeFormat('en-GB', { timeZone: tz, hour: '2-digit', minute: '2-digit', second: '2-digit', hourCycle: 'h23' }).format(now);
      clock.querySelector('[data-clock-date]').textContent = new Intl.DateTimeFormat('en-GB', { timeZone: tz, weekday: 'long', day: 'numeric', month: 'long' }).format(now);
      sess.forEach(function (s) {
        var parts = new Intl.DateTimeFormat('en-GB', { timeZone: s.dataset.zone, hour: '2-digit', minute: '2-digit', weekday: 'short', hourCycle: 'h23' }).formatToParts(now);
        var g = function (t) { return (parts.find(function (p) { return p.type === t; }) || {}).value; };
        var mins = +g('hour') * 60 + +g('minute'), wd = g('weekday'), on = wd !== 'Sat' && wd !== 'Sun' && mins >= +s.dataset.open && mins < +s.dataset.close;
        s.classList.toggle('on', on); var st = $('[data-session-state]', s); if (st) st.textContent = on ? 'ACTIVE' : 'closed';
      });
    };
    tick(); setInterval(tick, 1000);
  }

  /* ---------------------------------------------------------------- Rotating discipline quotes */
  var qbox = $('[data-quotes]');
  if (qbox) {
    var quotes = JSON.parse(qbox.dataset.quotes), qi = +qbox.dataset.start || 0;
    var showQ = function () { var q = quotes[qi % quotes.length]; $('[data-q-text]', qbox).textContent = '“' + q[0] + '”'; $('[data-q-by]', qbox).textContent = '— ' + q[1]; $('[data-q-theme]', qbox).textContent = q[2]; };
    $('[data-q-next]', qbox).addEventListener('click', function () { qi++; showQ(); });
  }

  /* ---------------------------------------------------------------- Voice input (Web Speech API) */
  // <button data-voice-into="#textarea" [data-voice-append]> — speech becomes editable text; nothing is sent automatically.
  function voiceLang() { return ($('[data-voice-lang]') || {}).value || d.documentElement.dataset.voiceLang || 'en-US'; }
  $$('[data-voice-into]').forEach(function (b) {
    var target = $(b.dataset.voiceInto), label = b.innerHTML, rec = null;
    if (!SR) { b.disabled = true; b.title = 'Voice input needs a browser with speech recognition (Chrome, Edge or Safari).'; var n = $(b.dataset.voiceNote || '[data-voice-note]'); if (n) n.hidden = false; return; }
    b.addEventListener('click', function () {
      if (rec) { rec.stop(); return; }
      rec = new SR(); rec.lang = voiceLang(); rec.continuous = b.hasAttribute('data-voice-append'); rec.interimResults = false;
      rec.onresult = function (ev) {
        for (var i = ev.resultIndex; i < ev.results.length; i++) if (ev.results[i].isFinal) {
          var t = ev.results[i][0].transcript.trim();
          target.value = b.hasAttribute('data-voice-append') ? target.value + (target.value && !/\s$/.test(target.value) ? ' ' : '') + t : t;
        }
        target.dispatchEvent(new Event('input', { bubbles: true }));
      };
      rec.onerror = function (ev) { toast(ev.error === 'not-allowed' ? 'Microphone access was blocked. Allow it in your browser to use voice input.' : 'Voice input error: ' + ev.error, 'error'); };
      rec.onend = function () { rec = null; b.classList.remove('rec'); b.innerHTML = label; if (b.dataset.voiceAfter && target.value.trim()) { var a = $(b.dataset.voiceAfter); if (a) a.click(); } };
      rec.start(); b.classList.add('rec'); b.textContent = '■ Stop listening';
    });
  });

  /* ---------------------------------------------------------------- Voice / sentence trade entry */
  var vt = $('[data-voice-trade]');
  if (vt) {
    var vin = $('[data-vt-text]', vt), vprev = $('[data-vt-preview]', vt), tform = $(vt.dataset.voiceTrade);
    var fields = { symbol: 't-symbol', side: 't-side', entry: 't-entry', stop: 't-stop', tp: 't-tp', exit: 't-exit', lots: 't-lots' };
    $('[data-vt-parse]', vt).addEventListener('click', function () {
      if (!vin.value.trim()) { toast('Say or type a trade first.', 'error'); return; }
      post(base + '/quick-trade/parse', { command: vin.value, voice: '1' }).then(function (r) {
        vprev.textContent = ''; var p = r.parsed; if (!p) { vprev.textContent = r.error || 'Could not read that sentence.'; return; }
        var rows = [['Instrument', p.symbol], ['Direction', p.side ? (p.side === 'LONG' ? 'BUY' : 'SELL') : null], ['Entry', p.entry], ['Stop loss', p.stop], ['Take profit', p.tp], ['Exit', p.exit_spoken], ['Lot size', p.lots_spoken ? p.lots : null]];
        var dl = txt('dl', 'vt-dl'); rows.forEach(function (r2) { dl.appendChild(txt('dt', '', r2[0])); dl.appendChild(txt('dd', r2[1] === null || r2[1] === undefined ? 'not heard' : '', r2[1] === null || r2[1] === undefined ? '—' : String(r2[1]))); });
        vprev.appendChild(dl);
        (p.errors || []).forEach(function (m) { vprev.appendChild(txt('p', 'err', '✕ ' + m)); });
        if (!p.symbol && !p.entry) return;
        var set = function (k, v) { if (v === null || v === undefined) return; var el = d.getElementById(fields[k]); if (!el) return; el.value = v; el.classList.add('filled'); setTimeout(function () { el.classList.remove('filled'); }, 2500); };
        set('symbol', p.symbol); set('side', p.side); set('entry', p.entry); set('stop', p.stop); set('tp', p.tp); set('exit', p.exit_spoken); if (p.lots_spoken) set('lots', p.lots);
        vprev.appendChild(txt('p', 'ok', '✓ Values copied into the form below. Check and correct them, then press “Save trade”. Nothing is saved until you confirm.'));
        if (tform) tform.scrollIntoView({ behavior: 'smooth', block: 'start' });
      });
    });
  }

  /* ---------------------------------------------------------------- Share / flex cards */
  function drawCard(canvas, data, style) {
    var cx = canvas.getContext('2d'), W = canvas.width, H = canvas.height, win = data.positive;
    var pal = style === 'light'
      ? { bg: '#f7f9fb', grid: '#e2e8ef', text: '#0f172a', mute: '#5b6676', main: win ? '#047857' : '#b42318', glow: win ? '#10b98133' : '#ef444433' }
      : { bg: '#060b10', grid: '#121c26', text: '#f1f5f9', mute: '#8b97a8', main: win ? '#34d399' : '#f87171', glow: win ? '#10b98140' : '#ef444440' };
    cx.fillStyle = pal.bg; cx.fillRect(0, 0, W, H);
    var g = cx.createRadialGradient(W * 0.25, H * 0.35, 10, W * 0.25, H * 0.35, W * 0.9); g.addColorStop(0, pal.glow); g.addColorStop(1, 'transparent'); cx.fillStyle = g; cx.fillRect(0, 0, W, H);
    cx.strokeStyle = pal.grid; cx.lineWidth = 1; for (var x = 0; x <= W; x += 54) { cx.beginPath(); cx.moveTo(x, 0); cx.lineTo(x, H); cx.stroke(); } for (var y = 0; y <= H; y += 54) { cx.beginPath(); cx.moveTo(0, y); cx.lineTo(W, y); cx.stroke(); }
    cx.fillStyle = pal.main; cx.fillRect(0, 0, W, 10);
    var pad = 80;
    cx.fillStyle = pal.text; cx.font = '700 52px "JetBrains Mono", monospace'; cx.fillText(data.symbol, pad, 150);
    var sw = cx.measureText(data.symbol).width + 28; cx.font = '700 34px "JetBrains Mono", monospace';
    var side = data.side === 'LONG' ? 'BUY' : 'SELL', bw = cx.measureText(side).width + 36;
    cx.fillStyle = data.side === 'LONG' ? (style === 'light' ? '#047857' : '#34d399') : (style === 'light' ? '#b42318' : '#f87171'); cx.globalAlpha = 0.16; cx.fillRect(pad + sw, 108, bw, 54); cx.globalAlpha = 1; cx.fillText(side, pad + sw + 18, 148);
    cx.fillStyle = pal.mute; cx.font = '600 30px Inter, sans-serif'; cx.fillText(data.positive ? 'PROFIT' : 'LOSS', pad, 300);
    cx.fillStyle = pal.main; var size = 170; cx.font = '800 ' + size + 'px "JetBrains Mono", monospace'; while (cx.measureText(data.pnl).width > W - pad * 2 && size > 60) { size -= 8; cx.font = '800 ' + size + 'px "JetBrains Mono", monospace'; } cx.fillText(data.pnl, pad, 300 + size * 0.95);
    if (data.r) { cx.font = '700 64px "JetBrains Mono", monospace'; cx.fillText(data.r, pad, 300 + size * 0.95 + 96); }
    var rows = [['ENTRY', data.entry], ['EXIT', data.exit], ['LOT SIZE', data.lots], ['STRATEGY', data.strategy]];
    var top = H - 330;
    cx.strokeStyle = pal.grid; cx.lineWidth = 2; cx.beginPath(); cx.moveTo(pad, top - 40); cx.lineTo(W - pad, top - 40); cx.stroke();
    rows.forEach(function (r, i) {
      var xx = pad + (i % 2) * ((W - pad * 2) / 2), yy = top + Math.floor(i / 2) * 110;
      cx.fillStyle = pal.mute; cx.font = '600 24px Inter, sans-serif'; cx.fillText(r[0], xx, yy);
      cx.fillStyle = pal.text; cx.font = '600 38px "JetBrains Mono", monospace'; cx.fillText(String(r[1] || '—').slice(0, 20), xx, yy + 48);
    });
    cx.fillStyle = pal.text; cx.font = '800 36px "Plus Jakarta Sans", Inter, sans-serif'; cx.fillText(data.brand, pad, H - 60);
    cx.fillStyle = pal.mute; cx.font = '500 22px Inter, sans-serif'; var tag = data.demo ? 'DEMO DATA' : 'Trading journal'; cx.fillText(tag, W - pad - cx.measureText(tag).width, H - 64);
  }
  var shareModal = $('#tm-share');
  function openShare(data) {
    if (!shareModal) return;
    var canvas = $('canvas', shareModal), style = ($('[name=card_style]:checked', shareModal) || {}).value || 'dark';
    shareModal._data = data; drawCard(canvas, data, style); window.tmOpen(shareModal);
  }
  if (shareModal) {
    var canvas = $('canvas', shareModal);
    $$('[name=card_style]', shareModal).forEach(function (r) { r.addEventListener('change', function () { if (shareModal._data) drawCard(canvas, shareModal._data, r.value); }); });
    $('[data-card-download]', shareModal).addEventListener('click', function () { var a = d.createElement('a'); a.download = 'journzey-' + shareModal._data.symbol + '-trade.png'; a.href = canvas.toDataURL('image/png'); a.click(); });
    var cp = $('[data-card-copy]', shareModal);
    if (!window.ClipboardItem || !navigator.clipboard || !navigator.clipboard.write) { cp.disabled = true; cp.title = 'Copying images is not supported in this browser — use Download.'; }
    else cp.addEventListener('click', function () { canvas.toBlob(function (b) { navigator.clipboard.write([new ClipboardItem({ 'image/png': b })]).then(function () { toast('Card copied — paste it into Instagram, X, Telegram, Discord or WhatsApp.', 'success'); }, function () { toast('Your browser blocked clipboard access. Use Download instead.', 'error'); }); }); });
  }
  $$('[data-share-trade]').forEach(function (b) { b.addEventListener('click', function () { openShare(JSON.parse(b.dataset.shareTrade)); }); });

  /* ---------------------------------------------------------------- Strategy builder list editors */
  $$('[data-list-editor]').forEach(function (box) {
    var store = $(box.dataset.listEditor), list = $('ol', box), input = $('[data-le-input]', box);
    var sync = function () { store.value = $$('li input', list).map(function (i) { return i.value.trim(); }).filter(Boolean).join('\n'); };
    var add = function (v) {
      var li = txt('li'), inp = d.createElement('input'); inp.value = v; inp.maxLength = 200; inp.setAttribute('aria-label', 'Item');
      inp.addEventListener('input', sync);
      var ctl = txt('span', 'le-ctl');
      [['↑', 'Move up', function () { if (li.previousElementSibling) list.insertBefore(li, li.previousElementSibling); sync(); }],
       ['↓', 'Move down', function () { if (li.nextElementSibling) list.insertBefore(li.nextElementSibling, li); sync(); }],
       ['✕', 'Remove', function () { li.remove(); sync(); }]].forEach(function (c) { var b = txt('button', 'tm-icon-btn', c[0]); b.type = 'button'; b.title = c[1]; b.setAttribute('aria-label', c[1]); b.addEventListener('click', c[2]); ctl.appendChild(b); });
      li.appendChild(inp); li.appendChild(ctl); list.appendChild(li); sync();
    };
    store.value.split('\n').map(function (s) { return s.trim(); }).filter(Boolean).forEach(add);
    var addNow = function () { if (input.value.trim()) { add(input.value.trim()); input.value = ''; input.focus(); } };
    $('[data-le-add]', box).addEventListener('click', addNow);
    input.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); addNow(); } });
  });

  /* ---------------------------------------------------------------- Equity adjust modal tabs */
  $$('[data-cap-tab]').forEach(function (b) {
    b.addEventListener('click', function () {
      var m = b.closest('.tm-modal'); $$('[data-cap-tab]', m).forEach(function (x) { x.classList.toggle('on', x === b); });
      $('[name=type]', m).value = b.dataset.capTab; $$('[data-cap-help]', m).forEach(function (h) { h.hidden = h.dataset.capHelp !== b.dataset.capTab; });
      $('[data-cap-label]', m).textContent = b.dataset.capTab === 'SET' ? 'New equity' : 'Amount';
    });
  });
  $$('[data-open-modal]').forEach(function (b) { b.addEventListener('click', function () { var m = $(b.dataset.openModal); if (m) { d.body.classList.remove('side-open'); window.tmOpen(m); } }); });

  /* ---------------------------------------------------------------- Ruin radar scenarios */
  $$('[data-radar] [data-scen]').forEach(function (b) {
    b.addEventListener('click', function () {
      var box = b.closest('[data-radar]'), x = JSON.parse(b.dataset.scen), p = x.p * 100;
      $$('[data-scen]', box).forEach(function (o) { o.classList.toggle('on', o === b); });
      var big = $('[data-radar-p]', box); big.textContent = p.toFixed(1) + '%'; big.className = 'radar-big ' + (x.p >= 0.25 ? 'down' : x.p >= 0.1 ? 'warn' : 'up');
      $('[data-radar-mark]', box).style.left = Math.min(100, p) + '%';
      $('[data-radar-detail]', box).textContent = 'At ' + x.risk + '% risk per trade: median worst drawdown ' + (x.median_dd * 100).toFixed(1) + '% · bad case (95th pct) ' + (x.p95_dd * 100).toFixed(1) + '%';
    });
  });

  /* ---------------------------------------------------------------- Segmented radio styling helper */
  $$('.seg input[type=radio]').forEach(function (r) { var sync = function () { $$('input[name="' + r.name + '"]', r.form || d).forEach(function (x) { x.parentNode.classList.toggle('on', x.checked); }); }; r.addEventListener('change', sync); sync(); });
})();
