import type { TradingSession } from './constants.js';

/**
 * Trading-session classification. Session windows are defined in each market's
 * local time so DST is handled by the IANA tz database, not by fixed UTC offsets.
 *  - Asia:     Tokyo 09:00–15:00 local
 *  - London:   London 08:00–16:30 local
 *  - New York: New York 08:00–17:00 local
 *  - Overlap:  inside both London and New York windows
 */
function localMinutes(date: Date, timeZone: string): { minutes: number; weekday: number } {
  const parts = new Intl.DateTimeFormat('en-GB', {
    timeZone,
    hour: '2-digit',
    minute: '2-digit',
    weekday: 'short',
    hourCycle: 'h23',
  }).formatToParts(date);
  const hour = Number(parts.find((p) => p.type === 'hour')?.value ?? 0);
  const minute = Number(parts.find((p) => p.type === 'minute')?.value ?? 0);
  const wd = parts.find((p) => p.type === 'weekday')?.value ?? 'Mon';
  const weekday = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].indexOf(wd);
  return { minutes: hour * 60 + minute, weekday };
}

const within = (m: number, start: number, end: number) => m >= start && m < end;

export function classifySession(date: Date): TradingSession {
  const ldn = localMinutes(date, 'Europe/London').minutes;
  const ny = localMinutes(date, 'America/New_York').minutes;
  const tky = localMinutes(date, 'Asia/Tokyo').minutes;
  const inLondon = within(ldn, 8 * 60, 16 * 60 + 30);
  const inNy = within(ny, 8 * 60, 17 * 60);
  if (inLondon && inNy) return 'LONDON_NY_OVERLAP';
  if (inLondon) return 'LONDON';
  if (inNy) return 'NEW_YORK';
  if (within(tky, 9 * 60, 15 * 60)) return 'ASIA';
  return 'OFF_HOURS';
}

export interface MarketClock {
  key: string;
  timeZone: string;
  /** Local cash/main session hours [startMinute, endMinute). */
  open: number;
  close: number;
}

export const WORLD_CLOCKS: readonly MarketClock[] = [
  { key: 'india', timeZone: 'Asia/Kolkata', open: 9 * 60 + 15, close: 15 * 60 + 30 },
  { key: 'new_york', timeZone: 'America/New_York', open: 9 * 60 + 30, close: 16 * 60 },
  { key: 'london', timeZone: 'Europe/London', open: 8 * 60, close: 16 * 60 + 30 },
  { key: 'tokyo', timeZone: 'Asia/Tokyo', open: 9 * 60, close: 15 * 60 },
  { key: 'singapore', timeZone: 'Asia/Singapore', open: 9 * 60, close: 17 * 60 },
  { key: 'dubai', timeZone: 'Asia/Dubai', open: 10 * 60, close: 15 * 60 },
];

/** Whether a cash equity market is open (weekday + local hours; exchange holidays not modelled). */
export function isMarketOpen(clock: MarketClock, now: Date): boolean {
  const { minutes, weekday } = localMinutes(now, clock.timeZone);
  // Dubai (DFM) trades Monday–Friday since 2022, same as the others.
  if (weekday === 0 || weekday === 6) return false;
  return within(minutes, clock.open, clock.close);
}

export function isValidTimeZone(tz: string): boolean {
  try {
    new Intl.DateTimeFormat('en-US', { timeZone: tz });
    return true;
  } catch {
    return false;
  }
}

/** Calendar date (YYYY-MM-DD) of an instant in a given IANA time zone. */
export function dateInTimeZone(date: Date, timeZone: string): string {
  const parts = new Intl.DateTimeFormat('en-CA', {
    timeZone,
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
  }).formatToParts(date);
  const y = parts.find((p) => p.type === 'year')?.value;
  const m = parts.find((p) => p.type === 'month')?.value;
  const dd = parts.find((p) => p.type === 'day')?.value;
  return `${y}-${m}-${dd}`;
}

/** Hour of day (0–23) of an instant in a given time zone. */
export function hourInTimeZone(date: Date, timeZone: string): number {
  return Math.floor(localMinutes(date, timeZone).minutes / 60);
}

/** Weekday (0 = Sunday) of an instant in a given time zone. */
export function weekdayInTimeZone(date: Date, timeZone: string): number {
  return localMinutes(date, timeZone).weekday;
}
