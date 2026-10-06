import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import type { StrategyAnalyticsRow, StrategyDto } from '@journzey/shared';
import { Badge, Button, ConfirmDialog, EmptyState, ErrorState, Field, Input, Modal, Panel, Pnl, Spinner, Textarea, useToast } from '../../components/ui';
import { DateRangeFilter, PageHeader } from '../../components/common';
import { GroupBarChart } from '../../components/charts';
import { ApiError, del, get, patch, post } from '../../lib/api';
import { fmtNum, fmtPct } from '../../lib/format';
import { qk, useScope, useSettings, useStrategies, invalidateTradeData } from '../../lib/queries';
import { useDateRange } from '../../lib/dateRange';

function StrategyModal({ strategy, open, onClose }: { strategy: StrategyDto | null; open: boolean; onClose: () => void }) {
  const qc = useQueryClient();
  const toast = useToast();
  const [name, setName] = useState('');
  const [description, setDescription] = useState('');
  const [targetRr, setTargetRr] = useState('');
  const [checklist, setChecklist] = useState('');
  const [errors, setErrors] = useState<Record<string, string[]>>({});
  const [lastOpen, setLastOpen] = useState(false);
  if (open !== lastOpen) {
    setLastOpen(open);
    if (open) {
      setName(strategy?.name ?? '');
      setDescription(strategy?.description ?? '');
      setTargetRr(strategy?.targetRr ?? '2');
      setChecklist((strategy?.checklist ?? []).join('\n'));
      setErrors({});
    }
  }
  const save = useMutation({
    mutationFn: () => {
      const body = { name, description: description || null, targetRr: targetRr || null, checklist: checklist.split('\n').map((s) => s.trim()).filter(Boolean) };
      return strategy ? patch<StrategyDto>(`/strategies/${strategy.id}`, body) : post<StrategyDto>('/strategies', body);
    },
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: qk.strategies });
      invalidateTradeData(qc);
      toast('success', 'Playbook saved');
      onClose();
    },
    onError: (e) => {
      if (e instanceof ApiError && e.fields) setErrors(e.fields);
      toast('error', e instanceof Error ? e.message : 'Save failed');
    },
  });
  return (
    <Modal
      open={open}
      onClose={onClose}
      title={strategy ? `Edit ${strategy.name}` : 'New strategy'}
      footer={
        <>
          <Button onClick={onClose}>Cancel</Button>
          <Button variant="primary" loading={save.isPending} onClick={() => save.mutate()} disabled={!name.trim()}>
            Save
          </Button>
        </>
      }
    >
      <div className="space-y-3">
        <Field label="Name" htmlFor="st-name" error={errors.name?.[0]}>
          <Input id="st-name" value={name} maxLength={80} onChange={(e) => setName(e.target.value)} />
        </Field>
        <Field label="Description" htmlFor="st-desc" error={errors.description?.[0]}>
          <Textarea id="st-desc" value={description} maxLength={1000} onChange={(e) => setDescription(e.target.value)} />
        </Field>
        <Field label="Target R" htmlFor="st-rr" error={errors.targetRr?.[0]}>
          <Input id="st-rr" className="num" inputMode="decimal" value={targetRr} onChange={(e) => setTargetRr(e.target.value)} />
        </Field>
        <Field label="Checklist (one rule per line)" htmlFor="st-check" error={errors.checklist?.[0]}>
          <Textarea id="st-check" value={checklist} onChange={(e) => setChecklist(e.target.value)} />
        </Field>
      </div>
    </Modal>
  );
}

export default function Strategies() {
  const settings = useSettings();
  const { scope } = useScope();
  const { range } = useDateRange();
  const params = { account: scope, from: range.from, to: range.to };
  const q = useQuery({ queryKey: qk.analytics('strategies', params), queryFn: () => get<{ currency: string; rows: StrategyAnalyticsRow[] }>('/analytics/strategies', params) });
  const strategies = useStrategies();
  const qc = useQueryClient();
  const toast = useToast();
  const [editing, setEditing] = useState<{ open: boolean; s: StrategyDto | null }>({ open: false, s: null });
  const [deleting, setDeleting] = useState<StrategyDto | null>(null);
  const remove = useMutation({
    mutationFn: (s: StrategyDto) => del(`/strategies/${s.id}`),
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: qk.strategies });
      invalidateTradeData(qc);
      setDeleting(null);
      toast('success', 'Strategy deleted; its trades are now unassigned');
    },
  });
  const toggle = useMutation({
    mutationFn: (s: StrategyDto) => patch(`/strategies/${s.id}`, { active: !s.active }),
    onSuccess: () => void qc.invalidateQueries({ queryKey: qk.strategies }),
  });
  const cur = q.data?.currency ?? settings.baseCurrency;
  const byId = new Map((strategies.data ?? []).map((s) => [s.id, s]));

  return (
    <>
      <PageHeader
        title="Strategy Analysis"
        subtitle="Playbooks, setup analytics and comparative strategy telemetry"
        actions={
          <>
            <DateRangeFilter />
            <Button size="sm" variant="primary" icon={<Plus className="h-4 w-4" />} onClick={() => setEditing({ open: true, s: null })}>
              New strategy
            </Button>
          </>
        }
      />
      {q.isLoading ? (
        <Spinner />
      ) : q.isError ? (
        <ErrorState error={q.error} onRetry={() => q.refetch()} />
      ) : q.data!.rows.length === 0 ? (
        <Panel>
          <EmptyState title="No strategies yet. Create your first playbook." />
        </Panel>
      ) : (
        <>
          <Panel title="Comparative P&L">
            <GroupBarChart groups={q.data!.rows.filter((r) => r.summary.trades > 0).map((r) => ({ key: r.name, summary: r.summary }))} currency={cur} labelFor={(k) => k} />
          </Panel>
          <div className="mt-4 grid gap-3 md:grid-cols-2 2xl:grid-cols-3">
            {q.data!.rows.map((r) => {
              const s = r.strategyId ? byId.get(r.strategyId) : null;
              return (
                <article key={r.strategyId ?? 'none'} className="panel p-4">
                  <header className="flex items-start justify-between gap-2">
                    <div>
                      <h2 className="font-semibold">{r.name}</h2>
                      <p className="text-2xs text-muted">
                        Target {r.targetRr ? `${Number(r.targetRr).toFixed(1)}R` : '—'} {s && !s.active && <Badge>inactive</Badge>}
                      </p>
                    </div>
                    {s && (
                      <div className="flex">
                        <button onClick={() => setEditing({ open: true, s })} aria-label={`Edit ${s.name}`} className="rounded p-2 text-muted hover:bg-panel2">
                          <Pencil className="h-4 w-4" />
                        </button>
                        <button onClick={() => setDeleting(s)} aria-label={`Delete ${s.name}`} className="rounded p-2 text-muted hover:bg-panel2 hover:text-loss">
                          <Trash2 className="h-4 w-4" />
                        </button>
                      </div>
                    )}
                  </header>
                  <dl className="mt-3 grid grid-cols-4 gap-2 text-center">
                    <div>
                      <dt className="label">Trades</dt>
                      <dd className="num">{r.summary.trades}</dd>
                    </div>
                    <div>
                      <dt className="label">W / L</dt>
                      <dd className="num">
                        {r.summary.wins}/{r.summary.losses}
                      </dd>
                    </div>
                    <div>
                      <dt className="label">Win %</dt>
                      <dd className="num">{fmtPct(r.summary.winRate, 0)}</dd>
                    </div>
                    <div>
                      <dt className="label">PF</dt>
                      <dd className="num">{r.summary.profitFactor === null ? '—' : fmtNum(r.summary.profitFactor)}</dd>
                    </div>
                    <div className="col-span-2">
                      <dt className="label">P&L</dt>
                      <dd>
                        <Pnl value={r.summary.netPnl} currency={cur} />
                      </dd>
                    </div>
                    <div>
                      <dt className="label">Avg R</dt>
                      <dd className="num">{r.summary.avgR === null ? '—' : fmtNum(r.summary.avgR)}</dd>
                    </div>
                    <div>
                      <dt className="label">Rules</dt>
                      <dd className="num">{fmtPct(r.summary.ruleCompliance, 0)}</dd>
                    </div>
                  </dl>
                  {s && s.checklist.length > 0 && (
                    <ul className="mt-3 space-y-0.5 border-t border-line pt-2 text-2xs text-muted">
                      {s.checklist.map((c) => (
                        <li key={c}>• {c}</li>
                      ))}
                    </ul>
                  )}
                  {s && (
                    <button onClick={() => toggle.mutate(s)} className="mt-2 text-2xs text-accent underline">
                      Mark {s.active ? 'inactive' : 'active'}
                    </button>
                  )}
                </article>
              );
            })}
          </div>
        </>
      )}
      <StrategyModal open={editing.open} strategy={editing.s} onClose={() => setEditing({ open: false, s: null })} />
      <ConfirmDialog
        open={!!deleting}
        onClose={() => setDeleting(null)}
        onConfirm={() => deleting && remove.mutate(deleting)}
        loading={remove.isPending}
        title="Delete strategy?"
        message={<p>Trades tagged with “{deleting?.name}” are kept but become unassigned.</p>}
      />
    </>
  );
}
