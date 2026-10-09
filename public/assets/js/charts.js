/* journzey.ai terminal charts — dependency-free SVG.
 * Elements: <div class="chart" data-chart="line|bars|band" data-json='…' data-format="money:USD|pct|num|r">.
 * Line/area: crosshair + tooltip snapping to the nearest point. Bars: per-bar hover/focus tooltip, signed value labels,
 * gains green / losses red with signed labels and a zero baseline (secondary encoding for colour-blind readers).
 * Multi-series lines: data-chart="lines" with {series:[{name, cls, points:[{x,y}]}], base}. */
(function () {
  'use strict';
  var NS = 'http://www.w3.org/2000/svg';
  function el(tag, attrs, parent) { var n = document.createElementNS(NS, tag); for (var k in attrs) n.setAttribute(k, attrs[k]); if (parent) parent.appendChild(n); return n; }
  function fmt(v, f) {
    if (v === null || v === undefined || isNaN(v)) return '—';
    var p = (f || 'num').split(':');
    if (p[0] === 'money') {
      var sym = { USD: '$', EUR: '€', GBP: '£', JPY: '¥', INR: '₹', AUD: 'A$', CAD: 'C$', SGD: 'S$' }[p[1]] || ((p[1] || '') + ' ');
      var abs = Math.abs(v), s = abs >= 1e6 ? (abs / 1e6).toFixed(2) + 'M' : abs >= 1e4 ? (abs / 1e3).toFixed(1) + 'k' : abs.toLocaleString(undefined, { maximumFractionDigits: 2, minimumFractionDigits: abs < 1000 ? 2 : 0 });
      return (v < 0 ? '−' : '') + sym + s;
    }
    if (p[0] === 'pct') return (v < 0 ? '−' : '') + Math.abs(v).toFixed(1) + '%';
    if (p[0] === 'r') return (v > 0 ? '+' : v < 0 ? '−' : '') + Math.abs(v).toFixed(2) + 'R';
    if (p[0] === 'ratio') return (v * 100).toFixed(1) + '%';
    return Math.abs(v) >= 1000 ? v.toLocaleString() : (Math.round(v * 100) / 100).toString();
  }
  function signed(v, f) { return (v > 0 ? '+' : '') + fmt(v, f); }
  function niceTicks(min, max, n) {
    if (min === max) { min -= 1; max += 1; }
    var span = max - min, step = Math.pow(10, Math.floor(Math.log10(span / n))), err = span / n / step;
    step *= err >= 7.5 ? 10 : err >= 3.5 ? 5 : err >= 1.5 ? 2 : 1;
    var lo = Math.floor(min / step) * step, hi = Math.ceil(max / step) * step, t = [];
    for (var v = lo; v <= hi + step / 2; v += step) t.push(Math.round(v / step) * step);
    return t;
  }
  function tip(root) { var t = root.querySelector('.chart-tip'); if (!t) { t = document.createElement('div'); t.className = 'chart-tip'; t.hidden = true; root.appendChild(t); } return t; }
  function setTip(t, title, value, x, y) { t.textContent = ''; var s = document.createElement('strong'); s.textContent = value; t.appendChild(s); t.appendChild(document.createTextNode(title)); t.style.left = x + 'px'; t.style.top = y + 'px'; t.hidden = false; }

  function line(root, data, f) {
    var pts = data.points || data; var w = root.clientWidth || 600, h = +(root.dataset.height || 220), pl = 62, pr = 10, pt = 10, pb = 24;
    root.querySelectorAll('svg').forEach(function (s) { s.remove(); });
    if (!pts.length) { root.innerHTML = '<div class="chart-empty">' + (root.dataset.empty || 'No closed trades in this period.') + '</div>'; return; }
    var ys = pts.map(function (p) { return p.y; }), base = data.base, min = Math.min.apply(null, ys.concat(base !== undefined ? [base] : [])), max = Math.max.apply(null, ys.concat(base !== undefined ? [base] : []));
    var ticks = niceTicks(min, max, 4); min = ticks[0]; max = ticks[ticks.length - 1];
    var svg = el('svg', { viewBox: '0 0 ' + w + ' ' + h, role: 'img', 'aria-label': root.dataset.label || 'Chart' }, null);
    var X = function (i) { return pl + (pts.length === 1 ? (w - pl - pr) / 2 : i * (w - pl - pr) / (pts.length - 1)); };
    var Y = function (v) { return pt + (max - v) / (max - min) * (h - pt - pb); };
    ticks.forEach(function (t) { el('line', { x1: pl, x2: w - pr, y1: Y(t), y2: Y(t), class: 'grid-l' }, svg); var tx = el('text', { x: pl - 8, y: Y(t) + 3.5, 'text-anchor': 'end', class: 'axis-t' }, svg); tx.textContent = fmt(t, f); });
    if (base !== undefined) el('line', { x1: pl, x2: w - pr, y1: Y(base), y2: Y(base), class: 'zero' }, svg);
    var d = pts.map(function (p, i) { return (i ? 'L' : 'M') + X(i).toFixed(1) + ' ' + Y(p.y).toFixed(1); }).join(' ');
    if (root.dataset.area) {
      var by = Y(base !== undefined ? base : min);
      el('path', { d: d + ' L' + X(pts.length - 1).toFixed(1) + ' ' + by + ' L' + X(0).toFixed(1) + ' ' + by + ' Z', class: 'area' + (root.dataset.area === 'neg' ? ' neg' : '') }, svg);
    }
    el('path', { d: d, class: 'line', style: root.dataset.area === 'neg' ? 'stroke: var(--bar-neg)' : '' }, svg);
    // Capital events (deposits / withdrawals) as dots on the curve.
    pts.forEach(function (p, i) { if (p.mark) el('circle', { cx: X(i), cy: Y(p.y), r: 5.5, class: 'mark ' + p.mark }, svg); });
    var nx = Math.min(pts.length, Math.max(2, Math.floor((w - pl) / 110)));
    for (var k = 0; k < nx; k++) { var i = Math.round(k * (pts.length - 1) / Math.max(1, nx - 1)); var lx = el('text', { x: X(i), y: h - 6, 'text-anchor': k === 0 ? 'start' : k === nx - 1 ? 'end' : 'middle', class: 'axis-t' }, svg); lx.textContent = pts[i].x; }
    var cross = el('line', { y1: pt, y2: h - pb, class: 'cross', visibility: 'hidden' }, svg), dot = el('circle', { r: 4.5, class: 'dot', visibility: 'hidden' }, svg);
    if (root.dataset.area === 'neg') dot.setAttribute('style', 'fill: var(--bar-neg)');
    var hit = el('rect', { x: pl, y: 0, width: w - pl - pr, height: h, fill: 'transparent', tabindex: 0 }, svg), t = tip(root);
    function show(i) {
      i = Math.max(0, Math.min(pts.length - 1, i)); var x = X(i), y = Y(pts[i].y);
      cross.setAttribute('x1', x); cross.setAttribute('x2', x); cross.setAttribute('visibility', 'visible');
      dot.setAttribute('cx', x); dot.setAttribute('cy', y); dot.setAttribute('visibility', 'visible');
      var sc = root.clientWidth / w; setTip(t, pts[i].x + (pts[i].note ? ' · ' + pts[i].note : ''), fmt(pts[i].y, f), x * sc, y * sc - 8);
    }
    var cur = pts.length - 1;
    hit.addEventListener('pointermove', function (e) { var r = svg.getBoundingClientRect(), x = (e.clientX - r.left) / r.width * w; cur = Math.round((x - pl) / ((w - pl - pr) / Math.max(1, pts.length - 1))); show(cur); });
    hit.addEventListener('pointerleave', function () { cross.setAttribute('visibility', 'hidden'); dot.setAttribute('visibility', 'hidden'); t.hidden = true; });
    hit.addEventListener('keydown', function (e) { if (e.key === 'ArrowLeft') { cur = Math.max(0, cur - 1); show(cur); } if (e.key === 'ArrowRight') { cur = Math.min(pts.length - 1, cur + 1); show(cur); } });
    hit.addEventListener('focus', function () { show(cur); }); hit.addEventListener('blur', function () { t.hidden = true; });
    root.insertBefore(svg, root.firstChild);
  }

  function bars(root, items, f) {
    root.querySelectorAll('svg').forEach(function (s) { s.remove(); });
    if (!items.length) { root.innerHTML = '<div class="chart-empty">' + (root.dataset.empty || 'Not enough data yet.') + '</div>'; return; }
    var w = root.clientWidth || 500, row = 26, gap = 4, lw = Math.min(140, Math.max(80, w * 0.28)), vw = 86, h = items.length * (row + gap);
    var vals = items.map(function (i) { return i.value; }), max = Math.max(0, Math.max.apply(null, vals)), min = Math.min(0, Math.min.apply(null, vals)), span = (max - min) || 1;
    var x0 = lw + (w - lw - vw) * (-min / span), scale = (w - lw - vw) / span;
    var svg = el('svg', { viewBox: '0 0 ' + w + ' ' + h, role: 'img', 'aria-label': root.dataset.label || 'Bar chart' }, null), t = tip(root);
    el('line', { x1: x0, x2: x0, y1: 0, y2: h, class: 'zero' }, svg);
    items.forEach(function (it, i) {
      var y = i * (row + gap), g = el('g', { class: 'b', tabindex: 0 }, svg), bw = Math.max(2, Math.abs(it.value) * scale), bx = it.value >= 0 ? x0 : x0 - bw;
      el('rect', { x: 0, y: y - 1, width: w, height: row + 2, class: 'bar-hit' }, g);
      var lab = el('text', { x: 0, y: y + row / 2 + 4, class: 'cat-label' }, g); lab.textContent = it.label.length > 18 ? it.label.slice(0, 17) + '…' : it.label;
      el('rect', { x: bx, y: y + 5, width: bw, height: row - 10, rx: 3, class: it.value >= 0 ? 'bar-pos' : 'bar-neg' }, g);
      var vt = el('text', { x: it.value >= 0 ? Math.min(bx + bw + 6, w - vw + 6) : Math.max(lw, bx - 6), y: y + row / 2 + 4, 'text-anchor': it.value >= 0 ? 'start' : 'end', class: 'bar-label' }, g);
      if (it.value < 0 && bx - 6 < lw + 40) { vt.setAttribute('x', x0 + 6); vt.setAttribute('text-anchor', 'start'); }
      vt.textContent = signed(it.value, f);
      var show = function () { var sc = root.clientWidth / w; setTip(t, it.label + (it.sub ? ' · ' + it.sub : ''), signed(it.value, f), (x0 + (it.value >= 0 ? bw / 2 : -bw / 2)) * sc, y * sc + 2); };
      g.addEventListener('pointerenter', show); g.addEventListener('focus', show);
      g.addEventListener('pointerleave', function () { t.hidden = true; }); g.addEventListener('blur', function () { t.hidden = true; });
    });
    root.insertBefore(svg, root.firstChild);
  }

  function band(root, pts, f) {
    root.querySelectorAll('svg').forEach(function (s) { s.remove(); });
    var w = root.clientWidth || 600, h = +(root.dataset.height || 220), pl = 54, pr = 10, pt = 10, pb = 24;
    var lo = Math.min.apply(null, pts.map(function (p) { return p.p5; })), hi = Math.max.apply(null, pts.map(function (p) { return p.p95; }));
    var ticks = niceTicks(Math.min(lo, 1), Math.max(hi, 1), 4), min = ticks[0], max = ticks[ticks.length - 1];
    var X = function (i) { return pl + i * (w - pl - pr) / Math.max(1, pts.length - 1); }, Y = function (v) { return pt + (max - v) / (max - min) * (h - pt - pb); };
    var svg = el('svg', { viewBox: '0 0 ' + w + ' ' + h, role: 'img', 'aria-label': 'Monte Carlo equity percentile bands' }, null);
    ticks.forEach(function (tv) { el('line', { x1: pl, x2: w - pr, y1: Y(tv), y2: Y(tv), class: 'grid-l' }, svg); var tx = el('text', { x: pl - 8, y: Y(tv) + 3.5, 'text-anchor': 'end', class: 'axis-t' }, svg); tx.textContent = ((tv - 1) * 100).toFixed(0) + '%'; });
    el('line', { x1: pl, x2: w - pr, y1: Y(1), y2: Y(1), class: 'zero' }, svg);
    var up = pts.map(function (p, i) { return (i ? 'L' : 'M') + X(i) + ' ' + Y(p.p95); }).join(' '), down = pts.slice().reverse().map(function (p, i) { return 'L' + X(pts.length - 1 - i) + ' ' + Y(p.p5); }).join(' ');
    el('path', { d: up + ' ' + down + ' Z', class: 'band' }, svg);
    el('path', { d: pts.map(function (p, i) { return (i ? 'L' : 'M') + X(i) + ' ' + Y(p.p50); }).join(' '), class: 'line' }, svg);
    [0, Math.floor((pts.length - 1) / 2), pts.length - 1].forEach(function (i, k) { var tx = el('text', { x: X(i), y: h - 6, 'text-anchor': k === 0 ? 'start' : k === 2 ? 'end' : 'middle', class: 'axis-t' }, svg); tx.textContent = 'Trade ' + pts[i].step; });
    var t = tip(root), hit = el('rect', { x: pl, y: 0, width: w - pl - pr, height: h, fill: 'transparent' }, svg), cross = el('line', { y1: pt, y2: h - pb, class: 'cross', visibility: 'hidden' }, svg);
    hit.addEventListener('pointermove', function (e) {
      var r = svg.getBoundingClientRect(), i = Math.max(0, Math.min(pts.length - 1, Math.round(((e.clientX - r.left) / r.width * w - pl) / ((w - pl - pr) / Math.max(1, pts.length - 1))))), p = pts[i], sc = root.clientWidth / w;
      cross.setAttribute('x1', X(i)); cross.setAttribute('x2', X(i)); cross.setAttribute('visibility', 'visible');
      setTip(t, 'After ' + p.step + ' trades · 5th–95th pct ' + ((p.p5 - 1) * 100).toFixed(1) + '% to ' + ((p.p95 - 1) * 100).toFixed(1) + '%', 'Median ' + ((p.p50 - 1) * 100).toFixed(1) + '%', X(i) * sc, Y(p.p50) * sc - 8);
    });
    hit.addEventListener('pointerleave', function () { t.hidden = true; cross.setAttribute('visibility', 'hidden'); });
    root.insertBefore(svg, root.firstChild);
  }

  function lines(root, data, f) {
    var series = data.series || [], w = root.clientWidth || 600, h = +(root.dataset.height || 220), pl = 62, pr = 10, pt = 10, pb = 24;
    root.querySelectorAll('svg').forEach(function (s) { s.remove(); });
    var n = series.length ? series[0].points.length : 0;
    if (!n) { root.innerHTML = '<div class="chart-empty">' + (root.dataset.empty || 'Not enough data yet.') + '</div>'; return; }
    var ys = []; series.forEach(function (s) { s.points.forEach(function (p) { ys.push(p.y); }); }); if (data.base !== undefined) ys.push(data.base);
    var ticks = niceTicks(Math.min.apply(null, ys), Math.max.apply(null, ys), 4), min = ticks[0], max = ticks[ticks.length - 1];
    var svg = el('svg', { viewBox: '0 0 ' + w + ' ' + h, role: 'img', 'aria-label': root.dataset.label || 'Line chart' }, null);
    var X = function (i) { return pl + (n === 1 ? (w - pl - pr) / 2 : i * (w - pl - pr) / (n - 1)); }, Y = function (v) { return pt + (max - v) / (max - min) * (h - pt - pb); };
    ticks.forEach(function (t) { el('line', { x1: pl, x2: w - pr, y1: Y(t), y2: Y(t), class: 'grid-l' }, svg); var tx = el('text', { x: pl - 8, y: Y(t) + 3.5, 'text-anchor': 'end', class: 'axis-t' }, svg); tx.textContent = fmt(t, f); });
    if (data.base !== undefined) el('line', { x1: pl, x2: w - pr, y1: Y(data.base), y2: Y(data.base), class: 'zero' }, svg);
    series.forEach(function (s) { el('path', { d: s.points.map(function (p, i) { return (i ? 'L' : 'M') + X(i).toFixed(1) + ' ' + Y(p.y).toFixed(1); }).join(' '), class: 'line ' + (s.cls || '') }, svg); });
    var nx = Math.min(n, Math.max(2, Math.floor((w - pl) / 110)));
    for (var k = 0; k < nx; k++) { var i = Math.round(k * (n - 1) / Math.max(1, nx - 1)); var lx = el('text', { x: X(i), y: h - 6, 'text-anchor': k === 0 ? 'start' : k === nx - 1 ? 'end' : 'middle', class: 'axis-t' }, svg); lx.textContent = series[0].points[i].x; }
    var cross = el('line', { y1: pt, y2: h - pb, class: 'cross', visibility: 'hidden' }, svg), t = tip(root), cur = n - 1;
    var hit = el('rect', { x: pl, y: 0, width: w - pl - pr, height: h, fill: 'transparent', tabindex: 0 }, svg);
    var show = function (i) { i = Math.max(0, Math.min(n - 1, i)); cur = i; cross.setAttribute('x1', X(i)); cross.setAttribute('x2', X(i)); cross.setAttribute('visibility', 'visible'); var sc = root.clientWidth / w;
      setTip(t, series.map(function (s) { return s.name + ': ' + fmt(s.points[i].y, f); }).join(' · '), series[0].points[i].x, X(i) * sc, Y(series[0].points[i].y) * sc - 8); };
    hit.addEventListener('pointermove', function (e) { var r = svg.getBoundingClientRect(); show(Math.round(((e.clientX - r.left) / r.width * w - pl) / ((w - pl - pr) / Math.max(1, n - 1)))); });
    hit.addEventListener('pointerleave', function () { t.hidden = true; cross.setAttribute('visibility', 'hidden'); });
    hit.addEventListener('keydown', function (e) { if (e.key === 'ArrowLeft') show(cur - 1); if (e.key === 'ArrowRight') show(cur + 1); });
    hit.addEventListener('focus', function () { show(cur); }); hit.addEventListener('blur', function () { t.hidden = true; });
    root.insertBefore(svg, root.firstChild);
  }

  function render(root) {
    var data; try { data = JSON.parse(root.dataset.json || '[]'); } catch (e) { return; }
    var f = root.dataset.format || 'num';
    if (root.dataset.chart === 'line') line(root, data, f);
    else if (root.dataset.chart === 'bars') bars(root, data, f);
    else if (root.dataset.chart === 'band') band(root, data, f);
    else if (root.dataset.chart === 'lines') lines(root, data, f);
  }
  window.jzChart = render;
  function all() { document.querySelectorAll('.chart[data-chart]').forEach(render); }
  document.addEventListener('DOMContentLoaded', all);
  var rt; window.addEventListener('resize', function () { clearTimeout(rt); rt = setTimeout(all, 150); });
})();
