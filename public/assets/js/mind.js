/* journzey Mind: roast level, Account Death Clock, share-to-story cards and the weekly Psych Art wallpaper. */
(function () {
  'use strict';
  var d = document, $ = function (s, r) { return (r || d).querySelector(s); }, $$ = function (s, r) { return Array.prototype.slice.call((r || d).querySelectorAll(s)); };
  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ---------------------------------------------------------------- roast level */
  var root = $('[data-mind]');
  $$('[data-roast-level]').forEach(function (b) {
    b.addEventListener('click', function () {
      if (!window.tmPost || !root) return;
      $$('[data-roast-level]').forEach(function (x) { x.classList.toggle('on', x === b); x.setAttribute('aria-checked', x === b ? 'true' : 'false'); });
      window.tmPost(root.dataset.levelUrl, { level: b.dataset.roastLevel }).then(function (r) { if (r && r.ok) location.reload(); });
    });
  });

  /* ---------------------------------------------------------------- death clock */
  var audio = null;
  function tick(fast) {
    if (!audio) return;
    try {
      var o = audio.createOscillator(), g = audio.createGain();
      o.type = 'square'; o.frequency.value = fast ? 1800 : 1200; g.gain.setValueAtTime(0.0001, audio.currentTime);
      g.gain.exponentialRampToValueAtTime(0.06, audio.currentTime + 0.004); g.gain.exponentialRampToValueAtTime(0.0001, audio.currentTime + 0.05);
      o.connect(g); g.connect(audio.destination); o.start(); o.stop(audio.currentTime + 0.06);
    } catch (e) {}
  }
  $$('[data-death-clock]').forEach(function (box) {
    var at = +box.dataset.deathAt, face = $('[data-clock-face]', box), fast = box.dataset.speed === 'fast';
    var snd = $('[data-clock-sound]', box);
    if (snd) snd.addEventListener('click', function () {
      if (!audio) { try { audio = new (window.AudioContext || window.webkitAudioContext)(); } catch (e) { audio = null; } }
      else { audio.close(); audio = null; }
      snd.setAttribute('aria-pressed', audio ? 'true' : 'false'); snd.classList.toggle('on', !!audio);
    });
    if (!at || !face) return;
    var pad = function (n) { return (n < 10 ? '0' : '') + n; };
    var paint = function () {
      var s = Math.max(0, at - Math.floor(Date.now() / 1000));
      $('[data-u=d]', face).textContent = Math.floor(s / 86400); $('[data-u=h]', face).textContent = pad(Math.floor(s % 86400 / 3600));
      $('[data-u=m]', face).textContent = pad(Math.floor(s % 3600 / 60)); $('[data-u=s]', face).textContent = pad(s % 60);
    };
    paint(); setInterval(paint, 1000);
    // rule breaks make it tick (and pulse) faster; a clean last day slows it down
    var beat = fast ? 520 : 1400;
    setInterval(function () { if (!reduce) { face.classList.remove('beat'); void face.offsetWidth; face.classList.add('beat'); } tick(fast); }, beat);
  });

  /* ---------------------------------------------------------------- share cards (1080×1920 story) */
  function wrap(ctx, text, maxW) {
    var words = String(text).split(/\s+/), lines = [], line = '';
    words.forEach(function (w) { var t = line ? line + ' ' + w : w; if (ctx.measureText(t).width > maxW && line) { lines.push(line); line = w; } else line = t; });
    if (line) lines.push(line); return lines;
  }
  function brandBg(ctx, W, H) {
    ctx.fillStyle = '#07060f'; ctx.fillRect(0, 0, W, H);
    var g1 = ctx.createRadialGradient(W * 0.2, H * 0.1, 0, W * 0.2, H * 0.1, W); g1.addColorStop(0, 'rgba(124,92,255,.35)'); g1.addColorStop(1, 'rgba(124,92,255,0)'); ctx.fillStyle = g1; ctx.fillRect(0, 0, W, H);
    var g2 = ctx.createRadialGradient(W, H, 0, W, H, W * 0.9); g2.addColorStop(0, 'rgba(254,178,180,.22)'); g2.addColorStop(1, 'rgba(254,178,180,0)'); ctx.fillStyle = g2; ctx.fillRect(0, 0, W, H);
  }
  function grad(ctx, x0, x1) { var g = ctx.createLinearGradient(x0, 0, x1, 0); g.addColorStop(0, '#7c5cff'); g.addColorStop(.6, '#c084d8'); g.addColorStop(1, '#feb2b4'); return g; }
  function storyCard(kind, data) {
    var W = 1080, H = 1920, c = d.createElement('canvas'); c.width = W; c.height = H; var ctx = c.getContext('2d');
    brandBg(ctx, W, H);
    ctx.font = '700 64px Inter, system-ui, sans-serif'; ctx.fillStyle = grad(ctx, 90, 420); ctx.fillText('journzey', 90, 180);
    ctx.font = '600 44px Inter, system-ui, sans-serif'; ctx.fillStyle = '#9d9abb'; ctx.fillText(data.title || '', 90, 520);
    var big = kind === 'roast' ? 64 : 112;
    ctx.font = '700 ' + big + 'px Inter, system-ui, sans-serif'; ctx.fillStyle = '#faf8ff';
    var lines = wrap(ctx, data.text || '', W - 180), y = 640 + big;
    if (kind === 'roast') {
      var bh = lines.length * (big * 1.25) + 90; ctx.fillStyle = grad(ctx, 60, W - 60);
      ctx.beginPath(); if (ctx.roundRect) ctx.roundRect(60, 600, W - 120, bh, [48, 48, 48, 12]); else ctx.rect(60, 600, W - 120, bh); ctx.fill(); ctx.fillStyle = '#fff';
      y = 600 + 45 + big;
    }
    lines.forEach(function (l) { ctx.fillText(l, kind === 'roast' ? 110 : 90, y); y += big * 1.25; });
    if (data.sub) { ctx.font = '500 44px Inter, system-ui, sans-serif'; ctx.fillStyle = '#dedcec'; wrap(ctx, data.sub, W - 180).forEach(function (l) { y += 30; ctx.fillText(l, 90, y + 30); y += 40; }); }
    ctx.font = '500 34px Inter, system-ui, sans-serif'; ctx.fillStyle = '#9d9abb'; ctx.fillText('journzey.ai · Trade the plan. Journal the truth.', 90, H - 140);
    return c;
  }
  function deliver(canvas, name, text) {
    canvas.toBlob(function (blob) {
      if (!blob) return;
      var file = new File([blob], name, { type: 'image/png' });
      if (navigator.canShare && navigator.canShare({ files: [file] })) { navigator.share({ files: [file], text: text || '' }).catch(function () {}); return; }
      var a = d.createElement('a'); a.href = URL.createObjectURL(blob); a.download = name; d.body.appendChild(a); a.click(); setTimeout(function () { URL.revokeObjectURL(a.href); a.remove(); }, 800);
    }, 'image/png');
  }
  $$('[data-share-card]').forEach(function (b) {
    b.addEventListener('click', function () {
      var data = {}; try { data = JSON.parse(b.dataset.card || '{}'); } catch (e) {}
      deliver(storyCard(b.dataset.shareCard, data), 'journzey-' + b.dataset.shareCard + '.png', data.title);
    });
  });

  /* ---------------------------------------------------------------- Psych Art */
  function rng(seed) { var s = (seed % 2147483646) + 1; return function () { s = s * 16807 % 2147483647; return (s - 1) / 2147483646; }; }
  function paintArt(ctx, W, H, a, name) {
    var r = rng(a.seed || 1), cx = W / 2, cy = H * 0.44, R = Math.min(W, H) * 0.34, k = W / 1080;
    var calm = a.trades ? a.disciplined / a.trades : 0.5;
    ctx.fillStyle = '#05040b'; ctx.fillRect(0, 0, W, H);
    var bg = ctx.createRadialGradient(cx, cy, 0, cx, cy, H * 0.75);
    bg.addColorStop(0, a.revenge > a.disciplined ? 'rgba(120,20,40,.45)' : 'rgba(101,65,233,.38)'); bg.addColorStop(0.55, 'rgba(60,30,110,.12)'); bg.addColorStop(1, 'rgba(0,0,0,0)');
    ctx.fillStyle = bg; ctx.fillRect(0, 0, W, H);
    // haze: other rule breaks
    for (var i = 0; i < a.other * 3; i++) { var hx = r() * W, hy = r() * H, hr = (120 + r() * 260) * k, hg = ctx.createRadialGradient(hx, hy, 0, hx, hy, hr); hg.addColorStop(0, 'rgba(255,190,120,.10)'); hg.addColorStop(1, 'rgba(255,190,120,0)'); ctx.fillStyle = hg; ctx.fillRect(hx - hr, hy - hr, hr * 2, hr * 2); }
    // black tentacles: FOMO & chasing
    ctx.lineCap = 'round';
    for (i = 0; i < a.fomo * 3; i++) {
      var side = r() * Math.PI * 2, sx = cx + Math.cos(side) * H, sy = cy + Math.sin(side) * H, ex = cx + Math.cos(side) * R * (0.35 + r() * 0.3), ey = cy + Math.sin(side) * R * (0.35 + r() * 0.3);
      var c1x = sx + (r() - .5) * W * .8, c1y = sy + (r() - .5) * H * .4, c2x = ex + (r() - .5) * W * .5, c2y = ey + (r() - .5) * H * .3;
      for (var w = 0; w < 2; w++) {
        ctx.strokeStyle = w ? 'rgba(0,0,0,.95)' : 'rgba(157,124,255,.35)'; ctx.lineWidth = (w ? 18 : 26) * k * (0.6 + r() * 0.6);
        ctx.beginPath(); ctx.moveTo(sx, sy); ctx.bezierCurveTo(c1x, c1y, c2x, c2y, ex, ey); ctx.stroke();
      }
    }
    // white symmetry: disciplined trades
    var petals = Math.max(6, Math.min(48, 6 + a.disciplined * 2)), rings = Math.max(1, Math.min(7, Math.ceil(a.disciplined / 3)));
    ctx.save(); ctx.translate(cx, cy);
    for (var g = 0; g < rings; g++) {
      var rr = R * (0.25 + g * 0.11), alpha = 0.25 + 0.55 * calm;
      ctx.strokeStyle = 'rgba(250,248,255,' + alpha.toFixed(2) + ')'; ctx.lineWidth = (1.4 + g * 0.3) * k;
      for (var p = 0; p < petals; p++) { ctx.rotate(Math.PI * 2 / petals); ctx.beginPath(); ctx.ellipse(rr * 0.5, 0, rr * 0.5, rr * 0.16, 0, 0, Math.PI * 2); ctx.stroke(); }
    }
    ctx.beginPath(); ctx.arc(0, 0, R * 0.06, 0, Math.PI * 2); var core = ctx.createRadialGradient(0, 0, 0, 0, 0, R * 0.12); core.addColorStop(0, '#fff'); core.addColorStop(1, 'rgba(254,178,180,0)'); ctx.fillStyle = core; ctx.fill();
    ctx.restore();
    // red spikes: revenge
    for (i = 0; i < a.revenge * 6; i++) {
      var ang = r() * Math.PI * 2, r0 = R * (0.15 + r() * 0.2), len = R * (0.6 + r() * 0.9), wd = (8 + r() * 14) * k;
      ctx.save(); ctx.translate(cx, cy); ctx.rotate(ang);
      var sg = ctx.createLinearGradient(r0, 0, r0 + len, 0); sg.addColorStop(0, 'rgba(255,60,80,.95)'); sg.addColorStop(1, 'rgba(255,60,80,0)');
      ctx.fillStyle = sg; ctx.beginPath(); ctx.moveTo(r0, -wd); ctx.lineTo(r0 + len, 0); ctx.lineTo(r0, wd); ctx.closePath(); ctx.fill(); ctx.restore();
    }
    // cracks: moved stops
    for (i = 0; i < a.stop * 2; i++) {
      var x = cx + (r() - .5) * W * 0.8, y = cy + (r() - .5) * H * 0.6, seg = 8 + Math.floor(r() * 8), dir = r() * Math.PI * 2;
      ctx.lineWidth = 3 * k; ctx.strokeStyle = 'rgba(255,255,255,.75)'; ctx.shadowColor = 'rgba(0,0,0,.9)'; ctx.shadowBlur = 6 * k;
      ctx.beginPath(); ctx.moveTo(x, y);
      for (var s = 0; s < seg; s++) { dir += (r() - .5) * 1.4; x += Math.cos(dir) * (30 + r() * 50) * k; y += Math.sin(dir) * (30 + r() * 50) * k; ctx.lineTo(x, y); if (r() < 0.25) { ctx.moveTo(x, y); ctx.lineTo(x + (r() - .5) * 90 * k, y + (r() - .5) * 90 * k); ctx.moveTo(x, y); } }
      ctx.stroke(); ctx.shadowBlur = 0;
    }
    if (!a.trades) { ctx.font = '600 ' + Math.round(40 * k) + 'px Inter, system-ui, sans-serif'; ctx.fillStyle = 'rgba(250,248,255,.6)'; ctx.textAlign = 'center'; ctx.fillText('No trades this week yet', cx, cy + R * 1.2); ctx.textAlign = 'left'; }
    // small signature at the bottom
    ctx.font = '700 ' + Math.round(54 * k) + 'px Inter, system-ui, sans-serif'; ctx.fillStyle = grad(ctx, 90 * k, 420 * k); ctx.fillText(a.type || 'journzey', 90 * k, H - 230 * k);
    ctx.font = '500 ' + Math.round(30 * k) + 'px Inter, system-ui, sans-serif'; ctx.fillStyle = 'rgba(222,220,236,.75)';
    ctx.fillText((name ? name + ' · ' : '') + (a.week || '') + ' · ' + (a.score || 0) + '% disciplined', 90 * k, H - 175 * k);
    ctx.fillStyle = 'rgba(157,154,187,.7)'; ctx.fillText('journzey.ai', 90 * k, H - 120 * k);
  }
  $$('[data-art]').forEach(function (box) {
    var a = {}; try { a = JSON.parse(box.dataset.art); } catch (e) { return; }
    var cv = $('[data-art-canvas]', box), name = box.dataset.artName || '';
    if (cv) paintArt(cv.getContext('2d'), cv.width, cv.height, a, name);
    var big = function () { var c = d.createElement('canvas'); c.width = 2160; c.height = 3840; paintArt(c.getContext('2d'), 2160, 3840, a, name); return c; };
    var dl = $('[data-art-download]', box), sh = $('[data-art-share]', box);
    if (dl) dl.addEventListener('click', function () { big().toBlob(function (b) { var l = d.createElement('a'); l.href = URL.createObjectURL(b); l.download = 'journzey-psych-art-' + (a.seed || '') + '.png'; d.body.appendChild(l); l.click(); setTimeout(function () { URL.revokeObjectURL(l.href); l.remove(); }, 800); }, 'image/png'); });
    if (sh) sh.addEventListener('click', function () { var c = d.createElement('canvas'); c.width = 1080; c.height = 1920; paintArt(c.getContext('2d'), 1080, 1920, a, name); deliver(c, 'journzey-psych-art.png', 'My trading psychology this week: ' + a.type); });
  });
})();

/* Home Hub roast strip: shown once per roast date until dismissed */
(function () {
  var p = document.querySelector('[data-roast-strip]'); if (!p) return;
  var key = 'jz-roast-seen', date = p.getAttribute('data-roast-strip'), seen = '';
  try { seen = localStorage.getItem(key) || ''; } catch (e) {}
  if (seen !== date) p.hidden = false;
  var x = p.querySelector('[data-roast-dismiss]');
  if (x) x.addEventListener('click', function () { p.hidden = true; try { localStorage.setItem(key, date); } catch (e) {} });
})();
