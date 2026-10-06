import { parse } from 'csv-parse/sync';
import { createHash } from 'node:crypto';
import { d, resolveInstrument, type Side } from '@journzey/shared';
import type { NormalizedTrade } from './types.js';

const ALIASES: Record<keyof Omit<NormalizedTrade, 'setupTag' | 'notes'> | 'swap' | 'commission', string[]> = {
  brokerTradeId: ['ticket', 'position', 'position id', 'order', 'deal', 'trade id', 'id', 'trade #', 'trade number'],
  symbol: ['symbol', 'instrument', 'item', 'market', 'pair'],
  side: ['type', 'side', 'direction', 'action', 'market pos.', 'market pos', 'buy/sell'],
  executedAt: ['open time', 'opentime', 'entry time', 'time', 'open date', 'date', 'opening time'],
  closedAt: ['close time', 'closetime', 'exit time', 'closing time', 'close date'],
  entryPrice: ['open price', 'entry price', 'price', 'entry', 'opening price'],
  exitPrice: ['close price', 'exit price', 'exit', 'closing price'],
  stopLoss: ['s / l', 's/l', 'sl', 'stop loss', 'stoploss'],
  takeProfit: ['t / p', 't/p', 'tp', 'take profit', 'takeprofit'],
  lotSize: ['volume', 'lots', 'size', 'quantity', 'qty', 'lot'],
  pnl: ['profit', 'pnl', 'p&l', 'net profit', 'realized pnl', 'net p/l', 'profit/loss'],
  fees: ['fees', 'fee'],
  commission: ['commission', 'commissions'],
  swap: ['swap', 'swaps', 'rollover'],
};

const norm = (h: string) => h.trim().toLowerCase().replace(/\s+/g, ' ');

function parseDate(v: string, offsetMinutes: number): string | null {
  const s = v.trim();
  if (!s) return null;
  // MT4/MT5: 2026.08.03 08:14[:00]
  const mt = /^(\d{4})[.\-/](\d{2})[.\-/](\d{2})[ T](\d{2}):(\d{2})(?::(\d{2}))?$/.exec(s);
  if (mt) {
    const [, y, mo, da, h, mi, se] = mt;
    const utc = Date.UTC(+y!, +mo! - 1, +da!, +h!, +mi!, +(se ?? 0)) - offsetMinutes * 60_000;
    return new Date(utc).toISOString();
  }
  const t = Date.parse(s);
  return Number.isNaN(t) ? null : new Date(t).toISOString();
}

const num = (v: string | undefined) => {
  if (v === undefined) return null;
  const s = v.replace(/[\s,]/g, '').replace(/^\((.*)\)$/, '-$1');
  if (s === '' || s === '-') return null;
  return /^-?\d+(\.\d+)?$/.test(s) ? s : null;
};

export interface CsvParseResult {
  trades: NormalizedTrade[];
  errors: Array<{ ref: string; message: string }>;
  skipped: number;
}

/**
 * Parses broker history CSV exports into normalised trades.
 * @param serverUtcOffsetMinutes broker server-time offset (MT servers commonly run at UTC+2/+3)
 */
export function parseTradeCsv(content: string, serverUtcOffsetMinutes = 0): CsvParseResult {
  const delimiter = (content.split('\n')[0] ?? '').includes(';') && !(content.split('\n')[0] ?? '').includes(',') ? ';' : ',';
  const records = parse(content, {
    columns: (header: string[]) => header.map(norm),
    skip_empty_lines: true,
    relax_column_count: true,
    trim: true,
    bom: true,
    delimiter,
    to: 50_000,
  }) as Array<Record<string, string>>;
  const out: CsvParseResult = { trades: [], errors: [], skipped: 0 };
  if (records.length === 0) return out;
  const headers = Object.keys(records[0]!);
  const col = (field: keyof typeof ALIASES) => headers.find((h) => ALIASES[field].includes(h));
  const cols = Object.fromEntries((Object.keys(ALIASES) as Array<keyof typeof ALIASES>).map((k) => [k, col(k)])) as Record<keyof typeof ALIASES, string | undefined>;
  for (const required of ['symbol', 'side', 'executedAt', 'entryPrice', 'lotSize'] as const) {
    if (!cols[required]) {
      out.errors.push({ ref: 'header', message: `Missing required column for ${required} (accepted: ${ALIASES[required].join(', ')})` });
    }
  }
  if (out.errors.length) return out;

  records.forEach((r, i) => {
    const ref = `row ${i + 2}`;
    const get = (k: keyof typeof ALIASES) => (cols[k] ? r[cols[k]!] : undefined);
    const sideRaw = (get('side') ?? '').toLowerCase();
    // Skip balance/credit/deposit rows typically present in MT statements.
    if (!sideRaw || /balance|credit|deposit|withdraw/.test(sideRaw)) {
      out.skipped++;
      return;
    }
    const side: Side | null = /^(buy|long|b)\b/.test(sideRaw) ? 'LONG' : /^(sell|short|s)\b/.test(sideRaw) ? 'SHORT' : null;
    if (!side) return void out.errors.push({ ref, message: `Unrecognised side "${get('side')}"` });
    if (/limit|stop/.test(sideRaw)) return void out.skipped++;
    const inst = resolveInstrument(get('symbol') ?? '');
    if (!inst) return void out.errors.push({ ref, message: `Unsupported instrument "${get('symbol')}"` });
    const executedAt = parseDate(get('executedAt') ?? '', serverUtcOffsetMinutes);
    if (!executedAt) return void out.errors.push({ ref, message: 'Invalid open time' });
    const entry = num(get('entryPrice'));
    const lots = num(get('lotSize'));
    if (!entry || d(entry).lte(0)) return void out.errors.push({ ref, message: 'Invalid entry price' });
    if (!lots || d(lots).lte(0)) return void out.errors.push({ ref, message: 'Invalid volume' });
    const exit = num(get('exitPrice'));
    const sl = num(get('stopLoss'));
    const tp = num(get('takeProfit'));
    const profit = num(get('pnl'));
    const commission = num(get('commission'));
    const swap = num(get('swap'));
    const feesCol = num(get('fees'));
    // Broker statements report commission/swap as signed P&L components; net P&L = profit + commission + swap.
    const netPnl = profit !== null ? d(profit).plus(commission ?? 0).plus(swap ?? 0).toFixed(2) : null;
    const fees = feesCol ?? (commission !== null || swap !== null ? d(commission ?? 0).plus(swap ?? 0).negated().toFixed(2) : null);
    const id =
      get('brokerTradeId') ||
      `csv-${createHash('sha256').update(`${inst.symbol}|${side}|${executedAt}|${entry}|${lots}|${exit ?? ''}`).digest('hex').slice(0, 24)}`;
    out.trades.push({
      brokerTradeId: String(id).slice(0, 120),
      symbol: inst.symbol,
      side,
      executedAt,
      closedAt: parseDate(get('closedAt') ?? '', serverUtcOffsetMinutes),
      entryPrice: entry,
      exitPrice: exit && d(exit).gt(0) ? exit : null,
      stopLoss: sl && d(sl).gt(0) ? sl : null,
      takeProfit: tp && d(tp).gt(0) ? tp : null,
      lotSize: lots,
      pnl: netPnl,
      fees: fees && d(fees).gte(0) ? fees : null,
    });
  });
  return out;
}
