/* journzey.ai — Log Trade modal: multilingual voice console, live R:R / P&L calculator, strategy context,
 * screenshot paste/upload/link and psychology wiring. Nothing is saved until the member presses Save;
 * the server recalculates P&L and R from the submitted prices. */
(function () {
  'use strict';
  var d = document, form = d.querySelector('[data-lt]');
  if (!form) return;
  function $(s, c) { return (c || form).querySelector(s); }
  function $$(s, c) { return Array.prototype.slice.call((c || form).querySelectorAll(s)); }
  var specs = JSON.parse(form.dataset.specs || '{}'), strategies = JSON.parse(form.dataset.strategies || '[]'), cur = form.dataset.currency;
  var toast = function (m, t) { if (window.tmToast) window.tmToast(m, t); };
  var store = { get: function (k) { try { return localStorage.getItem(k); } catch (e) { return null; } }, set: function (k, v) { try { localStorage.setItem(k, v); } catch (e) {} } };

  /* ------------------------------------------------------------------ spoken numbers (EN / RU / PT / ZH) */
  var LEX = {
    en: { u: { zero: 0, oh: 0, one: 1, two: 2, three: 3, four: 4, five: 5, six: 6, seven: 7, eight: 8, nine: 9, ten: 10, eleven: 11, twelve: 12, thirteen: 13, fourteen: 14, fifteen: 15, sixteen: 16, seventeen: 17, eighteen: 18, nineteen: 19, twenty: 20, thirty: 30, forty: 40, fifty: 50, sixty: 60, seventy: 70, eighty: 80, ninety: 90 },
      h: ['hundred'], m: { thousand: 1e3, million: 1e6 }, p: ['point', 'dot', 'decimal'], j: ['and'] },
    ru: { u: { 'ноль': 0, 'нуль': 0, 'один': 1, 'одна': 1, 'одно': 1, 'два': 2, 'две': 2, 'три': 3, 'четыре': 4, 'пять': 5, 'шесть': 6, 'семь': 7, 'восемь': 8, 'девять': 9, 'десять': 10, 'одиннадцать': 11, 'двенадцать': 12, 'тринадцать': 13, 'четырнадцать': 14, 'пятнадцать': 15, 'шестнадцать': 16, 'семнадцать': 17, 'восемнадцать': 18, 'девятнадцать': 19, 'двадцать': 20, 'тридцать': 30, 'сорок': 40, 'пятьдесят': 50, 'шестьдесят': 60, 'семьдесят': 70, 'восемьдесят': 80, 'девяносто': 90, 'сто': 100, 'двести': 200, 'триста': 300, 'четыреста': 400, 'пятьсот': 500, 'шестьсот': 600, 'семьсот': 700, 'восемьсот': 800, 'девятьсот': 900 },
      h: [], m: { 'тысяча': 1e3, 'тысячи': 1e3, 'тысяч': 1e3, 'миллион': 1e6, 'миллиона': 1e6, 'миллионов': 1e6 }, p: ['точка', 'запятая', 'целых', 'целая'], j: ['и'] },
    pt: { u: { zero: 0, um: 1, uma: 1, dois: 2, duas: 2, 'três': 3, tres: 3, quatro: 4, cinco: 5, seis: 6, sete: 7, oito: 8, nove: 9, dez: 10, onze: 11, doze: 12, treze: 13, catorze: 14, quatorze: 14, quinze: 15, dezesseis: 16, dezasseis: 16, dezessete: 17, dezassete: 17, dezoito: 18, dezenove: 19, dezanove: 19, vinte: 20, trinta: 30, quarenta: 40, cinquenta: 50, sessenta: 60, setenta: 70, oitenta: 80, noventa: 90, cem: 100, cento: 100, duzentos: 200, duzentas: 200, trezentos: 300, trezentas: 300, quatrocentos: 400, quinhentos: 500, seiscentos: 600, setecentos: 700, oitocentos: 800, novecentos: 900 },
      h: [], m: { mil: 1e3, 'milhão': 1e6, milhao: 1e6, 'milhões': 1e6 }, p: ['vírgula', 'virgula', 'ponto'], j: ['e'] }
  };
  function wordsToDigits(text, lang) {
    var L = LEX[lang]; if (!L) return text;
    var toks = text.split(/\s+/), out = [], st = null;
    function begin() { st = st || { total: 0, cur: 0, dec: null, grp: 0 }; }
    function flush() {
      if (!st) return;
      var n = st.total + st.cur, s = String(n);
      if (st.dec !== null) { if (st.grp) st.dec += String(st.grp); if (st.dec !== '') s += '.' + st.dec; }
      out.push(s); st = null;
    }
    function peek(j) { return (toks[j] || '').toLowerCase().replace(/[“”"«»!?;:.,]+$/, ''); }
    function isNumWord(x) { return Object.prototype.hasOwnProperty.call(L.u, x) || /^\d/.test(x); }
    for (var i = 0; i < toks.length; i++) {
      var rawTok = toks[i], stopHere = /[,;!?]$|[^\d]\.$/.test(rawTok);   // punctuation after a word ends the number
      var w = rawTok.replace(/[“”"«»!?;:]+/g, '').replace(/[.,]+$/, ''), lw = w.toLowerCase(), used = true;
      if (/^\d+(?:[.,]\d+)?$/.test(lw)) {
        var val = lw.replace(',', '.');
        if (st && st.dec !== null) st.dec += val.replace('.', '');
        else { flush(); begin(); var parts = val.split('.'); st.cur = parseFloat(parts[0]); if (parts[1]) st.dec = parts[1]; }
      } else if (Object.prototype.hasOwnProperty.call(L.u, lw)) {
        var v = L.u[lw]; begin();
        if (st.dec !== null) { if (v < 10 && !st.grp) st.dec += String(v); else st.grp += v; } else st.cur += v;
      } else if (L.h.indexOf(lw) >= 0 && st && st.dec === null) { st.cur = (st.cur || 1) * 100; }
      else if (Object.prototype.hasOwnProperty.call(L.m, lw) && (!st || st.dec === null)) { begin(); st.total += (st.cur || 1) * L.m[lw]; st.cur = 0; }
      else if (L.p.indexOf(lw) >= 0 && isNumWord(peek(i + 1)) && (!st || st.dec === null)) { begin(); st.dec = ''; }
      else if (L.j.indexOf(lw) >= 0 && st && (Object.prototype.hasOwnProperty.call(L.u, peek(i + 1)) || Object.prototype.hasOwnProperty.call(L.m, peek(i + 1)))) { /* "and" inside a number */ }
      else { used = false; }
      if (!used) { flush(); out.push(w); }
      else if (stopHere) flush();
    }
    flush();
    return out.join(' ');
  }
  var ZH = { '零': 0, '〇': 0, '一': 1, '二': 2, '两': 2, '三': 3, '四': 4, '五': 5, '六': 6, '七': 7, '八': 8, '九': 9 }, ZHM = { '十': 10, '百': 100, '千': 1000, '万': 10000 };
  function zhInt(s) {
    if (/^\d+$/.test(s)) return parseInt(s, 10);
    var total = 0, sec = 0, num = 0;
    for (var i = 0; i < s.length; i++) {
      var c = s[i];
      if (ZH[c] !== undefined) num = ZH[c]; else if (/\d/.test(c)) num = num * 10 + +c;
      else if (c === '万') { total += (sec + num) * 10000; sec = 0; num = 0; }
      else if (ZHM[c]) { sec += (num || 1) * ZHM[c]; num = 0; }
    }
    return total + sec + num;
  }
  function zhToDigits(text) {
    return text.replace(/[零〇一二两三四五六七八九十百千万\d]+(?:点[零〇一二两三四五六七八九\d]+)?/g, function (run) {
      var p = run.split('点'), n = String(zhInt(p[0]));
      if (p[1]) n += '.' + p[1].split('').map(function (c) { return ZH[c] !== undefined ? ZH[c] : c; }).join('');
      return ' ' + n + ' ';
    });
  }
  function normalize(text, locale) {
    var lang = (locale || 'en-US').slice(0, 2), t = String(text);
    if (lang === 'zh') return zhToDigits(t).replace(/\s+/g, ' ').trim().toLowerCase();
    if (lang === 'pt' || lang === 'ru') t = t.replace(/(\d),(\d)/g, '$1.$2');
    else t = t.replace(/(\d),(?=\d{3}\b)/g, '$1');
    return wordsToDigits(t, lang).toLowerCase();
  }

  /* ------------------------------------------------------------------ field extraction */
  var KW = {
    entry: ['entry price', 'entry', 'entered at', 'enter at', 'цена входа', 'вход', 'входа', '入场价', '入场', '进场', '开仓价', '开仓', 'preço de entrada', 'entrada'],
    stop: ['stop loss', 'stop-loss', 'stoploss', 'стоп-лосс', 'стоп лосс', 'стоп', '止损', 'stop', 'sl'],
    exit: ['exit price', 'exit', 'closed at', 'close at', 'выход', 'закрыл по', 'закрыл', '出场价', '出场', '平仓', 'saída', 'saida', 'fechei em', 'fechei'],
    tp: ['take profit', 'take-profit', 'target', 'тейк-профит', 'тейк', 'цель', '止盈', 'alvo', 'tp'],
    lots: ['lot size', 'lots', 'lot', 'лотов', 'лота', 'лот', 'lotes', 'lote']
  };
  var ASSETS = [
    [/\b(gold|xau)\b|золот|黄金|\bouro\b/, 'XAUUSD'], [/\b(silver|xag)\b|серебр|白银|\bprata\b/, 'XAGUSD'],
    [/\beuro ?dollar\b|\beur ?usd\b|евро|欧元|\beuro\b/, 'EURUSD'], [/\b(pound|cable|gbp ?usd)\b|фунт|英镑|\blibra\b/, 'GBPUSD'],
    [/\b(yen|usd ?jpy)\b|иен|日元|\biene\b/, 'USDJPY'], [/\b(dow|us ?30)\b|доу|道琼斯/, 'US30'], [/\b(nasdaq|nas ?100)\b|насдак|纳斯达克|纳指/, 'NAS100'],
    [/\b(s&p|spx|us ?500)\b|标普/, 'US500'], [/\b(bitcoin|btc)\b|биткоин|比特币/, 'BTCUSDT'], [/\b(ethereum|eth)\b|эфир|以太坊/, 'ETHUSDT'],
    [/\b(oil|wti)\b|нефть|原油|petróleo/, 'USOIL'], [/\b(dax|ger ?40)\b/, 'GER40']
  ];
  var SIDES = [[/\b(buy|bought|long|buying)\b|купил|покупк|лонг|买入|做多|\bbuy\b|compr/, 'LONG'], [/\b(sell|sold|short|selling)\b|продал|продаж|шорт|卖出|做空|vend/, 'SHORT']];
  var SESS = [[/\bny ?pm\b|afternoon|после обеда|纽约下午|à tarde|tarde/, 'NY_PM'], [/\basia[n]?\b|азиат|азия|亚洲|亚盘|ásia|asiática/, 'ASIA'], [/\blondon\b|лондон|伦敦|londres/, 'LONDON'], [/new york|\bny\b|нью-?\s?йорк|纽约|nova york|nova iorque/, 'NEW_YORK']];
  function esc(s) { return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); }
  function numAfter(s, words) {
    for (var i = 0; i < words.length; i++) {
      var w = words[i], alpha = /^[a-zа-яёà-ÿ]/i.test(w);   // letters need a word boundary ("лот" inside "золото")
      var re = new RegExp((alpha ? '(?:^|[^a-zа-яёà-ÿ])' : '') + esc(w) + '[^0-9\\-]{0,14}?(-?\\d+(?:\\.\\d+)?)');
      var m = s.match(re); if (m) return parseFloat(m[1]);
    }
    return null;
  }
  function findAsset(s) {
    for (var i = 0; i < ASSETS.length; i++) if (ASSETS[i][0].test(s) && specs[ASSETS[i][1]]) return ASSETS[i][1];
    var syms = Object.keys(specs);
    for (var k = 0; k < syms.length; k++) { if (new RegExp('\\b' + syms[k].toLowerCase() + '\\b').test(s)) return syms[k]; }
    for (var j = 0; j < syms.length; j++) { var al = specs[syms[j]].a || []; for (var a = 0; a < al.length; a++) if (al[a].length > 2 && new RegExp('\\b' + esc(al[a].toLowerCase()) + '\\b').test(s)) return syms[j]; }
    return null;
  }
  function pick(list, s) { for (var i = 0; i < list.length; i++) if (list[i][0].test(s)) return list[i][1]; return null; }
  function findStrategy(s) { for (var i = 0; i < strategies.length; i++) if (s.indexOf(strategies[i].name.toLowerCase()) >= 0) return strategies[i]; return null; }
  function extract(raw, locale) {
    var s = normalize(raw, locale), r = {};
    r.symbol = findAsset(s); r.side = pick(SIDES, s); r.session = pick(SESS, s);
    r.entry = numAfter(s, KW.entry); r.stop = numAfter(s, KW.stop); r.exit = numAfter(s, KW.exit); r.tp = numAfter(s, KW.tp);
    // "0.5 lots" / "0.2手" (number first) vs "lot size 0.5" / "лот 0.2" / "lote 0.3" (keyword first)
    var before = s.match(/(\d+(?:\.\d+)?)\s*(?:lots\b|手)/), after = numAfter(s, KW.lots);
    r.lots = (locale || '').slice(0, 2) === 'zh' || (before && after === null) ? (before ? parseFloat(before[1]) : after) : (after !== null ? after : (before ? parseFloat(before[1]) : null));
    if (r.entry === null) { var at = s.match(/(?:^|[^a-z])at\s+(\d+(?:\.\d+)?)/); if (at) r.entry = parseFloat(at[1]); }
    var st = findStrategy(s); r.strategy = st ? st.id : null;
    return { norm: s, fields: r };
  }

  /* ------------------------------------------------------------------ speech output */
  var SAY = {
    en: { symbol: 'Asset set to', side: 'Direction set to', entry: 'Entry price set to', exit: 'Exit price set to', stop: 'Stop loss set to', tp: 'Take profit set to', lots: 'Lot size set to', session: 'Session set to', strategy: 'Strategy set to', LONG: 'long', SHORT: 'short' },
    ru: { symbol: 'Актив', side: 'Направление', entry: 'Цена входа', exit: 'Цена выхода', stop: 'Стоп-лосс', tp: 'Тейк-профит', lots: 'Объём лота', session: 'Сессия', strategy: 'Стратегия', LONG: 'покупка', SHORT: 'продажа' },
    zh: { symbol: '品种', side: '方向', entry: '入场价', exit: '出场价', stop: '止损', tp: '止盈', lots: '手数', session: '时段', strategy: '策略', LONG: '做多', SHORT: '做空' },
    pt: { symbol: 'Ativo', side: 'Direção', entry: 'Preço de entrada', exit: 'Preço de saída', stop: 'Stop loss', tp: 'Alvo', lots: 'Lote', session: 'Sessão', strategy: 'Estratégia', LONG: 'compra', SHORT: 'venda' }
  };
  var LABEL = { symbol: 'Asset', side: 'Direction', entry: 'Entry', exit: 'Exit', stop: 'Stop loss', tp: 'Take profit', lots: 'Lot size', session: 'Session', strategy: 'Strategy' };
  var locale = d.documentElement.dataset.voiceLang || 'en-US', audioOn = store.get('jz-lt-audio') !== 'off';
  function speak(lines) {
    if (!audioOn || !window.speechSynthesis || !lines.length) return;
    try { window.speechSynthesis.cancel(); var u = new SpeechSynthesisUtterance(lines.join('. ')); u.lang = locale; u.rate = 1.05; window.speechSynthesis.speak(u); } catch (e) {}
  }

  /* ------------------------------------------------------------------ apply values */
  var el = {
    symbol: $('[data-lt-symbol]'), entry: $('#t-entry'), exit: $('#t-exit'), stop: $('#t-stop'), tp: $('#t-tp'), lots: $('#t-lots'),
    session: $('[data-lt-session]'), strategy: $('[data-lt-strategy]'), setup: $('[data-lt-setup]'), rate: $('#t-rate'), pnl: $('#t-pnl')
  };
  var changes = $('[data-lt-changes]');
  function flash(x) { if (!x) return; x.classList.add('filled'); setTimeout(function () { x.classList.remove('filled'); }, 1800); }
  function setSide(v) { var r = $('input[name=side][value="' + v + '"]'); if (r) { r.checked = true; r.dispatchEvent(new Event('change', { bubbles: true })); flash(r.parentNode); } }
  function setSession(v) { el.session.value = v || ''; $$('[data-lt-session-btn]').forEach(function (b) { var on = b.dataset.ltSessionBtn === v; b.classList.toggle('on', on); b.setAttribute('aria-pressed', String(on)); }); }
  function apply(fields, only) {
    var said = [], lang = locale.slice(0, 2), S = SAY[lang] || SAY.en;
    changes.textContent = '';
    Object.keys(fields).forEach(function (k) {
      var v = fields[k]; if (v === null || v === undefined || (only && only !== k)) return;
      var shown = v;
      if (k === 'side') { setSide(v); shown = v === 'LONG' ? 'LONG' : 'SHORT'; }
      else if (k === 'session') { setSession(v); shown = ($('[data-lt-session-btn="' + v + '"]') || {}).textContent || v; flash($('[data-lt-session-btn="' + v + '"]')); }
      else if (k === 'strategy') { selectStrategy(String(v)); shown = (strategies.filter(function (s) { return s.id === v; })[0] || {}).name || v; }
      else { el[k].value = v; el[k].dispatchEvent(new Event('input', { bubbles: true })); flash(el[k]); }
      var li = d.createElement('li'); li.textContent = '✓ ' + LABEL[k] + ': ' + shown; changes.appendChild(li);
      said.push(S[k] + ' ' + (k === 'side' ? S[v] : shown));
    });
    speak(said);
    calc();
    return said.length;
  }

  /* ------------------------------------------------------------------ voice console */
  var SR = window.SpeechRecognition || window.webkitSpeechRecognition, rec = null, heard = $('[data-lt-heard]'), listenBadge = $('[data-lt-listen]');
  function markLocale() { $$('[data-lt-locale]').forEach(function (b) { b.classList.toggle('on', b.dataset.ltLocale === locale); b.setAttribute('aria-pressed', String(b.dataset.ltLocale === locale)); }); }
  $$('[data-lt-locale]').forEach(function (b) { b.addEventListener('click', function () { locale = b.dataset.ltLocale; markLocale(); }); });
  markLocale();
  var audioB = $('[data-lt-audio]');
  function markAudio() { audioB.setAttribute('aria-pressed', String(audioOn)); $('[data-lt-audio-on]').hidden = !audioOn; $('[data-lt-audio-off]').hidden = audioOn; }
  audioB.addEventListener('click', function () { audioOn = !audioOn; store.set('jz-lt-audio', audioOn ? 'on' : 'off'); if (!audioOn && window.speechSynthesis) window.speechSynthesis.cancel(); markAudio(); });
  markAudio();
  function handleSpeech(text, field) {
    heard.textContent = text;
    if (field === 'all') {
      var r = extract(text, locale);
      if (!apply(r.fields)) toast('Nothing recognised — try “bought gold at 2862.5, stop 2855, exit 2872, 0.5 lots”.', 'error');
      return;
    }
    var x = extract(text, locale), f = {};
    if (['entry', 'exit', 'stop', 'tp', 'lots'].indexOf(field) >= 0) {
      var n = x.fields[field]; if (n === null) { var m = x.norm.match(/-?\d+(?:\.\d+)?/); n = m ? parseFloat(m[0]) : null; }
      f[field] = n;
    } else if (field === 'symbol') f.symbol = x.fields.symbol || (text.trim().split(/\s+/)[0] || '').toUpperCase();
    else f[field] = x.fields[field];
    if (f[field] === null || f[field] === undefined || f[field] === '') { toast('Not recognised — please try again.', 'error'); return; }
    apply(f, field);
  }
  function stopRec() { if (rec) { try { rec.stop(); } catch (e) {} } }
  function listen(field, btn) {
    if (!SR) { toast('Voice needs Chrome, Edge or Safari — you can type or use the “Try it” examples.', 'error'); return; }
    if (rec) { stopRec(); return; }
    rec = new SR(); rec.lang = locale; rec.interimResults = true; rec.continuous = false;
    var finalText = '';
    btn.classList.add('rec'); btn.setAttribute('aria-pressed', 'true'); listenBadge.hidden = false;
    rec.onresult = function (ev) { var t = ''; for (var i = 0; i < ev.results.length; i++) { t += ev.results[i][0].transcript; if (ev.results[i].isFinal) finalText = t; } heard.textContent = t; };
    rec.onerror = function (ev) { if (ev.error === 'not-allowed' || ev.error === 'service-not-allowed') toast('Microphone access is blocked — allow it in your browser settings.', 'error'); };
    rec.onend = function () { rec = null; btn.classList.remove('rec'); btn.setAttribute('aria-pressed', 'false'); listenBadge.hidden = true; if (finalText.trim()) handleSpeech(finalText.trim(), field); };
    try { rec.start(); } catch (e) { rec = null; btn.classList.remove('rec'); listenBadge.hidden = true; }
  }
  if (!SR) $('[data-lt-unsupported]').hidden = false;
  $$('[data-lt-mic]').forEach(function (b) { b.addEventListener('click', function () { listen(b.dataset.ltMic, b); }); });
  $$('[data-lt-sim]').forEach(function (b) { b.addEventListener('click', function () { locale = b.dataset.ltSim; markLocale(); handleSpeech(b.dataset.text, 'all'); }); });

  /* ------------------------------------------------------------------ asset / direction */
  function resolveSym(v) {
    var u = String(v || '').trim().toUpperCase().replace(/[\s\/_-]/g, '');
    if (specs[u]) return u;
    var hit = findAsset(String(v || '').toLowerCase()); return hit;
  }
  el.symbol.addEventListener('input', function () { var p = el.symbol.selectionStart; el.symbol.value = el.symbol.value.toUpperCase(); try { el.symbol.setSelectionRange(p, p); } catch (e) {} calc(); });
  el.symbol.addEventListener('change', function () { var r = resolveSym(el.symbol.value); if (r) el.symbol.value = r; calc(); });
  $$('[data-lt-quick]').forEach(function (b) { b.addEventListener('click', function () { el.symbol.value = b.dataset.ltQuick; flash(el.symbol); calc(); }); });
  var badge = $('[data-lt-badge]');
  $$('input[name=side]').forEach(function (r) { r.addEventListener('change', function () {
    var long = r.value === 'LONG' && r.checked; if (!r.checked) return;
    badge.classList.toggle('is-long', long); badge.classList.toggle('is-short', !long);
    badge.innerHTML = long ? '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 17 17 7M9 7h8v8"/></svg>' : '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 7l10 10M17 9v8H9"/></svg>';
    calc();
  }); });

  /* ------------------------------------------------------------------ live calculator */
  var out = { risk: $('[data-lt-o=risk]'), rr: $('[data-lt-o=rr]'), pnl: $('[data-lt-o=pnl]'), pnlLabel: $('[data-lt-o=pnl-label]') };
  var rateWrap = $('[data-rate-wrap]'), gold = $('[data-lt-gold]'), ovBtn = $('[data-lt-override]'), ovBox = $('[data-lt-override-box]');
  function sym(c) { return { USD: '$', EUR: '€', GBP: '£', JPY: '¥', INR: '₹', AUD: 'A$', CAD: 'C$', SGD: 'S$' }[c] || (c + ' '); }
  function money(v, c, sign) { var s = sym(c) + Math.abs(v).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); return (v < 0 ? '−' : (sign && v > 0 ? '+' : '')) + s; }
  function num(x) { var v = parseFloat(x.value); return isNaN(v) ? null : v; }
  function calc() {
    var s = resolveSym(el.symbol.value), sp = specs[s];
    gold.hidden = !(s && /^XAU/.test(s));
    var side = ($('input[name=side]:checked') || {}).value === 'SHORT' ? -1 : 1;
    var entry = num(el.entry), exit = num(el.exit), stop = num(el.stop), tp = num(el.tp), lots = num(el.lots), conv = null;
    if (sp) {
      if (sp.q === cur) conv = 1;
      else if (sp.b === cur && (exit || entry)) conv = 1 / (exit || entry);
      else if (el.rate && num(el.rate) > 0) conv = num(el.rate);
      var needRate = sp.q !== cur && sp.b !== cur;
      rateWrap.hidden = !needRate && !$('.f-err', rateWrap);
      if (needRate) $('[data-rate-pair]', rateWrap).textContent = '(1 ' + sp.q + ' = ? ' + cur + ')';
    }
    var ccy = conv === null && sp ? sp.q : cur, k = conv === null ? 1 : conv;
    var riskDist = entry !== null && stop !== null ? Math.abs(entry - stop) : null;
    if (sp && riskDist && lots) { out.risk.textContent = money(-riskDist * lots * sp.c * k, ccy); } else out.risk.textContent = '—';
    var target = exit !== null ? exit : tp, planned = exit === null && tp !== null;
    if (riskDist && target !== null && entry !== null) {
      var r = (target - entry) * side / riskDist;
      out.rr.textContent = '1 : ' + Math.abs(r).toFixed(2) + ' R' + (r < 0 ? ' (loss −' + Math.abs(r).toFixed(2) + 'R)' : '');
    } else out.rr.textContent = '—';
    var overriding = ovBtn.getAttribute('aria-pressed') === 'true' && el.pnl.value !== '';
    var pnl = null;
    if (overriding) { pnl = parseFloat(el.pnl.value); out.pnlLabel.textContent = 'Manual net P&L'; ccy = cur; }
    else if (sp && target !== null && entry !== null && lots) { pnl = (target - entry) * side * lots * sp.c * k; out.pnlLabel.textContent = planned ? 'Planned P&L (take profit)' : 'Calculated net P&L'; }
    else out.pnlLabel.textContent = 'Calculated net P&L';
    out.pnl.textContent = pnl === null || isNaN(pnl) ? '—' : money(pnl, ccy, true) + (conv === null && sp && !overriding ? ' (≈ ' + sp.q + ')' : '');
    out.pnl.classList.toggle('up', pnl !== null && pnl >= 0); out.pnl.classList.toggle('down', pnl !== null && pnl < 0);
  }
  [el.entry, el.exit, el.stop, el.tp, el.lots, el.rate, el.pnl].forEach(function (x) { if (x) x.addEventListener('input', calc); });
  ovBtn.addEventListener('click', function () {
    var on = ovBtn.getAttribute('aria-pressed') !== 'true'; ovBtn.setAttribute('aria-pressed', String(on));
    ovBox.hidden = !on; el.pnl.disabled = !on; if (on) el.pnl.focus(); calc();
  });

  /* ------------------------------------------------------------------ session & strategy */
  $$('[data-lt-session-btn]').forEach(function (b) { b.addEventListener('click', function () { setSession(el.session.value === b.dataset.ltSessionBtn ? '' : b.dataset.ltSessionBtn); }); });
  var ctx = $('[data-lt-context]'), vp = $('[data-lt-vp]'), cats = $('[data-lt-catalysts]'), rules = $('[data-lt-rules]'), vpLevel = $('[data-lt-vp-level]'), vpRef = $('[data-lt-vp-ref]');
  function chip(text, onClick, cls) { var b = d.createElement('button'); b.type = 'button'; b.className = 'lt-chip' + (cls ? ' ' + cls : ''); b.textContent = text; if (onClick) b.addEventListener('click', onClick); return b; }
  function selectStrategy(id) {
    el.strategy.value = id || '';
    $$('[data-lt-strat]').forEach(function (b) { b.classList.toggle('on', b.dataset.ltStrat === String(id)); });
    var s = strategies.filter(function (x) { return String(x.id) === String(id); })[0];
    cats.textContent = ''; rules.textContent = '';
    if (!s) { ctx.hidden = true; return; }
    ctx.hidden = false;
    var isVP = s.style === 'VOLUME_PROFILE' || /volume profile|\bpoc\b|\bvah\b|\bval\b/i.test(s.name);
    vp.hidden = !isVP;
    var src = s.setups.length ? s.setups : s.rules.filter(function (r) { return r.length <= 48; }).slice(0, 5);
    if (s.rr) cats.appendChild(chip('Target R:R 1:' + (+s.rr).toFixed(1), null, 'lt-rr-badge'));
    src.forEach(function (c) { cats.appendChild(chip(c, function () { el.setup.value = c; flash(el.setup); })); });
    if (s.rules.length) { var ul = d.createElement('ul'); s.rules.slice(0, 3).forEach(function (r) { var li = d.createElement('li'); li.textContent = r; ul.appendChild(li); }); var h = d.createElement('p'); h.className = 'muted small'; h.textContent = 'Your rules for this strategy:'; rules.appendChild(h); rules.appendChild(ul); }
    flash($('[data-lt-strat="' + id + '"]'));
  }
  $$('[data-lt-strat]').forEach(function (b) { b.addEventListener('click', function () { selectStrategy(el.strategy.value === b.dataset.ltStrat ? '' : b.dataset.ltStrat); }); });
  function vpCompose() { if (vpLevel.value) { el.setup.value = vpLevel.value + ' · ' + vpRef.value; flash(el.setup); } }
  vpLevel.addEventListener('change', vpCompose); vpRef.addEventListener('change', vpCompose);
  if (el.strategy.value) selectStrategy(el.strategy.value);

  /* ------------------------------------------------------------------ screenshot: paste / upload / link */
  var file = $('[data-lt-file]'), urlIn = $('[data-lt-url]'), prev = $('[data-lt-preview]'), prevImg = $('[data-lt-preview-img]'), msg = $('[data-shot-msg]'), readB = $('[data-shot-read]');
  function tab(name) {
    $$('[data-lt-shot-tab]').forEach(function (b) { var on = b.dataset.ltShotTab === name; b.classList.toggle('on', on); b.setAttribute('aria-selected', String(on)); });
    $$('[data-lt-shot-pane]').forEach(function (p) { p.hidden = p.dataset.ltShotPane !== name; });
  }
  $$('[data-lt-shot-tab]').forEach(function (b) { b.addEventListener('click', function () { tab(b.dataset.ltShotTab); }); });
  function showFile(f, how) {
    if (!/^image\/(png|jpeg|webp)$/.test(f.type)) { msg.textContent = 'Only PNG, JPG or WEBP images.'; return false; }
    if (f.size > 10 * 1024 * 1024) { msg.textContent = 'Screenshots must be 10 MB or smaller.'; return false; }
    var r = new FileReader(); r.onload = function () { prevImg.src = r.result; prev.hidden = false; }; r.readAsDataURL(f);
    msg.textContent = '✓ ' + how + ' — it will be saved with the trade.'; if (readB) readB.disabled = false;
    return true;
  }
  file.addEventListener('change', function () { if (file.files[0] && !showFile(file.files[0], 'Image selected')) file.value = ''; });
  d.addEventListener('paste', function (e) {
    var items = (e.clipboardData || {}).items || [];
    for (var i = 0; i < items.length; i++) {
      if (items[i].kind === 'file' && /^image\//.test(items[i].type)) {
        var blob = items[i].getAsFile(), ext = blob.type.split('/')[1].replace('jpeg', 'jpg');
        var f = new File([blob], 'pasted-chart.' + ext, { type: blob.type });
        if (!showFile(f, 'Pasted from clipboard')) return;
        try { var dt = new DataTransfer(); dt.items.add(f); file.files = dt.files; } catch (err) { msg.textContent = 'This browser cannot attach pasted images — use Upload instead.'; }
        e.preventDefault(); return;
      }
    }
  });
  urlIn.addEventListener('input', function () {
    var v = urlIn.value.trim();
    if (/^https:\/\/\S+$/i.test(v)) { prevImg.src = v; prev.hidden = false; msg.textContent = 'Image link will be saved with the trade.'; }
    else if (!v) { prev.hidden = true; }
  });
  prevImg.addEventListener('error', function () { if (urlIn.value) msg.textContent = 'The link is saved, but it does not look like a direct image — it will open as a link.'; });
  $('[data-lt-shot-clear]').addEventListener('click', function () { file.value = ''; urlIn.value = ''; prev.hidden = true; prevImg.removeAttribute('src'); msg.textContent = ''; if (readB) readB.disabled = true; });
  if (urlIn.value) { tab('url'); urlIn.dispatchEvent(new Event('input')); }
  if (readB) readB.addEventListener('click', function () {
    var f = file.files[0]; if (!f) return;
    var fd = new FormData(); fd.append('screenshot', f); readB.disabled = true; readB.classList.add('busy'); msg.textContent = 'Reading the chart…';
    window.tmPost(readB.dataset.shotRead, fd).then(function (r) {
      readB.disabled = false; readB.classList.remove('busy');
      if (!r || !r.ok) { msg.textContent = (r && r.error) || 'Could not read the chart — please type the values.'; return; }
      var n = apply({ symbol: r.fields.symbol, side: r.fields.side, entry: r.fields.entry, stop: r.fields.stop, tp: r.fields.tp, exit: r.fields.exit });
      msg.textContent = n ? '✓ Filled from the screenshot — check every value before saving.' + (r.note ? ' ' + r.note : '') : 'No position tool or prices found — please type the values.';
    });
  });

  /* ------------------------------------------------------------------ psychology & discipline */
  var emo = $('[data-lt-emotion]'), mis = $('[data-lt-mistake]'), rulesBox = $('[data-lt-rules-box]');
  emo.addEventListener('change', function () {
    var map = { FOMO: 'FOMO_ENTRY', REVENGE: 'REVENGE_TRADE' };
    if (map[emo.value]) { mis.value = map[emo.value]; mis.dispatchEvent(new Event('change')); flash(mis); }
  });
  mis.addEventListener('change', function () { if (mis.value !== 'NONE') { rulesBox.checked = false; flash(rulesBox.parentNode); } });

  calc();
})();
