/* journzey.ai — per-field voice buttons. Any <button data-vf="key" data-vf-target="#input"> becomes a microphone that
 * fills just that one field: numbers are read from words or digits in the chosen language (shared parser in
 * voice-parse.js), selects are matched to what was said, text fields are dictated. Pages can register a smarter
 * handler: jzVoiceField.handlers[key] = function (text, lang) { return 'shown value' | false }.
 * The voice language is picked with any <select data-vf-lang> and remembered on this device. */
(function () {
  'use strict';
  var d = document;
  var LANGS = [['en-US', 'English'], ['hi-IN', 'हिंदी'], ['ru-RU', 'Русский'], ['zh-CN', '中文'], ['pt-BR', 'Português'], ['ar-SA', 'العربية']];
  var BY_PAGE = { en: 'en-US', hi: 'hi-IN', ru: 'ru-RU', zh: 'zh-CN', pt: 'pt-BR', ar: 'ar-SA' };
  var store = { get: function (k) { try { return localStorage.getItem(k); } catch (e) { return null; } }, set: function (k, v) { try { localStorage.setItem(k, v); } catch (e) {} } };
  function lang() { var l = store.get('jz-voice-lang'); if (l && LANGS.some(function (x) { return x[0] === l; })) return l; return BY_PAGE[(d.documentElement.lang || 'en').slice(0, 2)] || 'en-US'; }
  var parser = null;
  function P() { if (!parser && window.jzVoiceParser) parser = window.jzVoiceParser({}, []); return parser; }
  function toast(m, t) { if (window.tmToast) window.tmToast(m, t); }

  /* ---------------------------------------------------------------- small "heard" bubble next to the mic */
  var bubble = null, hideT = null;
  function say(btn, text, kind) {
    if (!bubble) { bubble = d.createElement('div'); bubble.className = 'vf-bubble'; bubble.setAttribute('role', 'status'); bubble.setAttribute('aria-live', 'polite'); d.body.appendChild(bubble); }
    clearTimeout(hideT);
    bubble.textContent = text; bubble.className = 'vf-bubble on' + (kind ? ' ' + kind : '');
    var r = btn.getBoundingClientRect(), w = Math.min(300, window.innerWidth - 24);
    bubble.style.maxWidth = w + 'px';
    var x = Math.min(Math.max(12, r.left + r.width / 2 - 20), window.innerWidth - w - 12);
    bubble.style.left = x + 'px'; bubble.style.top = (r.bottom + 8) + 'px';
    if (kind) hideT = setTimeout(function () { bubble.className = 'vf-bubble'; }, kind === 'err' ? 3200 : 2200);
  }

  /* ---------------------------------------------------------------- generic fillers */
  function firstNumber(text) {
    var p = P(), s = p ? p.normalize(text, lang()) : String(text).toLowerCase();
    var m = s.replace(/(\d)\s*[,](?=\d{3}\b)/g, '$1').match(/-?\d+(?:\.\d+)?/);
    return m ? m[0] : null;
  }
  function fire(el) { el.dispatchEvent(new Event('input', { bubbles: true })); el.dispatchEvent(new Event('change', { bubbles: true })); el.classList.add('filled'); setTimeout(function () { el.classList.remove('filled'); }, 1800); }
  function words(s) { return String(s).toLowerCase().normalize('NFKD').replace(/[̀-ͯ]/g, '').replace(/[^\p{L}\p{N}\s]/gu, ' ').split(/\s+/).filter(function (w) { return w.length > 1; }); }
  function matchOption(sel, text) {
    var heard = words(text), best = null, bestScore = 0, low = String(text).toLowerCase();
    Array.prototype.forEach.call(sel.options, function (o) {
      if (o.disabled) return;
      var label = (o.textContent + ' ' + o.value.replace(/_/g, ' ') + ' ' + (o.dataset.vfAlias || '')).toLowerCase(), score = 0;
      words(label).forEach(function (w) { if (heard.indexOf(w) >= 0) score += w.length; else if ((w.length > 3 && low.indexOf(w.slice(0, 4)) >= 0) || (/[^\x00-\x7f]/.test(w) && low.indexOf(w) >= 0)) score += 2; });
      if (o.value === '' && score) score -= 1;
      if (score > bestScore) { best = o; bestScore = score; }
    });
    return best;
  }
  function dateFrom(text) {
    var t = String(text).toLowerCase(), day = new Date();
    if (/yesterday|вчера|昨天|ontem|कल|أمس|امس/.test(t)) day.setDate(day.getDate() - 1);
    else { var n = firstNumber(t); if (n !== null && /ago|назад|前|atrás|पहले|منذ|قبل/.test(t)) day.setDate(day.getDate() - Math.round(+n)); else if (!/today|сегодня|今天|hoje|आज|اليوم/.test(t)) return null; }
    var p = function (x) { return (x < 10 ? '0' : '') + x; };
    return day.getFullYear() + '-' + p(day.getMonth() + 1) + '-' + p(day.getDate());
  }
  function generic(target, text) {
    if (!target) return false;
    var tag = target.tagName, type = (target.type || '').toLowerCase();
    if (tag === 'SELECT') {
      var o = matchOption(target, text); if (!o) return false;
      target.value = o.value; fire(target); return o.textContent.trim();
    }
    if (type === 'number') { var n = firstNumber(text); if (n === null) return false; target.value = n; fire(target); return n; }
    if (type === 'date') { var dt = dateFrom(text); if (!dt) return false; target.value = dt; fire(target); return dt; }
    var clean = String(text).trim(); if (!clean) return false;
    if (tag === 'TEXTAREA' || target.dataset.vfMode === 'append') {
      var cur = target.value.replace(/\s+$/, '');
      clean = clean.charAt(0).toUpperCase() + clean.slice(1); if (!/[.!?。！？]$/.test(clean)) clean += '.';
      target.value = (cur ? cur + ' ' : '') + clean;
    } else target.value = clean;
    if (target.maxLength > 0 && target.value.length > target.maxLength) target.value = target.value.slice(0, target.maxLength);
    fire(target);
    return clean.length > 40 ? clean.slice(0, 40) + '…' : clean;
  }

  /* ---------------------------------------------------------------- microphone */
  var SR = window.SpeechRecognition || window.webkitSpeechRecognition, active = null;
  function listen(btn) {
    if (!SR) { toast('Voice needs Chrome, Edge or Safari. You can still type every field.', 'error'); return; }
    if (active) { var was = active.btn; active.rec.stop(); if (was === btn) return; }
    var key = btn.dataset.vf, target = btn.dataset.vfTarget ? d.querySelector(btn.dataset.vfTarget) : null;
    var long = target && (target.tagName === 'TEXTAREA' || target.dataset.vfMode === 'append');
    btn.classList.add('rec'); btn.setAttribute('aria-pressed', 'true'); say(btn, 'Listening… say the ' + (btn.dataset.vfLabel || 'value'));
    var rec = window.jzRecognize({ lang: lang(), continuous: !!long, silenceMs: 1800, firstMs: 7000, maxMs: long ? 45000 : 12000,
      onInterim: function (t) { say(btn, '“' + t + '”'); },
      onError: function (code) { if (code === 'not-allowed' || code === 'service-not-allowed') toast('Microphone access is blocked. Allow it in your browser settings.', 'error'); },
      onDone: function (t) {
        btn.classList.remove('rec'); btn.setAttribute('aria-pressed', 'false'); active = null;
        if (!t) { say(btn, 'Didn’t catch that. Tap and try again.', 'err'); return; }
        var h = window.jzVoiceField.handlers[key], shown = h ? h(t, lang(), target) : generic(target, t);
        if (shown === false || shown === null || shown === undefined) say(btn, 'Not recognised: “' + t + '”', 'err');
        else {
          say(btn, '✓ ' + shown, 'ok');
          if (btn.dataset.vfSubmit !== undefined && target && target.form) target.form.requestSubmit ? target.form.requestSubmit() : target.form.submit();
        }
      } });
    if (!rec) { btn.classList.remove('rec'); btn.setAttribute('aria-pressed', 'false'); say(btn, 'Microphone could not start.', 'err'); return; }
    active = { rec: rec, btn: btn };
  }
  d.addEventListener('click', function (e) {
    var b = e.target.closest && e.target.closest('[data-vf]'); if (!b) return;
    e.preventDefault(); listen(b);
  });

  /* ---------------------------------------------------------------- language pickers */
  function initLang() {
    Array.prototype.forEach.call(d.querySelectorAll('select[data-vf-lang]'), function (s) {
      if (!s.options.length) LANGS.forEach(function (l) { var o = d.createElement('option'); o.value = l[0]; o.textContent = l[1]; s.appendChild(o); });
      s.value = lang();
      s.addEventListener('change', function () { store.set('jz-voice-lang', s.value); Array.prototype.forEach.call(d.querySelectorAll('select[data-vf-lang]'), function (o) { o.value = s.value; }); });
    });
    if (!SR) Array.prototype.forEach.call(d.querySelectorAll('[data-vf]'), function (b) { b.title = 'Voice needs Chrome, Edge or Safari'; b.classList.add('vf-off'); });
  }
  if (d.readyState === 'loading') d.addEventListener('DOMContentLoaded', initLang); else initLang();

  window.jzVoiceField = { handlers: {}, lang: lang, firstNumber: firstNumber, generic: generic };
})();
