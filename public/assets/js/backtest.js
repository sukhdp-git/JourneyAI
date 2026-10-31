/* journzey.ai Backtester — rule builder, price-history loading/upload, run + results. The server does every calculation. */
(function () {
  'use strict';
  var d = document, root = d.querySelector('[data-bt]');
  if (!root) return;
  var C = JSON.parse(root.dataset.cfg), post = window.tmPost, toast = window.tmToast || function () {};
  function $(s, c) { return (c || root).querySelector(s); }
  function $$(s, c) { return Array.prototype.slice.call((c || root).querySelectorAll(s)); }
  function el(tag, cls, text) { var n = d.createElement(tag); if (cls) n.className = cls; if (text != null) n.textContent = text; return n; }
  function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
  function fmtR(v) { v = +v; return (v > 0 ? '+' : v < 0 ? '−' : '') + Math.abs(v).toFixed(2) + 'R'; }
  function money(v) { v = +v; return (v < 0 ? '−$' : '$') + Math.abs(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
  function pct(v) { v = +v; return (v > 0 ? '+' : v < 0 ? '−' : '') + Math.abs(v).toFixed(2) + '%'; }
  function day(ts) { return new Date(ts * 1000).toISOString().slice(0, 10); }
  function dt(ts) { return new Date(ts * 1000).toISOString().slice(0, 16).replace('T', ' '); }
  var NEEDS_N = { ema: 20, sma: 50, rsi: 14, atr: 14, bb_upper: 20, bb_mid: 20, bb_lower: 20, hh: 20, ll: 20 };

  // ---------------------------------------------------------------- tabs + datasets
  $$('[data-bt-tab]').forEach(function (b) {
    b.addEventListener('click', function () {
      $$('[data-bt-tab]').forEach(function (x) { var on = x === b; x.classList.toggle('on', on); x.setAttribute('aria-selected', String(on)); });
      $$('[data-bt-pane]').forEach(function (p) { p.hidden = p.dataset.btPane !== b.dataset.btTab; });
    });
  });
  var ds = null;
  function selectDs(lbl) {
    $$('.bt-ds').forEach(function (x) { x.classList.toggle('sel', x === lbl); });
    var r = $('input[type=radio]', lbl); if (r) r.checked = true;
    ds = { id: +lbl.dataset.btDs, tf: +lbl.dataset.tf, first: +lbl.dataset.first, last: +lbl.dataset.last, symbol: lbl.dataset.symbol };
    var from = Math.max(ds.first, ds.last - C.years * 365 * 86400);
    $('[data-bt-from]').value = day(from); $('[data-bt-to]').value = day(ds.last);
    $('[data-bt-from]').min = $('[data-bt-to]').min = day(ds.first); $('[data-bt-from]').max = $('[data-bt-to]').max = day(ds.last);
    var sel = $('[data-bt-tf]');
    Array.prototype.forEach.call(sel.options, function (o) { o.disabled = C.tfs[o.value] < ds.tf; });
    if (sel.selectedOptions[0].disabled) sel.value = Object.keys(C.tfs).filter(function (k) { return C.tfs[k] >= ds.tf; })[0];
  }
  root.addEventListener('change', function (e) { var lbl = e.target.closest('.bt-ds'); if (e.target.name === 'bt-ds' && lbl) selectDs(lbl); });
  var firstReady = $('.bt-ds[data-ready="1"]'); if (firstReady) selectDs(firstReady);

  // Twelve Data: one page per request until 2 years are stored
  $$('[data-bt-load]').forEach(function (b) {
    b.addEventListener('click', function (e) {
      e.preventDefault(); var lbl = b.closest('.bt-ds'), bar = $('.bt-prog', lbl), info = $('[data-bt-dsinfo]', lbl), sym = b.dataset.btLoad;
      b.disabled = true; bar.hidden = false;
      (function step() {
        post(C.urls.fetch, { symbol: sym }).then(function (j) {
          if (!j.ok) { b.disabled = false; bar.hidden = true; info.textContent = j.error || 'Could not load history.'; toast(j.error || 'Could not load history.', 'error'); return; }
          $('span', bar).style.width = (j.progress || 2) + '%';
          if (j.wait) { var n = j.wait; info.textContent = 'Data provider limit reached, continuing in ' + n + 's…'; var iv = setInterval(function () { n--; info.textContent = 'Data provider limit reached, continuing in ' + n + 's…'; if (n <= 0) { clearInterval(iv); step(); } }, 1000); return; }
          info.textContent = (j.rows || 0).toLocaleString() + ' candles loaded…';
          if (!j.done) return step();
          bar.hidden = true; b.disabled = false; b.lastChild.textContent = ' Update';
          lbl.classList.remove('empty'); lbl.dataset.ready = '1'; lbl.dataset.btDs = j.id; lbl.dataset.first = j.first; lbl.dataset.last = j.last;
          var r = $('input[type=radio]', lbl); r.disabled = false; r.value = j.id;
          info.textContent = (j.rows || 0).toLocaleString() + ' candles · ' + day(j.first) + ' – ' + day(j.last);
          selectDs(lbl); toast(sym + ': price history ready.', 'success');
        });
      })();
    });
  });

  var up = $('[data-bt-upload]');
  if (up) {
    $('input[type=file]', up).addEventListener('change', function () { $('[data-bt-filename]', up).textContent = this.files[0] ? this.files[0].name : 'Choose a CSV of candles'; });
    up.addEventListener('submit', function (e) {
      e.preventDefault(); var btn = $('button[type=submit]', up); btn.disabled = true; btn.lastChild.textContent = ' Reading candles…';
      post(C.urls.upload, new FormData(up)).then(function (j) {
        btn.disabled = false; btn.lastChild.textContent = ' Upload candles';
        if (!j.ok) { toast(j.error || 'Upload failed.', 'error'); return; }
        toast(j.message, 'success'); var x = j.dataset;
        var lbl = el('label', 'bt-ds'); lbl.dataset.btDs = x.id; lbl.dataset.symbol = x.symbol; lbl.dataset.tf = x.tf; lbl.dataset.first = x.first_ts; lbl.dataset.last = x.last_ts; lbl.dataset.ready = '1';
        lbl.innerHTML = '<input type="radio" name="bt-ds" value="' + x.id + '"><b>' + esc(x.symbol) + '</b><small>' + esc(x.name) + ' · ' + (+x.rows_count).toLocaleString() + ' candles · ' + day(x.first_ts) + ' – ' + day(x.last_ts) + '</small>';
        $('[data-bt-own]').prepend(lbl); selectDs(lbl); up.reset(); $('[data-bt-filename]', up).textContent = 'Choose a CSV of candles';
      });
    });
  }
  root.addEventListener('click', function (e) {
    var b = e.target.closest('[data-bt-dsdel]'); if (!b) return; e.preventDefault();
    if (!confirm('Delete this uploaded dataset?')) return;
    post(C.urls.datasets + '/' + b.dataset.btDsdel + '/delete', {}).then(function (j) { if (j.ok) { var l = b.closest('.bt-ds'); if (l.classList.contains('sel')) ds = null; l.remove(); } });
  });

  // ---------------------------------------------------------------- rule builder
  var state = { long: [], short: [] };
  function operandSel(val, allowValue) {
    var s = el('select'); s.setAttribute('aria-label', 'Value');
    var groups = { Price: ['close', 'open', 'high', 'low'], Indicator: ['ema', 'sma', 'rsi', 'atr', 'macd', 'macd_signal', 'macd_hist', 'bb_upper', 'bb_mid', 'bb_lower', 'hh', 'll'] };
    if (allowValue) groups.Other = ['value'];
    Object.keys(groups).forEach(function (g) { var og = el('optgroup'); og.label = g; groups[g].forEach(function (k) { var o = el('option', null, C.operands[k]); o.value = k; if (k === val) o.selected = true; og.appendChild(o); }); s.appendChild(og); });
    return s;
  }
  function operandUi(o, allowValue, onChange) {
    var w = el('span', 'bt-opnd'), s = operandSel(o.k, allowValue), n = el('input'); n.type = 'number'; n.className = 'bt-n-in'; n.step = 'any';
    function sync() {
      var k = s.value; o.k = k;
      if (k === 'value') { n.hidden = false; n.placeholder = 'number'; n.setAttribute('aria-label', 'Number'); if (o.v == null) o.v = 0; n.value = o.v; delete o.n; }
      else if (NEEDS_N[k]) { n.hidden = false; n.placeholder = 'length'; n.setAttribute('aria-label', 'Length'); if (!o.n) o.n = NEEDS_N[k]; n.value = o.n; delete o.v; }
      else { n.hidden = true; delete o.n; delete o.v; }
      onChange && onChange();
    }
    s.addEventListener('change', sync); n.addEventListener('input', function () { if (o.k === 'value') o.v = n.value; else o.n = n.value; });
    w.appendChild(s); w.appendChild(n); sync(); return w;
  }
  function row(side, c, i) {
    var r = el('div', 'bt-cond');
    if (c.pattern) {
      var p = el('select'); p.setAttribute('aria-label', 'Candle pattern');
      Object.keys(C.patterns).forEach(function (k) { var o = el('option', null, C.patterns[k]); o.value = k; if (k === c.pattern) o.selected = true; p.appendChild(o); });
      p.addEventListener('change', function () { c.pattern = p.value; });
      r.appendChild(el('span', 'bt-ptag', 'Candle is')); r.appendChild(p);
    } else {
      r.appendChild(operandUi(c.a, false));
      var op = el('select'); op.setAttribute('aria-label', 'Comparison');
      Object.keys(C.ops).forEach(function (k) { var o = el('option', null, C.ops[k]); o.value = k; if (k === c.op) o.selected = true; op.appendChild(o); });
      op.addEventListener('change', function () { c.op = op.value; }); r.appendChild(op);
      r.appendChild(operandUi(c.b, true));
    }
    var x = el('button', 'bt-x'); x.type = 'button'; x.setAttribute('aria-label', 'Remove condition'); x.innerHTML = '&times;';
    x.addEventListener('click', function () { state[side].splice(i, 1); draw(side); });
    r.appendChild(x); return r;
  }
  function draw(side) { var list = $('[data-bt-list="' + side + '"]'); list.innerHTML = ''; state[side].forEach(function (c, i) { list.appendChild(row(side, c, i)); }); if (!state[side].length) list.appendChild(el('p', 'bt-empty', 'No conditions yet.')); }
  $$('[data-bt-add]').forEach(function (b) { b.addEventListener('click', function () { state[b.dataset.btAdd].push({ a: { k: 'close' }, op: 'crosses_above', b: { k: 'ema', n: 50 } }); draw(b.dataset.btAdd); }); });
  $$('[data-bt-addp]').forEach(function (b) { b.addEventListener('click', function () { state[b.dataset.btAddp].push({ pattern: b.dataset.btAddp === 'long' ? 'bull_engulf' : 'bear_engulf' }); draw(b.dataset.btAddp); }); });
  var SWAP = { crosses_above: 'crosses_below', crosses_below: 'crosses_above', gt: 'lt', lt: 'gt', hh: 'll', ll: 'hh', bb_upper: 'bb_lower', bb_lower: 'bb_upper', bull_engulf: 'bear_engulf', bear_engulf: 'bull_engulf', pin_bull: 'pin_bear', pin_bear: 'pin_bull', green: 'red', red: 'green' };
  function mirrorOperand(o, other) {
    var m = JSON.parse(JSON.stringify(o)); if (SWAP[m.k]) m.k = SWAP[m.k];
    if (m.k === 'value' && other) { if (other.k === 'rsi') m.v = 100 - (+m.v); else if (/^macd/.test(other.k)) m.v = -(+m.v); }
    return m;
  }
  function mirror(list) { return list.map(function (c) { return c.pattern ? { pattern: SWAP[c.pattern] || c.pattern } : { a: mirrorOperand(c.a, c.b), op: SWAP[c.op], b: mirrorOperand(c.b, c.a) }; }); }
  $('[data-bt-mirror]').addEventListener('click', function () { state.short = mirror(state.long); draw('short'); });
  var sideSel = $('[data-bt-side]');
  function sideSync() { $('[data-bt-block="long"]').hidden = sideSel.value === 'short'; $('[data-bt-block="short"]').hidden = sideSel.value === 'long'; }
  sideSel.addEventListener('change', sideSync);

  var PRESETS = {
    ema: { long: [{ a: { k: 'ema', n: 20 }, op: 'crosses_above', b: { k: 'ema', n: 50 } }], sl: ['atr', 1.5], tp: ['r', 2] },
    breakout: { long: [{ a: { k: 'close' }, op: 'crosses_above', b: { k: 'hh', n: 20 } }], sl: ['atr', 2], tp: ['r', 2] },
    rsi: { long: [{ a: { k: 'close' }, op: 'gt', b: { k: 'ema', n: 200 } }, { a: { k: 'rsi', n: 14 }, op: 'crosses_above', b: { k: 'value', v: 30 } }], sl: ['atr', 1.5], tp: ['r', 1.5] },
    bb: { long: [{ a: { k: 'close' }, op: 'crosses_above', b: { k: 'bb_lower', n: 20 } }], sl: ['swing', 1, 10], tp: ['r', 1.5] },
    macd: { long: [{ a: { k: 'macd' }, op: 'crosses_above', b: { k: 'macd_signal' } }, { a: { k: 'close' }, op: 'gt', b: { k: 'ema', n: 200 } }], sl: ['atr', 1.5], tp: ['r', 2] },
  };
  function f(name) { return $('[data-bt-f="' + name + '"]'); }
  $$('[data-bt-preset]').forEach(function (b) {
    b.addEventListener('click', function () {
      var p = PRESETS[b.dataset.btPreset]; state.long = JSON.parse(JSON.stringify(p.long)); state.short = mirror(state.long);
      f('sl.type').value = p.sl[0]; f('sl.value').value = p.sl[1]; if (p.sl[2]) f('sl.len').value = p.sl[2]; f('tp.type').value = p.tp[0]; f('tp.value').value = p.tp[1];
      sideSel.value = 'both'; sideSync(); exitSync(); draw('long'); draw('short');
      $$('[data-bt-preset]').forEach(function (x) { x.classList.toggle('on', x === b); });
    });
  });
  function exitSync() {
    var t = f('sl.type').value;
    $('[data-bt-sllbl]').textContent = { atr: '× ATR', swing: 'Extra buffer (ATR, 0 = none)', points: 'Price points', percent: '% of price' }[t];
    $('[data-bt-sllen]').hidden = !(t === 'atr' || t === 'swing');
    f('sl.value').closest('label').hidden = t === 'swing';
    $('[data-bt-tpval]').hidden = f('tp.type').value === 'none';
    $('[data-bt-tpval]').firstChild.textContent = { r: 'Target (R)', atr: 'Target (× ATR)', points: 'Target (price points)', none: '' }[f('tp.type').value];
  }
  f('sl.type').addEventListener('change', exitSync); f('tp.type').addEventListener('change', exitSync);

  function collect() {
    var num = function (n) { var v = f(n).value; return v === '' ? null : +v; };
    return {
      side: sideSel.value, long: state.long, short: state.short,
      filters: { sessions: $$('[data-bt-sessions] input:checked').map(function (x) { return x.value; }), days: $$('[data-bt-days] input:checked').map(function (x) { return +x.value; }) },
      exit: { sl: { type: f('sl.type').value, value: num('sl.value'), len: num('sl.len') }, tp: { type: f('tp.type').value, value: num('tp.value') }, be_at_r: num('be_at_r'), max_bars: num('max_bars'), opposite: f('opposite').checked },
      risk: { balance: num('balance'), risk_pct: num('risk_pct'), spread: num('spread') || 0, max_per_day: num('max_per_day') || 0 },
    };
  }
  function load(r) {
    state.long = r.long || []; state.short = r.short || []; sideSel.value = r.side; sideSync();
    f('sl.type').value = r.exit.sl.type; f('sl.value').value = r.exit.sl.value; f('sl.len').value = r.exit.sl.len; f('tp.type').value = r.exit.tp.type; f('tp.value').value = r.exit.tp.value;
    f('be_at_r').value = r.exit.be_at_r || ''; f('max_bars').value = r.exit.max_bars || ''; f('opposite').checked = !!r.exit.opposite;
    f('balance').value = r.risk.balance; f('risk_pct').value = r.risk.risk_pct; f('spread').value = r.risk.spread; f('max_per_day').value = r.risk.max_per_day || '';
    $$('[data-bt-sessions] input').forEach(function (x) { x.checked = r.filters.sessions.indexOf(x.value) >= 0; });
    $$('[data-bt-days] input').forEach(function (x) { x.checked = r.filters.days.indexOf(+x.value) >= 0; });
    exitSync(); draw('long'); draw('short');
  }
  // sensible default so the page is never empty
  $('[data-bt-preset="ema"]').click();

  // ---------------------------------------------------------------- run
  var err = $('[data-bt-err]'), runBtn = $('[data-bt-run]');
  function showErr(m) { err.textContent = m; err.hidden = !m; }
  runBtn.addEventListener('click', function () {
    showErr('');
    if (!ds || !ds.id) { showErr('Choose (or load) the price history in step 1 first.'); return; }
    runBtn.disabled = true; runBtn.classList.add('busy'); var label = runBtn.lastChild.textContent; runBtn.lastChild.textContent = ' Testing every candle…';
    post(C.urls.run, { dataset_id: ds.id, tf: $('[data-bt-tf]').value, from: $('[data-bt-from]').value, to: $('[data-bt-to]').value, name: $('[data-bt-name]').value, rules: JSON.stringify(collect()) }).then(function (j) {
      runBtn.disabled = false; runBtn.classList.remove('busy'); runBtn.lastChild.textContent = label;
      if (!j.ok) { showErr(j.error || 'The test could not run.'); return; }
      if (j.used != null) { $('[data-bt-used]').textContent = j.used; $('[data-bt-usedbar]').style.width = Math.min(100, j.used / j.limit * 100) + '%'; }
      render(j); addRun(j);
    });
  });
  function addRun(j) {
    var ul = $('[data-bt-runs]'), e = $('.bt-empty', ul); if (e) e.remove();
    var li = el('li'); li.innerHTML = '<button type="button" data-bt-open="' + j.id + '"><b>' + esc(j.name) + '</b><span>' + esc(j.summary.symbol) + ' · ' + esc(j.summary.tf) + ' · ' + j.summary.trades + ' trades · <em class="' + (j.summary.net_r >= 0 ? 'up' : 'dn') + '">' + fmtR(j.summary.net_r) + '</em></span></button>';
    ul.prepend(li);
  }
  root.addEventListener('click', function (e) {
    var b = e.target.closest('[data-bt-open]'); if (!b) return;
    fetch(C.urls.runs + '/' + b.dataset.btOpen, { credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }).then(function (r) { return r.json(); }).then(function (j) { if (j.ok) render(j); else toast(j.error || 'Could not open the test.', 'error'); });
  });

  // ---------------------------------------------------------------- results
  var out = $('[data-bt-results]');
  function kpi(lbl, val, cls, sub) { return '<div class="bt-kpi ' + (cls || '') + '"><span>' + lbl + '</span><b>' + val + '</b>' + (sub ? '<small>' + sub + '</small>' : '') + '</div>'; }
  function render(j) {
    var s = j.summary, sign = function (v) { return v > 0 ? 'up' : v < 0 ? 'dn' : ''; };
    var h = '<header class="bt-res-head"><div><span class="bt-kicker">' + esc(s.symbol) + ' · ' + esc(s.tf) + ' · ' + esc(s.from) + ' → ' + esc(s.to) + ' · ' + (s.source === 'upload' ? 'your data' : 'Twelve Data') + '</span><h2>' + esc(j.name) + '</h2>'
      + '<ul class="bt-rules">' + j.rules.map(function (l) { return '<li>' + esc(l) + '</li>'; }).join('') + '</ul></div>'
      + '<div class="bt-res-act">' + (j.archived ? '' : '<a class="tm-btn tm-btn-sm" href="' + C.urls.runs + '/' + j.id + '/csv">Trades CSV</a><button type="button" class="tm-btn tm-btn-sm" data-bt-save="' + j.id + '">★ Save</button>') + (j.rules_raw ? '<button type="button" class="tm-btn tm-btn-sm" data-bt-loadrules>Edit these rules</button>' : '') + '</div></header>';
    if (!s.trades) {
      out.innerHTML = h + '<div class="bt-none"><b>No trades.</b> The rules never lined up in this period (' + s.bars.toLocaleString() + ' candles checked). Loosen a condition or try a faster timeframe.</div>';
    } else {
      h += '<div class="bt-kpis">'
        + kpi('Net result', fmtR(s.net_r), sign(s.net_r), pct(s.net_pct) + ' · ' + money(s.start) + ' → ' + money(s.end))
        + kpi('Win rate', s.win_rate + '%', '', s.wins + ' won · ' + s.losses + ' lost' + (s.breakeven ? ' · ' + s.breakeven + ' flat' : ''))
        + kpi('Profit factor', s.profit_factor == null ? '∞' : s.profit_factor, s.profit_factor != null && s.profit_factor < 1 ? 'dn' : 'up', 'gross won ÷ gross lost')
        + kpi('Expectancy', fmtR(s.expectancy), sign(s.expectancy), 'average per trade')
        + kpi('Max drawdown', '−' + s.max_dd_pct + '%', 'dn', '−' + s.max_dd_r + 'R from the peak')
        + kpi('Worst losing streak', s.max_losing_streak, '', 'losses in a row')
        + kpi('Trades', s.trades, '', s.trades_per_month + ' a month · ' + s.avg_bars + ' candles held')
        + kpi('Average win / loss', fmtR(s.avg_win) + ' / ' + fmtR(s.avg_loss), '', 'best ' + fmtR(s.best) + ' · worst ' + fmtR(s.worst))
        + '</div>';
      h += '<section class="panel bt-eq"><div class="panel-head"><h2>Account balance</h2><span class="muted">' + (s.risk_pct ? s.risk_pct + '% risk per trade, compounded' : '') + '</span></div><div class="bt-eq-wrap" data-bt-eq></div></section>';
      h += '<section class="panel"><div class="panel-head"><h2>Month by month (R)</h2></div><div class="bt-scroll">' + monthly(j.monthly) + '</div></section>';
      h += '<div class="bt-brk">' + brk('By session', j.breakdown.session, { ASIAN: 'Asia', LONDON: 'London', NEW_YORK: 'New York' }) + brk('By weekday', j.breakdown.weekday) + brk('Buy vs sell', j.breakdown.side, { long: 'Buys', short: 'Sells' }) + brk('How trades closed', j.breakdown.exit) + '</div>';
      h += '<section class="panel"><div class="panel-head"><h2>Trades</h2><span class="muted">' + (j.trade_count > j.trades.length ? 'last ' + j.trades.length + ' of ' + j.trade_count + ' (all in the CSV)' : j.trade_count + ' trades') + ' · times in UTC</span></div><div class="bt-scroll"><table class="bt-trades"><thead><tr><th>#</th><th>Side</th><th>Entry</th><th class="num">Price</th><th class="num">Stop</th><th class="num">Exit</th><th>Closed by</th><th class="num">R</th><th class="num">P&amp;L</th></tr></thead><tbody data-bt-tbody></tbody></table></div><button type="button" class="tm-btn tm-btn-sm bt-more" data-bt-more hidden>Show more</button></section>';
      out.innerHTML = h;
      equity($('[data-bt-eq]', out), j.equity, s.start);
      trades(j.trades, j.trade_count);
    }
    out.hidden = false; out.scrollIntoView({ behavior: 'smooth', block: 'start' });
    var sv = $('[data-bt-save]', out); if (sv) sv.addEventListener('click', function () { var n = prompt('Name this test', j.name); if (n === null) return; post(C.urls.runs + '/' + j.id + '/save', { name: n }).then(function (r) { if (r.ok) { toast('Saved.', 'success'); sv.disabled = true; sv.textContent = '★ Saved'; } }); });
    var lr = $('[data-bt-loadrules]', out); if (lr) lr.addEventListener('click', function () { load(j.rules_raw); var x = $('.bt-ds[data-bt-ds="' + j.dataset_id + '"]'); if (x && x.dataset.ready === '1') selectDs(x); $('[data-bt-tf]').value = s.tf; root.scrollIntoView({ behavior: 'smooth' }); toast('Rules loaded into the builder.', 'success'); });
  }
  function monthly(m) {
    var ys = {}, names = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'], max = 0;
    Object.keys(m).forEach(function (k) { var y = k.slice(0, 4); (ys[y] = ys[y] || {})[+k.slice(5) - 1] = m[k]; max = Math.max(max, Math.abs(m[k])); });
    var h = '<table class="bt-month"><thead><tr><th>Year</th>' + names.map(function (n) { return '<th>' + n + '</th>'; }).join('') + '<th>Total</th></tr></thead><tbody>';
    Object.keys(ys).sort().forEach(function (y) {
      var tot = 0; h += '<tr><th>' + y + '</th>';
      for (var i = 0; i < 12; i++) { var v = ys[y][i]; if (v == null) { h += '<td class="na">·</td>'; continue; } tot += v; var a = Math.min(1, Math.abs(v) / (max || 1)); h += '<td class="' + (v >= 0 ? 'up' : 'dn') + '" style="--a:' + (0.12 + a * 0.55).toFixed(2) + '">' + fmtR(v).replace('R', '') + '</td>'; }
      h += '<td class="tot ' + (tot >= 0 ? 'up' : 'dn') + '">' + fmtR(tot) + '</td></tr>';
    });
    return h + '</tbody></table>';
  }
  function brk(title, g, names) {
    var keys = Object.keys(g || {}); if (!keys.length) return '';
    return '<section class="panel bt-brk-box"><div class="panel-head"><h2>' + title + '</h2></div><table><thead><tr><th></th><th class="num">Trades</th><th class="num">Win %</th><th class="num">Net</th></tr></thead><tbody>'
      + keys.map(function (k) { var v = g[k]; return '<tr><td>' + esc((names && names[k]) || k) + '</td><td class="num">' + v.n + '</td><td class="num">' + v.win_rate + '%</td><td class="num ' + (v.r >= 0 ? 'up' : 'dn') + '">' + fmtR(v.r) + '</td></tr>'; }).join('') + '</tbody></table></section>';
  }
  function trades(list, total) {
    var tb = $('[data-bt-tbody]', out), more = $('[data-bt-more]', out), shown = 0, rows = list.slice().reverse(), offset = total - list.length;
    function add(n) {
      var h = '';
      rows.slice(shown, shown + n).forEach(function (t, k) {
        var idx = total - (shown + k);
        h += '<tr><td class="mono">' + idx + '</td><td><span class="bt-side ' + (t.side === 'long' ? 'up' : 'dn') + '">' + (t.side === 'long' ? 'BUY' : 'SELL') + '</span></td><td class="mono">' + dt(t.entry_ts) + '</td><td class="num mono">' + (+t.entry).toPrecision(7) + '</td><td class="num mono">' + (+t.stop).toPrecision(7) + '</td><td class="num mono">' + (+t.exit).toPrecision(7) + '</td><td>' + esc(t.reason) + '</td><td class="num mono ' + (t.r >= 0 ? 'up' : 'dn') + '">' + fmtR(t.r) + '</td><td class="num mono ' + (t.pnl >= 0 ? 'up' : 'dn') + '">' + money(t.pnl) + '</td></tr>';
      });
      tb.insertAdjacentHTML('beforeend', h); shown += n; more.hidden = shown >= rows.length;
    }
    more.addEventListener('click', function () { add(50); }); add(50); void offset;
  }
  function equity(box, pts, start) {
    if (!pts || pts.length < 2) { box.textContent = 'Not enough trades to draw.'; return; }
    var W = 800, H = 260, L = 64, R = 12, T = 14, B = 28, xs = pts.map(function (p) { return p[0]; }), ys = pts.map(function (p) { return p[1]; });
    var x0 = xs[0], x1 = xs[xs.length - 1], lo = Math.min.apply(null, ys.concat([start])), hi = Math.max.apply(null, ys.concat([start])), pad = (hi - lo) * 0.08 || 1; lo -= pad; hi += pad;
    var X = function (t) { return L + (t - x0) / Math.max(1, x1 - x0) * (W - L - R); }, Y = function (v) { return T + (hi - v) / (hi - lo) * (H - T - B); };
    var line = pts.map(function (p, i) { return (i ? 'L' : 'M') + X(p[0]).toFixed(1) + ',' + Y(p[1]).toFixed(1); }).join(' ');
    var g = ''; for (var k = 0; k <= 4; k++) { var v = lo + (hi - lo) * k / 4; g += '<line x1="' + L + '" x2="' + (W - R) + '" y1="' + Y(v) + '" y2="' + Y(v) + '" class="g"/><text x="' + (L - 8) + '" y="' + (Y(v) + 4) + '" text-anchor="end">' + money(v).replace('.00', '').replace(/\.\d+$/, '') + '</text>'; }
    var up = ys[ys.length - 1] >= start;
    box.innerHTML = '<svg viewBox="0 0 ' + W + ' ' + H + '" class="bt-eq-svg ' + (up ? 'is-up' : 'is-dn') + '" role="img" aria-label="Account balance from ' + money(start) + ' to ' + money(ys[ys.length - 1]) + '">' + g
      + '<line x1="' + L + '" x2="' + (W - R) + '" y1="' + Y(start) + '" y2="' + Y(start) + '" class="base"/>'
      + '<path d="' + line + ' L' + X(x1) + ',' + (H - B) + ' L' + X(x0) + ',' + (H - B) + ' Z" class="area"/><path d="' + line + '" class="ln"/>'
      + '<text x="' + L + '" y="' + (H - 8) + '">' + day(x0) + '</text><text x="' + (W - R) + '" y="' + (H - 8) + '" text-anchor="end">' + day(x1) + '</text>'
      + '<line class="cx" y1="' + T + '" y2="' + (H - B) + '" hidden/><circle class="dotm" r="4.5" hidden/></svg><div class="bt-tip" hidden></div>';
    var svg = box.querySelector('svg'), cx = svg.querySelector('.cx'), dot = svg.querySelector('.dotm'), tip = box.querySelector('.bt-tip');
    function move(e) {
      var r = svg.getBoundingClientRect(), mx = (e.clientX - r.left) / r.width * W, best = 0, bd = 1e18;
      pts.forEach(function (p, i) { var dd = Math.abs(X(p[0]) - mx); if (dd < bd) { bd = dd; best = i; } });
      var p = pts[best], px = X(p[0]), py = Y(p[1]);
      cx.setAttribute('x1', px); cx.setAttribute('x2', px); cx.hidden = false; dot.setAttribute('cx', px); dot.setAttribute('cy', py); dot.hidden = false;
      tip.hidden = false; tip.innerHTML = '<b>' + money(p[1]) + '</b><span>' + day(p[0]) + ' · ' + pct((p[1] - start) / start * 100) + '</span>';
      tip.style.left = Math.min(r.width - 150, Math.max(0, px / W * r.width - 70)) + 'px'; tip.style.top = Math.max(0, py / H * r.height - 58) + 'px';
    }
    svg.addEventListener('pointermove', move); svg.addEventListener('pointerleave', function () { cx.hidden = dot.hidden = tip.hidden = true; });
  }
  sideSync(); exitSync();
})();
