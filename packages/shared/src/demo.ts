import { d, exitFromR } from './calc.js';
import type { Emotion, MistakeTag, Side } from './constants.js';
import { mulberry32 } from './analytics.js';
import { getInstrument } from './instruments.js';
import { classifySession } from './sessions.js';

/**
 * Deterministic DEMO DATA set (Aug–Oct 2026). Used by the onboarding "load demo data"
 * flow, the "Reset Demo Data" action and `database/seed.sql` generation.
 * Every record created from it is attached to a trading account flagged demo=true
 * (trades) or demo=true (journal entries) and is never mixed into real analytics.
 */
export interface DemoTrade {
  executedAt: string;
  closedAt: string;
  symbol: string;
  side: Side;
  entryPrice: string;
  stopLoss: string;
  takeProfit: string;
  exitPrice: string;
  lotSize: string;
  strategy: string;
  setupTag: string;
  session: string;
  emotion: Emotion;
  mistakeTag: MistakeTag;
  rulesFollowed: boolean;
  notes: string;
}

export interface DemoJournal {
  journalDate: string;
  compliance: number;
  emotionalState: Emotion;
  disciplineRating: number;
  reflection: string;
  keyLesson: string;
}

type Spec = [
  date: string,
  utcTime: string,
  symbol: string,
  side: Side,
  strategy: string,
  r: number,
  emotion: Emotion,
  mistake: MistakeTag,
  rulesFollowed: boolean,
  note: string,
];

const SPECS: Spec[] = [
  ['2026-08-03', '08:14', 'XAUUSD', 'LONG', 'VAL Bounce', 3, 'CALM', 'NONE', true, 'Clean acceptance back into value, held to target.'],
  ['2026-08-03', '14:02', 'US500', 'SHORT', 'VAH Rejection', -1, 'FOCUSED', 'NONE', true, 'Valid setup, stopped by CPI drift.'],
  ['2026-08-04', '09:31', 'EURUSD', 'LONG', 'Order Block', 2, 'CONFIDENT', 'NONE', true, 'London OB respected, partials at 1R.'],
  ['2026-08-05', '14:47', 'NAS100', 'LONG', 'ICT Silver Bullet', 2.5, 'FOCUSED', 'NONE', true, 'Silver bullet FVG with displacement.'],
  ['2026-08-06', '15:20', 'NAS100', 'SHORT', 'Liquidity Sweep', -1, 'ANXIOUS', 'LATE_ENTRY', false, 'Entered after the move; poor location.'],
  ['2026-08-07', '07:55', 'XAUUSD', 'SHORT', 'Fair Value Gap', 2, 'CALM', 'NONE', true, 'Asia high swept, FVG short into London.'],
  ['2026-08-10', '13:45', 'BTCUSDT', 'LONG', 'Trend Following', 1.8, 'CONFIDENT', 'NONE', true, 'Pullback to 20 EMA in uptrend.'],
  ['2026-08-11', '14:05', 'USOIL', 'SHORT', 'VAH Rejection', -1, 'FOMO', 'FOMO_ENTRY', false, 'Chased the inventory headline. No plan.'],
  ['2026-08-11', '14:18', 'USOIL', 'LONG', 'Liquidity Sweep', -1, 'REVENGE', 'REVENGE_TRADE', false, 'Revenge flip right after the loss.'],
  ['2026-08-11', '14:31', 'USOIL', 'SHORT', 'Trend Following', -1.6, 'REVENGE', 'MOVED_STOP', false, 'Moved stop wider. Third loss in 30 min — tilt.'],
  ['2026-08-13', '08:40', 'EURUSD', 'SHORT', 'POC', 1.5, 'NEUTRAL', 'NONE', true, 'Rotation back to POC.'],
  ['2026-08-14', '14:33', 'US500', 'LONG', 'Volume Profile', 2, 'FOCUSED', 'NONE', true, 'LVN acceptance, trended to HVN.'],
  ['2026-08-18', '08:22', 'XAUUSD', 'LONG', 'VAL Bounce', -1, 'CALM', 'NONE', true, 'Good trade, bad outcome.'],
  ['2026-08-19', '15:10', 'NAS100', 'LONG', 'ICT Silver Bullet', 3, 'FOCUSED', 'NONE', true, 'Textbook draw on liquidity.'],
  ['2026-08-20', '13:58', 'BTCUSDT', 'SHORT', 'Liquidity Sweep', 2.2, 'CALM', 'NONE', true, 'Swept equal highs then rejected.'],
  ['2026-08-24', '09:05', 'EURUSD', 'LONG', 'Fair Value Gap', -1, 'GREEDY', 'OVERSIZED', false, 'Doubled size after two wins.'],
  ['2026-08-26', '14:40', 'US500', 'SHORT', 'VAH Rejection', 2.5, 'FOCUSED', 'NONE', true, 'Failed auction above VAH.'],
  ['2026-08-28', '08:10', 'XAUUSD', 'SHORT', 'Order Block', 0.6, 'ANXIOUS', 'EARLY_EXIT', false, 'Cut early on noise; target hit later.'],
  ['2026-09-01', '14:36', 'NAS100', 'LONG', 'Trend Following', 1.5, 'CONFIDENT', 'NONE', true, 'Trend day, trailed stop.'],
  ['2026-09-02', '08:18', 'XAUUSD', 'LONG', 'VAL Bounce', 3, 'CALM', 'NONE', true, 'Best setup of the month.'],
  ['2026-09-03', '13:50', 'USOIL', 'LONG', 'Volume Profile', -1, 'NEUTRAL', 'NONE', true, 'Stopped on EIA spike.'],
  ['2026-09-04', '12:31', 'US500', 'SHORT', 'ICT Silver Bullet', -1, 'FOMO', 'NEWS_GAMBLE', false, 'Traded straight into NFP.'],
  ['2026-09-08', '09:12', 'EURUSD', 'SHORT', 'Liquidity Sweep', 2, 'FOCUSED', 'NONE', true, 'Frankfurt high sweep.'],
  ['2026-09-09', '14:44', 'BTCUSDT', 'LONG', 'Order Block', -1, 'CALM', 'NONE', true, 'Clean loss, plan followed.'],
  ['2026-09-10', '08:05', 'XAUUSD', 'SHORT', 'Fair Value Gap', 2.4, 'FOCUSED', 'NONE', true, 'London open displacement.'],
  ['2026-09-11', '15:02', 'NAS100', 'SHORT', 'VAH Rejection', -2.1, 'FRUSTRATED', 'NO_STOP', false, 'No hard stop; mental stop failed.'],
  ['2026-09-15', '14:38', 'US500', 'LONG', 'POC', 1.2, 'NEUTRAL', 'NONE', true, 'POC magnet rotation.'],
  ['2026-09-16', '08:27', 'XAUUSD', 'LONG', 'Liquidity Sweep', 2.8, 'CONFIDENT', 'NONE', true, 'Sell-side sweep into demand.'],
  ['2026-09-17', '18:15', 'BTCUSDT', 'SHORT', 'Trend Following', -1, 'BORED', 'OVERTRADING', false, 'Off-hours boredom trade.'],
  ['2026-09-22', '14:50', 'NAS100', 'LONG', 'ICT Silver Bullet', 2, 'FOCUSED', 'NONE', true, 'Silver bullet continuation.'],
  ['2026-09-23', '09:40', 'EURUSD', 'LONG', 'Order Block', 1.6, 'CALM', 'NONE', true, 'H1 OB with BOS.'],
  ['2026-09-24', '14:12', 'USOIL', 'SHORT', 'VAH Rejection', 2, 'FOCUSED', 'NONE', true, 'Rejected value high cleanly.'],
  ['2026-09-25', '08:33', 'XAUUSD', 'SHORT', 'Order Block', -1, 'CALM', 'NONE', true, 'Invalidated by USD weakness.'],
  ['2026-09-29', '14:41', 'US500', 'LONG', 'Volume Profile', 2.2, 'CONFIDENT', 'NONE', true, 'Composite profile HVN reclaim.'],
  ['2026-09-30', '15:05', 'NAS100', 'SHORT', 'Liquidity Sweep', -1, 'GREEDY', 'CHASING', false, 'Chased extension at month end.'],
  ['2026-10-01', '08:12', 'XAUUSD', 'LONG', 'VAL Bounce', 2.5, 'CALM', 'NONE', true, 'Q4 open: VAL hold.'],
  ['2026-10-01', '14:36', 'BTCUSDT', 'LONG', 'Fair Value Gap', 1.4, 'FOCUSED', 'NONE', true, 'FVG continuation.'],
  ['2026-10-02', '12:45', 'EURUSD', 'SHORT', 'Trend Following', -1, 'ANXIOUS', 'IGNORED_PLAN', false, 'Took a B-setup outside plan.'],
  ['2026-10-02', '14:48', 'NAS100', 'LONG', 'ICT Silver Bullet', 2.6, 'FOCUSED', 'NONE', true, 'Clean silver bullet.'],
  ['2026-10-05', '08:20', 'XAUUSD', 'SHORT', 'Liquidity Sweep', 1.9, 'CALM', 'NONE', true, 'Swept Asia high into supply.'],
  ['2026-10-05', '13:55', 'USOIL', 'LONG', 'Order Block', -1, 'NEUTRAL', 'NONE', true, 'Valid loss.'],
  ['2026-10-05', '14:40', 'US500', 'SHORT', 'VAH Rejection', 1.8, 'FOCUSED', 'NONE', true, 'VAH failure into close.'],
];

/** Approximate demo price levels and stop distances per instrument. */
const PRICE_MODEL: Record<string, { base: number; drift: number; stop: number; lot: number }> = {
  XAUUSD: { base: 3340, drift: 120, stop: 5, lot: 0.4 },
  US500: { base: 6380, drift: 140, stop: 10, lot: 20 },
  NAS100: { base: 23250, drift: 600, stop: 35, lot: 6 },
  EURUSD: { base: 1.162, drift: 0.02, stop: 0.0022, lot: 0.9 },
  BTCUSDT: { base: 112000, drift: 7000, stop: 900, lot: 0.22 },
  USOIL: { base: 66, drift: 4, stop: 0.35, lot: 0.55 },
};

export function generateDemoTrades(): DemoTrade[] {
  const rand = mulberry32(20260801);
  return SPECS.map((s, idx) => {
    const [date, time, symbol, side, strategy, r, emotion, mistake, rules, note] = s;
    const inst = getInstrument(symbol)!;
    const m = PRICE_MODEL[symbol]!;
    const progress = idx / SPECS.length;
    const entryNum = m.base + m.drift * (progress - 0.5) + (rand() - 0.5) * m.drift * 0.2;
    const entry = d(entryNum).toDecimalPlaces(inst.decimals);
    const stopDist = d(m.stop * (0.8 + rand() * 0.4)).toDecimalPlaces(inst.decimals);
    const stop = side === 'LONG' ? entry.minus(stopDist) : entry.plus(stopDist);
    const exit = exitFromR(side, entry, stop, r).toDecimalPlaces(inst.decimals);
    const tp = exitFromR(side, entry, stop, Math.max(2, Math.ceil(Math.abs(r)))).toDecimalPlaces(inst.decimals);
    const oversize = mistake === 'OVERSIZED' ? 2.5 : 1;
    const lot = d(m.lot * oversize * (0.85 + rand() * 0.3)).toDecimalPlaces(2);
    const executedAt = new Date(`${date}T${time}:00Z`);
    const holdMinutes = 12 + Math.floor(rand() * 140);
    const closedAt = new Date(executedAt.getTime() + holdMinutes * 60_000);
    return {
      executedAt: executedAt.toISOString(),
      closedAt: closedAt.toISOString(),
      symbol,
      side,
      entryPrice: entry.toFixed(inst.decimals),
      stopLoss: stop.toFixed(inst.decimals),
      takeProfit: tp.toFixed(inst.decimals),
      exitPrice: exit.toFixed(inst.decimals),
      lotSize: lot.toFixed(2),
      strategy,
      setupTag: strategy,
      session: classifySession(executedAt),
      emotion,
      mistakeTag: mistake,
      rulesFollowed: rules,
      notes: `[DEMO DATA] ${note}`,
    };
  });
}

export const DEMO_JOURNALS: readonly DemoJournal[] = [
  { journalDate: '2026-08-03', compliance: 5, emotionalState: 'CALM', disciplineRating: 9, reflection: 'Followed the pre-market routine. Waited for acceptance back into value before entering gold.', keyLesson: 'Patience at value edges pays.' },
  { journalDate: '2026-08-06', compliance: 3, emotionalState: 'ANXIOUS', disciplineRating: 5, reflection: 'Entered NAS short late after missing the initial move. Location was poor.', keyLesson: 'If I missed it, I missed it.' },
  { journalDate: '2026-08-11', compliance: 1, emotionalState: 'REVENGE', disciplineRating: 2, reflection: 'Three oil losses in under 30 minutes. Chased the headline, flipped in revenge, then moved my stop. Classic tilt.', keyLesson: 'After two losses in a session, stop trading for the day.' },
  { journalDate: '2026-08-14', compliance: 5, emotionalState: 'FOCUSED', disciplineRating: 9, reflection: 'Reset after the tilt day. Took only A+ volume-profile setups.', keyLesson: 'Fewer trades, better trades.' },
  { journalDate: '2026-08-24', compliance: 2, emotionalState: 'GREEDY', disciplineRating: 4, reflection: 'Doubled position size after two winners. The loss cost more than both wins combined.', keyLesson: 'Size is fixed by the plan, not by mood.' },
  { journalDate: '2026-08-28', compliance: 3, emotionalState: 'ANXIOUS', disciplineRating: 6, reflection: 'Closed gold short at 0.6R on noise. Price hit my target an hour later.', keyLesson: 'Let the stop and target do their job.' },
  { journalDate: '2026-09-02', compliance: 5, emotionalState: 'CALM', disciplineRating: 10, reflection: 'Best execution of the month on the VAL bounce.', keyLesson: 'My edge is strongest at London open on gold.' },
  { journalDate: '2026-09-04', compliance: 2, emotionalState: 'FOMO', disciplineRating: 3, reflection: 'Traded into NFP despite my rule. Pure gamble.', keyLesson: 'No positions 15 minutes either side of tier-1 news.' },
  { journalDate: '2026-09-11', compliance: 1, emotionalState: 'FRUSTRATED', disciplineRating: 2, reflection: 'Did not place a hard stop on NAS. Mental stop failed and the loss reached 2R.', keyLesson: 'Hard stop is placed before the entry fills. Always.' },
  { journalDate: '2026-09-17', compliance: 2, emotionalState: 'BORED', disciplineRating: 4, reflection: 'Off-hours BTC trade out of boredom.', keyLesson: 'Close the platform when the session ends.' },
  { journalDate: '2026-09-24', compliance: 5, emotionalState: 'FOCUSED', disciplineRating: 9, reflection: 'Oil VAH rejection executed exactly to plan.', keyLesson: 'Checklist before every entry.' },
  { journalDate: '2026-10-02', compliance: 3, emotionalState: 'ANXIOUS', disciplineRating: 6, reflection: 'Took a B-setup on EURUSD outside my plan, then recovered with a clean NAS silver bullet.', keyLesson: 'Only trade the playbook.' },
  { journalDate: '2026-10-05', compliance: 5, emotionalState: 'CALM', disciplineRating: 9, reflection: 'Strong start to October. Two winners, one valid loss.', keyLesson: 'Valid losses are part of the edge.' },
];

export const DEMO_ACCOUNT = {
  accountName: 'Demo Account',
  brokerName: 'DEMO DATA',
  currency: 'USD',
  startingCapital: '25000.00',
} as const;
