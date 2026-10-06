import { useMemo, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import clsx from 'clsx';
import { ChevronLeft, ChevronRight, TrendingDown, TrendingUp } from 'lucide-react';
import type { CalendarResponse, Paginated, TradeDto } from '@journzey/shared';
import { Badge, Button, ErrorState, Modal, Panel, Pnl, Spinner } from '../../components/ui';
import { PageHeader } from '../../components/common';
import { useTradeActions } from '../../components/trade/TradeActions';
import { get } from '../../lib/api';
import { fmtMoney, fmtR, fmtTime, shiftDate, todayIn } from '../../lib/format';
import { qk, useScope, useSettings } from '../../lib/queries';

const DOW = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

function monthGrid(month: string): string[][] {
  const first = `${month}-01`;
  const dow = (new Date(`${first}T00:00:00Z`).getUTCDay() + 6) % 7;
  let cursor = shiftDate(first, -dow);
  const weeks: string[][] = [];
  for (let w = 0; w < 6; w++) {
    const week: string[] = [];
    for (let i = 0; i < 7; i++) {
      week.push(cursor);
      cursor = shiftDate(cursor, 1);
    }
    if (week.some((d) => d.startsWith(month))) weeks.push(week);
  }
  return weeks;
}

function DayDetail({ date, onClose }: { date: string | null; onClose: () => void }) {
  const settings = useSettings();
  const { scope } = useScope();
  const actions = useTradeActions();
  const q = useQuery({
    enabled: !!date,
    queryKey: qk.trades({ account: scope, from: date, to: date, day: true }),
    queryFn: () => get<Paginated<TradeDto>>('/trades', { account: scope, from: date, to: date, pageSize: 200, sort: 'executedAt', order: 'asc' }),
  });
  if (!date) return null;
  return (
    <Modal open onClose={onClose} title={`Day detail · ${date}`} wide>
      {q.isLoading ? (
        <Spinner />
      ) : q.isError ? (
        <ErrorState error={q.error} onRetry={() => q.refetch()} />
      ) : !q.data || q.data.items.length === 0 ? (
        <p className="text-sm text-muted">No trades on this day.</p>
      ) : (
        <ul className="divide-y divide-line">
          {q.data.items.map((t) => (
            <li key={t.id}>
              <button onClick={() => (onClose(), actions.openView(t))} className="flex min-h-12 w-full flex-wrap items-center gap-x-4 gap-y-1 py-2 text-left text-sm hover:bg-panel2">
                <span className="num w-12 text-muted">{fmtTime(t.executedAt, settings.timezone)}</span>
                <span className="w-20 font-semibold">{t.symbol}</span>
                <Badge tone={t.side === 'LONG' ? 'profit' : 'loss'}>{t.side}</Badge>
                <span className="min-w-0 flex-1 truncate text-muted">{t.strategyName ?? t.setupTag ?? '—'}</span>
                <span className="num w-16 text-right">{fmtR(t.rr)}</span>
                <Pnl value={t.pnl} currency={t.currency} className="w-28 justify-end" />
              </button>
            </li>
          ))}
        </ul>
      )}
    </Modal>
  );
}

export default function CalendarPage() {
  const settings = useSettings();
  const { scope } = useScope();
  const [month, setMonth] = useState(() => todayIn(settings.timezone).slice(0, 7));
  const [day, setDay] = useState<string | null>(null);
  const q = useQuery({ queryKey: qk.calendar({ month, account: scope }), queryFn: () => get<CalendarResponse>('/calendar', { month, account: scope }) });
  const byDate = useMemo(() => new Map((q.data?.days ?? []).map((d) => [d.date, d])), [q.data]);
  const byWeek = useMemo(() => new Map((q.data?.weeks ?? []).map((w) => [w.weekStart, w])), [q.data]);
  const step = (n: number) => {
    const [y, m] = month.split('-').map(Number) as [number, number];
    const d = new Date(Date.UTC(y, m - 1 + n, 1));
    setMonth(d.toISOString().slice(0, 7));
  };
  const cur = q.data?.currency ?? settings.baseCurrency;
  const today = todayIn(settings.timezone);
  const label = new Date(`${month}-01T00:00:00Z`).toLocaleDateString(undefined, { month: 'long', year: 'numeric', timeZone: 'UTC' });

  return (
    <>
      <PageHeader
        title="Calendar"
        subtitle={`Daily P&L in ${settings.timezone}`}
        actions={
          <div className="flex items-center gap-2">
            <Button size="sm" variant="ghost" aria-label="Previous month" onClick={() => step(-1)}>
              <ChevronLeft className="h-4 w-4" />
            </Button>
            <span className="min-w-36 text-center text-sm font-semibold">{label}</span>
            <Button size="sm" variant="ghost" aria-label="Next month" onClick={() => step(1)}>
              <ChevronRight className="h-4 w-4" />
            </Button>
          </div>
        }
      />
      {q.isLoading ? (
        <Spinner />
      ) : q.isError ? (
        <ErrorState error={q.error} onRetry={() => q.refetch()} />
      ) : (
        <Panel bodyClassName="p-2 sm:p-3" title={`Month total · ${q.data!.monthTotal.trades} trades`} actions={<Pnl value={q.data!.monthTotal.pnl} currency={cur} />}>
          <div className="grid grid-cols-7 gap-1 sm:grid-cols-8" role="grid" aria-label={`Trading calendar ${label}`}>
            {DOW.map((d) => (
              <div key={d} className="label py-1 text-center" role="columnheader">
                {d}
              </div>
            ))}
            <div className="label hidden py-1 text-center sm:block" role="columnheader">
              Week
            </div>
            {monthGrid(month).map((week) => {
              const w = byWeek.get(week[0]!);
              return (
                <div key={week[0]} className="contents" role="row">
                  {week.map((date) => {
                    const d = byDate.get(date);
                    const inMonth = date.startsWith(month);
                    const n = Number(d?.pnl ?? 0);
                    return (
                      <button
                        key={date}
                        role="gridcell"
                        onClick={() => setDay(date)}
                        aria-label={`${date}${d ? `: ${fmtMoney(d.pnl, cur, { sign: true })}, ${d.trades} trades` : ': no trades'}`}
                        className={clsx(
                          'flex min-h-16 flex-col rounded-md border p-1 text-left transition-colors sm:min-h-20 sm:p-1.5',
                          !inMonth && 'opacity-40',
                          d ? (n > 0 ? 'border-profit/40 bg-profit/10' : n < 0 ? 'border-loss/40 bg-loss/10' : 'border-line bg-panel2') : 'border-line/60',
                          date === today && 'ring-1 ring-accent',
                        )}
                      >
                        <span className="num text-2xs text-muted">{Number(date.slice(8))}</span>
                        {d && (
                          <>
                            <span className={clsx('num mt-auto flex items-center gap-0.5 text-[10px] font-semibold sm:text-xs', n >= 0 ? 'text-profit' : 'text-loss')}>
                              {n >= 0 ? <TrendingUp className="hidden h-3 w-3 sm:block" aria-hidden /> : <TrendingDown className="hidden h-3 w-3 sm:block" aria-hidden />}
                              {fmtMoney(d.pnl, cur, { compact: true, sign: true })}
                            </span>
                            <span className="text-[10px] text-muted">{d.trades}t</span>
                          </>
                        )}
                      </button>
                    );
                  })}
                  <div className="hidden flex-col justify-center rounded-md bg-panel2 p-1.5 text-xs sm:flex">
                    {w ? (
                      <>
                        <Pnl value={w.pnl} currency={cur} compact className="text-xs" />
                        <span className="text-2xs text-muted">
                          {w.trades} trades · {w.wins}W/{w.losses}L
                        </span>
                      </>
                    ) : (
                      <span className="text-2xs text-muted">—</span>
                    )}
                  </div>
                </div>
              );
            })}
          </div>
          <ul className="mt-3 space-y-1 sm:hidden" aria-label="Weekly summaries">
            {q.data!.weeks.map((w) => (
              <li key={w.weekStart} className="flex items-center justify-between rounded bg-panel2 px-2 py-1.5 text-xs">
                <span className="text-muted">Week of {w.weekStart}</span>
                <span className="text-muted">{w.trades} trades</span>
                <Pnl value={w.pnl} currency={cur} className="text-xs" />
              </li>
            ))}
          </ul>
        </Panel>
      )}
      <DayDetail date={day} onClose={() => setDay(null)} />
    </>
  );
}
