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
        h: [], m: { mil: 1e3, 'milhão': 1e6, milhao: 1e6, 'milhões': 1e6 }, p: ['vírgula', 'virgula', 'ponto'], j: ['e'] },
      hi: { u: { 'शून्य': 0, 'एक': 1, 'दो': 2, 'तीन': 3, 'चार': 4, 'पांच': 5, 'पाँच': 5, 'छह': 6, 'छः': 6, 'छे': 6, 'सात': 7, 'आठ': 8, 'नौ': 9, 'दस': 10, 'ग्यारह': 11, 'बारह': 12, 'तेरह': 13, 'चौदह': 14, 'पंद्रह': 15, 'पन्द्रह': 15, 'सोलह': 16, 'सत्रह': 17, 'अठारह': 18, 'उन्नीस': 19, 'बीस': 20, 'इक्कीस': 21, 'बाईस': 22, 'तेईस': 23, 'चौबीस': 24, 'पच्चीस': 25, 'छब्बीस': 26, 'सत्ताईस': 27, 'अट्ठाईस': 28, 'उनतीस': 29, 'तीस': 30, 'इकतीस': 31, 'इकत्तीस': 31, 'बत्तीस': 32, 'तैंतीस': 33, 'चौंतीस': 34, 'पैंतीस': 35, 'छत्तीस': 36, 'सैंतीस': 37, 'अड़तीस': 38, 'उनतालीस': 39, 'चालीस': 40, 'इकतालीस': 41, 'बयालीस': 42, 'तैंतालीस': 43, 'चवालीस': 44, 'पैंतालीस': 45, 'छियालीस': 46, 'सैंतालीस': 47, 'अड़तालीस': 48, 'उनचास': 49, 'पचास': 50, 'इक्यावन': 51, 'बावन': 52, 'तिरेपन': 53, 'चौवन': 54, 'पचपन': 55, 'छप्पन': 56, 'सत्तावन': 57, 'अट्ठावन': 58, 'उनसठ': 59, 'साठ': 60, 'इकसठ': 61, 'बासठ': 62, 'तिरेसठ': 63, 'चौंसठ': 64, 'पैंसठ': 65, 'छियासठ': 66, 'सड़सठ': 67, 'अड़सठ': 68, 'उनहत्तर': 69, 'सत्तर': 70, 'इकहत्तर': 71, 'बहत्तर': 72, 'तिहत्तर': 73, 'चौहत्तर': 74, 'पचहत्तर': 75, 'छिहत्तर': 76, 'सतहत्तर': 77, 'अठहत्तर': 78, 'उन्यासी': 79, 'उनासी': 79, 'अस्सी': 80, 'इक्यासी': 81, 'बयासी': 82, 'तिरासी': 83, 'चौरासी': 84, 'पचासी': 85, 'छियासी': 86, 'सत्तासी': 87, 'अट्ठासी': 88, 'नवासी': 89, 'नब्बे': 90, 'इक्यानवे': 91, 'बानवे': 92, 'तिरानवे': 93, 'चौरानवे': 94, 'पचानवे': 95, 'छियानवे': 96, 'सत्तानवे': 97, 'अट्ठानवे': 98, 'निन्यानवे': 99 },
        h: ['सौ'], m: { 'हज़ार': 1e3, 'हजार': 1e3, 'लाख': 1e5, 'करोड़': 1e7 }, p: ['दशमलव', 'पॉइंट', 'प्वाइंट', 'point'], j: ['और'] },
      ar: { u: { 'صفر': 0, 'واحد': 1, 'واحدة': 1, 'أحد': 1, 'احد': 1, 'اثنان': 2, 'اثنين': 2, 'اثنتان': 2, 'اثنا': 2, 'إثنان': 2, 'ثلاثة': 3, 'ثلاث': 3, 'أربعة': 4, 'اربعة': 4, 'أربع': 4, 'اربع': 4, 'خمسة': 5, 'خمس': 5, 'ستة': 6, 'ست': 6, 'سبعة': 7, 'سبع': 7, 'ثمانية': 8, 'ثماني': 8, 'ثمان': 8, 'تسعة': 9, 'تسع': 9, 'عشرة': 10, 'عشر': 10, 'عشرون': 20, 'عشرين': 20, 'ثلاثون': 30, 'ثلاثين': 30, 'أربعون': 40, 'أربعين': 40, 'اربعين': 40, 'خمسون': 50, 'خمسين': 50, 'ستون': 60, 'ستين': 60, 'سبعون': 70, 'سبعين': 70, 'ثمانون': 80, 'ثمانين': 80, 'تسعون': 90, 'تسعين': 90, 'مئتان': 200, 'مئتين': 200, 'مائتان': 200, 'مائتين': 200, 'ثلاثمائة': 300, 'ثلاثمئة': 300, 'أربعمائة': 400, 'أربعمئة': 400, 'اربعمائة': 400, 'خمسمائة': 500, 'خمسمئة': 500, 'ستمائة': 600, 'ستمئة': 600, 'سبعمائة': 700, 'سبعمئة': 700, 'ثمانمائة': 800, 'ثمانمئة': 800, 'تسعمائة': 900, 'تسعمئة': 900 },
        h: ['مائة', 'مئة'], m: { 'ألف': 1e3, 'الف': 1e3, 'آلاف': 1e3, 'الاف': 1e3, 'ألفا': 1e3, 'الفا': 1e3, 'ألفان': 2e3, 'ألفين': 2e3, 'الفين': 2e3, 'مليون': 1e6 }, p: ['فاصلة', 'نقطة', 'فاصل'], j: [] }
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
        var rawTok = toks[i], stopHere = /[,;!?،।]$|[^\d]\.$/.test(rawTok);   // punctuation after a word ends the number
        var w = rawTok.replace(/[“”"«»!?;:؟]+/g, '').replace(/[.,،।]+$/, ''), lw = w.toLowerCase(), used = true;
        // "…point zero eight two, two lots": a digit right before a lot word starts a new number
        if (st && st.dec !== null && st.dec.length >= 3 && /^(lots?|лот|лота|лотов|lote|lotes|लॉट|लोट|لوت|عقد|عقود)$/.test(peek(i + 1)) && !isNumWord(peek(i + 2)) && !(lang === 'ar' && peek(i + 2)[0] === 'و' && isNumWord(peek(i + 2).slice(1)))) flush();
        // Arabic joins "and" to the next word: "وستون" = "و" + "ستون" (and sixty)
        if (lang === 'ar' && lw.length > 2 && lw[0] === 'و' && (isNumWord(lw.slice(1)) || L.h.indexOf(lw.slice(1)) >= 0 || Object.prototype.hasOwnProperty.call(L.m, lw.slice(1)))) { lw = lw.slice(1); w = w.slice(1); }
        if (/^\d+(?:[.,]\d+)?$/.test(lw)) {
          var val = lw.replace(',', '.');
          if (st && st.dec !== null) st.dec += val.replace('.', '');
          else if (st && st.total > 0 && !st.cur && st.dec === null && parseFloat(val) < st.total) { var pp = val.split('.'); st.cur = parseFloat(pp[0]); st.dig = true; if (pp[1]) st.dec = pp[1]; }   // "2 हज़ार 860"
          else { flush(); begin(); st.dig = true; var parts = val.split('.'); st.cur = parseFloat(parts[0]); if (parts[1]) st.dec = parts[1]; }
        } else if (Object.prototype.hasOwnProperty.call(L.u, lw)) {
          var v = L.u[lw]; if (st && st.dig && st.dec === null) flush();   // "2870 one point two five" = two numbers
          begin();
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
      return text.replace(/\d+(?:\.\d+)?(?![零〇一二两三四五六七八九十百千万点])|[零〇一二两三四五六七八九十百千万\d]+(?:点[零〇一二两三四五六七八九\d]+)?/g, function (run) {
        if (/^\d+(?:\.\d+)?$/.test(run)) return ' ' + run + ' ';
        var p = run.split('点'), n = String(zhInt(p[0]));
        if (p[1]) n += '.' + p[1].split('').map(function (c) { return ZH[c] !== undefined ? ZH[c] : c; }).join('');
        return ' ' + n + ' ';
      });
    }
    function normalize(text, locale) {
      var lang = (locale || 'en-US').slice(0, 2), t = String(text);
      if (lang === 'zh') return zhToDigits(t.replace(/百分之([零〇一二两三四五六七八九十点\d.]+)/g, '$1%')).replace(/\s+%/g, '%').replace(/\s+/g, ' ').trim().toLowerCase();
      if (lang === 'en') t = t.replace(/\b(a )?half (a )?lots?\b/gi, '0.5 lot').replace(/\ba quarter (of )?(a )?lots?\b/gi, '0.25 lot');
      if (lang === 'hi') t = t.replace(/[०-९]/g, function (c) { return String('०१२३४५६७८९'.indexOf(c)); }).replace(/।/g, ', ').replace(/आधा\s*(लॉट|लोट)/g, '0.5 लॉट');
      if (lang === 'ar') t = t.replace(/[٠-٩]/g, function (c) { return String('٠١٢٣٤٥٦٧٨٩'.indexOf(c)); }).replace(/٫/g, '.').replace(/،/g, ', ').replace(/ـ/g, '')
        .replace(/نصف\s*(لوت|عقد)/g, '0.5 لوت').replace(/(في\s*ال|بال)(مئة|مائة)|٪/g, ' % ');
      if (lang === 'pt') t = t.replace(/\bpor ?cento\b/gi, ' % ');
      if (lang === 'pt' || lang === 'ru') t = t.replace(/(\d),(\d)/g, '$1.$2');
      else t = t.replace(/(\d),(?=\d{3}\b)/g, '$1');
      return wordsToDigits(t, lang).toLowerCase();
    }

    /* ------------------------------------------------------------------ field extraction */
    var KW = {
      entry: ['entry price', 'entry', 'entered at', 'enter at', 'एंट्री', 'प्रवेश', 'سعر الدخول', 'الدخول', 'دخول', 'цена входа', 'вход', 'входа', '入场价', '入场', '进场', '开仓价', '开仓', 'preço de entrada', 'entrada'],
      stop: ['stop loss', 'stop-loss', 'stoploss', 'स्टॉप लॉस', 'स्टॉपलॉस', 'स्टॉप', 'وقف الخسارة', 'ستوب لوس', 'ستوب', 'стоп-лосс', 'стоп лосс', 'стоп', '止损', 'stop', 'sl'],
      exit: ['exit price', 'exit', 'closed at', 'close at', 'एग्जिट', 'एग्ज़िट', 'एक्ज़िट', 'एक्जिट', 'निकास', 'سعر الخروج', 'الخروج', 'خروج', 'выход', 'закрыл по', 'закрыл', '出场价', '出场', '平仓', 'saída', 'saida', 'fechei em', 'fechei'],
      tp: ['take profit', 'take-profit', 'target', 'टारगेट', 'लक्ष्य', 'جني الأرباح', 'الهدف', 'هدف', 'тейк-профит', 'тейк', 'цель', '止盈', 'alvo', 'tp'],
      lots: ['lot size', 'lots', 'lot', 'लॉट', 'लोट', 'لوت', 'عقود', 'عقد', 'лотов', 'лота', 'лот', 'lotes', 'lote']
    };
    var ASSETS = [
      [/\b(gold|xau)\b|золот|黄金|\bouro\b|सोना|सोने|गोल्ड|ذهب/, 'XAUUSD'], [/\b(silver|xag)\b|серебр|白银|\bprata\b|चांदी|चाँदी|सिल्वर|فضة/, 'XAGUSD'],
      [/\beuro ?dollar\b|\beur ?usd\b|евро|欧元|\beuro\b|यूरो|يورو/, 'EURUSD'], [/\b(pound|cable|gbp ?usd)\b|фунт|英镑|\blibra\b|पाउंड|باوند|جنيه/, 'GBPUSD'],
      [/\b(yen|usd ?jpy)\b|иен|日元|\biene\b|येन|ين ياباني|الين/, 'USDJPY'], [/\b(dow|us ?30)\b|доу|道琼斯|डाउ|داو/, 'US30'], [/\b(nasdaq|nas ?100)\b|насдак|纳斯达克|纳指|नैस्डैक|नैसडैक|ناسداك/, 'NAS100'],
      [/\b(s&p|spx|us ?500)\b|标普|एस एंड पी|اس اند بي/, 'US500'], [/\b(bitcoin|btc)\b|биткоин|比特币|बिटकॉइन|بيتكوين|بتكوين/, 'BTCUSDT'], [/\b(ethereum|eth)\b|эфир|以太坊|इथेरियम|ايثريوم|إيثريوم/, 'ETHUSDT'],
      [/\b(oil|wti)\b|нефть|原油|petróleo|क्रूड|कच्चा तेल|نفط/, 'USOIL'], [/\b(dax|ger ?40)\b/, 'GER40']
    ];
    var SIDES = [[/\b(buy|bought|long|buying)\b|купил|покупк|лонг|买入|做多|\bbuy\b|compr|खरीद|ख़रीद|बाय|लॉन्ग|اشتري|شراء|شريت|اشترى/, 'LONG'], [/\b(sell|sold|short|selling)\b|продал|продаж|шорт|卖出|做空|vend|बेच|सेल|शॉर्ट|بعت|بيع|باع/, 'SHORT']];
    var SESS = [[/\bny ?pm\b|afternoon|после обеда|纽约下午|à tarde|tarde/, 'NY_PM'], [/\basia[n]?\b|азиат|азия|亚洲|亚盘|ásia|asiática|एशिया|آسيا|اسيا/, 'ASIA'], [/\blondon\b|лондон|伦敦|londres|लंदन|لندن/, 'LONDON'], [/new york|\bny\b|нью-?\s?йорк|纽约|nova york|nova iorque|न्यूयॉर्क|न्यू यॉर्क|نيويورك/, 'NEW_YORK']];
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
      var before = s.match(/(\d+(?:\.\d+)?)\s*(?:lots?\b|手|लॉट|لوت)/), after = numAfter(s, KW.lots);
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
      [/цена входа|входа|вход|入场价|入场|进场|开仓价|开仓|preço de entrada|entrada|एंट्री|प्रवेश|سعر الدخول|الدخول|دخول/g, ' entry '],
      [/тейк-профит|тейк профит|тейк|止盈|alvo|цель|目标|टारगेट|लक्ष्य|جني الأرباح|الهدف|هدف/g, ' target '], [/стоп-лосс|стоп лосс|стоп|止损|स्टॉप लॉस|स्टॉप|وقف الخسارة|ستوب/g, ' stop '],
      [/выход|закрыл по|出场价|出场|平仓|saída|saida|एग्जिट|एग्ज़िट|निकास|الخروج|خروج/g, ' exit '], [/лотов|лота|лот|lotes|lote|手|लॉट|لوت/g, ' lots '],
      [/риск|风险|risco|जोखिम|रिस्क|المخاطرة|مخاطرة/g, ' risk '], [/процентов|процента|процент|por cento|porcento|प्रतिशत|परसेंट|फीसदी|%/g, ' percent ']
    ];
    function toEnglish(norm) { var t = ' ' + norm + ' '; EN_KW.forEach(function (r) { t = t.replace(r[0], r[1]); }); return t.replace(/\s+/g, ' ').trim(); }
    /** Whole-utterance voice commands in every language: 'stop' | 'skip' | 'back' | null. */
    function command(raw) {
      var t = String(raw).toLowerCase().replace(/[.!?。！？؟।]+$/, '').trim();
      if (/^(stop|cancel|finish|done|that's all|that is all|стоп|хватит|отмена|готово|停止|取消|结束|完成|parar|pare|cancelar|pronto|terminar|रुको|रोको|बंद करो|बस|توقف|إلغاء|الغاء|انتهى|خلاص)( listening| voice| voice log)?$/.test(t)) return 'stop';
      if (/^(skip|next|none|nothing|no|not yet|open|пропустить|пропусти|дальше|далее|нет|pular|pule|próximo|proximo|nenhum|não|nao|छोड़ो|छोडो|अगला|आगे|नहीं|تخطي|تخطى|التالي|لا)(?=\s|$)/.test(t) || /^(跳过|下一个|没有|无)/.test(t)) return 'skip';
      if (/^(back|previous|go back|назад|вернись|voltar|volta|anterior|पीछे|वापस|رجوع|السابق|ارجع)(?=\s|$)/.test(t) || /^(返回|上一个)/.test(t)) return 'back';
      return null;
    }
    function side(norm) { return pick(SIDES, norm); }
    return { normalize: normalize, extract: extractWith, findAsset: findAsset, toCommand: toCommand, toEnglish: toEnglish, command: command, side: side };
  };
})();
