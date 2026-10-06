import type { MistakeTag, Emotion, Side } from './constants.js';
import { MISTAKE_TAGS, EMOTIONS } from './constants.js';
import { d, exitFromR, realisedR } from './calc.js';
import { resolveInstrument } from './instruments.js';

/**
 * Natural-language quick-trade command parser.
 *
 * Grammar (order-insensitive after the side keyword, case-insensitive):
 *   <buy|long|sell|short> <asset> [@|at] <entry>
 *   sl|stop <price>          stop loss
 *   tp|target <price>        take profit
 *   exit|out|closed <price>  actual exit price
 *   <n>r                     target R multiple (e.g. 3r, 2.5r, -1r)
 *   <n> lot|lots | <n>       lot size (a bare number after the entry)
 *   win | loss | stopped | be  outcome shortcuts (exit at target / stop / entry)
 *   #fomo #revenge #moved_stop  mistake or emotion tags
 *   any remaining words       setup / strategy description
 *
 * Examples:
 *   buy gold 2862 sl 2858 3r 0.5 lot val bounce
 *   short us500 5880 sl 5890 2.5r 1.0 silver bullet
 *   long eurusd 1.0850 sl 1.0830 2r
 *   sell btc 62500 sl 63000 tp 61000 0.2
 */
export interface ParsedQuickTrade {
  symbol: string | null;
  side: Side | null;
  entryPrice: string | null;
  stopLoss: string | null;
  takeProfit: string | null;
  exitPrice: string | null;
  targetR: string | null;
  rr: string | null;
  lotSize: string | null;
  setup: string | null;
  mistakeTag: MistakeTag | null;
  emotion: Emotion | null;
  outcome: 'WIN' | 'LOSS' | 'BREAKEVEN' | null;
  errors: string[];
  warnings: string[];
}

const SIDE_WORDS: Record<string, Side> = { buy: 'LONG', long: 'LONG', b: 'LONG', sell: 'SHORT', short: 'SHORT', s: 'SHORT' };
const NUM = /^-?\d+(?:\.\d+)?$/;
const R_TOKEN = /^(-?\d+(?:\.\d+)?)r$/i;
const LOT_TOKEN = /^(\d+(?:\.\d+)?)(?:lots?|l)$/i;

const MISTAKE_ALIASES: Record<string, MistakeTag> = {
  fomo: 'FOMO_ENTRY',
  revenge: 'REVENGE_TRADE',
  movedstop: 'MOVED_STOP',
  moved_stop: 'MOVED_STOP',
  nostop: 'NO_STOP',
  no_stop: 'NO_STOP',
  oversized: 'OVERSIZED',
  early: 'EARLY_EXIT',
  earlyexit: 'EARLY_EXIT',
  late: 'LATE_ENTRY',
  chasing: 'CHASING',
  chase: 'CHASING',
  ignoredplan: 'IGNORED_PLAN',
  overtrading: 'OVERTRADING',
  news: 'NEWS_GAMBLE',
};

function parseTag(tag: string): { mistake?: MistakeTag; emotion?: Emotion } {
  const t = tag.toLowerCase().replace(/^#/, '');
  const upper = t.toUpperCase();
  if (MISTAKE_ALIASES[t]) return { mistake: MISTAKE_ALIASES[t] };
  if ((MISTAKE_TAGS as readonly string[]).includes(upper)) return { mistake: upper as MistakeTag };
  if ((EMOTIONS as readonly string[]).includes(upper)) return { emotion: upper as Emotion };
  return {};
}

export function parseQuickTrade(command: string): ParsedQuickTrade {
  const out: ParsedQuickTrade = {
    symbol: null,
    side: null,
    entryPrice: null,
    stopLoss: null,
    takeProfit: null,
    exitPrice: null,
    targetR: null,
    rr: null,
    lotSize: null,
    setup: null,
    mistakeTag: null,
    emotion: null,
    outcome: null,
    errors: [],
    warnings: [],
  };
  const tokens = command
    .trim()
    .replace(/\s+/g, ' ')
    .split(' ')
    .filter(Boolean);
  if (tokens.length === 0) {
    out.errors.push('Enter a command, e.g. "buy gold 2862 sl 2858 3r 0.5 lot val bounce"');
    return out;
  }

  const setupWords: string[] = [];
  let i = 0;
  const next = () => tokens[i + 1];

  while (i < tokens.length) {
    const raw = tokens[i]!;
    const tok = raw.toLowerCase();

    if (!out.side && SIDE_WORDS[tok]) {
      out.side = SIDE_WORDS[tok]!;
      i++;
      continue;
    }
    if (tok.startsWith('#')) {
      const tag = parseTag(tok);
      if (tag.mistake) out.mistakeTag = tag.mistake;
      else if (tag.emotion) out.emotion = tag.emotion;
      else out.warnings.push(`Unknown tag ${raw}`);
      i++;
      continue;
    }
    if (!out.symbol && !NUM.test(tok)) {
      const inst = resolveInstrument(tok);
      if (inst) {
        out.symbol = inst.symbol;
        i++;
        continue;
      }
    }
    if ((tok === '@' || tok === 'at') && next() && NUM.test(next()!)) {
      out.entryPrice = d(next()!).toString();
      i += 2;
      continue;
    }
    if ((tok === 'sl' || tok === 'stop') && next() && NUM.test(next()!)) {
      out.stopLoss = d(next()!).toString();
      i += 2;
      continue;
    }
    if ((tok === 'tp' || tok === 'target') && next() && NUM.test(next()!)) {
      out.takeProfit = d(next()!).toString();
      i += 2;
      continue;
    }
    if ((tok === 'exit' || tok === 'out' || tok === 'closed' || tok === 'close') && next() && NUM.test(next()!)) {
      out.exitPrice = d(next()!).toString();
      i += 2;
      continue;
    }
    if ((tok === 'lot' || tok === 'lots' || tok === 'size') && next() && NUM.test(next()!) && !out.lotSize) {
      out.lotSize = d(next()!).toString();
      i += 2;
      continue;
    }
    const rMatch = R_TOKEN.exec(tok);
    if (rMatch) {
      out.targetR = d(rMatch[1]!).toString();
      i++;
      continue;
    }
    const lotMatch = LOT_TOKEN.exec(tok);
    if (lotMatch) {
      out.lotSize = d(lotMatch[1]!).toString();
      i++;
      continue;
    }
    if (NUM.test(tok)) {
      if (next() && /^lots?$/i.test(next()!)) {
        out.lotSize = d(tok).toString();
        i += 2;
        continue;
      }
      if (!out.entryPrice) out.entryPrice = d(tok).toString();
      else if (!out.lotSize) out.lotSize = d(tok).toString();
      else out.warnings.push(`Ignored extra number ${raw}`);
      i++;
      continue;
    }
    if (['win', 'won', 'tp-hit', 'hit'].includes(tok)) {
      out.outcome = 'WIN';
      i++;
      continue;
    }
    if (['loss', 'lost', 'stopped', 'sl-hit'].includes(tok)) {
      out.outcome = 'LOSS';
      i++;
      continue;
    }
    if (['be', 'breakeven', 'scratch'].includes(tok)) {
      out.outcome = 'BREAKEVEN';
      i++;
      continue;
    }
    setupWords.push(raw);
    i++;
  }

  if (setupWords.length) {
    out.setup = setupWords
      .join(' ')
      .slice(0, 120)
      .replace(/\b([a-z])/g, (m) => m.toUpperCase())
      .replace(/\b(Val|Vah|Poc|Ict|Fvg|Ob|Bos|Choch|Vwap)\b/g, (m) => m.toUpperCase());
  }

  if (!out.side) out.errors.push('Missing direction (buy/long or sell/short)');
  if (!out.symbol) out.errors.push('Unknown or missing instrument');
  if (!out.entryPrice) out.errors.push('Missing entry price');
  if (out.lotSize && d(out.lotSize).lte(0)) out.errors.push('Lot size must be positive');
  if (!out.lotSize) {
    out.lotSize = '1';
    out.warnings.push('No lot size given — defaulting to 1.00 lot');
  }

  if (out.side && out.entryPrice && out.stopLoss) {
    const e = d(out.entryPrice);
    const s = d(out.stopLoss);
    if (s.eq(e)) out.errors.push('Stop loss cannot equal entry');
    else if (out.side === 'LONG' && s.gt(e)) out.errors.push('Long stop loss must be below entry');
    else if (out.side === 'SHORT' && s.lt(e)) out.errors.push('Short stop loss must be above entry');
  }

  if (out.errors.length === 0 && out.side && out.entryPrice) {
    const { side, entryPrice: entry, stopLoss: stop } = out;
    if (out.targetR && stop) {
      const target = exitFromR(side, entry, stop, out.targetR);
      if (!out.takeProfit && d(out.targetR).gt(0)) out.takeProfit = target.toString();
      if (!out.exitPrice && !out.outcome) out.exitPrice = target.toString();
    } else if (out.targetR && !stop) {
      out.errors.push('An R target needs a stop loss (sl <price>)');
    }
    if (!out.exitPrice && out.outcome === 'WIN' && out.takeProfit) out.exitPrice = out.takeProfit;
    if (!out.exitPrice && out.outcome === 'WIN' && !out.takeProfit) out.errors.push('"win" needs a tp or R target');
    if (!out.exitPrice && out.outcome === 'LOSS' && stop) out.exitPrice = stop;
    if (!out.exitPrice && out.outcome === 'LOSS' && !stop) out.errors.push('"loss" needs a stop loss');
    if (!out.exitPrice && out.outcome === 'BREAKEVEN') out.exitPrice = entry;
    if (!out.exitPrice && out.takeProfit && !out.outcome) out.exitPrice = out.takeProfit;
    if (out.exitPrice && stop) {
      const r = realisedR(side, entry, stop, out.exitPrice);
      out.rr = r ? r.toDecimalPlaces(2).toString() : null;
    }
    if (!out.exitPrice) out.warnings.push('No exit — trade will be logged as OPEN');
    if (!stop) out.warnings.push('No stop loss — R multiple cannot be calculated');
  }

  return out;
}
