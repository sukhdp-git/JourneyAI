import { useState } from 'react';
import { useNavigate } from 'react-router';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Archive, Copy, Download, KeyRound, Plug, RefreshCw, Trash2, Upload } from 'lucide-react';
import {
  ACCOUNT_TYPES,
  CURRENCIES,
  LANGUAGES,
  THEMES,
  type BrokerConnectionDto,
  type BrokerProviderInfo,
  type CapitalTransactionDto,
  type MeResponse,
  type UserDto,
} from '@journzey/shared';
import { Badge, Button, ConfirmDialog, DemoBadge, ErrorState, Field, Input, Modal, Panel, Select, Spinner, Tabs, useToast } from '../../components/ui';
import { PageHeader } from '../../components/common';
import { ApiError, del, download, get, patch, post, upload } from '../../lib/api';
import { fmtDate, fmtMoney, humanize, todayIn } from '../../lib/format';
import { invalidateTradeData, qk, useAccounts, useMe, useSettings, useUpdateSettings, type AccountWithFlags } from '../../lib/queries';

type Tab = 'profile' | 'preferences' | 'accounts' | 'brokers' | 'data';
const THEME_NAMES: Record<string, string> = { 'clean-light': 'Clean Light', 'dark-terminal': 'Dark Terminal', 'cyberpunk-slate': 'Cyberpunk Slate', 'midnight-navy': 'Midnight Navy' };
const LANG_NAMES: Record<string, string> = { en: 'English', ru: 'Русский (partial)', zh: '简体中文 (partial)', pt: 'Português (partial)' };
const zones = (() => {
  try {
    return (Intl as unknown as { supportedValuesOf?: (k: string) => string[] }).supportedValuesOf?.('timeZone') ?? [];
  } catch {
    return [];
  }
})();

function ProfileTab() {
  const { data: me } = useMe();
  const qc = useQueryClient();
  const toast = useToast();
  const [name, setName] = useState(me?.user.name ?? '');
  const save = useMutation({
    mutationFn: () => patch<UserDto>('/account/profile', { name }),
    onSuccess: (u) => {
      qc.setQueryData<MeResponse | null>(qk.me, (m) => (m ? { ...m, user: u } : m));
      toast('success', 'Profile updated');
    },
    onError: (e) => toast('error', e.message),
  });
  const u = me!.user;
  const s = me!.settings!;
  return (
    <Panel title="Profile">
      <div className="flex flex-wrap items-center gap-4">
        {u.avatarUrl ? <img src={u.avatarUrl} alt="Google profile" referrerPolicy="no-referrer" className="h-16 w-16 rounded-full" /> : <div className="flex h-16 w-16 items-center justify-center rounded-full bg-accent/20 text-xl font-bold text-accent">{(u.name ?? u.email).slice(0, 1).toUpperCase()}</div>}
        <div>
          <p className="font-semibold">{u.name}</p>
          <p className="text-sm text-muted">{u.email}</p>
          <p className="text-2xs text-muted">Joined {fmtDate(u.createdAt, s.timezone)}</p>
        </div>
      </div>
      <div className="mt-4 grid max-w-xl gap-3 sm:grid-cols-2">
        <Field label="Display name" htmlFor="pf-name">
          <Input id="pf-name" value={name} maxLength={120} onChange={(e) => setName(e.target.value)} />
        </Field>
        <Field label="Email" htmlFor="pf-email" hint="Managed by your Google account; updated only when Google verifies a change.">
          <Input id="pf-email" value={u.email} readOnly disabled />
        </Field>
        <Field label="Time zone" htmlFor="pf-tz">
          <Input id="pf-tz" value={s.timezone} readOnly disabled />
        </Field>
        <Field label="Base currency" htmlFor="pf-ccy">
          <Input id="pf-ccy" value={s.baseCurrency} readOnly disabled />
        </Field>
      </div>
      <Button className="mt-3" variant="primary" loading={save.isPending} disabled={!name.trim() || name === u.name} onClick={() => save.mutate()}>
        Save profile
      </Button>
    </Panel>
  );
}

function PreferencesTab() {
  const s = useSettings();
  const update = useUpdateSettings();
  const toast = useToast();
  const [v, setV] = useState({
    theme: s.theme,
    language: s.language,
    timezone: s.timezone,
    baseCurrency: s.baseCurrency,
    defaultRiskPercentage: s.defaultRiskPercentage,
    maxDailyLoss: s.maxDailyLoss ?? '',
    maxWeeklyLoss: s.maxWeeklyLoss ?? '',
    defaultTargetRr: s.defaultTargetRr,
  });
  const [errors, setErrors] = useState<Record<string, string[]>>({});
  const save = () =>
    update.mutate(
      { ...v, maxDailyLoss: v.maxDailyLoss || null, maxWeeklyLoss: v.maxWeeklyLoss || null } as never,
      {
        onSuccess: () => {
          setErrors({});
          toast('success', 'Preferences saved');
        },
        onError: (e) => {
          if (e instanceof ApiError && e.fields) setErrors(e.fields);
          toast('error', e.message);
        },
      },
    );
  return (
    <Panel title="Preferences">
      <div className="grid max-w-3xl gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <Field label="Theme" htmlFor="pr-theme">
          <Select
            id="pr-theme"
            value={v.theme}
            onChange={(e) => {
              setV({ ...v, theme: e.target.value as typeof v.theme });
              document.documentElement.dataset.theme = e.target.value;
            }}
          >
            {THEMES.map((t) => (
              <option key={t} value={t}>
                {THEME_NAMES[t]}
              </option>
            ))}
          </Select>
        </Field>
        <Field label="Language" htmlFor="pr-lang">
          <Select id="pr-lang" value={v.language} onChange={(e) => setV({ ...v, language: e.target.value as typeof v.language })}>
            {LANGUAGES.map((l) => (
              <option key={l} value={l}>
                {LANG_NAMES[l]}
              </option>
            ))}
          </Select>
        </Field>
        <Field label="Time zone" htmlFor="pr-tz" error={errors.timezone?.[0]}>
          {zones.length ? (
            <Select id="pr-tz" value={v.timezone} onChange={(e) => setV({ ...v, timezone: e.target.value })}>
              {zones.map((z) => (
                <option key={z}>{z}</option>
              ))}
            </Select>
          ) : (
            <Input id="pr-tz" value={v.timezone} onChange={(e) => setV({ ...v, timezone: e.target.value })} />
          )}
        </Field>
        <Field label="Base currency" htmlFor="pr-ccy">
          <Select id="pr-ccy" value={v.baseCurrency} onChange={(e) => setV({ ...v, baseCurrency: e.target.value })}>
            {CURRENCIES.map((c) => (
              <option key={c}>{c}</option>
            ))}
          </Select>
        </Field>
        <Field label="Risk per trade %" htmlFor="pr-risk" error={errors.defaultRiskPercentage?.[0]}>
          <Input id="pr-risk" className="num" inputMode="decimal" value={v.defaultRiskPercentage} onChange={(e) => setV({ ...v, defaultRiskPercentage: e.target.value })} />
        </Field>
        <Field label="Target R" htmlFor="pr-rr" error={errors.defaultTargetRr?.[0]}>
          <Input id="pr-rr" className="num" inputMode="decimal" value={v.defaultTargetRr} onChange={(e) => setV({ ...v, defaultTargetRr: e.target.value })} />
        </Field>
        <Field label="Max daily loss" htmlFor="pr-dl" error={errors.maxDailyLoss?.[0]}>
          <Input id="pr-dl" className="num" inputMode="decimal" value={v.maxDailyLoss} onChange={(e) => setV({ ...v, maxDailyLoss: e.target.value })} />
        </Field>
        <Field label="Max weekly loss" htmlFor="pr-wl" error={errors.maxWeeklyLoss?.[0]}>
          <Input id="pr-wl" className="num" inputMode="decimal" value={v.maxWeeklyLoss} onChange={(e) => setV({ ...v, maxWeeklyLoss: e.target.value })} />
        </Field>
      </div>
      <Button className="mt-4" variant="primary" loading={update.isPending} onClick={save}>
        Save preferences
      </Button>
    </Panel>
  );
}

function CapitalModal({ account, onClose }: { account: AccountWithFlags | null; onClose: () => void }) {
  const qc = useQueryClient();
  const toast = useToast();
  const [type, setType] = useState<'DEPOSIT' | 'WITHDRAWAL' | 'ADJUSTMENT'>('DEPOSIT');
  const [amount, setAmount] = useState('');
  const [note, setNote] = useState('');
  const list = useQuery({ enabled: !!account, queryKey: ['capital', account?.id], queryFn: () => get<CapitalTransactionDto[]>(`/accounts/${account!.id}/transactions`) });
  const add = useMutation({
    mutationFn: () => post(`/accounts/${account!.id}/transactions`, { type, amount, note: note || null }),
    onSuccess: () => {
      setAmount('');
      setNote('');
      void list.refetch();
      invalidateTradeData(qc);
      toast('success', 'Capital transaction recorded');
    },
    onError: (e) => toast('error', e instanceof ApiError && e.fields ? Object.values(e.fields).flat().join(' · ') : e.message),
  });
  const remove = useMutation({
    mutationFn: (id: string) => del(`/accounts/${account!.id}/transactions/${id}`),
    onSuccess: () => {
      void list.refetch();
      invalidateTradeData(qc);
    },
  });
  return (
    <Modal open={!!account} onClose={onClose} title={`Capital management · ${account?.accountName ?? ''}`} wide>
      <div className="grid gap-3 sm:grid-cols-4">
        <Field label="Type" htmlFor="cap-type">
          <Select id="cap-type" value={type} onChange={(e) => setType(e.target.value as typeof type)}>
            <option value="DEPOSIT">Deposit</option>
            <option value="WITHDRAWAL">Withdrawal</option>
            <option value="ADJUSTMENT">Adjustment (±)</option>
          </Select>
        </Field>
        <Field label={`Amount (${account?.currency})`} htmlFor="cap-amt">
          <Input id="cap-amt" className="num" inputMode="decimal" value={amount} onChange={(e) => setAmount(e.target.value)} />
        </Field>
        <Field label="Note" htmlFor="cap-note" className="sm:col-span-2">
          <Input id="cap-note" maxLength={500} value={note} onChange={(e) => setNote(e.target.value)} />
        </Field>
      </div>
      <Button className="mt-3" variant="primary" loading={add.isPending} disabled={!amount} onClick={() => add.mutate()}>
        Record
      </Button>
      <div className="mt-4">
        {list.isLoading ? (
          <Spinner />
        ) : (list.data ?? []).length === 0 ? (
          <p className="text-sm text-muted">No deposits, withdrawals or adjustments yet.</p>
        ) : (
          <ul className="divide-y divide-line text-sm">
            {list.data!.map((t) => (
              <li key={t.id} className="flex items-center gap-3 py-2">
                <Badge tone={t.type === 'WITHDRAWAL' ? 'loss' : t.type === 'DEPOSIT' ? 'profit' : 'neutral'}>{t.type}</Badge>
                <span className="num">{fmtMoney(t.type === 'WITHDRAWAL' ? `-${t.amount}` : t.amount, account?.currency, { sign: true })}</span>
                <span className="flex-1 truncate text-muted">{t.note}</span>
                <span className="text-2xs text-muted">{t.occurredAt.slice(0, 10)}</span>
                <button onClick={() => remove.mutate(t.id)} aria-label="Delete transaction" className="p-2 text-muted hover:text-loss">
                  <Trash2 className="h-4 w-4" />
                </button>
              </li>
            ))}
          </ul>
        )}
      </div>
    </Modal>
  );
}

function AccountsTab() {
  const accounts = useAccounts();
  const qc = useQueryClient();
  const toast = useToast();
  const [form, setForm] = useState({ accountName: '', brokerName: '', accountType: 'PERSONAL', currency: 'USD', startingCapital: '', demo: false });
  const [capital, setCapital] = useState<AccountWithFlags | null>(null);
  const [deleting, setDeleting] = useState<AccountWithFlags | null>(null);
  const create = useMutation({
    mutationFn: () => post('/accounts', { ...form, brokerName: form.brokerName || null }),
    onSuccess: () => {
      setForm({ ...form, accountName: '', brokerName: '', startingCapital: '' });
      invalidateTradeData(qc);
      toast('success', 'Account created');
    },
    onError: (e) => toast('error', e instanceof ApiError && e.fields ? Object.values(e.fields).flat().join(' · ') : e.message),
  });
  const archive = useMutation({
    mutationFn: (a: AccountWithFlags) => patch(`/accounts/${a.id}`, { archived: !a.archived }),
    onSuccess: () => invalidateTradeData(qc),
  });
  const remove = useMutation({
    mutationFn: (a: AccountWithFlags) => del(`/accounts/${a.id}`),
    onSuccess: () => {
      setDeleting(null);
      invalidateTradeData(qc);
      void qc.invalidateQueries({ queryKey: qk.me });
      toast('success', 'Account and its trades deleted');
    },
  });
  return (
    <div className="space-y-4">
      <Panel title="Trading accounts" bodyClassName="p-0">
        {accounts.isLoading ? (
          <Spinner />
        ) : accounts.isError ? (
          <ErrorState error={accounts.error} onRetry={() => accounts.refetch()} />
        ) : (
          <ul className="divide-y divide-line">
            {accounts.data!.map((a) => (
              <li key={a.id} className="flex flex-wrap items-center gap-3 px-4 py-3">
                <div className="min-w-40 flex-1">
                  <p className="flex items-center gap-2 font-semibold">
                    {a.accountName} {a.sampleData ? <DemoBadge /> : a.demo ? <Badge tone="info">Demo / practice</Badge> : <Badge tone="profit">Real</Badge>} {a.archived && <Badge>Archived</Badge>}
                  </p>
                  <p className="text-2xs text-muted">
                    {a.brokerName ?? 'No broker'} · {humanize(a.accountType)} · {a.tradeCount ?? 0} trades
                  </p>
                </div>
                <div className="text-right">
                  <p className="num font-semibold">{fmtMoney(a.currentCapital, a.currency)}</p>
                  <p className="num text-2xs text-muted">start {fmtMoney(a.startingCapital, a.currency)}</p>
                </div>
                <div className="flex gap-1">
                  {!a.sampleData && (
                    <>
                      <Button size="sm" onClick={() => setCapital(a)}>
                        Capital
                      </Button>
                      <Button size="sm" variant="ghost" icon={<Archive className="h-4 w-4" />} onClick={() => archive.mutate(a)} aria-label={a.archived ? `Unarchive ${a.accountName}` : `Archive ${a.accountName}`}>
                        {a.archived ? 'Unarchive' : 'Archive'}
                      </Button>
                    </>
                  )}
                  <Button size="sm" variant="ghost" icon={<Trash2 className="h-4 w-4" />} onClick={() => setDeleting(a)} aria-label={`Delete ${a.accountName}`} />
                </div>
              </li>
            ))}
          </ul>
        )}
      </Panel>
      <Panel title="Add trading account">
        <div className="grid gap-3 sm:grid-cols-3 lg:grid-cols-6">
          <Field label="Name" htmlFor="ac-name" className="lg:col-span-2">
            <Input id="ac-name" maxLength={80} value={form.accountName} onChange={(e) => setForm({ ...form, accountName: e.target.value })} />
          </Field>
          <Field label="Broker" htmlFor="ac-broker">
            <Input id="ac-broker" maxLength={80} value={form.brokerName} onChange={(e) => setForm({ ...form, brokerName: e.target.value })} />
          </Field>
          <Field label="Type" htmlFor="ac-type">
            <Select id="ac-type" value={form.accountType} onChange={(e) => setForm({ ...form, accountType: e.target.value })}>
              {ACCOUNT_TYPES.map((t) => (
                <option key={t} value={t}>
                  {humanize(t)}
                </option>
              ))}
            </Select>
          </Field>
          <Field label="Currency" htmlFor="ac-ccy">
            <Select id="ac-ccy" value={form.currency} onChange={(e) => setForm({ ...form, currency: e.target.value })}>
              {CURRENCIES.map((c) => (
                <option key={c}>{c}</option>
              ))}
            </Select>
          </Field>
          <Field label="Starting capital" htmlFor="ac-cap">
            <Input id="ac-cap" className="num" inputMode="decimal" value={form.startingCapital} onChange={(e) => setForm({ ...form, startingCapital: e.target.value })} />
          </Field>
        </div>
        <label className="mt-3 flex items-center gap-2 text-sm">
          <input type="checkbox" checked={form.demo} onChange={(e) => setForm({ ...form, demo: e.target.checked })} className="h-4 w-4 accent-[rgb(var(--accent))]" /> Demo / practice account (excluded from real analytics)
        </label>
        <Button className="mt-3" variant="primary" loading={create.isPending} disabled={!form.accountName || !form.startingCapital} onClick={() => create.mutate()}>
          Create account
        </Button>
      </Panel>
      <CapitalModal account={capital} onClose={() => setCapital(null)} />
      <ConfirmDialog
        open={!!deleting}
        onClose={() => setDeleting(null)}
        onConfirm={() => deleting && remove.mutate(deleting)}
        loading={remove.isPending}
        title="Delete trading account?"
        requireText={deleting?.accountName}
        message={
          <p>
            This permanently deletes <b>{deleting?.accountName}</b>, its {deleting?.tradeCount ?? 0} trades, screenshots and capital history, and disconnects linked broker connections.
          </p>
        }
      />
    </div>
  );
}

function BrokersTab() {
  const qc = useQueryClient();
  const toast = useToast();
  const accounts = useAccounts();
  const writable = (accounts.data ?? []).filter((a) => !a.sampleData && !a.archived);
  const providers = useQuery({ queryKey: [...qk.brokers, 'providers'], queryFn: () => get<BrokerProviderInfo[]>('/broker-connections/providers') });
  const conns = useQuery({ queryKey: qk.brokers, queryFn: () => get<BrokerConnectionDto[]>('/broker-connections') });
  const [connect, setConnect] = useState<BrokerProviderInfo | null>(null);
  const [secret, setSecret] = useState<{ secret: string; url: string | null } | null>(null);
  const [form, setForm] = useState({ label: '', tradingAccountId: '', apiKey: '', apiSecret: '', symbols: 'BTCUSDT,ETHUSDT' });
  const [importFor, setImportFor] = useState<BrokerConnectionDto | null>(null);
  const [offset, setOffset] = useState('0');
  const [file, setFile] = useState<File | null>(null);
  const [removing, setRemoving] = useState<BrokerConnectionDto | null>(null);

  const create = useMutation({
    mutationFn: () => {
      const provider = connect!.id;
      const base = { provider, label: form.label || connect!.name, tradingAccountId: form.tradingAccountId || writable[0]?.id };
      return post<{ connection: BrokerConnectionDto; webhookSecret?: string }>(
        '/broker-connections',
        provider === 'binance' ? { ...base, apiKey: form.apiKey, apiSecret: form.apiSecret, symbols: form.symbols.split(',').map((s) => s.trim().toUpperCase()).filter(Boolean) } : base,
      );
    },
    onSuccess: (res) => {
      void qc.invalidateQueries({ queryKey: qk.brokers });
      setConnect(null);
      setForm({ ...form, apiKey: '', apiSecret: '', label: '' });
      if (res.webhookSecret) setSecret({ secret: res.webhookSecret, url: res.connection.webhookUrl });
      toast('success', 'Connection created');
    },
    onError: (e) => toast('error', e.message),
  });
  const sync = useMutation({
    mutationFn: (c: BrokerConnectionDto) => post<{ imported: number; duplicates: number; failed: number }>(`/broker-connections/${c.id}/sync`),
    onSuccess: (r) => {
      invalidateTradeData(qc);
      void qc.invalidateQueries({ queryKey: qk.brokers });
      toast('success', `Sync complete: ${r.imported} imported, ${r.duplicates} duplicates, ${r.failed} failed`);
    },
    onError: () => {
      void qc.invalidateQueries({ queryKey: qk.brokers });
      toast('error', 'Broker synchronization is temporarily unavailable. Your existing journal data is safe.');
    },
  });
  const doImport = useMutation({
    mutationFn: () => {
      const fd = new FormData();
      fd.append('serverUtcOffsetMinutes', offset);
      fd.append('file', file!);
      return upload<{ imported: number; duplicates: number; failed: number; errors: Array<{ ref: string; message: string }> }>(`/broker-connections/${importFor!.id}/import`, fd);
    },
    onSuccess: (r) => {
      invalidateTradeData(qc);
      void qc.invalidateQueries({ queryKey: qk.brokers });
      setImportFor(null);
      setFile(null);
      toast(r.failed ? 'info' : 'success', `Import: ${r.imported} imported, ${r.duplicates} duplicates skipped, ${r.failed} rejected${r.errors[0] ? ` (e.g. ${r.errors[0].ref}: ${r.errors[0].message})` : ''}`);
    },
    onError: (e) => toast('error', e instanceof ApiError && e.fields ? Object.values(e.fields).flat().slice(0, 3).join(' · ') : e.message),
  });
  const rotate = useMutation({
    mutationFn: (c: BrokerConnectionDto) => post<{ webhookSecret: string }>(`/broker-connections/${c.id}/rotate-secret`),
    onSuccess: (r, c) => setSecret({ secret: r.webhookSecret, url: c.webhookUrl }),
  });
  const remove = useMutation({
    mutationFn: (c: BrokerConnectionDto) => del(`/broker-connections/${c.id}`),
    onSuccess: () => {
      setRemoving(null);
      void qc.invalidateQueries({ queryKey: qk.brokers });
      toast('success', 'Connection removed and credentials deleted');
    },
  });
  const tone = (s: string) => (s === 'AVAILABLE' ? 'profit' : s === 'BETA' ? 'info' : s === 'UNSUPPORTED' ? 'loss' : 'warn');
  const connectable = (p: BrokerProviderInfo) => p.connectable || ['vantage', 'exness', 'ftmo', 'fundednext', 'ninjatrader'].includes(p.id);

  return (
    <div className="space-y-4">
      <Panel title="Universal Broker Sync Hub">
        {providers.isLoading ? (
          <Spinner />
        ) : (
          <ul className="grid gap-2 md:grid-cols-2 xl:grid-cols-3">
            {(providers.data ?? []).map((p) => (
              <li key={p.id} className="rounded-md border border-line p-3">
                <div className="flex items-center justify-between gap-2">
                  <span className="font-semibold">{p.name}</span>
                  <Badge tone={tone(p.status)}>{humanize(p.status)}</Badge>
                </div>
                <p className="mt-1 text-2xs text-muted">{p.method}</p>
                <p className="mt-1 text-xs text-muted">{p.description}</p>
                {connectable(p) && (
                  <Button
                    size="sm"
                    className="mt-2"
                    icon={<Plug className="h-3.5 w-3.5" />}
                    disabled={writable.length === 0}
                    onClick={() => {
                      const viaCsv = !p.connectable;
                      setForm({ ...form, label: viaCsv ? `${p.name} statement` : p.name, tradingAccountId: writable[0]?.id ?? '' });
                      setConnect(viaCsv ? { ...(providers.data ?? []).find((x) => x.id === 'csv_import')!, name: `${p.name} (CSV import)` } : p);
                    }}
                  >
                    {p.connectable ? 'Connect' : 'Set up CSV import'}
                  </Button>
                )}
              </li>
            ))}
          </ul>
        )}
      </Panel>
      <Panel title="Your connections" bodyClassName="p-0">
        {conns.isLoading ? (
          <Spinner />
        ) : conns.isError ? (
          <ErrorState message="Broker synchronization is temporarily unavailable. Your existing journal data is safe." onRetry={() => conns.refetch()} />
        ) : conns.data!.length === 0 ? (
          <p className="p-4 text-sm text-muted">No broker connections yet.</p>
        ) : (
          <ul className="divide-y divide-line">
            {conns.data!.map((c) => (
              <li key={c.id} className="flex flex-wrap items-center gap-3 px-4 py-3 text-sm">
                <div className="min-w-48 flex-1">
                  <p className="font-semibold">
                    {c.label} <Badge tone={c.status === 'ACTIVE' ? 'profit' : c.status === 'ERROR' ? 'loss' : 'neutral'}>{c.status}</Badge>
                  </p>
                  <p className="text-2xs text-muted">
                    {humanize(c.provider)} {c.credentialHint ? `· ${c.credentialHint}` : ''} · last sync {c.lastSyncedAt ? new Date(c.lastSyncedAt).toLocaleString() : 'never'}
                  </p>
                  {c.webhookUrl && <p className="num mt-1 break-all text-2xs">{c.webhookUrl}</p>}
                  {c.lastError && <p className="text-2xs text-loss">{c.lastError}</p>}
                </div>
                <div className="flex flex-wrap gap-1">
                  {c.provider === 'csv_import' && (
                    <Button size="sm" icon={<Upload className="h-3.5 w-3.5" />} onClick={() => setImportFor(c)}>
                      Import CSV
                    </Button>
                  )}
                  {c.provider === 'binance' && (
                    <Button size="sm" icon={<RefreshCw className="h-3.5 w-3.5" />} loading={sync.isPending && sync.variables?.id === c.id} onClick={() => sync.mutate(c)}>
                      Sync now
                    </Button>
                  )}
                  {c.provider === 'webhook' && (
                    <Button size="sm" icon={<KeyRound className="h-3.5 w-3.5" />} onClick={() => rotate.mutate(c)}>
                      Rotate secret
                    </Button>
                  )}
                  <Button size="sm" variant="ghost" icon={<Trash2 className="h-3.5 w-3.5" />} onClick={() => setRemoving(c)} aria-label={`Remove ${c.label}`} />
                </div>
              </li>
            ))}
          </ul>
        )}
      </Panel>

      <Modal
        open={!!connect}
        onClose={() => setConnect(null)}
        title={`Connect · ${connect?.name ?? ''}`}
        footer={
          <>
            <Button onClick={() => setConnect(null)}>Cancel</Button>
            <Button variant="primary" loading={create.isPending} onClick={() => create.mutate()}>
              {connect?.id === 'binance' ? 'Verify & connect' : 'Create'}
            </Button>
          </>
        }
      >
        <div className="space-y-3">
          <Field label="Label" htmlFor="bc-label">
            <Input id="bc-label" value={form.label} maxLength={80} onChange={(e) => setForm({ ...form, label: e.target.value })} />
          </Field>
          <Field label="Import into account" htmlFor="bc-acc">
            <Select id="bc-acc" value={form.tradingAccountId} onChange={(e) => setForm({ ...form, tradingAccountId: e.target.value })}>
              {writable.map((a) => (
                <option key={a.id} value={a.id}>
                  {a.accountName} ({a.currency})
                </option>
              ))}
            </Select>
          </Field>
          {connect?.id === 'binance' && (
            <>
              <p className="rounded-md border border-info/40 bg-info/10 p-2 text-2xs">BETA. Create a <b>read-only</b> API key (disable trading and withdrawals). The key is verified against Binance, then encrypted with AES-256-GCM and never shown again.</p>
              <Field label="API key" htmlFor="bc-key">
                <Input id="bc-key" autoComplete="off" value={form.apiKey} onChange={(e) => setForm({ ...form, apiKey: e.target.value })} />
              </Field>
              <Field label="API secret" htmlFor="bc-secret">
                <Input id="bc-secret" type="password" autoComplete="off" value={form.apiSecret} onChange={(e) => setForm({ ...form, apiSecret: e.target.value })} />
              </Field>
              <Field label="Symbols (comma separated)" htmlFor="bc-sym">
                <Input id="bc-sym" value={form.symbols} onChange={(e) => setForm({ ...form, symbols: e.target.value })} />
              </Field>
            </>
          )}
          {connect?.id === 'webhook' && <p className="text-2xs text-muted">A signing secret is generated and shown exactly once. Sign each POST with HMAC-SHA256 over “timestamp.body” (see README → Broker webhooks).</p>}
          {connect?.id === 'csv_import' && <p className="text-2xs text-muted">After creating, use “Import CSV” to upload MT4/MT5, cTrader or NinjaTrader trade history. Re-imports skip duplicates by ticket.</p>}
        </div>
      </Modal>

      <Modal open={!!secret} onClose={() => setSecret(null)} title="Webhook signing secret" footer={<Button variant="primary" onClick={() => setSecret(null)}>I have stored it securely</Button>}>
        <p className="text-sm text-warn">Copy this secret now. It will not be shown again.</p>
        <div className="mt-2 flex items-center gap-2">
          <code className="num flex-1 break-all rounded bg-panel2 p-2 text-xs">{secret?.secret}</code>
          <Button size="sm" icon={<Copy className="h-3.5 w-3.5" />} aria-label="Copy secret" onClick={() => secret && navigator.clipboard?.writeText(secret.secret).then(() => toast('success', 'Copied'))} />
        </div>
        {secret?.url && (
          <p className="mt-3 text-2xs text-muted">
            Endpoint: <span className="num break-all">{secret.url}</span>
          </p>
        )}
      </Modal>

      <Modal
        open={!!importFor}
        onClose={() => setImportFor(null)}
        title={`Import CSV · ${importFor?.label ?? ''}`}
        footer={
          <>
            <Button onClick={() => setImportFor(null)}>Cancel</Button>
            <Button variant="primary" loading={doImport.isPending} disabled={!file} onClick={() => doImport.mutate()}>
              Import
            </Button>
          </>
        }
      >
        <div className="space-y-3">
          <Field label="CSV file" htmlFor="imp-file" hint="Columns are auto-detected (ticket, symbol, type, open time, open price, close price, volume, S/L, T/P, commission, swap, profit).">
            <input id="imp-file" type="file" accept=".csv,text/csv" onChange={(e) => setFile(e.target.files?.[0] ?? null)} className="text-sm" />
          </Field>
          <Field label="Broker server time offset from UTC (minutes)" htmlFor="imp-off" hint="MT4/MT5 servers often run at UTC+2 (120) or UTC+3 (180) in summer.">
            <Input id="imp-off" className="num" inputMode="numeric" value={offset} onChange={(e) => setOffset(e.target.value)} />
          </Field>
        </div>
      </Modal>
      <ConfirmDialog
        open={!!removing}
        onClose={() => setRemoving(null)}
        onConfirm={() => removing && remove.mutate(removing)}
        loading={remove.isPending}
        title="Remove connection?"
        confirmLabel="Remove"
        message={<p>The connection and its encrypted credentials are deleted. Previously imported trades are kept.</p>}
      />
    </div>
  );
}

function DataTab() {
  const { data: me } = useMe();
  const qc = useQueryClient();
  const toast = useToast();
  const navigate = useNavigate();
  const accounts = useAccounts();
  const hasDemo = (accounts.data ?? []).some((a) => a.sampleData);
  const [confirmDelete, setConfirmDelete] = useState(false);
  const [confirmReset, setConfirmReset] = useState(false);
  const [confirmClear, setConfirmClear] = useState(false);
  const exportJson = useMutation({ mutationFn: () => download('/account/export', {}, 'journzey-export.json'), onError: (e) => toast('error', e.message) });
  const exportCsv = useMutation({ mutationFn: () => download('/trades/export.csv', { account: 'all' }, `JournzeyAI_Trades_${todayIn(me?.settings?.timezone ?? 'UTC')}.csv`), onError: (e) => toast('error', e.message) });
  const reset = useMutation({
    mutationFn: () => post('/demo/reset'),
    onSuccess: () => {
      setConfirmReset(false);
      invalidateTradeData(qc);
      void qc.invalidateQueries({ queryKey: ['journal'] });
      toast('success', 'Demo data reset');
    },
  });
  const clear = useMutation({
    mutationFn: () => del('/demo'),
    onSuccess: () => {
      setConfirmClear(false);
      invalidateTradeData(qc);
      void qc.invalidateQueries({ queryKey: qk.me });
      void qc.invalidateQueries({ queryKey: ['journal'] });
      toast('success', 'Demo data removed — you are starting with your own data only');
    },
  });
  const deleteAccount = useMutation({
    mutationFn: () => del('/account', { confirmation: me!.user.email }),
    onSuccess: () => {
      qc.clear();
      qc.setQueryData(qk.me, null);
      navigate('/', { replace: true });
    },
    onError: (e) => toast('error', e.message),
  });
  return (
    <div className="space-y-4">
      <Panel title="Export my data">
        <p className="text-sm text-muted">Download everything you own — trades, journals, strategies, accounts, capital history and settings. Broker secrets are never exported.</p>
        <div className="mt-3 flex flex-wrap gap-2">
          <Button icon={<Download className="h-4 w-4" />} loading={exportJson.isPending} onClick={() => exportJson.mutate()}>
            Export JSON (all data)
          </Button>
          <Button icon={<Download className="h-4 w-4" />} loading={exportCsv.isPending} onClick={() => exportCsv.mutate()}>
            Export trades CSV
          </Button>
        </div>
      </Panel>
      <Panel title="Demo mode">
        <p className="text-sm text-muted">DEMO DATA lives in a separate “Demo Account” and demo journal, excluded from real analytics unless you select Demo or All in Account mode.</p>
        <div className="mt-3 flex flex-wrap gap-2">
          <Button onClick={() => setConfirmReset(true)}>{hasDemo ? 'Reset Demo Data' : 'Load Demo Data'}</Button>
          <Button onClick={() => setConfirmClear(true)} disabled={!hasDemo}>
            Start With Empty Account
          </Button>
        </div>
      </Panel>
      <Panel title="Delete account">
        <p className="text-sm text-muted">Permanently deletes your profile, trades, journals, strategies, settings, screenshots, broker connections and AI history. Security audit entries are retained without any link to you. This cannot be undone.</p>
        <Button className="mt-3" variant="danger" icon={<Trash2 className="h-4 w-4" />} onClick={() => setConfirmDelete(true)}>
          Delete Account
        </Button>
      </Panel>
      <ConfirmDialog open={confirmReset} onClose={() => setConfirmReset(false)} onConfirm={() => reset.mutate()} loading={reset.isPending} confirmLabel={hasDemo ? 'Reset' : 'Load'} title={hasDemo ? 'Reset demo data?' : 'Load demo data?'} message={<p>Demo trades and demo journal entries are regenerated. Your real data is not touched.</p>} />
      <ConfirmDialog open={confirmClear} onClose={() => setConfirmClear(false)} onConfirm={() => clear.mutate()} loading={clear.isPending} confirmLabel="Remove demo data" title="Start with an empty account?" message={<p>All DEMO DATA (demo account, trades and demo journal) is deleted. Your real data is not touched.</p>} />
      <ConfirmDialog
        open={confirmDelete}
        onClose={() => setConfirmDelete(false)}
        onConfirm={() => deleteAccount.mutate()}
        loading={deleteAccount.isPending}
        title="Delete your journzey.ai account?"
        confirmLabel="Permanently delete"
        requireText={me?.user.email}
        message={<p>Consider exporting your data first. Deletion is immediate and irreversible.</p>}
      />
    </div>
  );
}

export default function Settings() {
  const [tab, setTab] = useState<Tab>('profile');
  return (
    <>
      <PageHeader title="Account Settings" />
      <div className="mb-4">
        <Tabs
          label="Settings sections"
          value={tab}
          onChange={setTab}
          items={[
            { value: 'profile', label: 'Profile' },
            { value: 'preferences', label: 'Preferences' },
            { value: 'accounts', label: 'Accounts & capital' },
            { value: 'brokers', label: 'Broker sync' },
            { value: 'data', label: 'Data & privacy' },
          ]}
        />
      </div>
      {tab === 'profile' && <ProfileTab />}
      {tab === 'preferences' && <PreferencesTab />}
      {tab === 'accounts' && <AccountsTab />}
      {tab === 'brokers' && <BrokersTab />}
      {tab === 'data' && <DataTab />}
    </>
  );
}
