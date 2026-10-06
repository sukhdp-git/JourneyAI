/* journzey.ai Control Panel — vanilla JS (no build step). */
(function () {
  'use strict';
  var d = document;
  var csrf = (d.querySelector('meta[name="csrf-token"]') || {}).content || '';
  var base = (d.querySelector('meta[name="admin-base"]') || {}).content || '/control-panel/';
  function $(s, c) { return (c || d).querySelector(s); }
  function $$(s, c) { return Array.prototype.slice.call((c || d).querySelectorAll(s)); }
  function esc(s) { var div = d.createElement('div'); div.textContent = s == null ? '' : String(s); return div.innerHTML; }

  // ---------- Toasts
  var toastBox = $('[data-toasts]');
  function dismiss(t) { t.classList.add('out'); setTimeout(function () { t.remove(); }, 260); }
  function toast(msg, type) {
    if (!toastBox) return alert(msg);
    var t = d.createElement('div');
    t.className = 'toast toast-' + (type || 'success');
    t.setAttribute('role', type === 'error' ? 'alert' : 'status');
    t.innerHTML = '<p>' + esc(msg) + '</p><button type="button" class="toast-x" aria-label="Dismiss">×</button>';
    toastBox.appendChild(t); arm(t);
  }
  function arm(t) {
    var x = $('.toast-x', t); if (x) x.addEventListener('click', function () { dismiss(t); });
    if (!t.classList.contains('toast-error')) setTimeout(function () { dismiss(t); }, 5000);
  }
  $$('.toast').forEach(arm);
  window.cpToast = toast;

  function post(url, data) {
    var fd = data instanceof FormData ? data : new FormData();
    if (!(data instanceof FormData) && data) Object.keys(data).forEach(function (k) { [].concat(data[k]).forEach(function (v) { fd.append(k, v); }); });
    fd.append('_csrf', csrf);
    return fetch(url, { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'X-CSRF-Token': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
      .then(function (r) { return r.json().catch(function () { return { ok: false, error: 'Unexpected response (' + r.status + ')' }; }).then(function (j) { if (r.status === 401) { location.reload(); } return j; }); });
  }

  // ---------- Sidebar & user menu
  $$('[data-sidebar-open]').forEach(function (b) { b.addEventListener('click', function () { d.body.classList.add('sidebar-open'); var c = $('.cp-sidebar-close'); if (c) c.focus(); }); });
  $$('[data-sidebar-close]').forEach(function (b) { b.addEventListener('click', function () { d.body.classList.remove('sidebar-open'); }); });
  $$('[data-dropdown]').forEach(function (dd) {
    var btn = $('[data-dropdown-toggle]', dd);
    btn.addEventListener('click', function (e) { e.stopPropagation(); var open = !dd.classList.contains('is-open'); dd.classList.toggle('is-open', open); btn.setAttribute('aria-expanded', String(open)); });
    d.addEventListener('click', function () { dd.classList.remove('is-open'); btn.setAttribute('aria-expanded', 'false'); });
  });
  d.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    d.body.classList.remove('sidebar-open');
    $$('[data-dropdown].is-open').forEach(function (x) { x.classList.remove('is-open'); });
    var m = $('.modal:not([hidden])'); if (m) closeModal(m);
  });

  // ---------- Modals
  var lastFocus = null;
  function openModal(m) { lastFocus = d.activeElement; m.hidden = false; var f = $('button, input, [tabindex]', m.querySelector('.modal-card')); if (f) f.focus(); }
  function closeModal(m) { m.hidden = true; if (lastFocus) lastFocus.focus(); }
  $$('.modal').forEach(function (m) {
    $$('[data-modal-close]', m).forEach(function (b) { b.addEventListener('click', function () { closeModal(m); }); });
    m.addEventListener('keydown', function (e) { // focus trap
      if (e.key !== 'Tab') return;
      var f = $$('button:not([disabled]), input, select, textarea, a[href], [tabindex]:not([tabindex="-1"])', m).filter(function (x) { return x.offsetParent !== null; });
      if (!f.length) return;
      if (e.shiftKey && d.activeElement === f[0]) { e.preventDefault(); f[f.length - 1].focus(); }
      else if (!e.shiftKey && d.activeElement === f[f.length - 1]) { e.preventDefault(); f[0].focus(); }
    });
  });

  // ---------- Confirm dialogs (delete actions post a real form with the CSRF token)
  var cm = $('#confirm-modal'), pendingAction = null, pendingForm = null;
  // Forms marked data-confirm ask before submitting (their own fields and CSRF token are posted).
  d.addEventListener('submit', function (e) {
    var f = e.target; if (!cm || !f.matches('form[data-confirm]') || f.dataset.confirmed) return;
    e.preventDefault(); pendingForm = f; pendingAction = null;
    $('[data-confirm-text]', cm).textContent = f.getAttribute('data-confirm');
    openModal(cm);
  });
  d.addEventListener('click', function (e) {
    var b = e.target.closest('[data-confirm]'); if (!b || !cm || b.tagName === 'FORM') return;
    e.preventDefault();
    pendingAction = b.getAttribute('data-confirm-action'); pendingForm = null;
    $('[data-confirm-text]', cm).textContent = b.getAttribute('data-confirm');
    openModal(cm);
  });
  if (cm) $('[data-confirm-ok]', cm).addEventListener('click', function () {
    if (pendingForm) { pendingForm.dataset.confirmed = '1'; dirty = false; this.classList.add('is-loading'); pendingForm.submit(); return; }
    if (!pendingAction) return;
    var f = d.createElement('form'); f.method = 'post'; f.action = pendingAction;
    f.innerHTML = '<input type="hidden" name="_csrf" value="' + esc(csrf) + '">';
    d.body.appendChild(f); dirty = false; this.classList.add('is-loading'); f.submit();
  });

  // ---------- Enable/disable switches
  $$('[data-toggle-url]').forEach(function (b) {
    b.addEventListener('click', function () {
      b.classList.add('busy');
      post(b.getAttribute('data-toggle-url')).then(function (r) {
        b.classList.remove('busy');
        if (r.ok) { b.classList.toggle('on', r.on); b.setAttribute('aria-checked', String(r.on)); toast(r.message); }
        else toast(r.error || r.message || 'Could not update.', 'error');
      }).catch(function () { b.classList.remove('busy'); toast('Network error.', 'error'); });
    });
  });

  // ---------- Table reorder (drag & drop + keyboard-friendly arrow buttons)
  $$('table[data-reorder]').forEach(function (table) {
    var tbody = $('tbody', table), dragRow = null, timer = null;
    function save() {
      clearTimeout(timer);
      timer = setTimeout(function () {
        var ids = $$('tr[data-id]', tbody).map(function (r) { return r.getAttribute('data-id'); });
        post(table.getAttribute('data-reorder'), { 'ids[]': ids }).then(function (r) { toast(r.ok ? r.message : (r.error || 'Could not save order.'), r.ok ? 'success' : 'error'); });
      }, 350);
    }
    $$('tr[data-id]', tbody).forEach(function (row) {
      row.addEventListener('dragstart', function (e) { dragRow = row; row.classList.add('dragging'); e.dataTransfer.effectAllowed = 'move'; try { e.dataTransfer.setData('text/plain', row.dataset.id); } catch (x) {} });
      row.addEventListener('dragend', function () { row.classList.remove('dragging'); $$('.drop-target', tbody).forEach(function (r) { r.classList.remove('drop-target'); }); });
      row.addEventListener('dragover', function (e) { e.preventDefault(); if (row !== dragRow) row.classList.add('drop-target'); });
      row.addEventListener('dragleave', function () { row.classList.remove('drop-target'); });
      row.addEventListener('drop', function (e) {
        e.preventDefault(); row.classList.remove('drop-target');
        if (!dragRow || dragRow === row) return;
        var rows = $$('tr[data-id]', tbody);
        if (rows.indexOf(dragRow) < rows.indexOf(row)) row.after(dragRow); else row.before(dragRow);
        save();
      });
      $$('[data-move]', row).forEach(function (btn) {
        btn.addEventListener('click', function () {
          var up = btn.getAttribute('data-move') === 'up', sib = up ? row.previousElementSibling : row.nextElementSibling;
          if (!sib) return;
          if (up) sib.before(row); else sib.after(row);
          btn.focus(); save();
        });
      });
    });
  });

  // ---------- Tabs
  $$('[role=tablist]').forEach(function (list) {
    var tabs = $$('[role=tab]', list);
    function show(t) {
      tabs.forEach(function (x) { var on = x === t; x.setAttribute('aria-selected', String(on)); x.tabIndex = on ? 0 : -1; var p = d.getElementById(x.getAttribute('aria-controls')); if (p) p.hidden = !on; });
    }
    tabs.forEach(function (t, i) {
      t.addEventListener('click', function () { show(t); });
      t.addEventListener('keydown', function (e) {
        var n = e.key === 'ArrowRight' ? i + 1 : e.key === 'ArrowLeft' ? i - 1 : null;
        if (n === null) return; n = (n + tabs.length) % tabs.length; tabs[n].focus(); show(tabs[n]);
      });
    });
    var errTab = tabs.filter(function (t) { return t.querySelector('.tab-err'); })[0];
    if (errTab) show(errTab);
  });

  // ---------- Rich text editor (contenteditable; the server sanitises everything)
  function initRte(wrap) {
    if (wrap.dataset.ready) return; wrap.dataset.ready = '1';
    var area = $('.rte-area', wrap), src = $('.rte-src', wrap);
    area.innerHTML = src.value;
    function sync() { if (!wrap.classList.contains('is-source')) src.value = area.innerHTML.trim() === '<br>' ? '' : area.innerHTML; }
    area.addEventListener('input', function () { sync(); markDirty(); });
    area.addEventListener('blur', sync);
    area.addEventListener('paste', function (e) {
      var html = (e.clipboardData || window.clipboardData).getData('text/html');
      var text = (e.clipboardData || window.clipboardData).getData('text/plain');
      e.preventDefault();
      if (html) {
        var tmp = d.createElement('div'); tmp.innerHTML = html;
        $$('script,style,meta,link,iframe,object', tmp).forEach(function (n) { n.remove(); });
        $$('*', tmp).forEach(function (n) { Array.prototype.slice.call(n.attributes).forEach(function (a) { if (['href', 'src', 'alt'].indexOf(a.name) < 0) n.removeAttribute(a.name); }); });
        d.execCommand('insertHTML', false, tmp.innerHTML);
      } else d.execCommand('insertText', false, text);
      sync();
    });
    $$('.rte-bar button', wrap).forEach(function (b) {
      b.addEventListener('mousedown', function (e) { e.preventDefault(); });
      b.addEventListener('click', function () {
        var cmd = b.dataset.cmd;
        if (cmd === 'source') {
          var on = !wrap.classList.contains('is-source');
          if (on) { sync(); wrap.classList.add('is-source'); src.hidden = false; src.focus(); }
          else { area.innerHTML = src.value; wrap.classList.remove('is-source'); src.hidden = true; }
          b.classList.toggle('on', on); return;
        }
        if (wrap.classList.contains('is-source')) return;
        area.focus();
        if (cmd === 'link') {
          var u = prompt('Link URL (https://… or /page):', 'https://');
          if (u && /^(https?:\/\/|\/|mailto:|tel:|#)/i.test(u)) d.execCommand('createLink', false, u);
        } else if (cmd === 'image') {
          var sel = saveSel();
          pickMedia(function (item) { restoreSel(sel); d.execCommand('insertHTML', false, '<img src="' + esc(item.url) + '" alt="' + esc(item.alt || '') + '">'); sync(); });
        } else if (cmd === 'formatBlock') {
          d.execCommand('formatBlock', false, '<' + b.dataset.val + '>');
        } else d.execCommand(cmd, false, null);
        sync(); markDirty();
      });
    });
    var form = wrap.closest('form'); if (form) form.addEventListener('submit', function () { if (wrap.classList.contains('is-source')) area.innerHTML = src.value; else sync(); });
  }
  function saveSel() { var s = window.getSelection(); return s.rangeCount ? s.getRangeAt(0).cloneRange() : null; }
  function restoreSel(r) { if (!r) return; var s = window.getSelection(); s.removeAllRanges(); s.addRange(r); }

  // ---------- Media picker
  var mm = $('#media-modal'), pickCb = null, mPage = 1, mQuery = '', mTimer;
  function loadMedia() {
    var grid = $('[data-media-grid]', mm);
    grid.innerHTML = '<p class="loading">Loading…</p>';
    fetch(base + 'media/browse?page=' + mPage + '&q=' + encodeURIComponent(mQuery), { credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (!j.ok) { grid.innerHTML = '<p class="none">' + esc(j.error || 'Could not load media.') + '</p>'; return; }
        grid.innerHTML = j.items.length ? '' : '<p class="none">No images yet. Upload one above.</p>';
        j.items.forEach(function (it) {
          var b = d.createElement('button'); b.type = 'button';
          b.innerHTML = '<img src="' + esc(it.thumb) + '" alt="" loading="lazy"><span>' + esc(it.name) + '</span><span class="muted">' + esc(it.dims + ' ' + it.size) + '</span>';
          b.addEventListener('click', function () { closeModal(mm); if (pickCb) pickCb(it); });
          grid.appendChild(b);
        });
        $('[data-media-page]', mm).textContent = 'Page ' + j.page + ' of ' + j.pages;
        $('[data-media-prev]', mm).disabled = j.page <= 1; $('[data-media-next]', mm).disabled = j.page >= j.pages;
      }).catch(function () { grid.innerHTML = '<p class="none">Network error.</p>'; });
  }
  function pickMedia(cb) { if (!mm) return toast('Your role cannot use the media library.', 'error'); pickCb = cb; mPage = 1; openModal(mm); loadMedia(); }
  if (mm) {
    $('[data-media-search]', mm).addEventListener('input', function () { var v = this.value; clearTimeout(mTimer); mTimer = setTimeout(function () { mQuery = v; mPage = 1; loadMedia(); }, 300); });
    $('[data-media-prev]', mm).addEventListener('click', function () { mPage--; loadMedia(); });
    $('[data-media-next]', mm).addEventListener('click', function () { mPage++; loadMedia(); });
    $('[data-media-upload]', mm).addEventListener('change', function () {
      var fd = new FormData(); Array.prototype.forEach.call(this.files, function (f) { fd.append('files[]', f); });
      var input = this; toast('Uploading…', 'info');
      post(base + 'media/upload', fd).then(function (r) { toast(r.message || r.error, r.ok ? 'success' : 'error'); input.value = ''; mPage = 1; loadMedia(); });
    });
  }
  function initImage(w) {
    if (w.dataset.ready) return; w.dataset.ready = '1';
    var input = $('[data-image-input]', w), prev = $('[data-image-preview]', w), choose = $('[data-image-choose]', w), clear = $('[data-image-clear]', w);
    if (choose) choose.addEventListener('click', function () { pickMedia(function (it) { input.value = it.path; prev.innerHTML = '<img src="' + esc(it.thumb) + '" alt="">'; markDirty(); }); });
    if (clear) clear.addEventListener('click', function () { input.value = ''; prev.innerHTML = '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="m21 16-5-5-9 9"/></svg>'; markDirty(); });
  }

  // ---------- Small field helpers
  function initFields(root) {
    $$('[data-rte]', root).forEach(initRte);
    $$('[data-image-field]', root).forEach(initImage);
    $$('.color-field', root).forEach(function (c) {
      var p = $('[data-color-picker]', c), t = $('[data-color-text]', c);
      p.addEventListener('input', function () { t.value = p.value; t.dispatchEvent(new Event('input', { bubbles: true })); });
      t.addEventListener('input', function () { if (/^#[0-9a-f]{6}$/i.test(t.value)) p.value = t.value; });
    });
    $$('[data-icon-select]', root).forEach(function (s) {
      var wrap = s.closest('.icon-select'), prev = $('[data-icon-preview]', wrap), tpl = $('[data-icon-sprites]', wrap);
      s.addEventListener('change', function () { var n = tpl.content.querySelector('[data-n="' + s.value + '"]'); prev.innerHTML = n ? n.innerHTML : ''; });
    });
    $$('[data-pw-toggle]', root).forEach(function (b) {
      b.addEventListener('click', function () { var i = b.parentNode.querySelector('input'); var show = i.type === 'password'; i.type = show ? 'text' : 'password'; b.setAttribute('aria-label', show ? 'Hide password' : 'Show password'); });
    });
    $$('[data-counter-for]', root).forEach(function (c) {
      var el = d.getElementById(c.getAttribute('data-counter-for')), max = +c.getAttribute('data-max');
      if (!el) return;
      function upd() { var n = el.value.length; c.textContent = n + ' / ' + max; c.classList.toggle('over', n > max); }
      el.addEventListener('input', upd); upd();
    });
  }
  initFields(d);

  // Slug auto-fill from the title until the slug is edited by hand
  $$('[data-slug-source]').forEach(function (slug) {
    var srcName = slug.getAttribute('data-slug-source'); if (!srcName || slug.readOnly) return;
    var form = slug.closest('form'), src = form && form.querySelector('[name="' + srcName + '"]');
    if (!src) return;
    var manual = slug.value !== '';
    slug.addEventListener('input', function () { manual = slug.value !== ''; });
    src.addEventListener('input', function () {
      if (manual) return;
      slug.value = src.value.toLowerCase().normalize('NFKD').replace(/[̀-ͯ]/g, '').replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 150);
    });
    slug.addEventListener('blur', function () { slug.value = slug.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, ''); });
  });

  // ---------- Repeaters
  $$('[data-repeater]').forEach(function (rep) {
    var list = $('[data-rep-list]', rep), tpl = $('[data-rep-template]', rep), counter = Date.now();
    function wire(row) {
      $('[data-rep-remove]', row).addEventListener('click', function () { if (confirm('Remove this item?')) { row.remove(); markDirty(); } });
      $('[data-rep-up]', row).addEventListener('click', function () { var p = row.previousElementSibling; if (p) { p.before(row); markDirty(); } });
      $('[data-rep-down]', row).addEventListener('click', function () { var n = row.nextElementSibling; if (n) { n.after(row); markDirty(); } });
      $('[data-rep-collapse]', row).addEventListener('click', function () { row.classList.toggle('collapsed'); });
      var title = $('[data-rep-title]', row), first = $('input[type=text]', row);
      if (first) first.addEventListener('input', function () { title.textContent = first.value || 'Item'; });
      var handle = $('[data-rep-drag]', row);
      handle.addEventListener('mousedown', function () { row.draggable = true; });
      row.addEventListener('dragstart', function () { row.classList.add('dragging'); list.dataset.drag = '1'; rep._drag = row; });
      row.addEventListener('dragend', function () { row.classList.remove('dragging'); row.draggable = false; markDirty(); });
      row.addEventListener('dragover', function (e) { e.preventDefault(); var dr = rep._drag; if (!dr || dr === row) return; var r = row.getBoundingClientRect(); if (e.clientY < r.top + r.height / 2) row.before(dr); else row.after(dr); });
    }
    $$('[data-rep-row]', list).forEach(wire);
    $('[data-rep-add]', rep).addEventListener('click', function () {
      var html = tpl.innerHTML.replace(/__i__/g, 'n' + (counter++));
      var tmp = d.createElement('div'); tmp.innerHTML = html.trim();
      var row = tmp.firstElementChild; list.appendChild(row); wire(row); initFields(row);
      var f = $('input, textarea, select', row); if (f) f.focus(); markDirty();
    });
  });

  // ---------- Misc
  $$('[data-autosubmit]').forEach(function (s) { s.addEventListener('change', function () { s.form.submit(); }); });
  $$('[data-ajax-status]').forEach(function (s) {
    s.addEventListener('change', function () {
      var form = s.form;
      post(form.action, { status: s.value }).then(function (r) {
        toast(r.ok ? r.message : (r.error || 'Could not update.'), r.ok ? 'success' : 'error');
        if (r.ok) s.className = 'badge-select st-' + s.value;
      });
    });
  });
  $$('[data-ajax-form]').forEach(function (f) {
    f.addEventListener('submit', function (e) { e.preventDefault(); post(f.action, new FormData(f)).then(function (r) { toast(r.message || r.error, r.ok ? 'success' : 'error'); }); });
  });
  d.addEventListener('click', function (e) {
    var b = e.target.closest('[data-copy]'); if (!b) return;
    var v = b.getAttribute('data-copy');
    (navigator.clipboard ? navigator.clipboard.writeText(v) : Promise.reject()).then(function () { toast('URL copied to clipboard.'); }, function () { prompt('Copy this URL:', v); });
  });
  $$('[data-loading-form]').forEach(function (f) { f.addEventListener('submit', function () { var b = $('[type=submit]', f); if (b) b.classList.add('is-loading'); }); });

  // Media library drag & drop upload with progress
  $$('[data-dropzone]').forEach(function (z) {
    var input = $('[data-dropzone-input]', z), bar = $('[data-dropzone-progress]', z);
    function send(files) {
      if (!files.length) return;
      var fd = new FormData(); Array.prototype.forEach.call(files, function (f) { fd.append('files[]', f); }); fd.append('_csrf', csrf);
      var x = new XMLHttpRequest(); x.open('POST', z.action); x.setRequestHeader('X-Requested-With', 'XMLHttpRequest'); x.setRequestHeader('Accept', 'application/json'); x.setRequestHeader('X-CSRF-Token', csrf);
      bar.hidden = false;
      x.upload.onprogress = function (e) { if (e.lengthComputable) bar.firstElementChild.style.width = (e.loaded / e.total * 100) + '%'; };
      x.onload = function () {
        var r = {}; try { r = JSON.parse(x.responseText); } catch (err) { r = { ok: false, message: x.status === 413 ? 'The upload is larger than the server allows.' : 'Upload failed.' }; }
        toast(r.message || r.error, r.ok ? 'success' : 'error');
        if (r.ok) setTimeout(function () { location.reload(); }, 700); else bar.hidden = true;
      };
      x.onerror = function () { toast('Network error during upload.', 'error'); bar.hidden = true; };
      x.send(fd);
    }
    input.addEventListener('change', function () { send(input.files); });
    ['dragenter', 'dragover'].forEach(function (ev) { z.addEventListener(ev, function (e) { e.preventDefault(); z.classList.add('over'); }); });
    ['dragleave', 'drop'].forEach(function (ev) { z.addEventListener(ev, function (e) { e.preventDefault(); z.classList.remove('over'); }); });
    z.addEventListener('drop', function (e) { send(e.dataTransfer.files); });
  });

  // Live colour preview on Appearance → Colours
  var tp = $('[data-theme-preview]');
  if (tp) {
    var map = { color_primary: '--p-primary', color_secondary: '--p-secondary', color_accent: '--p-accent', color_text: '--p-text', color_heading: '--p-heading', color_muted: '--p-muted', color_bg: '--p-bg', color_surface: '--p-surface', color_border: '--p-border' };
    Object.keys(map).forEach(function (k) {
      var i = d.querySelector('[name="' + k + '"]'); if (!i) return;
      var set = function () { if (/^#[0-9a-f]{6}$/i.test(i.value)) tp.style.setProperty(map[k], i.value); };
      i.addEventListener('input', set); set();
    });
  }

  // Unsaved-changes warning
  var dirty = false;
  function markDirty() { dirty = true; }
  $$('form[data-dirty-check]').forEach(function (f) {
    f.addEventListener('input', markDirty); f.addEventListener('change', markDirty);
    f.addEventListener('submit', function () { dirty = false; var b = $('.form-foot [type=submit]', f); if (b) b.classList.add('is-loading'); });
  });
  window.addEventListener('beforeunload', function (e) { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
})();
