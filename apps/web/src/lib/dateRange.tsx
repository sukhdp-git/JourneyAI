import { createContext, useContext, useMemo, useState, type ReactNode } from 'react';
import { shiftDate, todayIn } from './format';

export type RangePreset = 'all' | 'today' | 'week' | 'month' | 'lastMonth' | 'custom';

export interface DateRange {
  preset: RangePreset;
  from?: string;
  to?: string;
}

/** Resolve a preset to concrete YYYY-MM-DD bounds in the user's configured time zone. */
export function resolveRange(preset: RangePreset, tz: string, custom?: { from?: string; to?: string }): DateRange {
  const today = todayIn(tz);
  if (preset === 'today') return { preset, from: today, to: today };
  if (preset === 'week') {
    const dow = new Date(`${today}T00:00:00Z`).getUTCDay();
    return { preset, from: shiftDate(today, -((dow + 6) % 7)), to: today };
  }
  if (preset === 'month') return { preset, from: `${today.slice(0, 7)}-01`, to: today };
  if (preset === 'lastMonth') {
    const lastDay = shiftDate(`${today.slice(0, 7)}-01`, -1);
    return { preset, from: `${lastDay.slice(0, 7)}-01`, to: lastDay };
  }
  if (preset === 'custom') return { preset, from: custom?.from, to: custom?.to };
  return { preset: 'all' };
}

const Ctx = createContext<{ range: DateRange; setRange: (r: DateRange) => void }>({ range: { preset: 'all' }, setRange: () => undefined });

export function DateRangeProvider({ children }: { children: ReactNode }) {
  const [range, setRange] = useState<DateRange>({ preset: 'all' });
  const value = useMemo(() => ({ range, setRange }), [range]);
  return <Ctx.Provider value={value}>{children}</Ctx.Provider>;
}

export const useDateRange = () => useContext(Ctx);
