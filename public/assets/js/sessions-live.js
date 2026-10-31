/* journzey.ai — live trading sessions: the rotating session globe (Home) and the "London LIVE" chip in the top bar (every page).
   Land: Natural Earth 1:50m (public domain), rasterised to a 1.5° grid. Sessions follow each market's own local time (DST included). */
(function () {
  'use strict';
  var d = document;
  var MARKETS = [
    { key: 'TOKYO', city: 'Tokyo', zone: 'Asia/Tokyo', open: 540, close: 1080, lat: 35.68, lon: 139.69, col: [255, 120, 170] },
    { key: 'LONDON', city: 'London', zone: 'Europe/London', open: 480, close: 1020, lat: 51.51, lon: -0.13, col: [150, 120, 255] },
    { key: 'NEW_YORK', city: 'New York', zone: 'America/New_York', open: 480, close: 1020, lat: 40.71, lon: -74.0, col: [80, 200, 255] }
  ];
  var fmt = {};
  function local(zone, t) {
    fmt[zone] = fmt[zone] || new Intl.DateTimeFormat('en-GB', { timeZone: zone, weekday: 'short', hour: '2-digit', minute: '2-digit', hourCycle: 'h23' });
    var p = {}; fmt[zone].formatToParts(t).forEach(function (x) { p[x.type] = x.value; });
    return { wd: p.weekday, mins: (+p.hour) * 60 + (+p.minute) };
  }
  function isOpen(m, t) { var l = local(m.zone, t); return l.wd !== 'Sat' && l.wd !== 'Sun' && l.mins >= m.open && l.mins < m.close; }
  function state(now) {
    var t = now || new Date(), live = MARKETS.filter(function (m) { return isOpen(m, t); }), next = null;
    if (!live.length) {
      for (var k = 1; k <= 4 * 96 && !next; k++) { var tt = new Date(t.getTime() + k * 15 * 60000); MARKETS.forEach(function (m) { if (!next && isOpen(m, tt)) next = { m: m, mins: k * 15 }; }); }
    }
    return { live: live, next: next };
  }
  function dur(mins) { var h = Math.floor(mins / 60), mm = mins % 60; return h >= 24 ? Math.floor(h / 24) + 'd ' + (h % 24) + 'h' : (h ? h + 'h ' : '') + (mm ? mm + 'm' : (h ? '' : '0m')); }
  function label(st) { return st.live.length ? st.live.map(function (m) { return m.city; }).join(' + ') + (st.live.length > 1 ? ' overlap' : ' session') + ' · LIVE' : 'Markets closed' + (st.next ? ' · ' + st.next.m.city + ' opens in ' + dur(st.next.mins) : ''); }
  window.jzSessions = { state: state, label: label, MARKETS: MARKETS };

  // ---------------------------------------------------------------- top-bar chip (every terminal page)
  var chip = d.querySelector('[data-session-chip]');
  function chipTick() {
    if (!chip) return; var st = state();
    chip.classList.toggle('on', st.live.length > 0);
    chip.querySelector('b').textContent = st.live.length ? st.live.map(function (m) { return m.city; }).join(' + ') : 'Closed';
    chip.querySelector('i').textContent = st.live.length ? 'LIVE' : (st.next ? st.next.m.city + ' in ' + dur(st.next.mins) : '');
    chip.title = label(st);
  }
  chipTick(); setInterval(chipTick, 30000);

  // ---------------------------------------------------------------- globe (Home)
  var box = d.querySelector('[data-session-globe]');
  if (!box) return;
  var cv = box.querySelector('canvas'), ctx = cv.getContext('2d'), tag = d.querySelector('[data-sg-status]'), labels = box.querySelector('.sg-labels');
  var MASK = 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAFwAA//wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA///////9cAAAAAAAIAAAAAAAAAAAAAAAAAAAAAAP//z/////gAAA/AAceAAAfAAAAAAAAAAAAAAAACOf/8/////+AAAf4AAAAAAAD8AAAAAAAAAAAAAADkANfwf/////AAADEAAAAAAAAOAAAAAAAAAAAAAAH//v/gAP///+AAAAAAAAHwAA//8AAfAAAAAAAAAAfAAvfgAD///+AAAAAAAA4AAH//4AACAAAAAAAAAA/+z+/8AB///4AAAAAAABwDD/////wHgAAAgAGAAAZ/4s//gB///8AAAAAAABwH//////7/+YAAAB//4f8/++H/4A///wAAAA/8AADn//////////+8wH//////8/vH8A//+AAAAD//5n//v////////////D////////+B/g//gAAAAH//9///////////////fP////////+P/Af8AP4AAP7+P///////////////AA////////NI+AP4AHgAA/3////////////////+AD///////8CPMAHwAAAAH/P////////////////8AD///////4APwADwAAAAH/P//////////////w/gAA/QH////4APzAAAAAAAH/H/////////////7hgAAAHgB////8AP/gAAAAAcAOL////////////8AHgAAAYAA/////wH/gAAAAAOBuP////////////wAPgAAAAAAf////+f/4AAAAA/A5/////////////gAPAAAAAAAX/////f/+AAAAB3n///////////////AOAAAAAAAD///////+AAAABnv///////////////AIAAAAAAAB///////2AAAAAEf//////////////9AAAAAAAAAA//////+HgAAAAH///////////////9AAAAAAAAAAf/////+DgAAAAD///////////////6AAAAAAAAAAf//////wAAAAAB///zfx/////////wAAAAAAAAAAf/////6AAAAAAB/7/gfj/////////jAAAAAAAAAAf/////gAAAAAA/wd/gHx////////+HAAAAAAAAAAf/////gAAAAAA/wGf/n4////////4EAAAAAAAAAAf////+AAAAAAA/Bid//5///////3gGAAAAAAAAAAP////8AAAAAAA/AGN//4///////hwEAAAAAAAAAAP////4AAAAAAAefkA//////////4wcAAAAAAAAAAH////4AAAAAAAP/gAA/////////wz8AAAAAAAAAAB////wAAAAAAAf/gAB/////////wHgAAAAAAAAAAA////gAAAAAAA//8cB/////////4EAAAAAAAAAAAA////AAAAAAAB///f//////////4AAAAAAAAAAAAAf/5DAAAAAAAB//////z///////4AAAAAAAAAAAAAL/gBgAAAAAAH//////7///////4AAAAAAAAAAAAAF/gBgAAAAAAP////+/8P//////wAAAAAAAAAAAAAE/gAAAAAAAAP/////f+wP/////oAAAAAAAAAAAAACfgBAAAAAAAf/////P/4D/////IAAAAAAAAAAAAAAPgQ4AAAAAAf/////v/8D//f/4AAAAAAAAAAAAAAAPwwMAAAAAAf/////n/4A/8P+QAAAAAAAAAAAAAAAH7wBwAAAAAf/////n/wA/wP+wAAAAAAAAAAAAAAAB/gAAAAAAAf/////z/gAfgH+AMAAAAAAAAAAAAAAAX8AAAAAAAf/////7+AAfAF/AIAAAAAAAAAAAAAAAB+AAAAAAAf/////94AAfAB/gMAAAAAAAAAAAAAAAAMAAAAAAAf//////AAAPAB/gKAAAAAAAAAAAAAAAAMDwAAAAAf/////+MAAOABngHAAAAAAAAAAAAAAAAGH/gAAAAH//////8AAGABCADAAAAAAAAAAAAAAAAD//4AAAAH//////8AAFABgADAAAAAAAAAAAAAAAAAP/8AAAAD//////4AABgAwABAAAAAAAAAAAAAAAAAP//gAAAA/H////wAAAACYBgAAAAAAAAAAAAAAAAAP//wAAAAAA////wAAAADYHgAAAAAAAAAAAAAAAAAf//4AAAAAA////gAAAAB4PgAAAAAAAAAAAAAAAAAf//4AAAAAA///+AAAAAA4fuQAAAAAAAAAAAAAAAA///+AAAAAA///8AAAAAAcfEGAAAAAAAAAAAAAAAA////gAAAAA///4AAAAAAefYDcAAAAAAAAAAAAAAA////8AAAAAf//4AAAAAAOCcj/BAAAAAAAAAAAAAA/////AAAAAP//wAAAAAAGAEAfzAAAAAAAAAAAAAA/////gAAAAP//wAAAAAAD4AAPwAAAAAAAAAAAAAAf////gAAAAH//wAAAAAAAOogPYAAAAAAAAAAAAAAP////AAAAAH//4AAAAAAAACAAMAAAAAAAAAAAAAAP///+AAAAAH//4AAAAAAAAAAAAAAAAAAAAAAAAAAH///+AAAAAH//4IAAAAAAAAHjAAAAAAAAAAAAAAAH///8AAAAAP//4MAAAAAAAAvjAAAAAAAAAAAAAAAD///8AAAAAP//48AAAAAAAB/zgAAAAAAAAAAAAAAA///8AAAAAP//x4AAAAAAAH/7gAAAAAAAAAAAAAAAf//8AAAAAP//B4AAAAAAAH//wAAAAAAAAAAAAAAAf//4AAAAAH/+B4AAAAAAAf//4AAAAAAAAAAAAAAAf//4AAAAAH//BwAAAAAAD///8AIAAAAAAAAAAAAAf//gAAAAAH//BwAAAAAAD///+AAAAAAAAAAAAAAAf/+AAAAAAD/+BwAAAAAAD////AAAAAAAAAAAAAAAf/8AAAAAAD/8AAAAAAAAD////AAAAAAAAAAAAAAA//8AAAAAAD/8AAAAAAAAD////AAAAAAAAAAAAAAA//4AAAAAAB/4AAAAAAAAD////AAAAAAAAAAAAAAA//wAAAAAAA/wAAAAAAAAB////AAAAAAAAAAAAAAA//gAAAAAAA/gAAAAAAAAB/j//AAAAAAAAAAAAAAA//gAAAAAAA/AAAAAAAAAB+A/+AAAAAAAAAAAAAAA/8AAAAAAAAAAAAAAAAAAAAAf+AAQAAAAAAAAAAAB/8AAAAAAAAAAAAAAAAAAAAAH8AAIAAAAAAAAAAAB/8AAAAAAAAAAAAAAAAAAAAAD4AAOAAAAAAAAAAAB/gAAAAAAAAAAAAAAAAAAAAAAAAAMAAAAAAAAAAAB/AAAAAAAAAAAAAAAAAAAAAAA4AAYAAAAAAAAAAAD+AAAAAAAAAAAAAAAAAAAAAAAYAAwAAAAAAAAAAAB+AAAAAAAAAAAAAAAAAAAAAAAAADgAAAAAAAAAAAD4AAAAAAAAAAAAAAAAAAAAAAAAAHAAAAAAAAAAAAD8AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAH4AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAD4AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAADwIAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAB4AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA8AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAPAAAAAAAAAAAAHwAAH//+Hv+AAAAAAAAAAAAAAAAGAAAAAAAAAAAD//+D///////8AAAAAAAAAAAAAAA/gAAAAAAAAAH///8f////////+AAAAAAAAAAAAAD/gAAAAB+///////9//////////+AAAAAAAAAH5gC/wAAAAP///////////////////+AAAAAABvwD////gAAAA////////////////////wAAAAB////////+AAAAf////////////////////gAAAH////////+AAAAf/////////////////////gAABD////////wYAPg//////////////////////wAAAAP///////8Ew/gP////////////////////+AAAAB/////////+AMf//////////////////////AAAPH////////////////////////////////////A////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////', STEP = 1.5, COLS = 240;
  var raw = atob(MASK), land = [];
  for (var i = 0; i < raw.length; i++) { var byte = raw.charCodeAt(i); for (var b = 0; b < 8; b++) if (byte & (128 >> b)) { var idx = i * 8 + b, r = Math.floor(idx / COLS), c = idx % COLS; var la = 90 - STEP / 2 - r * STEP, lo = -180 + STEP / 2 + c * STEP; land.push([la * Math.PI / 180, lo * Math.PI / 180]); } }
  var RAD = Math.PI / 180, reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
  var W = 0, H = 0, R = 0, dpr = 1, rot = 0, tilt = 24 * RAD, target = null, drag = null, visible = true, st = state(), chips = {};
  function size() { var w = box.clientWidth; dpr = Math.min(2, window.devicePixelRatio || 1); W = H = w; cv.width = cv.height = Math.round(w * dpr); cv.style.width = cv.style.height = w + 'px'; R = w * 0.4; }
  size(); window.addEventListener('resize', size);
  function sun(t) { // subsolar point (approximate, good to ~1°)
    var day = (Date.UTC(t.getUTCFullYear(), t.getUTCMonth(), t.getUTCDate()) - Date.UTC(t.getUTCFullYear(), 0, 0)) / 864e5;
    var decl = -23.44 * Math.cos(2 * Math.PI / 365 * (day + 10)) * RAD, hrs = t.getUTCHours() + t.getUTCMinutes() / 60;
    return [decl, (12 - hrs) * 15 * RAD];
  }
  function aimAt() { if (!st.live.length) return null; var x = 0, y = 0; st.live.forEach(function (m) { x += Math.cos(m.lon * RAD); y += Math.sin(m.lon * RAD); }); return Math.atan2(y, x); }
  MARKETS.forEach(function (m) { var el = d.createElement('div'); el.className = 'sg-chip'; el.innerHTML = '<span></span>' + m.city + ' <em>LIVE</em>'; el.style.setProperty('--c', 'rgb(' + m.col.join(',') + ')'); labels.appendChild(el); chips[m.key] = el; });
  function refresh() { st = state(); target = aimAt(); if (tag) { tag.textContent = label(st); tag.classList.toggle('on', st.live.length > 0); } }
  refresh(); setInterval(refresh, 30000);
  if (target !== null) rot = -target;
  function proj(lat, lon) {
    var l = lon + rot, cl = Math.cos(lat), x = cl * Math.sin(l), y = Math.cos(tilt) * Math.sin(lat) - Math.sin(tilt) * cl * Math.cos(l);
    var z = Math.sin(tilt) * Math.sin(lat) + Math.cos(tilt) * cl * Math.cos(l);
    return [W / 2 + R * x, H / 2 - R * y, z];
  }
  var t0 = performance.now();
  function frame(now) {
    var t = (now - t0) / 1000, date = new Date();
    if (!drag) {
      if (target !== null) { var want = -target + Math.sin(t / 7) * 0.35, diff = Math.atan2(Math.sin(want - rot), Math.cos(want - rot)); rot += diff * 0.02; }
      else rot += 0.0016;
    }
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0); ctx.clearRect(0, 0, W, H);
    // atmosphere + ocean
    var g = ctx.createRadialGradient(W / 2, H / 2, R * 0.9, W / 2, H / 2, R * 1.22); g.addColorStop(0, 'rgba(124,92,240,0.35)'); g.addColorStop(1, 'rgba(124,92,240,0)');
    ctx.fillStyle = g; ctx.beginPath(); ctx.arc(W / 2, H / 2, R * 1.22, 0, 7); ctx.fill();
    var o = ctx.createRadialGradient(W / 2 - R * 0.35, H / 2 - R * 0.4, R * 0.1, W / 2, H / 2, R); o.addColorStop(0, '#1d1840'); o.addColorStop(1, '#07061a');
    ctx.fillStyle = o; ctx.beginPath(); ctx.arc(W / 2, H / 2, R, 0, 7); ctx.fill();
    // graticule
    ctx.strokeStyle = 'rgba(160,140,255,0.08)'; ctx.lineWidth = 1;
    for (var gl = -60; gl <= 60; gl += 30) { ctx.beginPath(); var first = true; for (var gx = -180; gx <= 180; gx += 4) { var p = proj(gl * RAD, gx * RAD); if (p[2] < 0) { first = true; continue; } first ? ctx.moveTo(p[0], p[1]) : ctx.lineTo(p[0], p[1]); first = false; } ctx.stroke(); }
    for (var gm = -180; gm < 180; gm += 30) { ctx.beginPath(); var f2 = true; for (var gy = -84; gy <= 84; gy += 4) { var q = proj(gy * RAD, gm * RAD); if (q[2] < 0) { f2 = true; continue; } f2 ? ctx.moveTo(q[0], q[1]) : ctx.lineTo(q[0], q[1]); f2 = false; } ctx.stroke(); }
    // land dots: day side brighter, live-session regions glow in the session colour
    var s = sun(date), sl = Math.sin(s[0]), cs = Math.cos(s[0]), pulse = 0.65 + 0.35 * Math.sin(t * 2.4);
    var live = st.live.map(function (m) { return [Math.sin(m.lat * RAD), Math.cos(m.lat * RAD), m.lon * RAD, m.col]; });
    for (var k = 0; k < land.length; k++) {
      var la = land[k][0], lo = land[k][1], P = proj(la, lo); if (P[2] <= 0.02) continue;
      var sla = Math.sin(la), cla = Math.cos(la), light = sl * sla + cs * cla * Math.cos(lo - s[1]);
      var col = null, best = 0;
      for (var j = 0; j < live.length; j++) { var dist = live[j][0] * sla + live[j][1] * cla * Math.cos(lo - live[j][2]); if (dist > 0.91 && dist > best) { best = dist; col = live[j][3]; } }
      var a = (light > 0 ? 0.55 : 0.22) * (0.35 + 0.65 * P[2]), rr = 0.75 + 0.75 * P[2];
      if (col) { var w = Math.min(1, (best - 0.91) / 0.06) * pulse; ctx.fillStyle = 'rgba(' + col[0] + ',' + col[1] + ',' + col[2] + ',' + (0.35 + 0.65 * w) + ')'; rr *= 1.25; }
      else ctx.fillStyle = light > 0 ? 'rgba(220,214,255,' + a + ')' : 'rgba(120,110,190,' + a + ')';
      ctx.fillRect(P[0] - rr / 2, P[1] - rr / 2, rr, rr);
    }
    // terminator shading
    var sp = proj(s[0], s[1]), sg = ctx.createRadialGradient(sp[0], sp[1], 0, sp[0], sp[1], R * 2.1);
    sg.addColorStop(0, 'rgba(255,240,220,0.06)'); sg.addColorStop(0.5, 'rgba(0,0,0,0)'); sg.addColorStop(1, 'rgba(0,0,10,0.45)');
    ctx.save(); ctx.beginPath(); ctx.arc(W / 2, H / 2, R, 0, 7); ctx.clip(); ctx.fillStyle = sg; ctx.fillRect(0, 0, W, H); ctx.restore();
    // arc between overlapping sessions
    if (st.live.length > 1) {
      var A = st.live[0], B = st.live[1]; ctx.beginPath(); var started = false;
      for (var u = 0; u <= 1.0001; u += 0.02) { var lat = (A.lat + (B.lat - A.lat) * u) * RAD, lon = (A.lon + (B.lon - A.lon) * u) * RAD, pp = proj(lat, lon), lift = 1 + 0.12 * Math.sin(Math.PI * u); var px = W / 2 + (pp[0] - W / 2) * lift, py = H / 2 + (pp[1] - H / 2) * lift; if (pp[2] < 0) { started = false; continue; } started ? ctx.lineTo(px, py) : ctx.moveTo(px, py); started = true; }
      ctx.strokeStyle = 'rgba(255,255,255,0.55)'; ctx.lineWidth = 1.4; ctx.setLineDash([4, 5]); ctx.lineDashOffset = -t * 18; ctx.stroke(); ctx.setLineDash([]);
    }
    // city markers and labels
    MARKETS.forEach(function (m) {
      var P = proj(m.lat * RAD, m.lon * RAD), on = st.live.indexOf(m) >= 0, el = chips[m.key], show = P[2] > 0.08;
      if (show) {
        var c = 'rgb(' + m.col.join(',') + ')';
        if (on) for (var ring = 0; ring < 2; ring++) { var ph = ((t * 0.8 + ring / 2) % 1); ctx.strokeStyle = 'rgba(' + m.col.join(',') + ',' + (1 - ph) + ')'; ctx.lineWidth = 1.5; ctx.beginPath(); ctx.arc(P[0], P[1], 4 + ph * 18, 0, 7); ctx.stroke(); }
        ctx.fillStyle = on ? c : 'rgba(200,195,230,0.6)'; ctx.beginPath(); ctx.arc(P[0], P[1], on ? 3.6 : 2.4, 0, 7); ctx.fill();
      }
      el.classList.toggle('show', show && on); el.style.transform = 'translate(' + Math.round(P[0] + 10) + 'px,' + Math.round(P[1] - 12) + 'px)';
    });
    if (!reduce && visible && !d.hidden) requestAnimationFrame(frame); else running = false;
  }
  var running = false;
  function start() { if (!running) { running = true; requestAnimationFrame(frame); } }
  if ('IntersectionObserver' in window) new IntersectionObserver(function (es) { visible = es[0].isIntersecting; if (visible) start(); }).observe(box);
  d.addEventListener('visibilitychange', function () { if (!d.hidden) start(); });
  if (reduce) { requestAnimationFrame(frame); setInterval(function () { requestAnimationFrame(frame); }, 60000); } else start();
  // drag to spin
  cv.addEventListener('pointerdown', function (e) { drag = { x: e.clientX, r: rot }; cv.setPointerCapture(e.pointerId); });
  cv.addEventListener('pointermove', function (e) { if (drag) { rot = drag.r + (e.clientX - drag.x) / R; if (reduce) requestAnimationFrame(frame); } });
  cv.addEventListener('pointerup', function () { drag = null; });
})();

/* Terminal-wide touches: a soft light that follows the pointer across panels, and a ripple on primary buttons. */
(function () {
  'use strict';
  if (window.matchMedia && (matchMedia('(prefers-reduced-motion: reduce)').matches || !matchMedia('(hover: hover)').matches)) return;
  var last = null;
  document.addEventListener('pointermove', function (e) {
    var p = e.target.closest && e.target.closest('.panel, .dash-tile, .kpi, .ex-card, .cf-card');
    if (last && last !== p) last.classList.remove('lit');
    if (!p) { last = null; return; }
    var r = p.getBoundingClientRect(); p.style.setProperty('--mx', (e.clientX - r.left) + 'px'); p.style.setProperty('--my', (e.clientY - r.top) + 'px'); p.classList.add('lit'); last = p;
  }, { passive: true });
  document.addEventListener('pointerdown', function (e) {
    var b = e.target.closest && e.target.closest('.tm-btn-primary, .tm-btn-accent, .tm-tab-quick'); if (!b) return;
    var r = b.getBoundingClientRect(), s = document.createElement('span'); s.className = 'tm-ripple';
    s.style.left = (e.clientX - r.left) + 'px'; s.style.top = (e.clientY - r.top) + 'px'; b.appendChild(s); setTimeout(function () { s.remove(); }, 650);
  });
})();
