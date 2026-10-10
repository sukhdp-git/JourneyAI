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

  /* ---------------------------------------------------------------- Spoken numbers & guided voice forms */
  var WORDNUM = { zero: 0, oh: 0, one: 1, two: 2, three: 3, four: 4, five: 5, six: 6, seven: 7, eight: 8, nine: 9, ten: 10 };
  function spokenNumbers(t) {
    t = String(t).toLowerCase().replace(/(\d),(?=\d{3}\b)/g, '$1').replace(/\b(point|dot|decimal)\b/g, ' . ');
    t = t.replace(/\b(zero|oh|one|two|three|four|five|six|seven|eight|nine|ten)\b/g, function (w) { return WORDNUM[w]; });
    t = t.replace(/(\d)\s*\.\s*(\d)/g, '$1.$2').replace(/(^|\s)\.\s*(\d)/g, '$10.$2');
    t = t.replace(/(\.\d+)\s+(?=\d\b)/g, '$1');
    return (t.match(/-?\d+(?:\.\d+)?/g) || []).map(parseFloat);
  }
  function flash(el) { el.classList.add('filled'); setTimeout(function () { el.classList.remove('filled'); }, 1800); }
  function pickOption(sel, text) {
    var words = String(text).toLowerCase().replace(/[^a-z0-9 ]/g, ' ').split(/\s+/).filter(function (w) { return w.length > 1; }), best = null, score = 0;
    $$('option', sel).forEach(function (o) {
      var hay = ' ' + (o.dataset.search || (o.value + ' ' + o.textContent).toLowerCase()) + ' ', sc = 0;
      words.forEach(function (w) { if (hay.indexOf(' ' + w + ' ') >= 0) sc += 3; else if (w.length > 3 && hay.indexOf(w) >= 0) sc += 1; });
      var joined = words.join(''); if (joined.length > 3 && hay.indexOf(' ' + joined + ' ') >= 0) sc += 4;
      if (sc > score) { score = sc; best = o; }
    });
    return best;
  }
  /**
   * Guided voice: <form data-voice-guide> with fields marked data-vg-step="symbol|side|number|emotion|text|select" and
   * data-vg-ask="question". Listens for one answer per field, fills it and moves to the next field automatically.
   * "skip", "back" and "stop" work at any time. Several numbers in one answer are read by the server sentence parser.
   */
  $$('[data-voice-guide]').forEach(function (form) {
    var steps = $$('[data-vg-step]', form), startB = $('[data-vg-start]', form), stopB = $('[data-vg-stop]', form);
    var promptEl = $('[data-vg-prompt]', form), heardEl = $('[data-vg-heard]', form), labelEl = $('[data-vg-label]', form);
    var idx = -1, rec = null, active = false, handled = false, retries = 0;
    if (!startB) return;
    if (!SR) { startB.disabled = true; var un = $('[data-vg-unsupported]', form); if (un) un.hidden = false; return; }
    function field(st) { var k = st.dataset.vgStep; return (k === 'symbol' || k === 'emotion' || k === 'select') ? $('select', st) : k === 'side' ? ($('select', st) || $('input[type=radio]', st)) : $('input[type=number], input[type=text], textarea', st); }
    function mark() { steps.forEach(function (st, i) { st.classList.toggle('vg-on', i === idx); }); }
    function stopAll(msg) {
      active = false; if (rec) { try { rec.abort(); } catch (e) {} rec = null; }
      idx = -1; mark(); form.classList.remove('vg-live'); stopB.hidden = true; labelEl.textContent = 'Voice log';
      promptEl.textContent = msg || 'Voice log stopped.'; heardEl.textContent = 'Check the values, then save.';
    }
    function next() {
      idx++;
      if (idx >= steps.length) { stopAll('All done ✓ — check the values and press Save.'); var sb = $('button[type=submit]', form); if (sb) sb.focus(); return; }
      var st = steps[idx]; mark(); retries = 0;
      promptEl.textContent = (idx + 1) + '/' + steps.length + ' · ' + st.dataset.vgAsk;
      st.scrollIntoView({ behavior: 'smooth', block: 'center' });
      listen();
    }
    function listen() {
      if (!active) return;
      handled = false; rec = new SR(); rec.lang = voiceLang(); rec.interimResults = true; rec.continuous = false;
      rec.onresult = function (ev) {
        var res = ev.results[ev.results.length - 1], t = res[0].transcript.trim();
        heardEl.textContent = '“' + t + '”';
        if (res.isFinal && !handled) { handled = true; handle(t); }
      };
      rec.onerror = function (ev) { if (ev.error === 'not-allowed' || ev.error === 'service-not-allowed') { stopAll('Microphone access is blocked — allow it in your browser to use voice.'); } };
      rec.onend = function () { rec = null; if (active && !handled) { if (++retries > 6) { stopAll('No answer heard — voice log paused.'); return; } listen(); } };
      try { rec.start(); } catch (e) { setTimeout(listen, 300); }
    }
    function again(msg) { heardEl.textContent = msg; handled = false; setTimeout(listen, 250); }
    function advance() { setTimeout(next, 450); }
    function handle(t) {
      var low = t.toLowerCase(), st = steps[idx], kind = st.dataset.vgStep, el = field(st);
      if (/^(stop|cancel|finish|done|that's all|that is all)( listening| voice| voice log)?[.!]?$/.test(low)) { stopAll('Voice log stopped.'); return; }
      if (/^(skip|next|none|nothing|no|not yet|open)\b/.test(low)) { advance(); return; }
      if (/^(back|previous|go back)\b/.test(low)) { idx = Math.max(-1, idx - 2); advance(); return; }
      var nums = spokenNumbers(low);
      if (kind === 'number' && nums.length >= 2 && !$('#t-entry', form)) {
        // "entry 2645 stop 2639 target 2660 risk 1 percent" — fill the matching boxes by keyword
        var hit = 0;
        steps.forEach(function (st2) {
          var k2 = st2.dataset.vgKey; if (!k2) return;
          var m2 = low.match(new RegExp('(?:' + k2 + ')\\D{0,12}?(-?\\d+(?:[.,]\\d+)?)'));
          if (m2) { var f2 = field(st2); f2.value = parseFloat(m2[1].replace(',', '.')); f2.dispatchEvent(new Event('input', { bubbles: true })); flash(f2); hit++; }
        });
        if (hit) { var j2 = 0; while (j2 < steps.length && !(steps[j2].dataset.vgStep === 'number' && !field(steps[j2]).value)) j2++; idx = j2 - 1; advance(); return; }
      }
      if (kind === 'number' && nums.length >= 2 && $('#t-entry', form)) {
        post(base + '/quick-trade/parse', { command: t, voice: '1' }).then(function (r) {
          var p = r && r.parsed; if (!p) { again('Could not read that — say one value: ' + st.dataset.vgAsk); return; }
          var put = function (id, v) { var x = d.getElementById(id); if (x && v !== null && v !== undefined) { x.value = v; flash(x); } };
          put('t-symbol', p.symbol); put('t-side', p.side); put('t-entry', p.entry); put('t-stop', p.stop); put('t-tp', p.tp); put('t-exit', p.exit_spoken); if (p.lots_spoken) put('t-lots', p.lots);
          // continue with the first number field that is still empty
          var j = idx; while (j < steps.length && !(steps[j].dataset.vgStep === 'number' && !field(steps[j]).value)) j++;
          idx = j - 1; advance();
        });
        return;
      }
      if (kind === 'symbol') { var o = pickOption(el, low); if (!o) { again('Instrument not recognised — try “gold”, “nasdaq”, “euro dollar”…'); return; } el.value = o.value; el.dispatchEvent(new Event('change', { bubbles: true })); }
      else if (kind === 'side') {
        var sv = /\b(buy|long|bought|bullish)\b/.test(low) ? 'LONG' : /\b(sell|short|sold|bearish)\b/.test(low) ? 'SHORT' : null;
        if (!sv) { again('Say “buy” or “sell”.'); return; }
        if (el.type === 'radio') { var rb = $('input[type=radio][value="' + sv + '"]', st); rb.checked = true; rb.dispatchEvent(new Event('change', { bubbles: true })); el = rb.parentNode; } else el.value = sv;
      }
      else if (kind === 'number') { if (!nums.length) { again('No number heard — ' + st.dataset.vgAsk); return; } el.value = nums[0]; el.dispatchEvent(new Event('input', { bubbles: true })); }
      else if (kind === 'emotion' || kind === 'select') { var o2 = pickOption(el, low); if (!o2 || !o2.value) { again('Not recognised — say one of the options or “skip”.'); return; } el.value = o2.value; el.dispatchEvent(new Event('change', { bubbles: true })); }
      else if (kind === 'text') { el.value = el.value ? el.value + ' ' + t : t; }
      flash(el); advance();
    }
    startB.addEventListener('click', function () {
      if (active) { stopAll(); return; }
      active = true; idx = -1; form.classList.add('vg-live'); stopB.hidden = false; labelEl.textContent = 'Listening…'; next();
    });
    stopB.addEventListener('click', function () { stopAll(); });
  });

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

  /* ---------------------------------------------------------------- Daily flex card (+ QR "Verified by journzey") */
  function rr(cx, x, y, w, h, r) { cx.beginPath(); cx.moveTo(x + r, y); cx.arcTo(x + w, y, x + w, y + h, r); cx.arcTo(x + w, y + h, x, y + h, r); cx.arcTo(x, y + h, x, y, r); cx.arcTo(x, y, x + w, y, r); cx.closePath(); }
  function drawFlex(canvas, c, url, site) {
    var cx = canvas.getContext('2d'), W = canvas.width, H = canvas.height, up = c.net >= 0, main = up ? '#34d399' : '#f87171', P = 80;
    cx.fillStyle = '#07080d'; cx.fillRect(0, 0, W, H);
    var g1 = cx.createRadialGradient(W * 0.15, H * 0.12, 20, W * 0.15, H * 0.12, W); g1.addColorStop(0, up ? 'rgba(52,211,153,.30)' : 'rgba(248,113,113,.30)'); g1.addColorStop(1, 'rgba(0,0,0,0)'); cx.fillStyle = g1; cx.fillRect(0, 0, W, H);
    var g2 = cx.createRadialGradient(W, H * 0.75, 20, W, H * 0.75, W * 0.9); g2.addColorStop(0, 'rgba(124,108,255,.28)'); g2.addColorStop(1, 'rgba(0,0,0,0)'); cx.fillStyle = g2; cx.fillRect(0, 0, W, H);
    var bar = cx.createLinearGradient(0, 0, W, 0); bar.addColorStop(0, '#7c6cff'); bar.addColorStop(1, '#22d3ee'); cx.fillStyle = bar; cx.fillRect(0, 0, W, 12);
    // header
    rr(cx, P, 64, 56, 56, 14); cx.fillStyle = bar; cx.fill(); cx.fillStyle = '#fff'; cx.font = '800 34px Inter, sans-serif'; cx.textAlign = 'center'; cx.fillText('j', P + 28, 104); cx.textAlign = 'left';
    cx.fillStyle = '#f5f6fb'; cx.font = '700 36px Inter, sans-serif'; cx.fillText(site, P + 74, 104);
    cx.fillStyle = '#9aa1ba'; cx.font = '600 28px Inter, sans-serif'; cx.textAlign = 'right'; cx.fillText(c.date_label, W - P, 104); cx.textAlign = 'left';
    // title
    cx.fillStyle = '#9aa1ba'; cx.font = '700 26px "JetBrains Mono", monospace'; cx.fillText('DAY RECAP' + (c.demo ? ' · DEMO DATA' : ''), P, 214);
    cx.fillStyle = '#f5f6fb'; cx.font = '800 50px Inter, sans-serif'; cx.fillText((c.name ? c.name + '’s' : 'My') + ' trading day', P, 278);
    // P&L
    cx.fillStyle = '#9aa1ba'; cx.font = '700 28px Inter, sans-serif'; cx.fillText(up ? 'NET PROFIT' : 'NET LOSS', P, 372);
    var pnl = (c.net > 0 ? '+' : c.net < 0 ? '−' : '') + curSym(c.currency) + Math.abs(c.net).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    var size = 150; cx.font = '800 ' + size + 'px "JetBrains Mono", monospace'; while (cx.measureText(pnl).width > W - P * 2 && size > 60) { size -= 6; cx.font = '800 ' + size + 'px "JetBrains Mono", monospace'; }
    cx.shadowColor = main; cx.shadowBlur = 40; cx.fillStyle = main; cx.fillText(pnl, P, 372 + size * 0.95); cx.shadowBlur = 0;
    // stat tiles
    var tiles = [['TRADES', c.trades + '  (' + c.wins + 'W·' + c.losses + 'L)'], ['WIN RATE', c.win_rate === null ? '—' : Math.round(c.win_rate * 100) + '%'], ['TOTAL R', c.total_r === null ? '—' : (c.total_r > 0 ? '+' : '') + c.total_r.toFixed(2) + 'R'], ['DISCIPLINE', c.discipline === null ? '—' : c.discipline + '/10']];
    var ty = 600, tw = (W - P * 2 - 30) / 2, th = 130;
    tiles.forEach(function (t, i) {
      var x = P + (i % 2) * (tw + 30), y = ty + Math.floor(i / 2) * (th + 26);
      rr(cx, x, y, tw, th, 24); cx.fillStyle = 'rgba(255,255,255,.05)'; cx.fill(); cx.strokeStyle = 'rgba(255,255,255,.10)'; cx.lineWidth = 2; cx.stroke();
      cx.fillStyle = '#9aa1ba'; cx.font = '700 22px "JetBrains Mono", monospace'; cx.fillText(t[0], x + 28, y + 46);
      var col = '#f5f6fb'; if (i === 2 && c.total_r !== null) col = c.total_r >= 0 ? '#34d399' : '#f87171'; if (i === 1 && c.win_rate !== null) col = c.win_rate >= 0.5 ? '#34d399' : '#f87171';
      cx.fillStyle = col; cx.font = '800 44px "JetBrains Mono", monospace'; cx.fillText(t[1], x + 28, y + 102);
    });
    // emotions + best trade + lesson
    var y = ty + 2 * (th + 26) + 34;
    if (c.emotions && c.emotions.length) {
      cx.fillStyle = '#9aa1ba'; cx.font = '700 22px "JetBrains Mono", monospace'; cx.fillText('MOOD', P, y + 30);
      var x = P + 100; cx.font = '700 28px Inter, sans-serif';
      c.emotions.forEach(function (em) { var w = cx.measureText(em).width + 44; rr(cx, x, y, w, 48, 24); cx.fillStyle = 'rgba(124,108,255,.22)'; cx.fill(); cx.fillStyle = '#e6e3ff'; cx.fillText(em, x + 22, y + 34); x += w + 12; });
      y += 78;
    }
    if (c.best) { cx.fillStyle = '#9aa1ba'; cx.font = '600 28px Inter, sans-serif'; cx.fillText('Best trade  ', P, y + 24); var bw = cx.measureText('Best trade  ').width; cx.fillStyle = '#f5f6fb'; cx.font = '700 28px Inter, sans-serif'; cx.fillText(c.best.symbol, P + bw, y + 24); var sw = cx.measureText(c.best.symbol + '  ').width; cx.fillStyle = c.best.pnl >= 0 ? '#34d399' : '#f87171'; cx.font = '700 28px "JetBrains Mono", monospace'; cx.fillText(signedMoney(c.best.pnl, c.currency), P + bw + sw, y + 24); y += 52; }
    if (c.lesson) { cx.fillStyle = '#c8cde0'; cx.font = 'italic 500 28px Inter, sans-serif'; cx.fillText('“' + c.lesson + '”', P, y + 24); }
    // footer with QR
    var fy = H - 230; cx.fillStyle = 'rgba(255,255,255,.08)'; cx.fillRect(P, fy - 30, W - P * 2, 2);
    var qs = 170, qx = W - P - qs, qy = fy;
    rr(cx, qx - 12, qy - 12, qs + 24, qs + 24, 18); cx.fillStyle = '#ffffff'; cx.fill();
    if (window.qrcode) { var q = window.qrcode(0, 'M'); q.addData(url); q.make(); var n = q.getModuleCount(), m = qs / n; cx.fillStyle = '#07080d'; for (var r = 0; r < n; r++) for (var k = 0; k < n; k++) if (q.isDark(r, k)) cx.fillRect(qx + k * m, qy + r * m, Math.ceil(m), Math.ceil(m)); }
    cx.fillStyle = '#34d399'; cx.beginPath(); cx.arc(P + 22, fy + 52, 22, 0, Math.PI * 2); cx.fill(); cx.strokeStyle = '#07080d'; cx.lineWidth = 6; cx.beginPath(); cx.moveTo(P + 11, fy + 52); cx.lineTo(P + 19, fy + 61); cx.lineTo(P + 34, fy + 43); cx.stroke();
    cx.fillStyle = '#f5f6fb'; cx.font = '800 40px Inter, sans-serif'; cx.fillText('Verified by ' + site, P + 60, fy + 66);
    cx.fillStyle = '#9aa1ba'; cx.font = '500 26px Inter, sans-serif'; cx.fillText('Scan the code to check this card', P, fy + 124);
    cx.fillText('against the trader’s journal.', P, fy + 160);
  }
  var flexState = null;
  $$('[data-flex-day]').forEach(function (b) {
    b.addEventListener('click', function () {
      b.classList.add('busy');
      post(base + '/flex', { date: b.dataset.flexDay }).then(function (r) {
        b.classList.remove('busy');
        if (!r || !r.ok) { toast((r && r.error) || 'Could not build the card.', 'error'); return; }
        var m = $('#tm-flex'), cv = $('canvas', m); flexState = r;
        var go = function () { drawFlex(cv, r.card, r.url, r.site); window.tmOpen(m); };
        if (d.fonts && d.fonts.ready) d.fonts.ready.then(go); else go();
      });
    });
  });
  (function () {
    var m = $('#tm-flex'); if (!m) return; var cv = $('canvas', m);
    var name = function () { return 'journzey-day-' + (flexState ? flexState.card.date : 'card') + '.png'; };
    var download = function () { var a = d.createElement('a'); a.download = name(); a.href = cv.toDataURL('image/png'); a.click(); };
    $('[data-flex-download]', m).addEventListener('click', download);
    var cp = $('[data-flex-copy]', m);
    if (!window.ClipboardItem || !navigator.clipboard || !navigator.clipboard.write) cp.hidden = true;
    else cp.addEventListener('click', function () { cv.toBlob(function (bl) { navigator.clipboard.write([new ClipboardItem({ 'image/png': bl })]).then(function () { toast('Card copied — paste it into your post.', 'success'); }, function () { toast('Copy was blocked — use Download.', 'error'); }); }); });
    $('[data-flex-share]', m).addEventListener('click', function () {
      if (!flexState) return;
      var c = flexState.card, text = 'My trading day: ' + signedMoney(c.net, c.currency) + ' · ' + c.trades + ' trades · ' + (c.win_rate === null ? '' : Math.round(c.win_rate * 100) + '% win rate') + ' — journaled with ' + flexState.site;
      cv.toBlob(function (bl) {
        var file = new File([bl], name(), { type: 'image/png' });
        if (navigator.canShare && navigator.canShare({ files: [file] })) { navigator.share({ files: [file], text: text, url: flexState.url }).catch(function () {}); return; }
        download();
        window.open('https://x.com/intent/post?text=' + encodeURIComponent(text) + '&url=' + encodeURIComponent(flexState.url), '_blank', 'noopener');
        toast('Image downloaded — attach it to your post on X.', 'success');
      });
    });
  })();

  /* ---------------------------------------------------------------- Blow-up radar what-if + runner audit */
  $$('[data-radar] [data-scen]').forEach(function (b) {
    b.addEventListener('click', function () {
      var box = b.closest('[data-radar]'), x = JSON.parse(b.dataset.scen);
      $$('[data-scen]', box).forEach(function (o) { o.classList.toggle('on', o === b); });
      $('[data-radar-detail]', box).textContent = 'At ' + x.risk + '% per trade: ' + (x.p * 100).toFixed(1) + '% chance of being blown in 14 days · typical worst dip −' + (x.median_dd * 100).toFixed(1) + '%, bad case −' + (x.p95_dd * 100).toFixed(1) + '%.';
    });
  });
  $$('[data-runner-run]').forEach(function (b) {
    b.addEventListener('click', function () {
      var msg = $('[data-runner-msg]'); b.disabled = true; b.classList.add('busy'); if (msg) msg.textContent = 'Checking price action after your exits…';
      post(b.dataset.runnerRun, {}).then(function (r) {
        if (r && r.ok) { location.reload(); return; }
        b.disabled = false; b.classList.remove('busy'); if (msg) msg.textContent = (r && r.error) || 'Could not run the audit.';
      });
    });
  });

  /* ---------------------------------------------------------------- Alert beep (risk limits, tilt) */
  // Browsers only allow sound after the member has interacted with the page, so a beep that is blocked on
  // page load is replayed on the first click or key press. Each alert beeps at most once every 10 minutes.
  var audioCtx = null, pendingBeep = false;
  function play() {
    [0, 0.28, 0.56].forEach(function (t) {
      var o = audioCtx.createOscillator(), g = audioCtx.createGain(), at = audioCtx.currentTime + t;
      o.type = 'square'; o.frequency.value = 880; o.connect(g); g.connect(audioCtx.destination);
      g.gain.setValueAtTime(0.0001, at); g.gain.exponentialRampToValueAtTime(0.18, at + 0.02); g.gain.exponentialRampToValueAtTime(0.0001, at + 0.2);
      o.start(at); o.stop(at + 0.22);
    });
    window.__jzBeeps = (window.__jzBeeps || 0) + 1;
  }
  function beepNow() {
    try {
      audioCtx = audioCtx || new (window.AudioContext || window.webkitAudioContext)();
      if (audioCtx.state === 'running') { play(); return; }
      // resume() is asynchronous; it only succeeds once the browser allows audio (now, or after a gesture).
      pendingBeep = true;
      audioCtx.resume().then(function () { if (pendingBeep && audioCtx.state === 'running') { pendingBeep = false; play(); } }).catch(function () {});
    } catch (e) {}
  }
  window.jzBeep = function (key) {
    var k = 'jz-beep-' + (key || 'alert'), now = Date.now(), last = 0;
    try { last = +localStorage.getItem(k) || 0; } catch (e) {}
    if (key && now - last < 10 * 60 * 1000) return;
    try { localStorage.setItem(k, String(now)); } catch (e) {}
    beepNow();
  };
  ['pointerdown', 'keydown'].forEach(function (ev) { d.addEventListener(ev, function () { if (pendingBeep) { pendingBeep = false; beepNow(); } }, { once: false }); });
  $$('.limit-alert').forEach(function (a) { window.jzBeep(/WEEKLY/.test(a.textContent) ? 'weekly' : 'daily'); });
  if ($('.panel.tilt')) window.jzBeep('tilt');

  /* ---------------------------------------------------------------- Home Hub: speak a trade, it is logged */
  $$('[data-hub-voice]').forEach(function (form) {
    var mic = $('[data-hub-mic]', form), status = $('[data-hub-status]', form), input = $('[data-quick-input]', form);
    var P = window.jzVoiceParser ? window.jzVoiceParser(JSON.parse(form.dataset.specs || '{}'), []) : null;
    var loc = d.documentElement.dataset.voiceLang || 'en-US', rec = null, timer = null;
    var mark = function () { $$('[data-hub-locale]', form).forEach(function (b) { b.classList.toggle('on', b.dataset.hubLocale === loc); }); };
    $$('[data-hub-locale]', form).forEach(function (b) { b.addEventListener('click', function () { loc = b.dataset.hubLocale; mark(); }); });
    mark();
    var say = function (text, cls) { status.className = 'hub-voice-status' + (cls ? ' ' + cls : ''); status.textContent = text; };
    function logSpoken(heard) {
      if (!P) return;
      var f = P.extract(heard, loc).fields, cmd = P.toCommand(f);
      if (!f.symbol || f.entry === null || !f.side) {
        input.value = cmd; say('Heard “' + heard + '” — missing ' + [!f.side ? 'buy/sell' : '', !f.symbol ? 'instrument' : '', f.entry === null ? 'entry price' : ''].filter(Boolean).join(', ') + '. Tap the mic and try again.', 'bad');
        return;
      }
      input.value = cmd; say('Logging: ' + cmd + ' …');
      post(base + '/quick-trade', { command: cmd, voice: '1', heard: heard }).then(function (r) {
        if (!r || !r.ok) { say((r && r.error) || 'Could not log that trade.', 'bad'); return; }
        status.className = 'hub-voice-status good'; status.textContent = '✓ ' + r.message + ' ';
        var undo = txt('button', 'tm-btn tm-btn-sm', 'Undo'), edit = txt('a', 'tm-btn tm-btn-sm', 'Edit');
        edit.href = base + '/trades/' + r.id + '/edit'; status.appendChild(undo); status.appendChild(edit);
        if (r.alerts && r.alerts.length) { r.alerts.forEach(function (a) { window.jzBeep(/WEEKLY/.test(a) ? 'weekly' : (/TILT/.test(a) ? 'tilt' : 'daily')); }); toast('⚠ ' + r.alerts.join(' · '), 'error'); }
        try { if (window.speechSynthesis) { var u = new SpeechSynthesisUtterance('Trade logged'); u.lang = 'en-US'; window.speechSynthesis.speak(u); } } catch (e) {}
        timer = setTimeout(function () { location.reload(); }, 4500);
        undo.addEventListener('click', function () {
          clearTimeout(timer); undo.disabled = true;
          post(base + '/trades/' + r.id + '/delete', {}).then(function () { say('Trade removed.'); setTimeout(function () { location.reload(); }, 900); });
        });
      });
    }
    if (!SR) { mic.title = 'Voice needs Chrome, Edge or Safari'; mic.addEventListener('click', function () { toast('Voice logging needs Chrome, Edge or Safari with microphone access.', 'error'); }); return; }
    mic.addEventListener('click', function () {
      if (rec) { try { rec.stop(); } catch (e) {} return; }
      var finalText = '';
      rec = new SR(); rec.lang = loc; rec.interimResults = true; rec.continuous = false;
      mic.classList.add('rec'); mic.setAttribute('aria-pressed', 'true'); say('Listening… say your trade');
      rec.onresult = function (ev) { var t = ''; for (var i = 0; i < ev.results.length; i++) { t += ev.results[i][0].transcript; if (ev.results[i].isFinal) finalText = t; } say('“' + t + '”'); };
      rec.onerror = function (ev) { if (ev.error === 'not-allowed' || ev.error === 'service-not-allowed') say('Microphone access is blocked — allow it in your browser settings.', 'bad'); };
      rec.onend = function () { rec = null; mic.classList.remove('rec'); mic.setAttribute('aria-pressed', 'false'); if (finalText.trim()) logSpoken(finalText.trim()); else if (!/blocked/.test(status.textContent)) say('Nothing heard — tap the mic and try again.', 'bad'); };
      try { rec.start(); } catch (e) { rec = null; mic.classList.remove('rec'); }
    });
  });

  /* ---------------------------------------------------------------- TradingView widgets (loaded when visible) */
  // Each .tv-widget carries TradingView's script URL and JSON settings; the script is only added once the widget
  // scrolls into view or its tab is opened, so pages stay fast. TradingView draws the widget inside the container.
  function tvLoad(box) {
    if (box.dataset.tvLoaded) return; box.dataset.tvLoaded = '1';
    var inner = d.createElement('div'); inner.className = 'tradingview-widget-container__widget';
    var wrap = d.createElement('div'); wrap.className = 'tradingview-widget-container'; wrap.appendChild(inner);
    var sc = d.createElement('script'); sc.src = box.dataset.tvSrc; sc.async = true; sc.text = box.dataset.tvConfig;
    sc.onerror = function () { var l = $('.tv-loading', box); if (l) { l.textContent = 'Market widget could not load — check your connection or ad-blocker.'; l.classList.add('err'); } };
    wrap.appendChild(sc); box.appendChild(wrap);
    // Hide the placeholder once TradingView has inserted its frame.
    var tries = 0, t = setInterval(function () { if ($('iframe', box) || ++tries > 60) { clearInterval(t); if (!$('.tv-loading.err', box)) box.classList.add('ready'); } }, 250);
  }
  window.jzTvReload = function (box, patch) {
    var cfg = JSON.parse(box.dataset.tvConfig); for (var k in patch) cfg[k] = patch[k];
    box.dataset.tvConfig = JSON.stringify(cfg); delete box.dataset.tvLoaded; box.classList.remove('ready');
    $$('.tradingview-widget-container', box).forEach(function (n) { n.remove(); }); tvLoad(box);
  };
  var tvBoxes = $$('.tv-widget[data-tv-src]');
  if (tvBoxes.length) {
    if ('IntersectionObserver' in window) {
      var tvIo = new IntersectionObserver(function (es) { es.forEach(function (e) { if (e.isIntersecting) { tvIo.unobserve(e.target); tvLoad(e.target); } }); }, { rootMargin: '200px' });
      tvBoxes.forEach(function (b) { tvIo.observe(b); });
    } else tvBoxes.forEach(tvLoad);
  }
  // Markets page tabs
  $$('[data-mk-tabs]').forEach(function (bar) {
    var tabs = $$('[data-mk-tab]', bar);
    function show(name, push) {
      tabs.forEach(function (t) { var on = t.dataset.mkTab === name; t.classList.toggle('on', on); t.setAttribute('aria-selected', on ? 'true' : 'false'); });
      $$('[data-mk-panel]').forEach(function (p) { p.hidden = p.dataset.mkPanel !== name; });
      if (push && history.replaceState) history.replaceState(null, '', '?tab=' + name);
    }
    tabs.forEach(function (t) { t.addEventListener('click', function () { show(t.dataset.mkTab, true); }); });
  });
  $$('[data-mk-source]').forEach(function (sel) { sel.addEventListener('change', function () { var box = $('.tv-stocks'); if (box) window.jzTvReload(box, { dataSource: sel.value }); }); });

  /* ---------------------------------------------------------------- Segmented radio styling helper */
  $$('.seg input[type=radio]').forEach(function (r) { var sync = function () { $$('input[name="' + r.name + '"]', r.form || d).forEach(function (x) { x.parentNode.classList.toggle('on', x.checked); }); }; r.addEventListener('change', sync); sync(); });
})();
