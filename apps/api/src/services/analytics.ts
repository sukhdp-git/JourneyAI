import { and, asc, eq, isNotNull, lt, sql } from 'drizzle-orm';
import {
  bestByExpectancy,
  d,
  dateInTimeZone,
  detectTilt,
  disciplineLeak,
  emotionalVsDisciplined,
  equityCurve,
  getInstrument,
  groupSummaries,
  hourInTimeZone,
  monteCarlo,
  summarize,
  weekdayInTimeZone,
  NEGATIVE_EMOTIONS,
  type AnalyticsTrade,
  type CalendarResponse,
  type DashboardResponse,
  type EdgeMatrixResponse,
  type GroupStat,
  type PerformanceReview,
  type RiskOfRuinResponse,
  type StrategyAnalyticsRow,
  type Emotion,
} from '@journzey/shared';
import type { Database } from '../db/client.js';
import { capitalTransactions, journalEntries, performanceSnapshots, strategies, trades, userSettings } from '../db/schema.js';
import { badRequest } from '../lib/errors.js';
import { dateRangeWhere, resolveScope, tradeScopeWhere, type ResolvedScope } from './scope.js';

const WEEKDAYS = ['SUN', 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT'];

const toGroup = <K extends string>(rows: Array<{ key: K; summary: GroupStat['summary'] }>, label?: (k: K) => string): GroupStat[] =>
  rows.map((r) => ({ key: r.key, ...(label ? { label: label(r.key) } : {}), summary: r.summary }));

/** Shift a YYYY-MM-DD date by n days (pure calendar arithmetic, no time zones involved). */
export function addDays(date: string, n: number): string {
  const dt = new Date(`${date}T00:00:00Z`);
  dt.setUTCDate(dt.getUTCDate() + n);
  return dt.toISOString().slice(0, 10);
}

export function monthBounds(month: string): { from: string; to: string } {
  if (!/^\d{4}-\d{2}$/.test(month)) throw badRequest('Invalid month', { month: ['Expected YYYY-MM'] });
  const [y, m] = month.split('-').map(Number) as [number, number];
  const from = `${month}-01`;
  const last = new Date(Date.UTC(y, m, 0)).getUTCDate();
  return { from, to: `${month}-${String(last).padStart(2, '0')}` };
}

export function weekBounds(date: string): { from: string; to: string } {
  const dow = new Date(`${date}T00:00:00Z`).getUTCDay();
  const mondayOffset = (dow + 6) % 7;
  const from = addDays(date, -mondayOffset);
  return { from, to: addDays(from, 6) };
}

export class AnalyticsService {
  constructor(private readonly db: Database) {}

  async loadTrades(userId: string, scope: ResolvedScope, from?: string, to?: string): Promise<AnalyticsTrade[]> {
    const rows = await this.db
      .select({
        id: trades.id,
        executedAt: trades.executedAt,
        symbol: trades.symbol,
        side: trades.side,
        pnl: trades.pnl,
        rr: trades.rr,
        riskAmount: trades.riskAmount,
        strategyId: trades.strategyId,
        strategyName: strategies.name,
        setupTag: trades.setupTag,
        session: trades.session,
        emotion: trades.emotion,
        mistakeTag: trades.mistakeTag,
        rulesFollowed: trades.rulesFollowed,
      })
      .from(trades)
      .leftJoin(strategies, eq(trades.strategyId, strategies.id))
      .where(and(tradeScopeWhere(userId, scope), eq(trades.status, 'CLOSED'), isNotNull(trades.pnl), ...dateRangeWhere(from, to, scope.timezone)))
      .orderBy(asc(trades.executedAt), asc(trades.id));
    return rows.map((r) => ({ ...r, side: r.side as 'LONG' | 'SHORT' }));
  }

  /** Balance at the start of a range: starting capital + earlier P&L + earlier capital flows. */
  private async openingBalance(userId: string, scope: ResolvedScope, from?: string): Promise<string> {
    if (!from || scope.accountIds.length === 0) return scope.startingCapital;
    const ids = sql.join(scope.accountIds.map((id) => sql`${id}::uuid`), sql`, `);
    const [pnlRow] = await this.db
      .select({ v: sql<string>`COALESCE(SUM(${trades.pnl}), 0)::text` })
      .from(trades)
      .where(and(tradeScopeWhere(userId, scope), eq(trades.status, 'CLOSED'), sql`${trades.executedAt} < ((${from}::date)::timestamp AT TIME ZONE ${scope.timezone})`));
    const [flowRow] = await this.db
      .select({ v: sql<string>`COALESCE(SUM(CASE ${capitalTransactions.type} WHEN 'WITHDRAWAL' THEN -${capitalTransactions.amount} ELSE ${capitalTransactions.amount} END), 0)::text` })
      .from(capitalTransactions)
      .where(
        and(
          eq(capitalTransactions.userId, userId),
          sql`${capitalTransactions.tradingAccountId} IN (${ids})`,
          lt(capitalTransactions.occurredAt, sql`((${from}::date)::timestamp AT TIME ZONE ${scope.timezone})`),
        ),
      );
    return d(scope.startingCapital).plus(pnlRow?.v ?? 0).plus(flowRow?.v ?? 0).toFixed(2);
  }

  private async riskSettings(userId: string) {
    const [s] = await this.db.select().from(userSettings).where(eq(userSettings.userId, userId)).limit(1);
    return s;
  }

  async dashboard(userId: string, q: { account?: string; from?: string; to?: string }): Promise<DashboardResponse> {
    const scope = await resolveScope(this.db, userId, q.account);
    const tz = scope.timezone;
    const list = await this.loadTrades(userId, scope, q.from, q.to);
    const opening = await this.openingBalance(userId, scope, q.from);
    const curve = equityCurve(list, opening);
    const settings = await this.riskSettings(userId);

    const today = dateInTimeZone(new Date(), tz);
    const todayTrades = await this.loadTrades(userId, scope, today, today);
    const todayPnl = todayTrades.reduce((a, t) => a.plus(t.pnl ?? 0), d(0));
    const maxDaily = settings?.maxDailyLoss ?? null;
    const remaining = maxDaily ? d(maxDaily).minus(Math.max(0, todayPnl.negated().toNumber())).toFixed(2) : null;

    const since = new Date(Date.now() - 24 * 3600_000);
    const recent = await this.loadTrades(userId, scope, dateInTimeZone(since, tz));
    const tilt = detectTilt(recent, {
      lossCount: settings?.tiltLossCount ?? 3,
      windowMinutes: settings?.tiltWindowMinutes ?? 20,
      cooldownMinutes: settings?.tiltCooldownMinutes ?? 30,
    });

    return {
      scope: { account: scope.key, from: q.from ?? null, to: q.to ?? null, currency: scope.currency, demo: scope.demo },
      summary: summarize(list),
      equity: { ...curve, startingCapital: opening },
      byWeekday: toGroup(groupSummaries(list, (t) => WEEKDAYS[weekdayInTimeZone(new Date(t.executedAt), tz)]!)).sort(
        (a, b) => WEEKDAYS.indexOf(a.key) - WEEKDAYS.indexOf(b.key),
      ),
      bySession: toGroup(groupSummaries(list, (t) => t.session ?? 'OFF_HOURS')),
      byInstrument: toGroup(groupSummaries(list, (t) => t.symbol), (k) => getInstrument(k)?.displayName ?? k),
      byStrategy: toGroup(groupSummaries(list, (t) => t.strategyName ?? 'Unassigned')),
      tilt,
      today: { pnl: todayPnl.toFixed(2), trades: todayTrades.length, remainingDailyBudget: remaining, maxDailyLoss: maxDaily },
    };
  }

  async calendar(userId: string, q: { account?: string; month: string }): Promise<CalendarResponse> {
    const scope = await resolveScope(this.db, userId, q.account);
    const { from, to } = monthBounds(q.month);
    const day = sql<string>`to_char(${trades.executedAt} AT TIME ZONE ${scope.timezone}, 'YYYY-MM-DD')`;
    const rows = await this.db
      .select({
        date: day,
        pnl: sql<string>`COALESCE(SUM(${trades.pnl}), 0)::text`,
        trades: sql<number>`count(*)::int`,
        wins: sql<number>`count(*) FILTER (WHERE ${trades.pnl} > 0)::int`,
        losses: sql<number>`count(*) FILTER (WHERE ${trades.pnl} < 0)::int`,
      })
      .from(trades)
      .where(and(tradeScopeWhere(userId, scope), eq(trades.status, 'CLOSED'), ...dateRangeWhere(from, to, scope.timezone)))
      // Positional GROUP BY: the time-zone parameter makes the expression text differ per placeholder.
      .groupBy(sql`1`)
      .orderBy(sql`1`);
    const days = rows.map((r) => ({ ...r, pnl: d(r.pnl).toFixed(2) }));
    const weeks = new Map<string, { pnl: ReturnType<typeof d>; trades: number; wins: number; losses: number }>();
    for (const r of days) {
      const ws = weekBounds(r.date).from;
      const w = weeks.get(ws) ?? { pnl: d(0), trades: 0, wins: 0, losses: 0 };
      w.pnl = w.pnl.plus(r.pnl);
      w.trades += r.trades;
      w.wins += r.wins;
      w.losses += r.losses;
      weeks.set(ws, w);
    }
    return {
      month: q.month,
      days,
      weeks: [...weeks.entries()]
        .sort(([a], [b]) => a.localeCompare(b))
        .map(([weekStart, w]) => ({ weekStart, pnl: w.pnl.toFixed(2), trades: w.trades, wins: w.wins, losses: w.losses })),
      monthTotal: {
        pnl: days.reduce((a, r) => a.plus(r.pnl), d(0)).toFixed(2),
        trades: days.reduce((a, r) => a + r.trades, 0),
      },
      currency: scope.currency,
    };
  }

  async strategies(userId: string, q: { account?: string; from?: string; to?: string }): Promise<{ currency: string; rows: StrategyAnalyticsRow[] }> {
    const scope = await resolveScope(this.db, userId, q.account);
    const list = await this.loadTrades(userId, scope, q.from, q.to);
    const all = await this.db
      .select({ id: strategies.id, name: strategies.name, targetRr: strategies.targetRr })
      .from(strategies)
      .where(eq(strategies.userId, userId));
    const grouped = new Map(groupSummaries(list, (t) => t.strategyId ?? 'unassigned').map((g) => [g.key, g.summary]));
    const rows: StrategyAnalyticsRow[] = all.map((s) => ({
      strategyId: s.id,
      name: s.name,
      targetRr: s.targetRr,
      summary: grouped.get(s.id) ?? summarize([]),
    }));
    if (grouped.has('unassigned')) rows.push({ strategyId: null, name: 'Unassigned', targetRr: null, summary: grouped.get('unassigned')! });
    rows.sort((a, b) => b.summary.trades - a.summary.trades || d(b.summary.netPnl).comparedTo(a.summary.netPnl));
    return { currency: scope.currency, rows };
  }

  async sessions(userId: string, q: { account?: string; from?: string; to?: string }) {
    const scope = await resolveScope(this.db, userId, q.account);
    const list = await this.loadTrades(userId, scope, q.from, q.to);
    return {
      currency: scope.currency,
      sessions: toGroup(groupSummaries(list, (t) => t.session ?? 'OFF_HOURS')),
      hours: toGroup(groupSummaries(list, (t) => String(hourInTimeZone(new Date(t.executedAt), scope.timezone)).padStart(2, '0'))).sort((a, b) =>
        a.key.localeCompare(b.key),
      ),
    };
  }

  async discipline(userId: string, q: { account?: string; from?: string; to?: string }) {
    const scope = await resolveScope(this.db, userId, q.account);
    const list = await this.loadTrades(userId, scope, q.from, q.to);
    const s = summarize(list);
    return {
      currency: scope.currency,
      leak: disciplineLeak(list),
      ruleCompliance: s.ruleCompliance,
      trades: s.trades,
      ...emotionalVsDisciplined(list),
    };
  }

  async edgeMatrix(userId: string, q: { account?: string; from?: string; to?: string }): Promise<EdgeMatrixResponse & { currency: string }> {
    const scope = await resolveScope(this.db, userId, q.account);
    let { from, to } = q;
    let label = 'Custom range';
    if (!from && !to) {
      // Default: previous calendar month in the user's time zone.
      const today = dateInTimeZone(new Date(), scope.timezone);
      const firstThis = `${today.slice(0, 7)}-01`;
      const prev = monthBounds(addDays(firstThis, -1).slice(0, 7));
      from = prev.from;
      to = prev.to;
      label = `Previous month (${prev.from.slice(0, 7)})`;
    }
    const list = await this.loadTrades(userId, scope, from, to);
    const tz = scope.timezone;
    const byStrategy = toGroup(groupSummaries(list, (t) => t.strategyName ?? 'Unassigned'));
    const bySession = toGroup(groupSummaries(list, (t) => t.session ?? 'OFF_HOURS'));
    const byInstrument = toGroup(groupSummaries(list, (t) => t.symbol));
    const byHour = toGroup(groupSummaries(list, (t) => String(hourInTimeZone(new Date(t.executedAt), tz)).padStart(2, '0'))).sort((a, b) =>
      a.key.localeCompare(b.key),
    );
    const byMistake = toGroup(groupSummaries(list, (t) => (t.mistakeTag && t.mistakeTag !== 'NONE' ? t.mistakeTag : t.rulesFollowed ? 'CLEAN' : 'UNTAGGED_VIOLATION')));
    const byEmotion = toGroup(groupSummaries(list, (t) => t.emotion ?? 'UNSPECIFIED'));
    const byWeekday = toGroup(groupSummaries(list, (t) => WEEKDAYS[weekdayInTimeZone(new Date(t.executedAt), tz)]!));
    const bySetup = toGroup(groupSummaries(list, (t) => t.setupTag ?? t.strategyName ?? null));
    return {
      currency: scope.currency,
      period: { from: from ?? '', to: to ?? '', label },
      byStrategy,
      bySession,
      byInstrument,
      byHour,
      byMistake,
      byEmotion,
      bestWindow: {
        hour: bestByExpectancy(byHour),
        session: bestByExpectancy(bySession),
        instrument: bestByExpectancy(byInstrument),
        setup: bestByExpectancy(bySetup),
        weekday: bestByExpectancy(byWeekday),
        note: 'Highest historical expectancy among groups with at least 3 trades. Historical performance does not guarantee future results.',
      },
      disciplinedVsEmotional: emotionalVsDisciplined(list),
    };
  }

  async riskOfRuin(
    userId: string,
    q: { account?: string; from?: string; to?: string; riskPercent: number; paths: number; trades?: number },
  ): Promise<RiskOfRuinResponse> {
    const scope = await resolveScope(this.db, userId, q.account);
    const list = await this.loadTrades(userId, scope, q.from, q.to);
    const rs = list
      .map((t) => (t.rr !== null ? Number(t.rr) : t.riskAmount && Number(t.riskAmount) !== 0 ? Number(t.pnl) / Number(t.riskAmount) : null))
      .filter((r): r is number => r !== null && Number.isFinite(r));
    const wins = rs.filter((r) => r > 0);
    const losses = rs.filter((r) => r < 0);
    const firstTs = list[0] ? new Date(list[0].executedAt).getTime() : Date.now();
    const lastTs = list.at(-1) ? new Date(list.at(-1)!.executedAt).getTime() : Date.now();
    const weeks = Math.max(1, (lastTs - firstTs) / (7 * 24 * 3600_000));
    const tradesPerWeek = list.length / weeks;
    const tradesPerPath = q.trades ?? Math.max(20, Math.min(2000, Math.round(tradesPerWeek * 13)));
    const inputs = {
      winRate: rs.length ? wins.length / rs.length : 0,
      avgWinR: wins.length ? wins.reduce((a, b) => a + b, 0) / wins.length : 0,
      avgLossR: losses.length ? Math.abs(losses.reduce((a, b) => a + b, 0) / losses.length) : 1,
      riskPercent: q.riskPercent,
      tradesPerPath,
      sampleSize: rs.length,
      tradesPerWeek: Number(tradesPerWeek.toFixed(2)),
    };
    if (rs.length < 10) return { inputs, result: null, insufficientData: true };
    const result = monteCarlo({
      winRate: inputs.winRate,
      avgWinR: inputs.avgWinR,
      avgLossR: inputs.avgLossR,
      riskFraction: q.riskPercent / 100,
      tradesPerPath,
      paths: q.paths,
      seed: 1337,
    });
    return { inputs, result, insufficientData: false };
  }

  async review(
    userId: string,
    q: { period: 'week' | 'month'; anchorDate?: string; account?: string },
  ): Promise<PerformanceReview> {
    const scope = await resolveScope(this.db, userId, q.account);
    const anchor = q.anchorDate ?? dateInTimeZone(new Date(), scope.timezone);
    const { from, to } = q.period === 'week' ? weekBounds(anchor) : monthBounds(anchor.slice(0, 7));
    const list = await this.loadTrades(userId, scope, from, to);
    const summary = summarize(list);
    const byStrategy = toGroup(groupSummaries(list, (t) => t.strategyName ?? 'Unassigned'));
    const bySession = toGroup(groupSummaries(list, (t) => t.session ?? 'OFF_HOURS'));
    const leak = disciplineLeak(list);
    const topMistakes = toGroup(groupSummaries(list.filter((t) => t.mistakeTag && t.mistakeTag !== 'NONE'), (t) => t.mistakeTag!)).sort(
      (a, b) => b.summary.trades - a.summary.trades,
    );
    const journals = await this.db
      .select()
      .from(journalEntries)
      .where(
        and(
          eq(journalEntries.userId, userId),
          eq(journalEntries.demo, scope.sampleData),
          sql`${journalEntries.journalDate} BETWEEN ${from}::date AND ${to}::date`,
        ),
      );
    const emotionCounts = new Map<string, number>();
    for (const j of journals) if (j.emotionalState) emotionCounts.set(j.emotionalState, (emotionCounts.get(j.emotionalState) ?? 0) + 1);
    const ratings = journals.map((j) => j.disciplineRating).filter((r): r is number => r !== null);
    const sortedByPnl = [...byStrategy].filter((s) => s.summary.trades > 0).sort((a, b) => d(b.summary.netPnl).comparedTo(a.summary.netPnl));

    const risk: string[] = [];
    const settings = await this.riskSettings(userId);
    const riskAmounts = list.map((t) => t.riskAmount).filter((r): r is string => !!r).map(Number);
    if (riskAmounts.length) {
      const avg = riskAmounts.reduce((a, b) => a + b, 0) / riskAmounts.length;
      const max = Math.max(...riskAmounts);
      risk.push(`Average planned risk per trade: ${avg.toFixed(2)} ${scope.currency}; largest: ${max.toFixed(2)} ${scope.currency}.`);
      if (max > avg * 2) risk.push('At least one trade risked more than twice your average — check position sizing consistency.');
    } else risk.push('No stop-loss data recorded — risk per trade cannot be measured.');
    const noStop = list.filter((t) => t.mistakeTag === 'NO_STOP' || t.mistakeTag === 'MOVED_STOP').length;
    if (noStop) risk.push(`${noStop} trade(s) had a missing or moved stop.`);
    if (settings?.maxDailyLoss) {
      const daily = new Map<string, number>();
      for (const t of list) {
        const day = dateInTimeZone(new Date(t.executedAt), scope.timezone);
        daily.set(day, (daily.get(day) ?? 0) + Number(t.pnl));
      }
      const breaches = [...daily.values()].filter((v) => v < -Number(settings.maxDailyLoss)).length;
      if (breaches) risk.push(`Max daily loss limit breached on ${breaches} day(s).`);
    }

    const plan: string[] = [];
    const worstMistake = leak.byMistake[0];
    if (worstMistake && d(worstMistake.leak).gt(0)) plan.push(`Eliminate ${worstMistake.mistake.replace(/_/g, ' ').toLowerCase()} — it cost approximately ${worstMistake.leak} ${scope.currency}.`);
    const bestSession = bestByExpectancy(bySession);
    if (bestSession) plan.push(`Concentrate on the ${bestSession.key.replace(/_/g, ' ').toLowerCase()} session, your highest historical expectancy window.`);
    if (sortedByPnl[0]) plan.push(`Prioritise ${sortedByPnl[0].key}; review or reduce size on ${sortedByPnl.at(-1)!.key} if it remains negative.`);
    if (summary.ruleCompliance !== null && summary.ruleCompliance < 0.8) plan.push('Raise rule compliance above 80% — run the 9-step checklist before every session.');
    if (journals.length < 3) plan.push('Journal at least three sessions per week to improve pattern detection.');
    if (plan.length === 0) plan.push('Keep executing the current plan; no material leaks detected this period.');

    const pct = (v: number | null) => (v === null ? 'n/a' : `${(v * 100).toFixed(1)}%`);
    const label = q.period === 'week' ? `Week of ${from}` : `Month ${from.slice(0, 7)}`;
    const commonEmotions = [...emotionCounts.entries()].sort((a, b) => b[1] - a[1]).map(([emotion, count]) => ({ emotion, count }));
    const lessons = journals.map((j) => j.keyLesson).filter((l): l is string => !!l).slice(0, 5);
    const lines = [
      `journzey.ai ${q.period === 'week' ? 'Weekly' : 'Monthly'} Review — ${label} (${from} → ${to})${scope.demo ? ' [DEMO DATA]' : ''}`,
      '',
      `Net P&L: ${summary.netPnl} ${scope.currency} | Trades: ${summary.trades} | Win rate: ${pct(summary.winRate)} | Profit factor: ${summary.profitFactor ?? 'n/a'}`,
      `Best strategy: ${sortedByPnl[0]?.key ?? 'n/a'} | Worst strategy: ${sortedByPnl.at(-1)?.key ?? 'n/a'} | Best session: ${bestSession?.key ?? 'n/a'}`,
      `Discipline rate: ${pct(summary.ruleCompliance)} | Estimated discipline leak: ${leak.leak} ${scope.currency} across ${leak.violations} violation(s)`,
      `Emotional leaks: ${topMistakes.map((m) => `${m.key} ×${m.summary.trades}`).join(', ') || 'none tagged'}`,
      `Journal: ${journals.length} entr${journals.length === 1 ? 'y' : 'ies'}; avg discipline ${ratings.length ? (ratings.reduce((a, b) => a + b, 0) / ratings.length).toFixed(1) : 'n/a'}/10; common states: ${commonEmotions.map((e) => e.emotion).slice(0, 3).join(', ') || 'n/a'}`,
      '',
      'Risk observations:',
      ...risk.map((r) => `• ${r}`),
      '',
      'Next-period action plan:',
      ...plan.map((p, i) => `${i + 1}. ${p}`),
      '',
      'Historical analysis only — not a forecast or financial advice.',
    ];

    const review: PerformanceReview = {
      period: q.period,
      label,
      from,
      to,
      currency: scope.currency,
      summary,
      bestStrategy: sortedByPnl[0] ?? null,
      worstStrategy: sortedByPnl.at(-1) ?? null,
      bestSession,
      disciplineRate: summary.ruleCompliance,
      leak,
      topMistakes,
      journal: {
        entries: journals.length,
        avgDiscipline: ratings.length ? ratings.reduce((a, b) => a + b, 0) / ratings.length : null,
        commonEmotions,
        lessons,
      },
      riskObservations: risk,
      actionPlan: plan,
      text: lines.join('\n'),
    };

    // Cache the computed review metrics (performance_snapshots) for history/AI context.
    await this.db
      .insert(performanceSnapshots)
      .values({ userId, kind: `review_${q.period}`, scope: scope.key, periodStart: from, periodEnd: to, metrics: review as unknown as Record<string, unknown> })
      .onConflictDoUpdate({
        target: [performanceSnapshots.userId, performanceSnapshots.kind, performanceSnapshots.scope, performanceSnapshots.periodStart, performanceSnapshots.periodEnd],
        set: { metrics: review as unknown as Record<string, unknown>, computedAt: new Date() },
      });
    return review;
  }

  /** Compact, aggregated context for the AI Coach — never raw database dumps. */
  async aiContext(userId: string, account?: string) {
    const scope = await resolveScope(this.db, userId, account);
    const list = await this.loadTrades(userId, scope);
    const tz = scope.timezone;
    const today = dateInTimeZone(new Date(), tz);
    const thisMonth = monthBounds(today.slice(0, 7));
    const lastMonth = monthBounds(addDays(thisMonth.from, -1).slice(0, 7));
    const inRange = (t: AnalyticsTrade, r: { from: string; to: string }) => {
      const day = dateInTimeZone(new Date(t.executedAt), tz);
      return day >= r.from && day <= r.to;
    };
    const compact = (rows: GroupStat[], n = 8) =>
      rows.slice(0, n).map((g) => ({
        key: g.key,
        trades: g.summary.trades,
        winRate: g.summary.winRate === null ? null : Number(g.summary.winRate.toFixed(3)),
        netPnl: g.summary.netPnl,
        profitFactor: g.summary.profitFactor,
        avgR: g.summary.avgR === null ? null : Number(g.summary.avgR.toFixed(2)),
        compliance: g.summary.ruleCompliance === null ? null : Number(g.summary.ruleCompliance.toFixed(2)),
      }));
    const journals = await this.db
      .select({
        date: journalEntries.journalDate,
        emotionalState: journalEntries.emotionalState,
        discipline: journalEntries.disciplineRating,
        compliance: journalEntries.compliance,
        lesson: journalEntries.keyLesson,
        reflection: journalEntries.reflection,
      })
      .from(journalEntries)
      .where(and(eq(journalEntries.userId, userId), eq(journalEntries.demo, scope.sampleData)))
      .orderBy(sql`${journalEntries.journalDate} DESC`)
      .limit(12);
    return {
      scope: { account: scope.key, currency: scope.currency, demoData: scope.sampleData, timezone: tz },
      overall: summarize(list),
      thisMonth: summarize(list.filter((t) => inRange(t, thisMonth))),
      lastMonth: summarize(list.filter((t) => inRange(t, lastMonth))),
      byStrategy: compact(toGroup(groupSummaries(list, (t) => t.strategyName ?? 'Unassigned'))),
      bySession: compact(toGroup(groupSummaries(list, (t) => t.session ?? 'OFF_HOURS'))),
      byInstrument: compact(toGroup(groupSummaries(list, (t) => t.symbol))),
      byMistake: compact(toGroup(groupSummaries(list, (t) => t.mistakeTag ?? 'NONE'))),
      byEmotion: compact(toGroup(groupSummaries(list, (t) => t.emotion ?? 'UNSPECIFIED'))),
      disciplineLeak: (({ actualPnl, flawlessPnl, leak, violations, byMistake }) => ({ actualPnl, flawlessPnl, leak, violations, byMistake }))(disciplineLeak(list)),
      disciplinedVsEmotional: emotionalVsDisciplined(list),
      negativeEmotionTrades: list.filter((t) => t.emotion && NEGATIVE_EMOTIONS.includes(t.emotion as Emotion)).length,
      recentJournal: journals.map((j) => ({ ...j, reflection: j.reflection ? j.reflection.slice(0, 400) : null })),
    };
  }
}
