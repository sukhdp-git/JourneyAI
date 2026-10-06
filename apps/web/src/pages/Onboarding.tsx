import { useState } from 'react';
import { useNavigate } from 'react-router';
import { useQueryClient } from '@tanstack/react-query';
import clsx from 'clsx';
import { Check, ChevronLeft, ChevronRight } from 'lucide-react';
import { CURRENCIES, PRIMARY_MARKETS, isValidTimeZone, onboardingSchema, type MeResponse, type OnboardingInput } from '@journzey/shared';
import { Logo } from '../components/Logo';
import { Button, Field, Input, Select, useToast } from '../components/ui';
import { ApiError, post } from '../lib/api';
import { qk, useMe } from '../lib/queries';

const MARKET_LABELS: Record<string, string> = { METALS: 'Gold', FOREX: 'Forex', INDICES: 'Indices', CRYPTO: 'Crypto', COMMODITIES: 'Commodities' };
const STEPS = ['Welcome', 'Markets', 'Account', 'Risk', 'Timezone', 'Finish'];
const zones = (() => {
  try {
    return (Intl as unknown as { supportedValuesOf?: (k: string) => string[] }).supportedValuesOf?.('timeZone') ?? [];
  } catch {
    return [];
  }
})();

export default function Onboarding() {
  const { data: me } = useMe();
  const navigate = useNavigate();
  const qc = useQueryClient();
  const toast = useToast();
  const [step, setStep] = useState(0);
  const [busy, setBusy] = useState(false);
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [form, setForm] = useState<OnboardingInput>({
    primaryMarkets: ['METALS'],
    accountName: 'Main Account',
    startingCapital: '10000',
    currency: 'USD',
    demo: false,
    defaultRiskPercentage: '1',
    maxDailyLoss: '300',
    maxWeeklyLoss: '900',
    defaultTargetRr: '2',
    timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC',
    loadDemoData: true,
  });
  const set = <K extends keyof OnboardingInput>(k: K, v: OnboardingInput[K]) => setForm((f) => ({ ...f, [k]: v }));

  const validateStep = (): boolean => {
    const e: Record<string, string> = {};
    if (step === 1 && form.primaryMarkets.length === 0) e.primaryMarkets = 'Select at least one market';
    if (step === 2) {
      if (!String(form.accountName).trim()) e.accountName = 'Account name is required';
      if (!(Number(form.startingCapital) > 0)) e.startingCapital = 'Starting capital must be greater than zero';
    }
    if (step === 3) {
      const r = Number(form.defaultRiskPercentage);
      if (!(r > 0 && r <= 100)) e.defaultRiskPercentage = 'Risk per trade must be between 0 and 100%';
      if (!(Number(form.defaultTargetRr) > 0)) e.defaultTargetRr = 'Target R must be positive';
    }
    if (step === 4 && !isValidTimeZone(form.timezone)) e.timezone = 'Choose a valid time zone';
    setErrors(e);
    return Object.keys(e).length === 0;
  };

  const finish = async () => {
    const parsed = onboardingSchema.safeParse(form);
    if (!parsed.success) {
      setErrors(Object.fromEntries(parsed.error.issues.map((i) => [String(i.path[0]), i.message])));
      return;
    }
    setBusy(true);
    try {
      const res = await post<{ me: MeResponse }>('/onboarding', form);
      qc.setQueryData(qk.me, res.me);
      navigate('/app', { replace: true });
    } catch (e) {
      if (e instanceof ApiError && e.fields) setErrors(Object.fromEntries(Object.entries(e.fields).map(([k, v]) => [k, v[0] ?? 'Invalid'])));
      toast('error', e instanceof Error ? e.message : 'Setup failed');
    } finally {
      setBusy(false);
    }
  };

  return (
    <main id="main" className="mx-auto flex min-h-full max-w-xl flex-col px-4 py-8">
      <Logo />
      <ol className="mt-6 flex gap-1" aria-label="Setup progress">
        {STEPS.map((s, i) => (
          <li key={s} className={clsx('h-1 flex-1 rounded', i <= step ? 'bg-accent' : 'bg-line')} aria-current={i === step ? 'step' : undefined}>
            <span className="sr-only">
              Step {i + 1}: {s}
            </span>
          </li>
        ))}
      </ol>
      <div className="panel mt-6 flex-1 p-5 sm:flex-none">
        <p className="label">
          Step {step + 1} of {STEPS.length}
        </p>
        {step === 0 && (
          <section>
            <h1 className="mt-2 font-display text-2xl font-bold">Welcome to journzey.ai{me?.user.name ? `, ${me.user.name.split(' ')[0]}` : ''}</h1>
            <p className="mt-3 text-sm text-muted">Two minutes of setup configures your terminal: markets, your first trading account, risk rules and the time zone used for daily P&L and session analytics.</p>
          </section>
        )}
        {step === 1 && (
          <fieldset className="mt-2">
            <legend className="font-display text-xl font-bold">Primary markets</legend>
            <div className="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-3">
              {PRIMARY_MARKETS.map((m) => {
                const on = form.primaryMarkets.includes(m);
                return (
                  <button
                    type="button"
                    key={m}
                    aria-pressed={on}
                    onClick={() => set('primaryMarkets', on ? form.primaryMarkets.filter((x) => x !== m) : [...form.primaryMarkets, m])}
                    className={clsx('flex min-h-12 items-center justify-between rounded-md border px-3 text-sm', on ? 'border-accent bg-accent/10' : 'border-line')}
                  >
                    {MARKET_LABELS[m]} {on && <Check className="h-4 w-4 text-accent" aria-hidden />}
                  </button>
                );
              })}
            </div>
            {errors.primaryMarkets && <p role="alert" className="mt-2 text-2xs text-loss">{errors.primaryMarkets}</p>}
          </fieldset>
        )}
        {step === 2 && (
          <section className="mt-2 space-y-4">
            <h1 className="font-display text-xl font-bold">Account configuration</h1>
            <Field label="Account name" htmlFor="ob-name" error={errors.accountName}>
              <Input id="ob-name" value={form.accountName} onChange={(e) => set('accountName', e.target.value)} invalid={!!errors.accountName} />
            </Field>
            <div className="grid grid-cols-2 gap-3">
              <Field label="Starting capital" htmlFor="ob-cap" error={errors.startingCapital}>
                <Input id="ob-cap" inputMode="decimal" value={String(form.startingCapital)} onChange={(e) => set('startingCapital', e.target.value)} invalid={!!errors.startingCapital} className="num" />
              </Field>
              <Field label="Currency" htmlFor="ob-ccy">
                <Select id="ob-ccy" value={form.currency} onChange={(e) => set('currency', e.target.value as OnboardingInput['currency'])}>
                  {CURRENCIES.map((c) => (
                    <option key={c}>{c}</option>
                  ))}
                </Select>
              </Field>
            </div>
            <fieldset>
              <legend className="label">Account type</legend>
              <div className="mt-2 grid grid-cols-2 gap-2">
                {[
                  { v: false, l: 'Real (live funds)' },
                  { v: true, l: 'Demo / practice' },
                ].map((o) => (
                  <button type="button" key={o.l} aria-pressed={form.demo === o.v} onClick={() => set('demo', o.v)} className={clsx('min-h-11 rounded-md border px-3 text-sm', form.demo === o.v ? 'border-accent bg-accent/10' : 'border-line')}>
                    {o.l}
                  </button>
                ))}
              </div>
            </fieldset>
          </section>
        )}
        {step === 3 && (
          <section className="mt-2 grid grid-cols-2 gap-4">
            <h1 className="col-span-2 font-display text-xl font-bold">Risk settings</h1>
            <Field label="Risk per trade (%)" htmlFor="ob-risk" error={errors.defaultRiskPercentage}>
              <Input id="ob-risk" inputMode="decimal" className="num" value={String(form.defaultRiskPercentage)} onChange={(e) => set('defaultRiskPercentage', e.target.value)} />
            </Field>
            <Field label="Target R" htmlFor="ob-rr" error={errors.defaultTargetRr}>
              <Input id="ob-rr" inputMode="decimal" className="num" value={String(form.defaultTargetRr)} onChange={(e) => set('defaultTargetRr', e.target.value)} />
            </Field>
            <Field label={`Max daily loss (${form.currency})`} htmlFor="ob-dl" error={errors.maxDailyLoss}>
              <Input id="ob-dl" inputMode="decimal" className="num" value={String(form.maxDailyLoss ?? '')} onChange={(e) => set('maxDailyLoss', e.target.value)} />
            </Field>
            <Field label={`Max weekly loss (${form.currency})`} htmlFor="ob-wl" error={errors.maxWeeklyLoss}>
              <Input id="ob-wl" inputMode="decimal" className="num" value={String(form.maxWeeklyLoss ?? '')} onChange={(e) => set('maxWeeklyLoss', e.target.value)} />
            </Field>
          </section>
        )}
        {step === 4 && (
          <section className="mt-2 space-y-4">
            <h1 className="font-display text-xl font-bold">Timezone</h1>
            <p className="text-sm text-muted">Used for daily P&L, the calendar, journals and weekly analytics. Detected from your browser — change it if you trade on a different clock.</p>
            <Field label="Time zone" htmlFor="ob-tz" error={errors.timezone}>
              {zones.length ? (
                <Select id="ob-tz" value={form.timezone} onChange={(e) => set('timezone', e.target.value)}>
                  {zones.map((z) => (
                    <option key={z}>{z}</option>
                  ))}
                </Select>
              ) : (
                <Input id="ob-tz" value={form.timezone} onChange={(e) => set('timezone', e.target.value)} />
              )}
            </Field>
          </section>
        )}
        {step === 5 && (
          <section className="mt-2 space-y-4">
            <h1 className="font-display text-xl font-bold">Finish setup</h1>
            <dl className="grid grid-cols-2 gap-2 text-sm">
              <dt className="text-muted">Markets</dt>
              <dd>{form.primaryMarkets.map((m) => MARKET_LABELS[m]).join(', ')}</dd>
              <dt className="text-muted">Account</dt>
              <dd>
                {form.accountName} · {form.currency} {form.startingCapital} · {form.demo ? 'Demo' : 'Real'}
              </dd>
              <dt className="text-muted">Risk</dt>
              <dd>
                {form.defaultRiskPercentage}% / trade · {form.defaultTargetRr}R target
              </dd>
              <dt className="text-muted">Time zone</dt>
              <dd>{form.timezone}</dd>
            </dl>
            <fieldset>
              <legend className="label">Start with</legend>
              <div className="mt-2 grid gap-2">
                <button type="button" aria-pressed={form.loadDemoData} onClick={() => set('loadDemoData', true)} className={clsx('rounded-md border p-3 text-left text-sm', form.loadDemoData ? 'border-accent bg-accent/10' : 'border-line')}>
                  <span className="font-semibold">Demo Mode</span>
                  <span className="block text-2xs text-muted">Adds a separate “Demo Account” with 42 sample trades (Aug–Oct 2026) and 13 journal entries, clearly labelled DEMO DATA and kept out of your real analytics.</span>
                </button>
                <button type="button" aria-pressed={!form.loadDemoData} onClick={() => set('loadDemoData', false)} className={clsx('rounded-md border p-3 text-left text-sm', !form.loadDemoData ? 'border-accent bg-accent/10' : 'border-line')}>
                  <span className="font-semibold">Start With Empty Account</span>
                  <span className="block text-2xs text-muted">Only your own data. You can load demo data later from Settings.</span>
                </button>
              </div>
            </fieldset>
            {Object.keys(errors).length > 0 && (
              <p role="alert" className="text-2xs text-loss">
                {Object.values(errors).join(' · ')}
              </p>
            )}
          </section>
        )}
      </div>
      <div className="mt-4 flex justify-between gap-2 pb-[env(safe-area-inset-bottom)]">
        <Button onClick={() => setStep((s) => Math.max(0, s - 1))} disabled={step === 0} icon={<ChevronLeft className="h-4 w-4" />}>
          Back
        </Button>
        {step < STEPS.length - 1 ? (
          <Button variant="primary" onClick={() => validateStep() && setStep((s) => s + 1)}>
            Continue <ChevronRight className="h-4 w-4" aria-hidden />
          </Button>
        ) : (
          <Button variant="primary" onClick={finish} loading={busy}>
            Enter the terminal
          </Button>
        )}
      </div>
    </main>
  );
}
