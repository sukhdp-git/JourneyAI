/* journzey.ai University — certificate: live name preview, QR, PNG download (A4 landscape, 300 dpi), print, share. */
(function () {
  'use strict';
  var d = document, fig = d.querySelector('[data-cert]');
  if (!fig) return;
  var nameIn = d.querySelector('[data-cert-name]'), nameOut = fig.querySelector('[data-cert-name-out]');
  if (nameIn) nameIn.addEventListener('input', function () { nameOut.textContent = nameIn.value || ' '; fig.dataset.name = nameIn.value; });
  function qr(ctx, url, x, y, size, color) {
    if (!window.qrcode || !url) return;
    var q = window.qrcode(0, 'M'); q.addData(url); q.make();
    var n = q.getModuleCount(), m = size / n; ctx.fillStyle = color || '#15132a';
    for (var r = 0; r < n; r++) for (var k = 0; k < n; k++) if (q.isDark(r, k)) ctx.fillRect(x + k * m, y + r * m, Math.ceil(m), Math.ceil(m));
  }
  var small = fig.querySelector('[data-cert-qr]');
  function paintSmall() { if (!small || !fig.dataset.url) return; var c = small.getContext('2d'); c.fillStyle = '#fff'; c.fillRect(0, 0, 84, 84); qr(c, fig.dataset.url, 4, 4, 76); }
  if (window.qrcode) paintSmall(); else window.addEventListener('load', paintSmall);

  /* Full-size certificate drawn on a canvas: 3508 × 2480 px (A4 landscape at 300 dpi) */
  function render() {
    var W = 3508, H = 2480, c = d.createElement('canvas'); c.width = W; c.height = H; var x = c.getContext('2d');
    x.fillStyle = '#fbfaff'; x.fillRect(0, 0, W, H);
    var g1 = x.createRadialGradient(400, 300, 50, 400, 300, 1800); g1.addColorStop(0, 'rgba(101,65,233,.14)'); g1.addColorStop(1, 'rgba(101,65,233,0)'); x.fillStyle = g1; x.fillRect(0, 0, W, H);
    var g2 = x.createRadialGradient(3100, 2200, 50, 3100, 2200, 1600); g2.addColorStop(0, 'rgba(254,178,180,.22)'); g2.addColorStop(1, 'rgba(254,178,180,0)'); x.fillStyle = g2; x.fillRect(0, 0, W, H);
    var bg = x.createLinearGradient(0, 0, W, 0); bg.addColorStop(0, '#6541e9'); bg.addColorStop(.5, '#b17ace'); bg.addColorStop(1, '#feb2b4');
    x.strokeStyle = bg; x.lineWidth = 22; x.strokeRect(110, 110, W - 220, H - 220);
    x.strokeStyle = 'rgba(101,65,233,.25)'; x.lineWidth = 4; x.strokeRect(170, 170, W - 340, H - 340);
    var center = function (t, y, font, color) { x.font = font; x.fillStyle = color; x.textAlign = 'center'; x.fillText(t, W / 2, y); };
    x.textAlign = 'left'; x.font = '800 120px Inter, sans-serif'; x.fillStyle = bg; x.fillText('journzey', 300, 420);
    x.font = '600 70px Inter, sans-serif'; x.fillStyle = '#636080'; x.textAlign = 'right'; x.fillText('UNIVERSITY', W - 300, 410);
    center('CERTIFICATE OF COMPLETION', 760, '700 96px Inter, sans-serif', '#5232d4');
    center('This certifies that', 960, 'italic 400 92px Georgia, serif', '#636080');
    var name = fig.dataset.name || '', size = name.length > 28 ? 190 : 240;
    center(name, 1260, 'italic 400 ' + size + 'px "Instrument Serif", Georgia, serif', '#15132a');
    x.strokeStyle = 'rgba(101,65,233,.35)'; x.lineWidth = 4; x.beginPath(); x.moveTo(W / 2 - 1000, 1330); x.lineTo(W / 2 + 1000, 1330); x.stroke();
    center('has completed ' + fig.dataset.course + ', passing the quiz in every lesson.', 1490, '500 78px Inter, sans-serif', '#2c2a40');
    center(fig.dataset.meta, 1620, '500 60px Inter, sans-serif', '#8f8ca8');
    // footer: signature · seal · QR
    x.textAlign = 'left'; x.strokeStyle = '#15132a'; x.lineWidth = 3; x.beginPath(); x.moveTo(360, 2010); x.lineTo(1160, 2010); x.stroke();
    x.font = '700 62px Inter, sans-serif'; x.fillStyle = '#15132a'; x.fillText('journzey.ai University', 360, 2100);
    x.font = '500 52px Inter, sans-serif'; x.fillStyle = '#636080'; x.fillText('Issued ' + fig.dataset.date, 360, 2180);
    var sx = W / 2, sy = 2010, sr = 210; var sg = x.createLinearGradient(sx - sr, 0, sx + sr, 0); sg.addColorStop(0, '#6541e9'); sg.addColorStop(1, '#feb2b4');
    x.fillStyle = sg; x.beginPath(); x.arc(sx, sy, sr, 0, Math.PI * 2); x.fill();
    x.setLineDash([14, 14]); x.strokeStyle = '#fff'; x.lineWidth = 6; x.beginPath(); x.arc(sx, sy, sr - 34, 0, Math.PI * 2); x.stroke(); x.setLineDash([]);
    x.textAlign = 'center'; x.fillStyle = '#fff'; x.font = '800 62px Inter, sans-serif'; x.fillText('VERIFIED', sx, sy - 6); x.font = '600 44px Inter, sans-serif'; x.fillText('COMPLETE', sx, sy + 64);
    x.fillStyle = '#fff'; x.fillRect(W - 760, 1800, 400, 400); qr(x, fig.dataset.url, W - 740, 1820, 360);
    x.textAlign = 'right'; x.font = '600 46px Inter, sans-serif'; x.fillStyle = '#636080'; x.fillText('ID ' + fig.dataset.code, W - 360, 2270);
    return c;
  }
  function blob(cb) { render().toBlob(cb, 'image/png'); }
  function file(name) { return 'journzey-certificate-' + (fig.dataset.name || 'trader').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '') + '.png'; }
  var dl = d.querySelector('[data-cert-download]');
  if (dl) dl.addEventListener('click', function () {
    dl.disabled = true;
    blob(function (b) { dl.disabled = false; var a = d.createElement('a'); a.href = URL.createObjectURL(b); a.download = file(); d.body.appendChild(a); a.click(); setTimeout(function () { URL.revokeObjectURL(a.href); a.remove(); }, 500); });
  });
  var pr = d.querySelector('[data-cert-print]'); if (pr) pr.addEventListener('click', function () { d.body.classList.add('cx-printing'); window.print(); setTimeout(function () { d.body.classList.remove('cx-printing'); }, 500); });
  var cp = d.querySelector('[data-cert-copy]');
  if (cp) cp.addEventListener('click', function () { var u = cp.dataset.certCopy; if (navigator.clipboard) navigator.clipboard.writeText(u).then(function () { if (window.tmToast) window.tmToast('Verification link copied.', 'success'); }); else prompt('Copy this link', u); });
  var sh = d.querySelector('[data-cert-share]');
  if (sh) sh.addEventListener('click', function () {
    blob(function (b) {
      var f = new File([b], file(), { type: 'image/png' });
      if (navigator.canShare && navigator.canShare({ files: [f] })) navigator.share({ files: [f], title: 'My trading certificate', text: 'I completed ' + fig.dataset.course + ' on journzey.ai University. Verify: ' + fig.dataset.url }).catch(function () {});
      else if (navigator.clipboard) navigator.clipboard.writeText(fig.dataset.url).then(function () { if (window.tmToast) window.tmToast('Sharing is not supported here. The verification link was copied instead.', 'success'); });
    });
  });
})();
