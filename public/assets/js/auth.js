/* Sign-up timezone detection and onboarding stepper */
(function () {
  'use strict';
  var d = document;
  d.documentElement.classList.replace('no-js', 'js');
  var tz = ''; try { tz = Intl.DateTimeFormat().resolvedOptions().timeZone || ''; } catch (e) {}
  d.querySelectorAll('[data-tz]').forEach(function (i) { if (tz) i.value = tz; });
  var sel = d.getElementById('f-timezone');
  if (sel && tz && sel.dataset.touched !== '1') { var opt = sel.querySelector('option[value="' + tz + '"]'); if (opt && !sel.closest('form').querySelector('.has-error')) sel.value = tz; }
  // Field validation for sign-in, sign-up and password reset (the server validates everything again).
  var EMAIL = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  var msgFor = function (f) {
    var v = f.value.trim();
    if (f.required && v === '' && f.type !== 'checkbox') return f.type === 'email' ? 'Enter your email address.' : f.type === 'password' ? 'Enter your password.' : 'This field is required.';
    if (f.type === 'email' && v !== '' && (!EMAIL.test(v) || !f.checkValidity())) return 'Enter a valid email address.';
    return f.checkValidity() ? '' : f.validationMessage;
  };
  d.querySelectorAll('form[data-validate]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      var firstBad = null;
      form.querySelectorAll('input, select, textarea').forEach(function (f) {
        if (f.type === 'hidden' || f.closest('.hp')) return;
        var wrap = f.closest('.field'); if (!wrap) return;
        var old = wrap.querySelector('.field-error'); if (old) old.remove();
        var m = msgFor(f), id = 'err-' + f.name;
        if (m) {
          wrap.classList.add('has-error'); f.setAttribute('aria-invalid', 'true');
          var p = d.createElement('p'); p.className = 'field-error js'; p.id = id; p.textContent = m; wrap.appendChild(p);
          f.setAttribute('aria-describedby', id);
          firstBad = firstBad || f;
        } else { wrap.classList.remove('has-error'); f.removeAttribute('aria-invalid'); }
      });
      if (firstBad) { e.preventDefault(); firstBad.focus(); }
    });
  });
  // After a server-side validation error, move focus to the first invalid field.
  var bad = d.querySelector('form .field.has-error input, form .field.has-error select, form .field.has-error textarea');
  if (bad && !d.querySelector('[data-stepper]')) bad.focus();
  var st = d.querySelector('[data-stepper]'); if (!st) return;
  var steps = st.querySelectorAll('.ob-step'), bar = st.querySelectorAll('.steps-bar li'), i = 0;
  var prev = st.querySelector('[data-prev]'), next = st.querySelector('[data-next]'), fin = st.querySelector('[data-finish]');
  var errStep = Array.prototype.findIndex.call(steps, function (s) { return s.querySelector('.has-error'); });
  if (errStep >= 0) i = errStep;
  function show() {
    steps.forEach(function (s, k) { s.classList.toggle('on', k === i); });
    bar.forEach(function (b, k) { b.classList.toggle('on', k <= i); });
    prev.style.visibility = i ? 'visible' : 'hidden';
    next.hidden = i === steps.length - 1; fin.hidden = i !== steps.length - 1;
    var h = steps[i].querySelector('h2'); if (h) { h.tabIndex = -1; h.focus({ preventScroll: true }); }
  }
  next.addEventListener('click', function () {
    var bad = Array.prototype.find.call(steps[i].querySelectorAll('input,select'), function (f) { return !f.checkValidity(); });
    if (bad) { bad.reportValidity(); return; }
    i = Math.min(steps.length - 1, i + 1); show();
  });
  prev.addEventListener('click', function () { i = Math.max(0, i - 1); show(); });
  show();
})();
