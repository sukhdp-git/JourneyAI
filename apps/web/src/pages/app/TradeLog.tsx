import { useEffect, useState } from 'react';
import { keepPreviousData, useQuery } from '@tanstack/react-query';
import clsx from 'clsx';
import { ArrowDown, ArrowUp, Download, Eye, Pencil, Plus, Search, Share2, Trash2 } from 'lucide-react';
import { INSTRUMENTS, TRADING_SESSIONS, type Paginated, type TradeDto } from '@journzey/shared';
import { Badge, Button, DemoBadge, EmptyState, ErrorState, Input, Panel, Pnl, Select, Spinner, useToast } from '../../components/ui';
import { DateRangeFilter, PageHeader } from '../../components/common';
import { useTradeActions } from '../../components/trade/TradeActions';
import { download, get } from '../../lib/api';
import { fmtDate, fmtPrice, fmtR, fmtTime, humanize, todayIn } from '../../lib/format';
import { qk, useScope, useSettings, useStrategies } from '../../lib/queries';
import { useDateRange } from '../../lib/dateRange';
import { useI18n } from '../../lib/i18n';

type Sort = 'executedAt' | 'symbol' | 'pnl' | 'rr' | 'lotSize' | 'side';

export default function TradeLog() {
  const { t } = useI18n();
  const settings = useSettings();
  const tz = settings.timezone;
  const { scope } = useScope();
  const { range } = useDateRange();
  const strategies = useStrategies();
  const actions = useTradeActions();
  const toast = useToast();
  const [searchInput, setSearchInput] = useState('');
  const [f, setF] = useState({ search: '', strategyId: '', session: '', symbol: '', side: '', outcome: '', page: 1, pageSize: 25, sort: 'executedAt' as Sort, order: 'desc' as 'asc' | 'desc' });
  useEffect(() => {
    const id = setTimeout(() => setF((x) => ({ ...x, search: searchInput, page: 1 })), 300);
    return () => clearTimeout(id);
  }, [searchInput]);
  const params = { ...f, account: scope, from: range.from, to: range.to };
  const q = useQuery({ queryKey: qk.trades(params), queryFn: () => get<Paginated<TradeDto>>('/trades', params), placeholderData: keepPreviousData });
  const set = (k: keyof typeof f, v: string) => setF((x) => ({ ...x, [k]: v, page: 1 }));
  const sortBy = (s: Sort) => setF((x) => ({ ...x, sort: s, order: x.sort === s && x.order === 'desc' ? 'asc' : 'desc' }));
  const [exporting, setExporting] = useState(false);
  const exportCsv = async () => {
    setExporting(true);
    try {
      const { page: _p, pageSize: _s, ...rest } = params;
      await download('/trades/export.csv', rest, `JournzeyAI_Trades_${todayIn(tz)}.csv`);
    } catch (e) {
      toast('error', e instanceof Error ? e.message : 'Export failed');
    } finally {
      setExporting(false);
    }
  };

  const SortHead = ({ s, children, className }: { s: Sort; children: string; className?: string }) => (
    <th scope="col" aria-sort={f.sort === s ? (f.order === 'asc' ? 'ascending' : 'descending') : 'none'} className={clsx('px-2 py-2 font-semibold', className)}>
      <button onClick={() => sortBy(s)} className="inline-flex items-center gap-1 hover:text-fg">
        {children}
        {f.sort === s && (f.order === 'asc' ? <ArrowUp className="h-3 w-3" aria-hidden /> : <ArrowDown className="h-3 w-3" aria-hidden />)}
      </button>
    </th>
  );

  const RowActions = ({ tr }: { tr: TradeDto }) => (
    <div className="flex justify-end gap-0.5">
      <button onClick={() => actions.openView(tr)} aria-label={`View ${tr.symbol} trade`} className="rounded p-2 text-muted hover:bg-panel2 hover:text-fg">
        <Eye className="h-4 w-4" />
      </button>
      <button onClick={() => actions.openEdit(tr)} disabled={tr.demo} aria-label={`Edit ${tr.symbol} trade`} title={tr.demo ? 'Demo data is read-only' : undefined} className="rounded p-2 text-muted hover:bg-panel2 hover:text-fg disabled:opacity-30">
        <Pencil className="h-4 w-4" />
      </button>
      <button onClick={() => actions.openShare(tr)} aria-label={`Share card for ${tr.symbol} trade`} className="rounded p-2 text-muted hover:bg-panel2 hover:text-fg">
        <Share2 className="h-4 w-4" />
      </button>
      <button onClick={() => actions.confirmDelete(tr)} disabled={tr.demo} aria-label={`Delete ${tr.symbol} trade`} title={tr.demo ? 'Demo data is read-only' : undefined} className="rounded p-2 text-muted hover:bg-panel2 hover:text-loss disabled:opacity-30">
        <Trash2 className="h-4 w-4" />
      </button>
    </div>
  );

  const data = q.data;
  return (
    <>
      <PageHeader
        title={t('nav.trades')}
        subtitle="Execution ledger"
        actions={
          <>
            <DateRangeFilter />
            <Button size="sm" icon={<Download className="h-4 w-4" />} onClick={exportCsv} loading={exporting}>
              CSV
            </Button>
            <Button size="sm" variant="primary" icon={<Plus className="h-4 w-4" />} onClick={actions.openNew}>
              Log trade
            </Button>
          </>
        }
      />
      <Panel bodyClassName="p-3">
        <div className="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-7">
          <div className="relative col-span-2 sm:col-span-3 lg:col-span-2">
            <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted" aria-hidden />
            <Input aria-label="Search trades" placeholder="Search symbol, setup, notes…" value={searchInput} onChange={(e) => setSearchInput(e.target.value)} className="pl-9" />
          </div>
          <Select aria-label="Filter by strategy" value={f.strategyId} onChange={(e) => set('strategyId', e.target.value)}>
            <option value="">All strategies</option>
            {(strategies.data ?? []).map((s) => (
              <option key={s.id} value={s.id}>
                {s.name}
              </option>
            ))}
          </Select>
          <Select aria-label="Filter by session" value={f.session} onChange={(e) => set('session', e.target.value)}>
            <option value="">All sessions</option>
            {TRADING_SESSIONS.map((s) => (
              <option key={s} value={s}>
                {humanize(s)}
              </option>
            ))}
          </Select>
          <Select aria-label="Filter by instrument" value={f.symbol} onChange={(e) => set('symbol', e.target.value)}>
            <option value="">All instruments</option>
            {INSTRUMENTS.map((i) => (
              <option key={i.symbol}>{i.symbol}</option>
            ))}
          </Select>
          <Select aria-label="Filter by side" value={f.side} onChange={(e) => set('side', e.target.value)}>
            <option value="">Long & short</option>
            <option value="LONG">Long</option>
            <option value="SHORT">Short</option>
          </Select>
          <Select aria-label="Filter by outcome" value={f.outcome} onChange={(e) => set('outcome', e.target.value)}>
            <option value="">Winners & losers</option>
            <option value="WIN">Winners</option>
            <option value="LOSS">Losers</option>
            <option value="BREAKEVEN">Breakeven</option>
          </Select>
        </div>
      </Panel>
      <Panel className="mt-3" bodyClassName="p-0">
        {q.isLoading ? (
          <Spinner />
        ) : q.isError ? (
          <ErrorState error={q.error} onRetry={() => q.refetch()} />
        ) : data!.items.length === 0 ? (
          <EmptyState title={f.search || f.strategyId || f.session || f.symbol || f.side || f.outcome || range.from ? 'No trades match these filters.' : t('empty.trades')}>
            <Button variant="primary" size="sm" onClick={actions.openNew} icon={<Plus className="h-4 w-4" />}>
              Log trade
            </Button>
          </EmptyState>
        ) : (
          <>
            <div className="scrollbar-thin hidden overflow-x-auto md:block">
              <table className="w-full text-xs">
                <caption className="sr-only">Trades</caption>
                <thead className="border-b border-line text-left text-muted">
                  <tr>
                    <SortHead s="executedAt">Date</SortHead>
                    <th scope="col" className="px-2 py-2 font-semibold">
                      Time
                    </th>
                    <SortHead s="symbol">Symbol</SortHead>
                    <SortHead s="side">Side</SortHead>
                    <th scope="col" className="px-2 py-2 text-right font-semibold">
                      Entry
                    </th>
                    <th scope="col" className="px-2 py-2 text-right font-semibold">
                      Exit
                    </th>
                    <th scope="col" className="px-2 py-2 text-right font-semibold">
                      Stop
                    </th>
                    <SortHead s="lotSize" className="text-right">
                      Lots
                    </SortHead>
                    <SortHead s="pnl" className="text-right">
                      P&L
                    </SortHead>
                    <SortHead s="rr" className="text-right">
                      R
                    </SortHead>
                    <th scope="col" className="px-2 py-2 font-semibold">
                      Strategy
                    </th>
                    <th scope="col" className="px-2 py-2 font-semibold">
                      Setup
                    </th>
                    <th scope="col" className="px-2 py-2 font-semibold">
                      Session
                    </th>
                    <th scope="col" className="px-2 py-2 font-semibold">
                      Emotion
                    </th>
                    <th scope="col" className="px-2 py-2 text-right font-semibold">
                      Actions
                    </th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-line">
                  {data!.items.map((tr) => (
                    <tr key={tr.id} className="hover:bg-panel2/60">
                      <td className="num whitespace-nowrap px-2 py-1.5">
                        {fmtDate(tr.executedAt, tz)} {tr.demo && <DemoBadge />}
                      </td>
                      <td className="num px-2 py-1.5 text-muted">{fmtTime(tr.executedAt, tz)}</td>
                      <td className="px-2 py-1.5 font-semibold">{tr.symbol}</td>
                      <td className="px-2 py-1.5">
                        <Badge tone={tr.side === 'LONG' ? 'profit' : 'loss'}>{tr.side === 'LONG' ? '▲ LONG' : '▼ SHORT'}</Badge>
                      </td>
                      <td className="num px-2 py-1.5 text-right">{fmtPrice(tr.symbol, tr.entryPrice)}</td>
                      <td className="num px-2 py-1.5 text-right">{tr.status === 'OPEN' ? <Badge tone="info">OPEN</Badge> : fmtPrice(tr.symbol, tr.exitPrice)}</td>
                      <td className="num px-2 py-1.5 text-right text-muted">{fmtPrice(tr.symbol, tr.stopLoss)}</td>
                      <td className="num px-2 py-1.5 text-right">{Number(tr.lotSize).toFixed(2)}</td>
                      <td className="px-2 py-1.5 text-right">
                        <Pnl value={tr.pnl} currency={tr.currency} />
                      </td>
                      <td className="num px-2 py-1.5 text-right">{fmtR(tr.rr)}</td>
                      <td className="max-w-32 truncate px-2 py-1.5">{tr.strategyName ?? '—'}</td>
                      <td className="max-w-32 truncate px-2 py-1.5 text-muted">{tr.setupTag ?? '—'}</td>
                      <td className="whitespace-nowrap px-2 py-1.5 text-muted">{humanize(tr.session)}</td>
                      <td className="px-2 py-1.5 text-muted">
                        {humanize(tr.emotion)}
                        {tr.mistakeTag && tr.mistakeTag !== 'NONE' && (
                          <Badge tone="warn" className="ml-1">
                            {humanize(tr.mistakeTag)}
                          </Badge>
                        )}
                      </td>
                      <td className="px-1 py-0.5">
                        <RowActions tr={tr} />
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
            <ul className="divide-y divide-line md:hidden">
              {data!.items.map((tr) => (
                <li key={tr.id} className="p-3">
                  <div className="flex items-center justify-between gap-2">
                    <div className="flex items-center gap-2">
                      <span className="font-semibold">{tr.symbol}</span>
                      <Badge tone={tr.side === 'LONG' ? 'profit' : 'loss'}>{tr.side}</Badge>
                      {tr.demo && <DemoBadge />}
                    </div>
                    <Pnl value={tr.pnl} currency={tr.currency} className="font-semibold" />
                  </div>
                  <div className="mt-1 flex flex-wrap gap-x-3 text-2xs text-muted">
                    <span className="num">
                      {fmtDate(tr.executedAt, tz)} {fmtTime(tr.executedAt, tz)}
                    </span>
                    <span className="num">
                      {fmtPrice(tr.symbol, tr.entryPrice)} → {fmtPrice(tr.symbol, tr.exitPrice)}
                    </span>
                    <span className="num">{fmtR(tr.rr)}</span>
                    <span>{tr.strategyName ?? tr.setupTag ?? ''}</span>
                  </div>
                  <RowActions tr={tr} />
                </li>
              ))}
            </ul>
            <div className="flex flex-wrap items-center justify-between gap-2 border-t border-line px-3 py-2 text-xs text-muted">
              <span>
                {data!.total} trades · page {data!.page} of {data!.totalPages}
              </span>
              <div className="flex items-center gap-2">
                <Select aria-label="Rows per page" className="h-8 w-auto text-xs" value={f.pageSize} onChange={(e) => setF((x) => ({ ...x, pageSize: Number(e.target.value), page: 1 }))}>
                  {[25, 50, 100].map((n) => (
                    <option key={n} value={n}>
                      {n} / page
                    </option>
                  ))}
                </Select>
                <Button size="sm" disabled={f.page <= 1} onClick={() => setF((x) => ({ ...x, page: x.page - 1 }))}>
                  Previous
                </Button>
                <Button size="sm" disabled={f.page >= data!.totalPages} onClick={() => setF((x) => ({ ...x, page: x.page + 1 }))}>
                  Next
                </Button>
              </div>
            </div>
          </>
        )}
      </Panel>
    </>
  );
}
