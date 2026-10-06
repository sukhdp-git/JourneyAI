import { describe, expect, it } from 'vitest';
import {
  computeTradeMath,
  detectTilt,
  disciplineLeak,
  equityCurve,
  exitFromR,
  generateDemoTrades,
  lotSizeForRisk,
  monteCarlo,
  parseQuickTrade,
  realisedR,
  resolveInstrument,
  summarize,
  classifySession,
  tradeCreateSchema,
  type AnalyticsTrade,
} from './index.js';

const t = (id: string, pnl: string, extra: Partial<AnalyticsTrade> = {}): AnalyticsTrade => ({
  id,
  executedAt: `2026-09-0${id}T10:00:00Z`,
  symbol: 'XAUUSD',
  side: 'LONG',
  pnl,
  rr: null,
  riskAmount: '100',
  strategyId: null,
  session: 'LONDON',
  emotion: null,
  mistakeTag: null,
  rulesFollowed: true,
  ...extra,
});

describe('trade calculations', () => {
  it('derives exit from R for long and short', () => {
    expect(exitFromR('LONG', '2862', '2858', '3').toString()).toBe('2874');
    expect(exitFromR('SHORT', '5880', '5890', '2.5').toString()).toBe('5855');
  });
  it('computes signed R', () => {
    expect(realisedR('LONG', '100', '90', '120')!.toString()).toBe('2');
    expect(realisedR('SHORT', '100', '110', '120')!.toString()).toBe('-2');
  });
  it('computes gold P&L without float error', () => {
    const r = computeTradeMath({ symbol: 'XAUUSD', side: 'LONG', entryPrice: '2862', exitPrice: '2874', stopLoss: '2858', lotSize: '0.5', accountCurrency: 'USD' });
    expect(r.pnl).toBe('600.00');
    expect(r.rr).toBe('3.0000');
    expect(r.riskAmount).toBe('200.00');
  });
  it('computes EURUSD P&L precisely (0.1 + 0.2 class bugs)', () => {
    const r = computeTradeMath({ symbol: 'EURUSD', side: 'LONG', entryPrice: '1.0850', exitPrice: '1.0890', stopLoss: '1.0830', lotSize: '1.0', fees: '7', accountCurrency: 'USD' });
    expect(r.pnl).toBe('393.00');
  });
  it('converts USDJPY P&L into USD using the exit rate', () => {
    const r = computeTradeMath({ symbol: 'USDJPY', side: 'LONG', entryPrice: '150.00', exitPrice: '151.00', lotSize: '1', accountCurrency: 'USD' });
    expect(r.pnl).toBe('662.25');
  });
  it('refuses to invent FX conversion', () => {
    const r = computeTradeMath({ symbol: 'GER40', side: 'LONG', entryPrice: '18000', exitPrice: '18100', lotSize: '1', accountCurrency: 'USD' });
    expect(r.pnl).toBeNull();
  });
  it('sizes lots for risk', () => {
    expect(lotSizeForRisk({ symbol: 'XAUUSD', equity: '10000', riskPercent: '1', entry: '2862', stopLoss: '2858', accountCurrency: 'USD' })!.lots).toBe('0.25');
  });
});

describe('quick trade parser', () => {
  it('parses the gold example', () => {
    const p = parseQuickTrade('buy gold 2862 sl 2858 3r 0.5 lot val bounce');
    expect(p.errors).toEqual([]);
    expect(p).toMatchObject({ symbol: 'XAUUSD', side: 'LONG', entryPrice: '2862', stopLoss: '2858', exitPrice: '2874', takeProfit: '2874', lotSize: '0.5', rr: '3', setup: 'VAL Bounce' });
  });
  it('parses the index short example', () => {
    const p = parseQuickTrade('short us500 5880 sl 5890 2.5r 1.0 silver bullet');
    expect(p).toMatchObject({ symbol: 'US500', side: 'SHORT', exitPrice: '5855', lotSize: '1', setup: 'Silver Bullet', errors: [] });
  });
  it('parses fx without lot size', () => {
    const p = parseQuickTrade('long eurusd 1.0850 sl 1.0830 2r');
    expect(p.exitPrice).toBe('1.089');
    expect(p.lotSize).toBe('1');
    expect(p.warnings.length).toBeGreaterThan(0);
  });
  it('parses tp-based crypto example', () => {
    const p = parseQuickTrade('sell btc 62500 sl 63000 tp 61000 0.2');
    expect(p).toMatchObject({ symbol: 'BTCUSDT', side: 'SHORT', exitPrice: '61000', takeProfit: '61000', lotSize: '0.2', rr: '3', errors: [] });
  });
  it('handles losses and tags', () => {
    const p = parseQuickTrade('buy gold 2862 sl 2858 loss 0.5 lot #fomo');
    expect(p.exitPrice).toBe('2858');
    expect(p.mistakeTag).toBe('FOMO_ENTRY');
    expect(p.rr).toBe('-1');
  });
  it('rejects illogical stops', () => {
    expect(parseQuickTrade('buy gold 2862 sl 2870 2r').errors).toContain('Long stop loss must be below entry');
  });
  it('resolves broker suffixes', () => {
    expect(resolveInstrument('XAUUSD.r')?.symbol).toBe('XAUUSD');
    expect(resolveInstrument('US500m')?.symbol).toBe('US500');
  });
});

describe('analytics', () => {
  const trades = [t('1', '300'), t('2', '-100'), t('3', '200'), t('4', '-100')];
  it('computes win rate and profit factor', () => {
    const s = summarize(trades);
    expect(s.winRate).toBe(0.5);
    expect(s.profitFactor).toBe(2.5);
    expect(s.netPnl).toBe('300.00');
    expect(s.payoffRatio).toBe(2.5);
    expect(s.avgWin).toBe('250.00');
    expect(s.avgLoss).toBe('100.00');
  });
  it('builds equity and drawdown', () => {
    const e = equityCurve(trades, '1000');
    expect(e.points.map((p) => p.equity)).toEqual(['1300.00', '1200.00', '1400.00', '1300.00']);
    expect(e.maxDrawdown).toBe('100.00');
    expect(e.maxDrawdownPct).toBeCloseTo(100 / 1300, 5);
  });
  it('computes a conservative discipline leak', () => {
    const leak = disciplineLeak([
      t('1', '300'),
      t('2', '-250', { rulesFollowed: false, mistakeTag: 'MOVED_STOP', riskAmount: '100' }),
      t('3', '-120', { rulesFollowed: false, mistakeTag: 'REVENGE_TRADE' }),
      t('4', '80', { rulesFollowed: false, mistakeTag: 'FOMO_ENTRY' }),
    ]);
    expect(leak.actualPnl).toBe('10.00');
    expect(leak.flawlessPnl).toBe('200.00');
    expect(leak.leak).toBe('190.00');
    expect(leak.violations).toBe(3);
  });
  it('detects tilt', () => {
    const base = Date.parse('2026-08-11T14:05:00Z');
    const losses = [0, 13, 26].map((m, i) => t(String(i + 1), '-100', { executedAt: new Date(base + m * 60000).toISOString() }));
    const status = detectTilt(losses, { lossCount: 3, windowMinutes: 20, cooldownMinutes: 30 }, new Date(base + 30 * 60000));
    expect(status.triggered).toBe(false); // 26 minutes > 20-minute window
    const tight = [0, 8, 15].map((m, i) => t(String(i + 1), '-100', { executedAt: new Date(base + m * 60000).toISOString() }));
    const s2 = detectTilt(tight, { lossCount: 3, windowMinutes: 20, cooldownMinutes: 30 }, new Date(base + 20 * 60000));
    expect(s2.triggered).toBe(true);
    expect(s2.recentLosses).toHaveLength(3);
  });
  it('runs a reproducible Monte Carlo', () => {
    const params = { winRate: 0.5, avgWinR: 2, avgLossR: 1, riskFraction: 0.01, tradesPerPath: 100, paths: 1000, seed: 7 };
    const a = monteCarlo(params);
    const b = monteCarlo(params);
    expect(a.probabilities).toEqual(b.probabilities);
    expect(a.probabilities[0]!.probability).toBeGreaterThanOrEqual(a.probabilities[1]!.probability);
    expect(a.paths).toBe(1000);
    expect(a.medianFinalReturn).toBeGreaterThan(0);
  });
});

describe('sessions & validation & demo', () => {
  it('classifies sessions with DST', () => {
    expect(classifySession(new Date('2026-08-03T08:14:00Z'))).toBe('LONDON');
    expect(classifySession(new Date('2026-08-03T14:02:00Z'))).toBe('LONDON_NY_OVERLAP');
    expect(classifySession(new Date('2026-08-03T18:00:00Z'))).toBe('NEW_YORK');
    expect(classifySession(new Date('2026-08-04T01:00:00Z'))).toBe('ASIA');
  });
  it('validates stop consistency', () => {
    const r = tradeCreateSchema.safeParse({
      tradingAccountId: '6f1c1f9e-8d5e-4c1a-9a1e-2b2b2b2b2b2b',
      executedAt: '2026-09-01T10:00:00Z',
      symbol: 'xauusd',
      side: 'LONG',
      entryPrice: '2862',
      stopLoss: '2870',
      lotSize: '0.5',
    });
    expect(r.success).toBe(false);
  });
  it('generates at least 40 demo trades across Aug–Oct 2026', () => {
    const trades = generateDemoTrades();
    expect(trades.length).toBeGreaterThanOrEqual(40);
    const months = new Set(trades.map((x) => x.executedAt.slice(0, 7)));
    expect([...months].sort()).toEqual(['2026-08', '2026-09', '2026-10']);
    expect(new Set(trades.map((x) => x.symbol))).toEqual(new Set(['XAUUSD', 'US500', 'NAS100', 'EURUSD', 'BTCUSDT', 'USOIL']));
  });
});
