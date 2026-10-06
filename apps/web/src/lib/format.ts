import { getInstrument } from '@journzey/shared';

export function fmtMoney(value: string | number | null | undefined, currency = 'USD', opts: { sign?: boolean; compact?: boolean } = {}): string {
  if (value === null || value === undefined || value === '') return '—';
  const n = Number(value);
  if (!Number.isFinite(n)) return '—';
  const f = new Intl.NumberFormat(undefined, {
    style: 'currency',
    currency,
    minimumFractionDigits: opts.compact ? 0 : 2,
    maximumFractionDigits: opts.compact ? 1 : 2,
    notation: opts.compact ? 'compact' : 'standard',
  }).format(Math.abs(n));
  const sign = n < 0 ? '−' : opts.sign && n > 0 ? '+' : '';
  return `${sign}${f}`;
}

export function fmtNum(value: string | number | null | undefined, dp = 2): string {
  if (value === null || value === undefined || value === '') return '—';
  const n = Number(value);
  if (!Number.isFinite(n)) return '—';
  return n.toLocaleString(undefined, { minimumFractionDigits: dp, maximumFractionDigits: dp });
}

export function fmtPct(value: number | null | undefined, dp = 1): string {
  if (value === null || value === undefined || !Number.isFinite(value)) return '—';
  return `${(value * 100).toFixed(dp)}%`;
}

export function fmtR(value: string | number | null | undefined): string {
  if (value === null || value === undefined || value === '') return '—';
  const n = Number(value);
  return `${n > 0 ? '+' : n < 0 ? '−' : ''}${Math.abs(n).toFixed(2)}R`;
}

export function fmtPrice(symbol: string, value: string | null | undefined): string {
  if (value === null || value === undefined || value === '') return '—';
  const dp = getInstrument(symbol)?.decimals ?? 2;
  return Number(value).toLocaleString(undefined, { minimumFractionDigits: dp, maximumFractionDigits: dp });
}

export function fmtDate(iso: string, timeZone: string, opts: Intl.DateTimeFormatOptions = { year: 'numeric', month: 'short', day: '2-digit' }) {
  return new Intl.DateTimeFormat(undefined, { timeZone, ...opts }).format(new Date(iso));
}

export function fmtTime(iso: string, timeZone: string) {
  return new Intl.DateTimeFormat(undefined, { timeZone, hour: '2-digit', minute: '2-digit', hourCycle: 'h23' }).format(new Date(iso));
}

/** YYYY-MM-DD of "now" in a time zone. */
export function todayIn(timeZone: string): string {
  return new Intl.DateTimeFormat('en-CA', { timeZone, year: 'numeric', month: '2-digit', day: '2-digit' }).format(new Date());
}

export function shiftDate(date: string, days: number): string {
  const d = new Date(`${date}T00:00:00Z`);
  d.setUTCDate(d.getUTCDate() + days);
  return d.toISOString().slice(0, 10);
}

/** Converts a `datetime-local` value interpreted in `timeZone` into a UTC ISO string. */
export function zonedLocalToUtc(local: string, timeZone: string): string {
  const [datePart, timePart = '00:00'] = local.split('T');
  const [y, m, d] = datePart!.split('-').map(Number) as [number, number, number];
  const [hh, mm] = timePart.split(':').map(Number) as [number, number];
  const guess = Date.UTC(y, m - 1, d, hh, mm);
  const offset = (ts: number) => {
    const parts = new Intl.DateTimeFormat('en-US', { timeZone, hourCycle: 'h23', year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', second: '2-digit' }).formatToParts(new Date(ts));
    const get = (t: string) => Number(parts.find((p) => p.type === t)?.value);
    return Date.UTC(get('year'), get('month') - 1, get('day'), get('hour'), get('minute'), get('second')) - ts;
  };
  const first = guess - offset(guess);
  return new Date(guess - offset(first)).toISOString();
}

/** Formats a UTC ISO instant as a `datetime-local` value in `timeZone`. */
export function utcToZonedLocal(iso: string, timeZone: string): string {
  const parts = new Intl.DateTimeFormat('en-CA', { timeZone, hourCycle: 'h23', year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' }).formatToParts(new Date(iso));
  const get = (t: string) => parts.find((p) => p.type === t)?.value ?? '00';
  return `${get('year')}-${get('month')}-${get('day')}T${get('hour')}:${get('minute')}`;
}

export const pnlTone = (v: string | number | null | undefined) => {
  const n = Number(v);
  if (v === null || v === undefined || !Number.isFinite(n) || n === 0) return 'text-muted';
  return n > 0 ? 'text-profit' : 'text-loss';
};

export const humanize = (s: string | null | undefined) =>
  s ? s.toLowerCase().replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase()).replace(/\bNy\b/, 'NY') : '—';
