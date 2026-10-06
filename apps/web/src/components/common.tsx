import { useEffect, useState, type ReactNode } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { Lock, ShieldAlert, ShieldOff, Wind } from 'lucide-react';
import type { MeResponse, SettingsDto, TiltStatus } from '@journzey/shared';
import { Button, DemoBadge, Input, Pnl, Select } from './ui';
import { post } from '../lib/api';
import { fmtTime } from '../lib/format';
import { qk, useMe, useSettings } from '../lib/queries';
import { resolveRange, useDateRange, type RangePreset } from '../lib/dateRange';
import { useI18n } from '../lib/i18n';
import { fmtCountdown, useTerminalLock } from './trade/QuickTradeBar';

export function PageHeader({ title, subtitle, actions, demo }: { title: string; subtitle?: ReactNode; actions?: ReactNode; demo?: boolean }) {
  return (
    <div className="mb-4 flex flex-wrap items-end justify-between gap-3">
      <div>
        <h1 className="flex items-center gap-2 font-display text-xl font-bold sm:text-2xl">
          {title} {demo && <DemoBadge />}
        </h1>
        {subtitle && <p className="mt-0.5 text-sm text-muted">{subtitle}</p>}
      </div>
      {actions && <div className="flex flex-wrap items-center gap-2">{actions}</div>}
    </div>
  );
}

export function DateRangeFilter() {
  const { t } = useI18n();
  const settings = useSettings();
  const { range, setRange } = useDateRange();
  const presets: Array<[RangePreset, string]> = [
    ['all', t('range.all')],
    ['today', t('range.today')],
    ['week', t('range.week')],
    ['month', t('range.month')],
    ['lastMonth', t('range.lastMonth')],
    ['custom', t('range.custom')],
  ];
  return (
    <div className="flex flex-wrap items-center gap-2">
      <label htmlFor="range-preset" className="sr-only">
        Date range
      </label>
      <Select id="range-preset" className="h-9 w-auto text-xs" value={range.preset} onChange={(e) => setRange(resolveRange(e.target.value as RangePreset, settings.timezone, { from: range.from, to: range.to }))}>
        {presets.map(([v, l]) => (
          <option key={v} value={v}>
            {l}
          </option>
        ))}
      </Select>
      {range.preset === 'custom' && (
        <>
          <Input aria-label="From date" type="date" className="h-9 w-auto text-xs" value={range.from ?? ''} onChange={(e) => setRange({ ...range, from: e.target.value || undefined })} />
          <Input aria-label="To date" type="date" className="h-9 w-auto text-xs" value={range.to ?? ''} onChange={(e) => setRange({ ...range, to: e.target.value || undefined })} />
        </>
      )}
    </div>
  );
}

/** Tilt Circuit Breaker. Locks the journzey terminal only — never claims to lock the broker. */
export function TiltPanel({ tilt, remainingBudget, currency }: { tilt: TiltStatus; remainingBudget: string | null; currency: string }) {
  const { t } = useI18n();
  const settings = useSettings();
  const { data: me } = useMe();
  const qc = useQueryClient();
  const { locked, remainingMs } = useTerminalLock();
  const [now, setNow] = useState(Date.now());
  useEffect(() => {
    const id = setInterval(() => setNow(Date.now()), 1000);
    return () => clearInterval(id);
  }, []);
  const lock = useMutation({
    mutationFn: (minutes: number) => post<SettingsDto>('/settings/terminal-lock', { minutes }),
    onSuccess: (s) => qc.setQueryData<MeResponse | null>(qk.me, (m) => (m ? { ...m, settings: s } : m)),
  });
  if (!tilt.triggered && !locked) return null;
  const cooldownMs = tilt.cooldownEndsAt ? Math.max(0, new Date(tilt.cooldownEndsAt).getTime() - now) : 0;
  return (
    <section role="alert" aria-labelledby="tilt-title" className="mb-4 rounded-lg border border-loss/60 bg-loss/10 p-4">
      <div className="flex flex-wrap items-start gap-4">
        <div className="flex-1">
          <h2 id="tilt-title" className="flex items-center gap-2 font-mono text-sm font-bold tracking-widest text-loss">
            <ShieldAlert className="h-5 w-5" aria-hidden /> {t('tilt.title')}
          </h2>
          {tilt.triggered && (
            <p className="mt-1 text-sm">
              {settings.tiltLossCount} losing trades within {settings.tiltWindowMinutes} minutes. Suggested cooldown: <b className="num">{fmtCountdown(cooldownMs)}</b>
            </p>
          )}
          <ul className="mt-2 space-y-0.5 text-xs">
            {tilt.recentLosses.map((l) => (
              <li key={l.id} className="flex gap-3">
                <span className="num text-muted">{fmtTime(l.executedAt, settings.timezone)}</span>
                <span>{l.symbol}</span>
                <Pnl value={l.pnl} currency={currency} className="text-xs" />
              </li>
            ))}
          </ul>
          <p className="mt-2 text-xs text-muted">
            Today’s remaining risk budget: <b className="num text-fg">{remainingBudget ? `${remainingBudget} ${currency}` : 'no max daily loss set'}</b>
          </p>
        </div>
        <div className="flex flex-col items-center gap-2">
          <div className="flex h-20 w-20 animate-breathe items-center justify-center rounded-full bg-info/20" aria-hidden>
            <Wind className="h-6 w-6 text-info" />
          </div>
          <p className="text-center text-2xs text-muted">Breathe in 4s · hold 4s · out 4s</p>
        </div>
      </div>
      <div className="mt-3 grid gap-2 sm:grid-cols-2">
        <div className="rounded-md border border-line bg-panel p-3">
          <p className="flex items-center gap-2 text-xs font-bold tracking-wide">
            <Lock className="h-4 w-4 text-accent" aria-hidden /> JOURNZEY TERMINAL LOCK
          </p>
          {locked ? (
            <p className="mt-1 text-xs text-muted">
              Active — quick logging resumes in <b className="num text-fg">{fmtCountdown(remainingMs)}</b>.
            </p>
          ) : (
            <Button size="sm" className="mt-2" variant="danger" loading={lock.isPending} onClick={() => lock.mutate(Math.max(1, Math.ceil(cooldownMs / 60000)) || settings.tiltCooldownMinutes)}>
              Engage terminal lock
            </Button>
          )}
        </div>
        <div className="rounded-md border border-line bg-panel p-3">
          <p className="flex items-center gap-2 text-xs font-bold tracking-wide">
            <ShieldOff className="h-4 w-4 text-muted" aria-hidden /> BROKER EXECUTION LOCK
          </p>
          <p className="mt-1 text-xs text-muted">Not available. {me?.features ? 'No connected broker integration supports execution locking' : ''} — journzey.ai cannot block orders at your broker. Close your trading platform if you need a hard stop.</p>
        </div>
      </div>
    </section>
  );
}
