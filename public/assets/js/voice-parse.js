/* journzey.ai — spoken trade parser shared by the Log Trade modal and the Home Hub voice log.
 * Turns a sentence in English, Russian, Chinese or Portuguese (numbers in words or digits) into trade fields.
 * Usage: var P = jzVoiceParser(specs, strategies); P.extract(text, 'ru-RU') → { norm, fields }. */
(function () {
  'use strict';
  window.jzVoiceParser = function (specs, strategies) {
    var locale = 'en-US';
    strategies = strategies || [];
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
      if (lang === 'zh') return zhToDigits(t.replace(/百分之([零〇一二两三四五六七八九十点\d.]+)/g, '$1%')).replace(/\s+%/g, '%').replace(/\s+/g, ' ').trim().toLowerCase();
      if (lang === 'en') t = t.replace(/\b(a )?half (a )?lots?\b/gi, '0.5 lot').replace(/\ba quarter (of )?(a )?lots?\b/gi, '0.25 lot');
      if (lang === 'pt') t = t.replace(/\bpor ?cento\b/gi, ' % ');
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
      var before = s.match(/(\d+(?:\.\d+)?)\s*(?:lots?\b|手)/), after = numAfter(s, KW.lots);
      r.lots = (locale || '').slice(0, 2) === 'zh' || (before && after === null) ? (before ? parseFloat(before[1]) : after) : (after !== null ? after : (before ? parseFloat(before[1]) : null));
      if (r.entry === null) { var at = s.match(/(?:^|[^a-z])at\s+(\d+(?:\.\d+)?)/); if (at) r.entry = parseFloat(at[1]); }
      var st = findStrategy(s); r.strategy = st ? st.id : null;
      return { norm: s, fields: r };
    }

    function extractWith(raw, loc) { locale = loc || 'en-US'; return extract(raw, locale); }
    /** Canonical English command for the server quick-trade parser (only values that were actually spoken). */
    function toCommand(f) {
      var p = [];
      if (f.side) p.push(f.side === 'LONG' ? 'buy' : 'sell');
      if (f.symbol) p.push(f.symbol);
      if (f.entry !== null) p.push('entry ' + f.entry);
      if (f.stop !== null) p.push('stop loss ' + f.stop);
      if (f.tp !== null) p.push('take profit ' + f.tp);
      if (f.exit !== null) p.push('exit ' + f.exit);
      if (f.lots !== null) p.push(f.lots + ' lots');
      return p.join(' ');
    }
    /** Localised field words → English keywords, so keyword-based forms (calculator) work in every language. */
    var EN_KW = [
      [/цена входа|входа|вход|入场价|入场|进场|开仓价|开仓|preço de entrada|entrada/g, ' entry '],
      [/тейк-профит|тейк профит|тейк|止盈|alvo|цель|目标/g, ' target '], [/стоп-лосс|стоп лосс|стоп|止损/g, ' stop '],
      [/выход|закрыл по|出场价|出场|平仓|saída|saida/g, ' exit '], [/лотов|лота|лот|lotes|lote|手/g, ' lots '],
      [/риск|风险|risco/g, ' risk '], [/процентов|процента|процент|por cento|porcento|%/g, ' percent ']
    ];
    function toEnglish(norm) { var t = ' ' + norm + ' '; EN_KW.forEach(function (r) { t = t.replace(r[0], r[1]); }); return t.replace(/\s+/g, ' ').trim(); }
    /** Whole-utterance voice commands in every language: 'stop' | 'skip' | 'back' | null. */
    function command(raw) {
      var t = String(raw).toLowerCase().replace(/[.!?。！？]+$/, '').trim();
      if (/^(stop|cancel|finish|done|that's all|that is all|стоп|хватит|отмена|готово|停止|取消|结束|完成|parar|pare|cancelar|pronto|terminar)( listening| voice| voice log)?$/.test(t)) return 'stop';
      if (/^(skip|next|none|nothing|no|not yet|open|пропустить|пропусти|дальше|далее|нет|pular|pule|próximo|proximo|nenhum|não|nao)(?=\s|$)/.test(t) || /^(跳过|下一个|没有|无)/.test(t)) return 'skip';
      if (/^(back|previous|go back|назад|вернись|voltar|volta|anterior)(?=\s|$)/.test(t) || /^(返回|上一个)/.test(t)) return 'back';
      return null;
    }
    function side(norm) { return pick(SIDES, norm); }
    return { normalize: normalize, extract: extractWith, findAsset: findAsset, toCommand: toCommand, toEnglish: toEnglish, command: command, side: side };
  };
})();
