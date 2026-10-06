import { useEffect, useRef, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Clock, Download, Gauge, Layers, Target, Trophy } from 'lucide-react';
import type { DisciplineLeakResult, EdgeMatrixResponse, GroupStat, PerformanceSummary, RiskOfRuinResponse } from '@journzey/shared';
import { Button, ErrorState, Field, Input, Panel, Pnl, Spinner, Tabs, useToast } from '../../components/ui';
import { PageHeader } from '../../components/common';
import { GroupTable } from '../../components/GroupTable';
import { BandsChart } from '../../components/charts';
import { get } from '../../lib/api';
import { downloadCanvas, drawLeakCard } from '../../lib/cards';
import { fmtMoney, fmtNum, fmtPct, humanize } from '../../lib/format';
import { qk, useScope, useSettings, useUpdateSettings } from '../../lib/queries';

type Tab = 'edge' | 'leak' | 'risk' | 'tilt';

function WindowCard({ icon: Icon, label, g, currency, fmtKey }: { icon: typeof Clock; label: string; g: GroupStat | null; currency: string; fmtKey?: (k: string) => string }) {
  return (
    <div className="panel p-3">
      <div className="label flex items-center gap-1.5">
        <Icon className="h-3.5 w-3.5" aria-hidden /> {label}
      </div>
      {g ? (
        <>
          <div className="mt-1 text-base font-semibold">{fmtKey ? fmtKey(g.key) : humanize(g.key)}</div>
          <div className="mt-0.5 text-2xs text-muted">
            Expectancy <Pnl value={g.summary.expectancy} currency={currency} className="text-2xs" /> · {g.summary.trades} trades · {fmtPct(g.summary.winRate, 0)} win
          </div>
        </>
      ) : (
        <div className="mt-1 text-sm text-muted">Not enough data (min 3 trades)</div>
      )}
    </div>
  );
}

function CompareCard({ title, s, currency }: { title: string; s: PerformanceSummary; currency: string }) {
  return (
    <div className="rounded-md border border-line p-3">
      <h3 className="label">{title}</h3>
      <dl className="mt-2 grid grid-cols-2 gap-2 text-sm">
        <dt className="text-muted">Trades</dt>
        <dd className="num text-right">{s.trades}</dd>
        <dt className="text-muted">Win rate</dt>
        <dd className="num text-right">{fmtPct(s.winRate)}</dd>
        <dt className="text-muted">Expectancy</dt>
        <dd className="text-right">
          <Pnl value={s.expectancy} currency={currency} />
        </dd>
        <dt className="text-muted">Net P&L</dt>
        <dd className="text-right">
          <Pnl value={s.netPnl} currency={currency} />
        </dd>
      </dl>
    </div>
  );
}

function EdgeTab() {
  const { scope } = useScope();
  const [range, setRange] = useState<{ from?: string; to?: string }>({});
  const params = { account: scope, ...range };
  const q = useQuery({ queryKey: qk.analytics('edge', params), queryFn: () => get<EdgeMatrixResponse & { currency: string }>('/analytics/edge-matrix', params) });
  if (q.isLoading) return <Spinner />;
  if (q.isError) return <ErrorState error={q.error} onRetry={() => q.refetch()} />;
  const d = q.data!;
  const cur = d.currency;
  return (
    <div className="space-y-4">
      <div className="flex flex-wrap items-end gap-2">
        <p className="mr-auto text-sm">
          <b>{d.period.label}</b>{' '}
          <span className="text-muted">
            ({d.period.from} → {d.period.to})
          </span>
        </p>
        <Input aria-label="From" type="date" className="h-9 w-auto text-xs" value={range.from ?? ''} onChange={(e) => setRange((r) => ({ ...r, from: e.target.value || undefined }))} />
        <Input aria-label="To" type="date" className="h-9 w-auto text-xs" value={range.to ?? ''} onChange={(e) => setRange((r) => ({ ...r, to: e.target.value || undefined }))} />
        {(range.from || range.to) && (
          <Button size="sm" variant="ghost" onClick={() => setRange({})}>
            Previous month
          </Button>
        )}
      </div>
      <Panel title="Best trading window (historical)">
        <div className="grid gap-2 sm:grid-cols-2 xl:grid-cols-5">
          <WindowCard icon={Clock} label="Best hour" g={d.bestWindow.hour} currency={cur} fmtKey={(k) => `${k}:00–${k}:59`} />
          <WindowCard icon={Layers} label="Best session" g={d.bestWindow.session} currency={cur} />
          <WindowCard icon={Target} label="Best instrument" g={d.bestWindow.instrument} currency={cur} fmtKey={(k) => k} />
          <WindowCard icon={Trophy} label="Best setup" g={d.bestWindow.setup} currency={cur} fmtKey={(k) => k} />
          <WindowCard icon={Gauge} label="Highest-expectancy day" g={d.bestWindow.weekday} currency={cur} />
        </div>
        <p className="mt-2 text-2xs text-muted">{d.bestWindow.note}</p>
      </Panel>
      <div className="grid gap-4 xl:grid-cols-2">
        <Panel title="By strategy">
          <GroupTable groups={d.byStrategy} currency={cur} caption="Edge by strategy" keyLabel="Strategy" labelFor={(k) => k} />
        </Panel>
        <Panel title="By session">
          <GroupTable groups={d.bySession} currency={cur} caption="Edge by session" keyLabel="Session" />
        </Panel>
        <Panel title="By instrument">
          <GroupTable groups={d.byInstrument} currency={cur} caption="Edge by instrument" keyLabel="Instrument" labelFor={(k) => k} />
        </Panel>
        <Panel title="By hour (your time zone)">
          <GroupTable groups={d.byHour} currency={cur} caption="Edge by hour" keyLabel="Hour" labelFor={(k) => `${k}:00`} />
        </Panel>
        <Panel title="By mistake">
          <GroupTable groups={d.byMistake} currency={cur} caption="Edge by mistake" keyLabel="Mistake" />
        </Panel>
        <Panel title="Disciplined vs emotional trades">
          <div className="grid gap-2 sm:grid-cols-2">
            <CompareCard title="Disciplined" s={d.disciplinedVsEmotional.disciplined} currency={cur} />
            <CompareCard title="Emotional / rule-breaking" s={d.disciplinedVsEmotional.emotional} currency={cur} />
          </div>
        </Panel>
      </div>
    </div>
  );
}

function LeakTab() {
  const { scope } = useScope();
  const [range, setRange] = useState<{ from?: string; to?: string }>({});
  const params = { account: scope, ...range };
  const q = useQuery({
    queryKey: qk.analytics('discipline', params),
    queryFn: () => get<{ currency: string; leak: DisciplineLeakResult; ruleCompliance: number | null; trades: number }>('/analytics/discipline', params),
  });
  const canvas = useRef<HTMLCanvasElement>(null);
  const exportPng = () => {
    if (!q.data || !canvas.current) return;
    drawLeakCard(canvas.current, {
      leak: q.data.leak.leak,
      actual: q.data.leak.actualPnl,
      flawless: q.data.leak.flawlessPnl,
      violations: q.data.leak.violations,
      currency: q.data.currency,
      period: range.from || range.to ? `${range.from ?? '…'} → ${range.to ?? '…'}` : 'ALL TIME',
      demo: scope === 'demo',
    });
    downloadCanvas(canvas.current, 'journzey-discipline-leak.png');
  };
  if (q.isLoading) return <Spinner />;
  if (q.isError) return <ErrorState error={q.error} onRetry={() => q.refetch()} />;
  const d = q.data!;
  return (
    <div className="space-y-4">
      <div className="flex flex-wrap items-center gap-2">
        <Input aria-label="From" type="date" className="h-9 w-auto text-xs" value={range.from ?? ''} onChange={(e) => setRange((r) => ({ ...r, from: e.target.value || undefined }))} />
        <Input aria-label="To" type="date" className="h-9 w-auto text-xs" value={range.to ?? ''} onChange={(e) => setRange((r) => ({ ...r, to: e.target.value || undefined }))} />
        <Button size="sm" icon={<Download className="h-4 w-4" />} onClick={exportPng} className="ml-auto">
          Export PNG
        </Button>
      </div>
      <Panel title="Discipline Leak Mirror">
        <p className="text-sm text-muted">Your execution mistakes cost approximately</p>
        <p className="num mt-1 text-4xl font-bold text-loss">{fmtMoney(d.leak.leak, d.currency)}</p>
        <p className="mt-1 text-sm text-muted">this period, across {d.leak.violations} rule violation(s) in {d.trades} trades (rule compliance {fmtPct(d.ruleCompliance)}).</p>
        <dl className="mt-4 grid max-w-md grid-cols-2 gap-2 text-sm">
          <dt className="text-muted">Actual P&L</dt>
          <dd className="text-right">
            <Pnl value={d.leak.actualPnl} currency={d.currency} />
          </dd>
          <dt className="text-muted">Rule-compliant hypothetical</dt>
          <dd className="text-right">
            <Pnl value={d.leak.flawlessPnl} currency={d.currency} />
          </dd>
        </dl>
        <p className="mt-3 text-2xs text-muted">Discipline Leak = Flawless Execution P&L − Actual P&L. {d.leak.methodology} Hypothetical results are not guaranteed outcomes.</p>
      </Panel>
      <Panel title="Leak by mistake">
        {d.leak.byMistake.length === 0 ? (
          <p className="text-sm text-muted">No rule violations recorded in this period.</p>
        ) : (
          <table className="w-full text-xs">
            <caption className="sr-only">Leak by mistake type</caption>
            <thead className="text-left text-muted">
              <tr className="border-b border-line">
                <th scope="col" className="py-1.5">
                  Mistake
                </th>
                <th scope="col" className="py-1.5 text-right">
                  Trades
                </th>
                <th scope="col" className="py-1.5 text-right">
                  Actual
                </th>
                <th scope="col" className="py-1.5 text-right">
                  Hypothetical
                </th>
                <th scope="col" className="py-1.5 text-right">
                  Leak
                </th>
              </tr>
            </thead>
            <tbody className="divide-y divide-line">
              {d.leak.byMistake.map((m) => (
                <tr key={m.mistake}>
                  <th scope="row" className="py-1.5 text-left font-medium">
                    {humanize(m.mistake)}
                  </th>
                  <td className="num py-1.5 text-right">{m.trades}</td>
                  <td className="py-1.5 text-right">
                    <Pnl value={m.actualPnl} currency={d.currency} />
                  </td>
                  <td className="py-1.5 text-right">
                    <Pnl value={m.flawlessPnl} currency={d.currency} />
                  </td>
                  <td className="num py-1.5 text-right text-loss">{fmtMoney(m.leak, d.currency)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </Panel>
      <canvas ref={canvas} className="hidden" aria-hidden />
    </div>
  );
}

function RiskTab() {
  const { scope } = useScope();
  const [risk, setRisk] = useState(1);
  const [debounced, setDebounced] = useState(1);
  useEffect(() => {
    const id = setTimeout(() => setDebounced(risk), 350);
    return () => clearTimeout(id);
  }, [risk]);
  const params = { account: scope, riskPercent: debounced, paths: 5000 };
  // Simulation runs server-side (5,000 paths), keeping the UI thread free.
  const q = useQuery({ queryKey: qk.analytics('ror', params), queryFn: () => get<RiskOfRuinResponse>('/analytics/risk-of-ruin', params), placeholderData: (p) => p });
  return (
    <div className="space-y-4">
      <Panel title="Monte Carlo risk of ruin">
        <label htmlFor="risk-slider" className="label">
          Risk per trade: <span className="num text-fg">{risk.toFixed(2)}%</span>
        </label>
        <input id="risk-slider" type="range" min={0.25} max={5} step={0.25} value={risk} onChange={(e) => setRisk(Number(e.target.value))} className="mt-2 w-full accent-[rgb(var(--accent))]" />
        <div className="flex justify-between text-2xs text-muted">
          <span>0.25%</span>
          <span>5%</span>
        </div>
        {q.isLoading ? (
          <Spinner label="Running simulation…" />
        ) : q.isError ? (
          <ErrorState error={q.error} onRetry={() => q.refetch()} />
        ) : q.data!.insufficientData ? (
          <p className="mt-4 text-sm text-muted">At least 10 closed trades with a stop loss (or risk amount) are needed. Current sample: {q.data!.inputs.sampleSize}.</p>
        ) : (
          <div aria-live="polite" className={q.isFetching ? 'opacity-60' : ''}>
            <div className="mt-4 grid grid-cols-3 gap-2">
              {q.data!.result!.probabilities.map((p) => (
                <div key={p.drawdown} className="rounded-md border border-line p-3 text-center">
                  <div className="label">{(p.drawdown * 100).toFixed(0)}% drawdown</div>
                  <div className={`num mt-1 text-2xl font-bold ${p.probability > 0.5 ? 'text-loss' : p.probability > 0.2 ? 'text-warn' : 'text-profit'}`}>{fmtPct(p.probability)}</div>
                </div>
              ))}
            </div>
            <BandsChart bands={q.data!.result!.bands} />
            <dl className="grid grid-cols-2 gap-2 text-xs sm:grid-cols-4">
              <div>
                <dt className="label">Win rate</dt>
                <dd className="num">{fmtPct(q.data!.inputs.winRate)}</dd>
              </div>
              <div>
                <dt className="label">Avg win / loss</dt>
                <dd className="num">
                  {fmtNum(q.data!.inputs.avgWinR)}R / {fmtNum(q.data!.inputs.avgLossR)}R
                </dd>
              </div>
              <div>
                <dt className="label">Horizon</dt>
                <dd className="num">
                  {q.data!.inputs.tradesPerPath} trades (~{fmtNum(q.data!.inputs.tradesPerWeek, 1)}/wk)
                </dd>
              </div>
              <div>
                <dt className="label">Paths</dt>
                <dd className="num">{q.data!.result!.paths.toLocaleString()}</dd>
              </div>
            </dl>
            <p className="mt-3 text-2xs font-semibold text-warn">{q.data!.result!.disclaimer}</p>
          </div>
        )}
      </Panel>
    </div>
  );
}

function TiltTab() {
  const settings = useSettings();
  const update = useUpdateSettings();
  const toast = useToast();
  const [v, setV] = useState({ tiltLossCount: settings.tiltLossCount, tiltWindowMinutes: settings.tiltWindowMinutes, tiltCooldownMinutes: settings.tiltCooldownMinutes });
  return (
    <Panel title="Tilt circuit breaker rules">
      <p className="text-sm text-muted">
        Trigger a TILT RISK warning when you log <b>{v.tiltLossCount}</b> losing trades within <b>{v.tiltWindowMinutes}</b> minutes, with a <b>{v.tiltCooldownMinutes}</b>-minute cooldown.
      </p>
      <div className="mt-3 grid max-w-xl grid-cols-3 gap-3">
        <Field label="Losses" htmlFor="tilt-n">
          <Input id="tilt-n" type="number" min={2} max={20} value={v.tiltLossCount} onChange={(e) => setV({ ...v, tiltLossCount: Number(e.target.value) })} />
        </Field>
        <Field label="Window (min)" htmlFor="tilt-w">
          <Input id="tilt-w" type="number" min={1} max={1440} value={v.tiltWindowMinutes} onChange={(e) => setV({ ...v, tiltWindowMinutes: Number(e.target.value) })} />
        </Field>
        <Field label="Cooldown (min)" htmlFor="tilt-c">
          <Input id="tilt-c" type="number" min={1} max={1440} value={v.tiltCooldownMinutes} onChange={(e) => setV({ ...v, tiltCooldownMinutes: Number(e.target.value) })} />
        </Field>
      </div>
      <Button
        className="mt-3"
        variant="primary"
        loading={update.isPending}
        onClick={() => update.mutate(v, { onSuccess: () => toast('success', 'Tilt rules saved'), onError: (e) => toast('error', e.message) })}
      >
        Save rules
      </Button>
      <p className="mt-3 text-2xs text-muted">The breaker can lock the journzey.ai terminal (quick logging). It cannot lock your broker account — no supported integration provides execution locking.</p>
    </Panel>
  );
}

export default function EdgeMatrix() {
  const [tab, setTab] = useState<Tab>('edge');
  const { scope } = useScope();
  return (
    <>
      <PageHeader title="Edge Matrix" subtitle="Historical edge analysis — not a forecast" demo={scope === 'demo'} />
      <div className="mb-4">
        <Tabs
          label="Edge Matrix sections"
          value={tab}
          onChange={setTab}
          items={[
            { value: 'edge', label: 'Edge & best window' },
            { value: 'leak', label: 'Discipline leak' },
            { value: 'risk', label: 'Risk of ruin' },
            { value: 'tilt', label: 'Tilt breaker' },
          ]}
        />
      </div>
      {tab === 'edge' && <EdgeTab />}
      {tab === 'leak' && <LeakTab />}
      {tab === 'risk' && <RiskTab />}
      {tab === 'tilt' && <TiltTab />}
      <p className="mt-6 text-2xs text-muted">Post-trade runner audits are available per trade from the trade detail view (requires a market-data provider).</p>
    </>
  );
}
