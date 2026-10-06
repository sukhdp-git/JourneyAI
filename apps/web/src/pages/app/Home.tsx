import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import clsx from 'clsx';
import { CheckSquare, Square, Quote } from 'lucide-react';
import { WORLD_CLOCKS, isMarketOpen, CHECKLIST_ITEMS, type DashboardResponse, type MarketQuotesResponse, type Paginated, type TradeDto } from '@journzey/shared';
import { Badge, DemoBadge, ErrorState, Panel, Pnl, Spinner, useToast } from '../../components/ui';
import { PageHeader, TiltPanel } from '../../components/common';
import { QuickTradeBar } from '../../components/trade/QuickTradeBar';
import { useTradeActions } from '../../components/trade/TradeActions';
import { get, put } from '../../lib/api';
import { fmtNum, fmtPct, fmtTime, humanize, todayIn } from '../../lib/format';
import { qk, useMe, useScope } from '../../lib/queries';
import { DISCIPLINE_QUOTES } from '../../lib/quotes';

const CLOCK_LABELS: Record<string, string> = { india: 'India', new_york: 'New York', london: 'London', tokyo: 'Tokyo', singapore: 'Singapore', dubai: 'Dubai' };
const CHECK_LABELS: Record<string, string> = {
  news_checked: 'Economic news checked',
  levels_marked: 'Key levels marked',
  loss_budget_defined: 'Daily loss budget defined',
  mental_state_verified: 'Mental state verified',
  candle_confirmation: 'Candle confirmation',
  hard_stop_placed: 'Hard stop placed',
  stop_not_moved: 'Stop not emotionally moved',
  journal_completed: 'Journal completed',
  capital_updated: 'Capital management updated',
};

function MarketTicker() {
  const q = useQuery({ queryKey: qk.market, queryFn: () => get<MarketQuotesResponse>('/market/quotes'), refetchInterval: 60_000 });
  if (q.isLoading) return <div className="panel h-10" aria-busy />;
  if (q.isError || !q.data) return <div className="panel px-3 py-2 text-xs text-muted">Market data temporarily unavailable.</div>;
  const items = q.data.quotes;
  const row = (
    <div className="flex shrink-0 items-center gap-6 pr-6">
      {items.map((x) => (
        <span key={x.symbol} className="flex items-center gap-2 whitespace-nowrap text-xs">
          <span className="font-semibold">{x.displayName}</span>
          <span className="num">{x.price ? fmtNum(x.price, Math.min(5, (x.price.split('.')[1] ?? '').length)) : '—'}</span>
          {x.changePercent && (
            <span className={clsx('num', Number(x.changePercent) >= 0 ? 'text-profit' : 'text-loss')}>
              {Number(x.changePercent) >= 0 ? '▲' : '▼'} {Math.abs(Number(x.changePercent)).toFixed(2)}%
            </span>
          )}
          {x.error && <span className="text-2xs text-muted">n/a</span>}
        </span>
      ))}
    </div>
  );
  return (
    <div className="panel flex items-center overflow-hidden">
      <div className="z-10 flex shrink-0 items-center gap-2 border-r border-line bg-panel px-3 py-2">
        {q.data.source === 'demo' ? <DemoBadge /> : <Badge tone="profit">LIVE · {q.data.provider}</Badge>}
      </div>
      <div className="relative flex-1 overflow-hidden" aria-label="Market prices">
        <div className="flex w-max animate-ticker hover:[animation-play-state:paused]">
          {row}
          <div aria-hidden>{row}</div>
        </div>
      </div>
    </div>
  );
}

function WorldClocks() {
  const [now, setNow] = useState(new Date());
  useEffect(() => {
    const id = setInterval(() => setNow(new Date()), 15_000);
    return () => clearInterval(id);
  }, []);
  return (
    <div className="grid grid-cols-2 gap-2 sm:grid-cols-3 xl:grid-cols-6">
      {WORLD_CLOCKS.map((c) => {
        const open = isMarketOpen(c, now);
        return (
          <div key={c.key} className="panel px-3 py-2">
            <div className="flex items-center justify-between">
              <span className="label">{CLOCK_LABELS[c.key]}</span>
              <span className={clsx('text-2xs font-semibold', open ? 'text-profit' : 'text-muted')}>{open ? '● OPEN' : '○ CLOSED'}</span>
            </div>
            <div className="num mt-1 text-lg font-semibold">{fmtTime(now.toISOString(), c.timeZone)}</div>
          </div>
        );
      })}
    </div>
  );
}

function DisciplineQuote() {
  const [i, setI] = useState(() => Math.floor(Math.random() * DISCIPLINE_QUOTES.length));
  useEffect(() => {
    const id = setInterval(() => setI((x) => (x + 1) % DISCIPLINE_QUOTES.length), 20_000);
    return () => clearInterval(id);
  }, []);
  const q = DISCIPLINE_QUOTES[i]!;
  return (
    <Panel title="Discipline">
      <figure aria-live="polite">
        <Quote className="h-4 w-4 text-accent" aria-hidden />
        <blockquote className="mt-2 text-sm leading-relaxed">{q.q}</blockquote>
        <figcaption className="mt-2 text-2xs text-muted">— {q.a}</figcaption>
      </figure>
    </Panel>
  );
}

function Checklist() {
  const { data: me } = useMe();
  const date = todayIn(me?.settings?.timezone ?? 'UTC');
  const qc = useQueryClient();
  const toast = useToast();
  const q = useQuery({ queryKey: qk.checklist(date), queryFn: () => get<{ items: Array<{ key: string; phase: string; completed: boolean }> }>('/checklist', { date }) });
  const m = useMutation({
    mutationFn: (v: { itemKey: string; completed: boolean }) => put<{ items: Array<{ key: string; phase: string; completed: boolean }> }>('/checklist', { date, ...v }),
    onSuccess: (data) => qc.setQueryData(qk.checklist(date), data),
    onError: () => toast('error', 'Could not save checklist'),
  });
  const done = q.data?.items.filter((i) => i.completed).length ?? 0;
  return (
    <Panel title={`9-step checklist · ${date}`} actions={<span className="num text-xs text-muted">{done}/9</span>}>
      {q.isLoading ? (
        <Spinner />
      ) : q.isError ? (
        <ErrorState error={q.error} onRetry={() => q.refetch()} />
      ) : (
        <div className="space-y-3">
          {(['PRE_MARKET', 'IN_TRADE', 'POST_MARKET'] as const).map((phase) => (
            <fieldset key={phase}>
              <legend className="label">{humanize(phase)}</legend>
              <ul className="mt-1">
                {CHECKLIST_ITEMS.filter((c) => c.phase === phase).map((c) => {
                  const item = q.data!.items.find((x) => x.key === c.key);
                  const checked = !!item?.completed;
                  return (
                    <li key={c.key}>
                      <button
                        role="checkbox"
                        aria-checked={checked}
                        onClick={() => m.mutate({ itemKey: c.key, completed: !checked })}
                        className="flex min-h-10 w-full items-center gap-2 rounded px-1 text-left text-sm hover:bg-panel2"
                      >
                        {checked ? <CheckSquare className="h-4 w-4 text-profit" aria-hidden /> : <Square className="h-4 w-4 text-muted" aria-hidden />}
                        <span className={clsx(checked && 'text-muted line-through')}>{CHECK_LABELS[c.key]}</span>
                      </button>
                    </li>
                  );
                })}
              </ul>
            </fieldset>
          ))}
        </div>
      )}
    </Panel>
  );
}

export default function Home() {
  const { data: me } = useMe();
  const { scope } = useScope();
  const actions = useTradeActions();
  const tz = me?.settings?.timezone ?? 'UTC';
  const today = todayIn(tz);
  const dash = useQuery({ queryKey: qk.dashboard({ account: scope, from: today, to: today }), queryFn: () => get<DashboardResponse>('/dashboard', { account: scope, from: today, to: today }) });
  const recent = useQuery({ queryKey: qk.trades({ account: scope, recent: true }), queryFn: () => get<Paginated<TradeDto>>('/trades', { account: scope, pageSize: 6 }) });
  const isDesktop = useMemo(() => typeof window !== 'undefined' && window.matchMedia?.('(min-width: 1024px)').matches, []);
  return (
    <>
      <PageHeader title="Home Hub" subtitle="Terminal desk · market feeds · discipline" demo={dash.data?.scope.demo} />
      {dash.data && <TiltPanel tilt={dash.data.tilt} remainingBudget={dash.data.today.remainingDailyBudget} currency={dash.data.scope.currency} />}
      <div className="space-y-4">
        <MarketTicker />
        <Panel title="Execution desk">
          <QuickTradeBar autoFocus={isDesktop} />
        </Panel>
        <WorldClocks />
        <div className="grid gap-4 lg:grid-cols-3">
          <div className="space-y-4 lg:col-span-2">
            <Panel title="Today" actions={dash.data?.scope.demo ? <DemoBadge /> : undefined}>
              {dash.isLoading ? (
                <Spinner />
              ) : dash.isError ? (
                <ErrorState error={dash.error} onRetry={() => dash.refetch()} />
              ) : (
                <dl className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                  <div>
                    <dt className="label">P&L</dt>
                    <dd className="mt-1 text-lg">
                      <Pnl value={dash.data!.today.pnl} currency={dash.data!.scope.currency} />
                    </dd>
                  </div>
                  <div>
                    <dt className="label">Trades</dt>
                    <dd className="num mt-1 text-lg">{dash.data!.today.trades}</dd>
                  </div>
                  <div>
                    <dt className="label">Win rate</dt>
                    <dd className="num mt-1 text-lg">{fmtPct(dash.data!.summary.winRate)}</dd>
                  </div>
                  <div>
                    <dt className="label">Loss budget left</dt>
                    <dd className="num mt-1 text-lg">{dash.data!.today.remainingDailyBudget ?? '—'}</dd>
                  </div>
                </dl>
              )}
            </Panel>
            <Panel title="Recent executions" actions={<Link to="/app/trades" className="text-xs text-accent">View all</Link>} bodyClassName="p-0">
              {recent.isLoading ? (
                <Spinner />
              ) : recent.isError ? (
                <ErrorState error={recent.error} onRetry={() => recent.refetch()} />
              ) : recent.data!.items.length === 0 ? (
                <p className="p-4 text-sm text-muted">No trades logged yet. Log your first trade to start building your edge.</p>
              ) : (
                <ul className="divide-y divide-line">
                  {recent.data!.items.map((t) => (
                    <li key={t.id}>
                      <button onClick={() => actions.openView(t)} className="flex min-h-12 w-full items-center gap-3 px-4 py-2 text-left text-sm hover:bg-panel2">
                        <span className="num w-12 text-2xs text-muted">{fmtTime(t.executedAt, tz)}</span>
                        <span className="w-20 font-semibold">{t.symbol}</span>
                        <Badge tone={t.side === 'LONG' ? 'profit' : 'loss'}>{t.side}</Badge>
                        <span className="hidden flex-1 truncate text-muted sm:block">{t.strategyName ?? t.setupTag ?? ''}</span>
                        <Pnl value={t.pnl} currency={t.currency} className="ml-auto" />
                      </button>
                    </li>
                  ))}
                </ul>
              )}
            </Panel>
          </div>
          <div className="space-y-4">
            <Checklist />
            <DisciplineQuote />
          </div>
        </div>
      </div>
    </>
  );
}
