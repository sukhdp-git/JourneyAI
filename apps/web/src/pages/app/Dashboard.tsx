import { useMemo } from 'react';
import { useQuery } from '@tanstack/react-query';
import type { DashboardResponse } from '@journzey/shared';
import { EmptyState, ErrorState, Panel, Pnl, Spinner, Stat } from '../../components/ui';
import { DateRangeFilter, PageHeader, TiltPanel } from '../../components/common';
import { DrawdownChart, EquityChart, GroupBarChart } from '../../components/charts';
import { get } from '../../lib/api';
import { fmtDate, fmtMoney, fmtNum, fmtPct, pnlTone } from '../../lib/format';
import { qk, useScope, useSettings } from '../../lib/queries';
import { useDateRange } from '../../lib/dateRange';
import { useI18n } from '../../lib/i18n';

export default function Dashboard() {
  const { t } = useI18n();
  const settings = useSettings();
  const { scope } = useScope();
  const { range } = useDateRange();
  const params = { account: scope, from: range.from, to: range.to };
  const q = useQuery({ queryKey: qk.dashboard(params), queryFn: () => get<DashboardResponse>('/dashboard', params) });
  const d = q.data;
  const cur = d?.scope.currency ?? settings.baseCurrency;
  const points = useMemo(
    () =>
      (d?.equity.points ?? []).map((p) => ({
        label: fmtDate(p.executedAt, settings.timezone, { month: 'short', day: '2-digit' }),
        equity: Number(p.equity),
        cumulative: Number(p.cumulativePnl),
        drawdownPct: p.drawdownPct,
      })),
    [d, settings.timezone],
  );

  return (
    <>
      <PageHeader title={t('nav.dashboard')} subtitle="Performance computed server-side from your stored trades" demo={d?.scope.demo} actions={<DateRangeFilter />} />
      {q.isLoading ? (
        <Spinner />
      ) : q.isError ? (
        <ErrorState error={q.error} onRetry={() => q.refetch()} />
      ) : !d ? null : (
        <>
          <TiltPanel tilt={d.tilt} remainingBudget={d.today.remainingDailyBudget} currency={cur} />
          <div className="grid grid-cols-2 gap-2 sm:grid-cols-3 xl:grid-cols-5">
            <Stat label={t('stats.netPnl')} value={<Pnl value={d.summary.netPnl} currency={cur} />} />
            <Stat label={t('stats.winRate')} value={fmtPct(d.summary.winRate)} sub={`${d.summary.wins}W / ${d.summary.losses}L`} />
            <Stat label={t('stats.profitFactor')} value={d.summary.profitFactor === null ? (d.summary.wins ? '∞' : '—') : fmtNum(d.summary.profitFactor)} />
            <Stat label={t('stats.avgWin')} value={fmtMoney(d.summary.avgWin, cur)} tone="text-profit" />
            <Stat label={t('stats.avgLoss')} value={d.summary.avgLoss ? `−${fmtMoney(d.summary.avgLoss, cur)}` : '—'} tone="text-loss" />
            <Stat label={t('stats.payoff')} value={d.summary.payoffRatio === null ? '—' : fmtNum(d.summary.payoffRatio)} />
            <Stat label={t('stats.best')} value={fmtMoney(d.summary.bestTrade, cur, { sign: true })} tone={pnlTone(d.summary.bestTrade)} />
            <Stat label={t('stats.worst')} value={fmtMoney(d.summary.worstTrade, cur, { sign: true })} tone={pnlTone(d.summary.worstTrade)} />
            <Stat label={t('stats.trades')} value={d.summary.trades} sub={`Compliance ${fmtPct(d.summary.ruleCompliance)}`} />
            <Stat label={t('stats.maxDd')} value={Number(d.equity.maxDrawdown) > 0 ? `−${fmtMoney(d.equity.maxDrawdown, cur)}` : fmtMoney(0, cur)} sub={fmtPct(d.equity.maxDrawdownPct)} tone="text-loss" />
          </div>
          {d.summary.trades === 0 ? (
            <Panel className="mt-4">
              <EmptyState title={t('empty.trades')} />
            </Panel>
          ) : (
            <>
              <div className="mt-4 grid gap-4 xl:grid-cols-2">
                <Panel title={`Account equity · start ${fmtMoney(d.equity.startingCapital, cur)}`}>
                  <EquityChart data={points} currency={cur} dataKey="equity" label="Equity" />
                </Panel>
                <Panel title="Cumulative P&L">
                  <EquityChart data={points} currency={cur} dataKey="cumulative" label="Cumulative P&L" />
                </Panel>
              </div>
              <Panel title="Drawdown from peak" className="mt-4">
                <DrawdownChart data={points} />
              </Panel>
              <div className="mt-4 grid gap-4 lg:grid-cols-2">
                <Panel title="By weekday">
                  <GroupBarChart groups={d.byWeekday} currency={cur} labelFor={(k) => k.charAt(0) + k.slice(1).toLowerCase()} />
                </Panel>
                <Panel title="By session">
                  <GroupBarChart groups={d.bySession} currency={cur} />
                </Panel>
                <Panel title="By instrument">
                  <GroupBarChart groups={d.byInstrument} currency={cur} />
                </Panel>
                <Panel title="By strategy">
                  <GroupBarChart groups={d.byStrategy} currency={cur} labelFor={(k) => k} />
                </Panel>
              </div>
            </>
          )}
        </>
      )}
    </>
  );
}
