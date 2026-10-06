import { d, Decimal } from './calc.js';
import { MISTAKE_LEAK_TREATMENT, NEGATIVE_EMOTIONS, type Emotion, type MistakeTag } from './constants.js';

/** Minimal closed-trade shape used by all analytics. Money values are decimal strings. */
export interface AnalyticsTrade {
  id: string;
  executedAt: string | Date;
  symbol: string;
  side: 'LONG' | 'SHORT';
  pnl: string | null;
  rr: string | null;
  riskAmount: string | null;
  strategyId: string | null;
  strategyName?: string | null;
  setupTag?: string | null;
  session: string | null;
  emotion: string | null;
  mistakeTag: string | null;
  rulesFollowed: boolean;
}

export interface PerformanceSummary {
  trades: number;
  wins: number;
  losses: number;
  breakeven: number;
  winRate: number | null;
  netPnl: string;
  grossProfit: string;
  grossLoss: string;
  profitFactor: number | null;
  avgWin: string | null;
  avgLoss: string | null;
  payoffRatio: number | null;
  expectancy: string | null;
  avgR: number | null;
  bestTrade: string | null;
  worstTrade: string | null;
  ruleCompliance: number | null;
}

const closed = (trades: readonly AnalyticsTrade[]) => trades.filter((t) => t.pnl !== null && t.pnl !== undefined);
const round = (n: Decimal, dp = 2) => n.toDecimalPlaces(dp).toFixed(dp);

export function summarize(all: readonly AnalyticsTrade[]): PerformanceSummary {
  const trades = closed(all);
  let gp = d(0);
  let gl = d(0);
  let wins = 0;
  let losses = 0;
  let be = 0;
  let best: Decimal | null = null;
  let worst: Decimal | null = null;
  let rSum = d(0);
  let rCount = 0;
  let compliant = 0;
  for (const t of trades) {
    const p = d(t.pnl!);
    if (p.gt(0)) {
      wins++;
      gp = gp.plus(p);
    } else if (p.lt(0)) {
      losses++;
      gl = gl.plus(p.abs());
    } else be++;
    if (!best || p.gt(best)) best = p;
    if (!worst || p.lt(worst)) worst = p;
    if (t.rr !== null && t.rr !== undefined) {
      rSum = rSum.plus(t.rr);
      rCount++;
    }
    if (t.rulesFollowed) compliant++;
  }
  const n = trades.length;
  const net = gp.minus(gl);
  const avgWin = wins ? gp.dividedBy(wins) : null;
  const avgLoss = losses ? gl.dividedBy(losses) : null;
  return {
    trades: n,
    wins,
    losses,
    breakeven: be,
    winRate: n ? wins / n : null,
    netPnl: round(net),
    grossProfit: round(gp),
    grossLoss: round(gl),
    profitFactor: gl.isZero() ? null : gp.dividedBy(gl).toDecimalPlaces(4).toNumber(),
    avgWin: avgWin ? round(avgWin) : null,
    avgLoss: avgLoss ? round(avgLoss) : null,
    payoffRatio: avgWin && avgLoss && !avgLoss.isZero() ? avgWin.dividedBy(avgLoss).toDecimalPlaces(4).toNumber() : null,
    expectancy: n ? round(net.dividedBy(n)) : null,
    avgR: rCount ? rSum.dividedBy(rCount).toDecimalPlaces(4).toNumber() : null,
    bestTrade: best ? round(best) : null,
    worstTrade: worst ? round(worst) : null,
    ruleCompliance: n ? compliant / n : null,
  };
}

export interface EquityPoint {
  tradeId: string;
  executedAt: string;
  pnl: string;
  cumulativePnl: string;
  equity: string;
  peak: string;
  drawdown: string;
  drawdownPct: number;
}

/**
 * Equity curve + drawdown from the starting balance. Trades must be sorted ascending by time.
 * Capital flows (deposits/withdrawals) can be folded in by passing them as pseudo-trades with
 * the flow amount as pnl; the caller decides whether they count as performance.
 */
export function equityCurve(
  sortedTrades: readonly AnalyticsTrade[],
  startingCapital: string,
): { points: EquityPoint[]; maxDrawdown: string; maxDrawdownPct: number } {
  let equity = d(startingCapital);
  let peak = equity;
  let cum = d(0);
  let maxDd = d(0);
  let maxDdPct = 0;
  const points: EquityPoint[] = [];
  for (const t of closed(sortedTrades)) {
    const p = d(t.pnl!);
    cum = cum.plus(p);
    equity = equity.plus(p);
    if (equity.gt(peak)) peak = equity;
    const dd = peak.minus(equity);
    const ddPct = peak.gt(0) ? dd.dividedBy(peak).toNumber() : 0;
    if (dd.gt(maxDd)) maxDd = dd;
    if (ddPct > maxDdPct) maxDdPct = ddPct;
    points.push({
      tradeId: t.id,
      executedAt: new Date(t.executedAt).toISOString(),
      pnl: round(p),
      cumulativePnl: round(cum),
      equity: round(equity),
      peak: round(peak),
      drawdown: round(dd),
      drawdownPct: Number(ddPct.toFixed(6)),
    });
  }
  return { points, maxDrawdown: round(maxDd), maxDrawdownPct: Number(maxDdPct.toFixed(6)) };
}

export function groupSummaries<K extends string>(
  trades: readonly AnalyticsTrade[],
  keyOf: (t: AnalyticsTrade) => K | null | undefined,
): Array<{ key: K; summary: PerformanceSummary }> {
  const groups = new Map<K, AnalyticsTrade[]>();
  for (const t of closed(trades)) {
    const k = keyOf(t);
    if (k === null || k === undefined) continue;
    const arr = groups.get(k);
    if (arr) arr.push(t);
    else groups.set(k, [t]);
  }
  return [...groups.entries()]
    .map(([key, ts]) => ({ key, summary: summarize(ts) }))
    .sort((a, b) => d(b.summary.netPnl).comparedTo(a.summary.netPnl));
}

export interface DisciplineLeakResult {
  actualPnl: string;
  flawlessPnl: string;
  leak: string;
  violations: number;
  byMistake: Array<{ mistake: string; trades: number; actualPnl: string; flawlessPnl: string; leak: string }>;
  methodology: string;
}

/**
 * Discipline Leak = Flawless Execution P&L − Actual P&L.
 * The flawless hypothetical is deliberately conservative: it never invents upside.
 * See MISTAKE_LEAK_TREATMENT for how each violation is treated.
 */
export function disciplineLeak(all: readonly AnalyticsTrade[]): DisciplineLeakResult {
  const trades = closed(all);
  let actual = d(0);
  let flawless = d(0);
  let violations = 0;
  const per = new Map<string, { trades: number; actual: Decimal; flawless: Decimal }>();
  for (const t of trades) {
    const p = d(t.pnl!);
    actual = actual.plus(p);
    let hypothetical = p;
    const isViolation = !t.rulesFollowed || (t.mistakeTag && t.mistakeTag !== 'NONE');
    if (isViolation) {
      violations++;
      const mistake = (t.mistakeTag && t.mistakeTag !== 'NONE' ? t.mistakeTag : 'IGNORED_PLAN') as MistakeTag;
      const treatment = MISTAKE_LEAK_TREATMENT[mistake] ?? 'ACTUAL';
      if (treatment === 'SKIP') hypothetical = d(0);
      else if (treatment === 'CAP_LOSS' && p.lt(0) && t.riskAmount) {
        const cap = d(t.riskAmount).abs().negated();
        hypothetical = Decimal.max(p, cap);
      }
      const bucket = per.get(mistake) ?? { trades: 0, actual: d(0), flawless: d(0) };
      bucket.trades++;
      bucket.actual = bucket.actual.plus(p);
      bucket.flawless = bucket.flawless.plus(hypothetical);
      per.set(mistake, bucket);
    }
    flawless = flawless.plus(hypothetical);
  }
  return {
    actualPnl: round(actual),
    flawlessPnl: round(flawless),
    leak: round(flawless.minus(actual)),
    violations,
    byMistake: [...per.entries()]
      .map(([mistake, b]) => ({
        mistake,
        trades: b.trades,
        actualPnl: round(b.actual),
        flawlessPnl: round(b.flawless),
        leak: round(b.flawless.minus(b.actual)),
      }))
      .sort((a, b) => d(b.leak).comparedTo(a.leak)),
    methodology:
      'Rule-violating trades are re-scored conservatively: impulse entries (FOMO, revenge, chasing, overtrading, news gambles, ignored plan) are treated as not taken; stop/size violations have losses capped at the planned 1R risk; all other trades keep their actual result. No hypothetical profits are added.',
  };
}

export function emotionalVsDisciplined(all: readonly AnalyticsTrade[]) {
  const trades = closed(all);
  const disciplined = trades.filter(
    (t) => t.rulesFollowed && (!t.emotion || !NEGATIVE_EMOTIONS.includes(t.emotion as Emotion)),
  );
  const emotional = trades.filter(
    (t) => !t.rulesFollowed || (t.emotion !== null && NEGATIVE_EMOTIONS.includes(t.emotion as Emotion)),
  );
  return { disciplined: summarize(disciplined), emotional: summarize(emotional) };
}

/* ------------------------------------------------------------------ */
/* Monte Carlo risk of ruin                                             */
/* ------------------------------------------------------------------ */

export interface MonteCarloParams {
  winRate: number;
  /** Average win expressed in R (multiples of risk). */
  avgWinR: number;
  /** Average loss expressed in R (positive number, typically ~1). */
  avgLossR: number;
  /** Fraction of equity risked per trade, e.g. 0.01 for 1%. */
  riskFraction: number;
  tradesPerPath: number;
  paths: number;
  seed?: number;
  drawdownThresholds?: number[];
}

export interface MonteCarloResult {
  paths: number;
  tradesPerPath: number;
  probabilities: Array<{ drawdown: number; probability: number }>;
  medianFinalReturn: number;
  p5FinalReturn: number;
  p95FinalReturn: number;
  medianMaxDrawdown: number;
  /** Percentile bands of equity (start = 1.0) at evenly spaced steps, for charting. */
  bands: Array<{ step: number; p5: number; p50: number; p95: number }>;
  disclaimer: string;
}

/** Deterministic PRNG (mulberry32) so simulations are reproducible and testable. */
export function mulberry32(seed: number): () => number {
  let a = seed >>> 0;
  return () => {
    a = (a + 0x6d2b79f5) >>> 0;
    let t = a;
    t = Math.imul(t ^ (t >>> 15), t | 1);
    t ^= t + Math.imul(t ^ (t >>> 7), t | 61);
    return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
  };
}

const pct = (sorted: number[], p: number) => {
  if (sorted.length === 0) return 0;
  const idx = Math.min(sorted.length - 1, Math.max(0, Math.round((sorted.length - 1) * p)));
  return sorted[idx]!;
};

export function monteCarlo(params: MonteCarloParams): MonteCarloResult {
  const thresholds = params.drawdownThresholds ?? [0.1, 0.25, 0.5];
  const rand = mulberry32(params.seed ?? 42);
  const paths = Math.max(1, Math.floor(params.paths));
  const n = Math.max(1, Math.floor(params.tradesPerPath));
  const hits = thresholds.map(() => 0);
  const finals: number[] = new Array(paths);
  const maxDds: number[] = new Array(paths);
  const bandSteps = Math.min(n, 50);
  const stepEvery = n / bandSteps;
  const snapshots: number[][] = Array.from({ length: bandSteps + 1 }, () => new Array<number>(paths));

  for (let p = 0; p < paths; p++) {
    let equity = 1;
    let peak = 1;
    let maxDd = 0;
    snapshots[0]![p] = 1;
    let nextSnap = 1;
    for (let k = 1; k <= n; k++) {
      const win = rand() < params.winRate;
      const r = win ? params.avgWinR : -params.avgLossR;
      equity = Math.max(0, equity * (1 + params.riskFraction * r));
      if (equity > peak) peak = equity;
      const dd = peak > 0 ? (peak - equity) / peak : 1;
      if (dd > maxDd) maxDd = dd;
      while (nextSnap <= bandSteps && k >= Math.round(nextSnap * stepEvery)) {
        snapshots[nextSnap]![p] = equity;
        nextSnap++;
      }
    }
    finals[p] = equity - 1;
    maxDds[p] = maxDd;
    thresholds.forEach((th, i) => {
      if (maxDd >= th) hits[i]!++;
    });
  }

  finals.sort((a, b) => a - b);
  maxDds.sort((a, b) => a - b);
  const bands = snapshots.map((vals, i) => {
    const s = [...vals].sort((a, b) => a - b);
    return { step: Math.round(i * stepEvery), p5: pct(s, 0.05), p50: pct(s, 0.5), p95: pct(s, 0.95) };
  });

  return {
    paths,
    tradesPerPath: n,
    probabilities: thresholds.map((th, i) => ({ drawdown: th, probability: hits[i]! / paths })),
    medianFinalReturn: pct(finals, 0.5),
    p5FinalReturn: pct(finals, 0.05),
    p95FinalReturn: pct(finals, 0.95),
    medianMaxDrawdown: pct(maxDds, 0.5),
    bands,
    disclaimer: 'Statistical estimate based on historical assumptions — not a prediction.',
  };
}

/* ------------------------------------------------------------------ */
/* Tilt circuit breaker                                                 */
/* ------------------------------------------------------------------ */

export interface TiltRule {
  lossCount: number;
  windowMinutes: number;
  cooldownMinutes: number;
}

export interface TiltStatus {
  triggered: boolean;
  triggeredAt: string | null;
  cooldownEndsAt: string | null;
  recentLosses: Array<{ id: string; executedAt: string; symbol: string; pnl: string }>;
}

/**
 * Detects `lossCount` losing trades within `windowMinutes`. The breaker is active while the
 * cooldown that started at the last loss of the triggering cluster has not expired.
 */
export function detectTilt(all: readonly AnalyticsTrade[], rule: TiltRule, now: Date = new Date()): TiltStatus {
  const losses = closed(all)
    .filter((t) => d(t.pnl!).lt(0))
    .map((t) => ({ ...t, ts: new Date(t.executedAt).getTime() }))
    .sort((a, b) => a.ts - b.ts);
  const windowMs = rule.windowMinutes * 60_000;
  let trigger: { at: number; cluster: typeof losses } | null = null;
  for (let i = rule.lossCount - 1; i < losses.length; i++) {
    const first = losses[i - rule.lossCount + 1]!;
    const last = losses[i]!;
    if (last.ts - first.ts <= windowMs) {
      trigger = { at: last.ts, cluster: losses.slice(i - rule.lossCount + 1, i + 1) };
    }
  }
  if (!trigger) return { triggered: false, triggeredAt: null, cooldownEndsAt: null, recentLosses: [] };
  const cooldownEnd = trigger.at + rule.cooldownMinutes * 60_000;
  const active = now.getTime() < cooldownEnd && now.getTime() >= trigger.at;
  return {
    triggered: active,
    triggeredAt: new Date(trigger.at).toISOString(),
    cooldownEndsAt: new Date(cooldownEnd).toISOString(),
    recentLosses: trigger.cluster.map((t) => ({
      id: t.id,
      executedAt: new Date(t.executedAt).toISOString(),
      symbol: t.symbol,
      pnl: t.pnl!,
    })),
  };
}

/** Pick the best group by expectancy, requiring a minimum sample size. */
export function bestByExpectancy<K extends string>(
  groups: Array<{ key: K; summary: PerformanceSummary }>,
  minTrades = 3,
): { key: K; summary: PerformanceSummary } | null {
  const eligible = groups.filter((g) => g.summary.trades >= minTrades && g.summary.expectancy !== null);
  if (eligible.length === 0) return null;
  return eligible.reduce((best, g) => (d(g.summary.expectancy!).gt(best.summary.expectancy!) ? g : best));
}
