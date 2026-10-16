/* journzey.ai — Quick Log: instrument (editable favourites) → Buy/Sell → lot size → prices on a scroll wheel.
 * The wheel is centred on a reference price from the server (live quote when market data is configured, otherwise
 * the member's last logged price, clearly labelled); nothing is saved until "Log trade" and the server validates
 * everything again. Date, time and session are set by the server at the moment of logging. */
(function () {
  'use strict';
  var d = document;
  function $(s, c) { return (c || d).querySelector(s); }
  function $$(s, c) { return Array.prototype.slice.call((c || d).querySelectorAll(s)); }
  function el(tag, cls, text) { var n = d.createElement(tag); if (cls) n.className = cls; if (text !== undefined) n.textContent = text; return n; }
  var post = function (u, data) { return window.tmPost(u, data); };
  var getJson = function (u) {
    return fetch(u, { credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (r) { return r.json(); }, function () { return { ok: false, error: 'Connection problem — check your connection.' }; })
      .catch(function () { return { ok: false, error: 'Unexpected server response.' }; });
  };
  var SYM = { USD: '$', EUR: '€', GBP: '£', JPY: '¥', INR: '₹', AUD: 'A$', CAD: 'C$', SGD: 'S$' };
  function money(v, c, sign) { var s = (SYM[c] || c + ' ') + Math.abs(v).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); return (v < 0 ? '−' : (sign && v > 0 ? '+' : '')) + s; }

  $$('[data-quick-log]').forEach(function (root) {
    var base = root.dataset.base, cur = root.dataset.currency, all = JSON.parse(root.dataset.all || '{}');
    var favs = JSON.parse(root.dataset.symbols || '[]'), lotMem = JSON.parse(root.dataset.lots || '{}');
    var st = { sym: null, side: null, lots: null, entry: null, exit: null, stop: null }, ref = {}, step = 1;

    /* ---------------------------------------------------------------- steps */
    function show(n) {
      $$('[data-ql-step]', root).forEach(function (s) { s.hidden = +s.dataset.qlStep !== n; });
      $$('[data-ql-dot]', root).forEach(function (dt) { var k = +dt.dataset.qlDot; dt.classList.toggle('on', k === n); dt.classList.toggle('done', k < n); dt.tabIndex = k < n ? 0 : -1; });
      $('[data-ql-done]', root).hidden = true; closeSheet();
      step = n;
      var first = $('[data-ql-step="' + n + '"] button:not([hidden]), [data-ql-step="' + n + '"] input', root); if (first && root.offsetParent) first.focus({ preventScroll: true });
    }
    $$('[data-ql-dot]', root).forEach(function (dt) {
      var go = function () { if (dt.classList.contains('done')) show(+dt.dataset.qlDot); };
      dt.addEventListener('click', go); dt.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); go(); } });
    });

    /* ---------------------------------------------------------------- step 1: instrument */
    function renderFavs() {
      var box = $('[data-ql-favs]', root); box.textContent = '';
      favs.forEach(function (s) {
        var b = el('button', 'ql-chip ql-sym'); b.type = 'button'; b.setAttribute('role', 'radio'); b.setAttribute('aria-checked', String(st.sym === s)); b.dataset.qlSym = s;
        b.appendChild(el('b', '', s)); b.appendChild(el('small', '', (all[s] || {}).n || ''));
        b.addEventListener('click', function () { pickSymbol(s); });
        box.appendChild(b);
      });
    }
    function pickSymbol(s) {
      st.sym = s; st.entry = st.exit = st.stop = null; renderFavs();
      $('[data-ql-sym-label]', root).textContent = s;
      loadRef(s);
      show(2);
    }
    function loadRef(s) {
      if (ref[s] && Date.now() - ref[s].at < 15000) { applyRefLots(s); return Promise.resolve(ref[s]); }
      return getJson(base + '/price/' + encodeURIComponent(s)).then(function (r) {
        if (!r || !r.ok) { ref[s] = { price: null, label: (r && r.error) || 'Price unavailable — type a price', step: 0.01, tick: 0.01, decimals: 2, lot_step: 0.01, min_lot: 0.01, at: Date.now() }; }
        else { r.at = Date.now(); ref[s] = r; }
        applyRefLots(s); return ref[s];
      });
    }
    function applyRefLots(s) {
      var r = ref[s] || {}, inp = $('[data-ql-lots]', root);
      var l = lotMem[s] || r.last_lots || (st.lots && st.sym === s ? st.lots : null) || 0.1;
      inp.step = r.lot_step || 0.01; inp.min = r.min_lot || 0.01; inp.value = (+l).toFixed(2); st.lots = +l;
    }
    // favourites editor
    var editor = $('[data-ql-editor]', root), editBtn = $('[data-ql-edit]', root), pending = [];
    function renderAll(q) {
      var box = $('[data-ql-all]', root); box.textContent = ''; q = (q || '').toLowerCase().replace(/[\s\/]/g, '');
      Object.keys(all).forEach(function (s) {
        var hay = (s + ' ' + all[s].n).toLowerCase().replace(/[\s\/]/g, ''); if (q && hay.indexOf(q) < 0) return;
        var lb = el('label', 'ql-opt'), cb = d.createElement('input'); cb.type = 'checkbox'; cb.value = s; cb.checked = pending.indexOf(s) >= 0;
        cb.addEventListener('change', function () {
          var i = pending.indexOf(s);
          if (cb.checked && i < 0) { if (pending.length >= 8) { cb.checked = false; window.tmToast && window.tmToast('Up to 8 instruments.', 'error'); return; } pending.push(s); }
          if (!cb.checked && i >= 0) pending.splice(i, 1);
        });
        lb.appendChild(cb); lb.appendChild(el('b', '', s)); lb.appendChild(el('span', 'muted small', all[s].n));
        box.appendChild(lb);
      });
    }
    editBtn.addEventListener('click', function () { var open = editor.hidden; editor.hidden = !open; editBtn.setAttribute('aria-expanded', String(open)); if (open) { pending = favs.slice(); renderAll(''); $('[data-ql-find]', root).value = ''; $('[data-ql-find]', root).focus(); } });
    $('[data-ql-find]', root).addEventListener('input', function (e) { renderAll(e.target.value); });
    $('[data-ql-edit-cancel]', root).addEventListener('click', function () { editor.hidden = true; editBtn.setAttribute('aria-expanded', 'false'); });
    $('[data-ql-edit-save]', root).addEventListener('click', function () {
      if (!pending.length) { window.tmToast && window.tmToast('Choose at least one instrument.', 'error'); return; }
      var b = this; b.disabled = true;
      post(base + '/quick-log/prefs', { symbols: pending.join(',') }).then(function (r) {
        b.disabled = false;
        if (!r || !r.ok) { window.tmToast && window.tmToast((r && r.error) || 'Could not save.', 'error'); return; }
        favs = r.symbols; editor.hidden = true; editBtn.setAttribute('aria-expanded', 'false'); renderFavs();
        // keep every Quick Log on the page (Home Hub + Quick trade dialog) in sync
        $$('[data-quick-log]').forEach(function (o) { if (o !== root && o._setFavs) o._setFavs(favs); });
        window.tmToast && window.tmToast('Your instruments are saved.', 'success');
      });
    });
    root._setFavs = function (f) { favs = f; renderFavs(); };

    /* ---------------------------------------------------------------- step 2: side */
    $$('[data-ql-side]', root).forEach(function (b) {
      b.addEventListener('click', function () {
        st.side = b.dataset.qlSide; st.stop = null;
        $$('[data-ql-side]', root).forEach(function (x) { x.setAttribute('aria-checked', String(x === b)); });
        show(3);
      });
    });

    /* ---------------------------------------------------------------- step 3: lots */
    var lotIn = $('[data-ql-lots]', root);
    function setLots(v) { var r = ref[st.sym] || {}, stp = r.lot_step || 0.01, mn = r.min_lot || 0.01; v = Math.max(mn, Math.round(v / stp) * stp); lotIn.value = v.toFixed(2); st.lots = +lotIn.value; }
    $$('[data-ql-lot]', root).forEach(function (b) { b.addEventListener('click', function () { var r = ref[st.sym] || {}; setLots((+lotIn.value || 0) + (+b.dataset.qlLot) * (r.lot_step || 0.01)); }); });
    $$('[data-ql-lot-set]', root).forEach(function (b) { b.addEventListener('click', function () { setLots(+b.dataset.qlLotSet); }); });
    lotIn.addEventListener('input', function () { st.lots = +lotIn.value; });
    $('[data-ql-next="4"]', root).addEventListener('click', function () {
      if (!(+lotIn.value > 0)) { window.tmToast && window.tmToast('Enter a positive lot size.', 'error'); lotIn.focus(); return; }
      st.lots = +lotIn.value; paintPrices(); show(4);
    });
    $('[data-ql-back]', root).addEventListener('click', function () { show(3); });

    /* ---------------------------------------------------------------- step 4: prices */
    function dec() { var r = ref[st.sym] || {}; return r.decimals != null ? r.decimals : 2; }
    function fmt(v) { return v === null || v === undefined ? null : (+v).toFixed(dec()); }
    function validate() {
      var e = {};
      if (st.entry === null) e.entry = 'Set the entry price.';
      if (st.stop !== null && st.entry !== null) {
        if (st.stop === st.entry) e.stop = 'Stop loss cannot equal entry.';
        else if (st.side === 'LONG' && st.stop > st.entry) e.stop = 'A buy stop loss must be below entry.';
        else if (st.side === 'SHORT' && st.stop < st.entry) e.stop = 'A sell stop loss must be above entry.';
      }
      return e;
    }
    function paintPrices() {
      var a = all[st.sym] || {}, dir = st.side === 'LONG' ? 1 : -1;
      $('[data-ql-summary]', root).textContent = st.sym + ' · ' + (st.side === 'LONG' ? 'BUY' : 'SELL') + ' · ' + (+st.lots).toFixed(2) + ' lots';
      ['entry', 'exit', 'stop'].forEach(function (f) { var b = $('[data-ql-val="' + f + '"]', root); b.textContent = st[f] === null ? (f === 'entry' ? 'Tap to set' : '—') : fmt(st[f]); });
      var e = validate(), out = $('[data-ql-result]', root); out.textContent = '';
      $$('[data-ql-field]', root).forEach(function (b) { b.classList.toggle('bad', !!e[b.dataset.qlField]); });
      Object.keys(e).forEach(function (k) { if (k !== 'entry' || st.exit !== null || st.stop !== null) out.appendChild(el('p', 'ql-err', e[k])); });
      if (!Object.keys(e).length) {
        var parts = [], conv = a.q === cur ? 1 : null;
        if (st.stop !== null) parts.push(['Risk', money(-Math.abs(st.entry - st.stop) * st.lots * a.k * (conv || 1), conv ? cur : a.q), 'down']);
        if (st.exit !== null) {
          var pnl = (st.exit - st.entry) * dir * st.lots * a.k;
          parts.push(['P&L', money(pnl * (conv || 1), conv ? cur : a.q, true) + (conv ? '' : ' (≈, converted on save)'), pnl >= 0 ? 'up' : 'down']);
          if (st.stop !== null) { var rr = (st.exit - st.entry) * dir / Math.abs(st.entry - st.stop); parts.push(['R', (rr > 0 ? '+' : '') + rr.toFixed(2) + 'R', rr >= 0 ? 'up' : 'down']); }
        } else parts.push(['Status', 'OPEN trade (no exit yet)', '']);
        parts.forEach(function (p) { var s = el('span', 'ql-kv'); s.appendChild(el('small', '', p[0])); s.appendChild(el('b', p[2], p[1])); out.appendChild(s); });
      }
      $('[data-ql-save]', root).disabled = !!Object.keys(e).length;
    }
    $$('[data-ql-field]', root).forEach(function (b) { b.addEventListener('click', function () { openSheet(b.dataset.qlField); }); });

    /* ---------------------------------------------------------------- the price wheel */
    var sheet = $('[data-ql-sheet]', root), wheel = $('[data-ql-wheel]', root), typeIn = $('[data-ql-type]', root), stepLbl = $('[data-ql-steplbl]', root);
    var ROW = 40, SPAN = 150, wField = null, wStep = 1, wVals = [], wIdx = SPAN, scrollT = null, opener = null;
    var TITLES = { entry: 'Entry price', exit: 'Exit price', stop: 'Stop loss' };
    function snap(v) { var r = ref[st.sym] || {}, t = r.tick || 0.01; return +(Math.round(v / t) * t).toFixed(dec()); }
    function build(center) {
      wheel.textContent = ''; wVals = [];
      if (center === null || !(center > 0)) { wheel.appendChild(el('div', 'ql-wheel-empty', 'Type a price below to start the wheel')); wheel.removeAttribute('aria-valuenow'); return; }
      center = snap(center);
      wheel.appendChild(el('div', 'ql-spacer'));
      for (var i = -SPAN; i <= SPAN; i++) {
        var v = +(center + i * wStep).toFixed(dec());
        wVals.push(v);
        var row = el('div', 'ql-row', v > 0 ? v.toFixed(dec()) : ''); row.dataset.i = wVals.length - 1;
        if (v === ref[st.sym].price) row.classList.add('live');
        wheel.appendChild(row);
      }
      wheel.appendChild(el('div', 'ql-spacer'));
      setIdx(SPAN, false);
    }
    function setIdx(i, smooth) {
      wIdx = Math.max(0, Math.min(wVals.length - 1, i));
      if (wVals[wIdx] <= 0) { wIdx = wVals.findIndex(function (v) { return v > 0; }); }
      wheel.scrollTo({ top: wIdx * ROW, behavior: smooth ? 'smooth' : 'auto' });
      paintWheel();
    }
    function paintWheel() {
      $$('.ql-row', wheel).forEach(function (r) { r.classList.toggle('on', +r.dataset.i === wIdx); });
      var v = wVals[wIdx]; if (v !== undefined) { wheel.setAttribute('aria-valuenow', String(v)); wheel.setAttribute('aria-valuetext', v.toFixed(dec())); }
    }
    wheel.addEventListener('scroll', function () {
      if (!wVals.length) return;
      var i = Math.round(wheel.scrollTop / ROW); if (i !== wIdx && wVals[i] > 0) { wIdx = i; paintWheel(); }
      clearTimeout(scrollT);
      scrollT = setTimeout(function () {
        // recentre the list when the member scrolls near either end, so the wheel feels endless
        if (wIdx < 25 || wIdx > wVals.length - 26) build(wVals[wIdx]); else setIdx(wIdx, true);
      }, 120);
    }, { passive: true });
    // mouse drag (touch scrolling is native)
    var drag = null;
    wheel.addEventListener('pointerdown', function (e) { if (e.pointerType !== 'mouse' || !wVals.length) return; drag = { y: e.clientY, top: wheel.scrollTop }; wheel.setPointerCapture(e.pointerId); wheel.classList.add('dragging'); });
    wheel.addEventListener('pointermove', function (e) { if (drag) wheel.scrollTop = drag.top - (e.clientY - drag.y); });
    var endDrag = function () { if (!drag) return; drag = null; wheel.classList.remove('dragging'); setIdx(Math.round(wheel.scrollTop / ROW), true); };
    wheel.addEventListener('pointerup', endDrag); wheel.addEventListener('pointercancel', endDrag);
    wheel.addEventListener('click', function (e) { var r = e.target.closest('.ql-row'); if (r && !drag) setIdx(+r.dataset.i, true); });
    wheel.addEventListener('keydown', function (e) {
      var k = { ArrowDown: 1, ArrowUp: -1, PageDown: 10, PageUp: -10 }[e.key];
      if (k) { e.preventDefault(); var n = wIdx + k; if (n < 25 || n > wVals.length - 26) { build(wVals[wIdx] + k * wStep); } else setIdx(n, true); }
      else if (e.key === 'Enter') { e.preventDefault(); commit(); }
    });
    function setStep(s) {
      var r = ref[st.sym] || {}, t = r.tick || 0.01;
      wStep = +Math.max(t, +(Math.round(s / t) * t).toFixed(8)).toFixed(8);
      stepLbl.textContent = 'step ' + (+wStep.toFixed(8));
    }
    $$('[data-ql-stepx]', sheet).forEach(function (b) { b.addEventListener('click', function () { var c = wVals[wIdx]; setStep(wStep * +b.dataset.qlStepx); build(c === undefined ? null : c); }); });
    // Same rule as the server (QuickLog::step): 1 pip for forex, otherwise ~1/4000 of the price rounded to 1-2-5.
    function niceStep(price) {
      var r = ref[st.sym] || {}, t = r.tick || 0.01;
      if (r['class'] === 'FOREX') return Math.max(r.pip || t, t);
      if (!(price > 0)) return Math.max(r.pip || t, t);
      var raw = price / 4000, mag = Math.pow(10, Math.floor(Math.log10(raw))), n = raw / mag;
      return Math.max(Math.round(((n < 1.5 ? 1 : n < 3.5 ? 2 : n < 7.5 ? 5 : 10) * mag) / t) * t, t);
    }
    function startFromTyped() {
      var v = +typeIn.value; if (!(v > 0)) return;
      if (!wVals.length) setStep(niceStep(v)); // the wheel had no price yet: pick a step that suits this price
      build(v); wheel.focus();
    }
    typeIn.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); startFromTyped(); } });
    typeIn.addEventListener('change', startFromTyped);
    function openSheet(field) {
      wField = field; opener = $('[data-ql-field="' + field + '"]', root);
      var r = ref[st.sym] || {};
      $('[data-ql-wtitle]', sheet).textContent = TITLES[field] + ' · ' + st.sym;
      $('[data-ql-src]', sheet).textContent = r.label || '';
      $('[data-ql-src]', sheet).className = 'small ' + (r.source === 'live' ? 'up' : 'muted');
      $('[data-ql-clear]', sheet).hidden = field === 'entry';
      typeIn.value = '';
      var center = st[field] !== null ? st[field] : (field !== 'entry' && st.entry !== null ? st.entry : r.price);
      setStep(r.price ? (r.step || 1) : niceStep(center));
      // On phones the sheet docks to the bottom of the screen; it lives in <body> so no animated parent can offset it.
      var phone = window.matchMedia && window.matchMedia('(max-width: 640px)').matches;
      if (phone && sheet.parentNode !== d.body) d.body.appendChild(sheet); else if (!phone && sheet.parentNode !== root) root.appendChild(sheet);
      sheet.classList.toggle('ql-docked', phone);
      sheet.hidden = false; build(center === undefined ? null : center);
      if (wVals.length) wheel.focus(); else typeIn.focus();
    }
    function closeSheet() { if (!sheet.hidden) { sheet.hidden = true; if (opener) opener.focus({ preventScroll: true }); } }
    function commit() {
      var v = wVals[wIdx];
      if (+typeIn.value > 0 && document.activeElement === typeIn) v = snap(+typeIn.value);
      if (!(v > 0)) { typeIn.focus(); return; }
      st[wField] = v; closeSheet(); paintPrices();
      // after the entry, go straight on to the next empty price
      if (wField === 'entry' && st.exit === null && st.stop === null) setTimeout(function () { openSheet('exit'); }, 60);
    }
    $('[data-ql-set]', sheet).addEventListener('click', commit);
    $('[data-ql-wcancel]', sheet).addEventListener('click', closeSheet);
    $('[data-ql-clear]', sheet).addEventListener('click', function () { st[wField] = null; closeSheet(); paintPrices(); });
    sheet.addEventListener('keydown', function (e) { if (e.key === 'Escape') { e.stopPropagation(); closeSheet(); } });

    /* ---------------------------------------------------------------- save */
    $('[data-ql-save]', root).addEventListener('click', function () {
      var b = this; if (Object.keys(validate()).length) return;
      b.disabled = true; b.classList.add('busy');
      post(base + '/quick-log', { symbol: st.sym, side: st.side, lots: st.lots, entry: fmt(st.entry), exit: fmt(st.exit) || '', stop: fmt(st.stop) || '' }).then(function (r) {
        b.disabled = false; b.classList.remove('busy');
        var done = $('[data-ql-done]', root); done.textContent = '';
        if (!r || !r.ok) { var p = el('p', 'ql-err', (r && r.error) || 'Could not log the trade.'); $('[data-ql-result]', root).appendChild(p); return; }
        lotMem[st.sym] = st.lots; delete ref[st.sym]; // the next wheel can start from the price just logged
        $$('[data-ql-step]', root).forEach(function (s) { s.hidden = true; });
        done.appendChild(el('p', 'ql-ok', '✓ ' + r.message));
        (r.alerts || []).forEach(function (a) { done.appendChild(el('p', 'ql-err', a)); });
        var acts = el('div', 'ql-actions'), more = el('a', 'tm-btn tm-btn-primary', 'Add setup, emotions & notes'); more.href = r.edit;
        var again = el('button', 'tm-btn', 'Log another'); again.type = 'button';
        again.addEventListener('click', function () { st = { sym: null, side: null, lots: null, entry: null, exit: null, stop: null }; renderFavs(); show(1); });
        acts.appendChild(again); acts.appendChild(more); done.appendChild(acts); done.hidden = false;
        $$('[data-ql-dot]', root).forEach(function (dt) { dt.classList.add('done'); dt.classList.remove('on'); });
        if (window.jzBeep) (r.alerts || []).forEach(function (a) { window.jzBeep(/WEEKLY/.test(a) ? 'weekly' : /TILT/.test(a) ? 'tilt' : 'daily'); });
        d.dispatchEvent(new CustomEvent('jz:trade-logged', { detail: r }));
      });
    });

    renderFavs(); show(1);
  });
})();
