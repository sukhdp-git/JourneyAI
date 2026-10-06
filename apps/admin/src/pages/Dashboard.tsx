import { Link } from 'react-router';
import { useQuery } from '@tanstack/react-query';
import { Bar, BarChart, CartesianGrid, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';
import { ArrowRight, Bot, CandlestickChart, Globe, KeyRound, Users } from 'lucide-react';
import { get } from '../lib/api';
import type { AuditItem, Overview } from '../lib/types';
import { Badge, Card, ErrorBox, PageHeader, Spinner, fmtDateTime, fmtInt } from '../components/ui';

function Stat({ label, value, sub }: { label: string; value: number; sub?: string }) {
  return (
    <div className="card p-5">
      <p className="text-sm font-medium text-slate-500">{label}</p>
      <p className="tnum mt-2 text-3xl font-semibold tracking-tight text-slate-900">{fmtInt(value)}</p>
      {sub && <p className="mt-1 text-xs text-slate-500">{sub}</p>}
    </div>
  );
}

/** Single-series daily bar chart (one measure per chart — never a dual axis). */
function DailyChart({ data, dataKey, label }: { data: Overview['daily']; dataKey: 'signups' | 'trades'; label: string }) {
  const total = data.reduce((s, d) => s + d[dataKey], 0);
  return (
    <figure>
      <figcaption className="mb-3 flex items-baseline justify-between">
        <span className="text-sm font-medium text-slate-700">{label}</span>
        <span className="tnum text-sm text-slate-500">{fmtInt(total)} in 30 days</span>
      </figcaption>
      <div className="h-48" role="img" aria-label={`${label}: ${total} over the last 30 days`}>
        <ResponsiveContainer>
          <BarChart data={data} margin={{ top: 4, right: 4, bottom: 0, left: -16 }} barCategoryGap={2}>
            <CartesianGrid vertical={false} stroke="#e2e8f0" />
            <XAxis dataKey="day" tickFormatter={(d: string) => d.slice(5)} tick={{ fill: '#64748b', fontSize: 11 }} tickLine={false} axisLine={{ stroke: '#cbd5e1' }} minTickGap={24} />
            <YAxis allowDecimals={false} tick={{ fill: '#64748b', fontSize: 11 }} tickLine={false} axisLine={false} width={40} />
            <Tooltip
              cursor={{ fill: '#eef2ff' }}
              contentStyle={{ borderRadius: 8, border: '1px solid #e2e8f0', fontSize: 12, color: '#0f172a' }}
              formatter={(v: number) => [fmtInt(v), label]}
              labelFormatter={(d: string) => new Date(`${d}T00:00:00Z`).toLocaleDateString(undefined, { dateStyle: 'medium', timeZone: 'UTC' })}
            />
            <Bar dataKey={dataKey} fill="#4f46e5" radius={[4, 4, 0, 0]} isAnimationActive={false} />
          </BarChart>
        </ResponsiveContainer>
      </div>
    </figure>
  );
}

export default function Dashboard() {
  const q = useQuery({ queryKey: ['overview'], queryFn: () => get<Overview>('/overview'), refetchInterval: 60_000 });
  const audit = useQuery({ queryKey: ['audit', 'recent'], queryFn: () => get<{ items: AuditItem[] }>('/audit?source=admin&pageSize=8') });
  if (q.isLoading) return <Spinner />;
  if (q.isError) return <ErrorBox error={q.error} onRetry={() => q.refetch()} />;
  const o = q.data!;
  const s = o.stats;
  const integ = [
    { icon: KeyRound, name: 'Google sign-in', ok: o.integrations.google.configured, detail: o.integrations.google.enabled ? (o.integrations.google.configured ? 'Accepting sign-ins' : 'Missing client ID/secret') : 'Disabled' },
    { icon: Bot, name: 'AI Coach', ok: o.integrations.ai.active, detail: o.integrations.ai.active ? o.integrations.ai.model : o.integrations.ai.configured ? 'Key set, feature disabled' : 'No API key' },
    { icon: CandlestickChart, name: 'Market data', ok: o.integrations.marketData.active, detail: o.integrations.marketData.active ? 'Live quotes' : 'Showing DEMO DATA' },
  ];
  return (
    <>
      <PageHeader title="Dashboard" description="Platform health and activity across the live journzey.ai website." />
      {o.site.maintenance && (
        <div role="status" className="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
          Maintenance mode is ON — traders cannot use the terminal. <Link to="/website" className="font-medium underline">Website controls</Link>
        </div>
      )}
      <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <Stat label="Registered traders" value={s.users} sub={`${fmtInt(s.newUsers7d)} new this week · ${fmtInt(s.usersSuspended)} suspended`} />
        <Stat label="Active traders (7 days)" value={s.activeUsers7d} sub={`${fmtInt(s.activeSessions)} live sessions`} />
        <Stat label="Trades logged" value={s.trades} sub={`${fmtInt(s.trades24h)} in the last 24 h · excludes demo data`} />
        <Stat label="AI coach replies (30 days)" value={s.aiMessages30d} sub={`${fmtInt(s.journals)} journal entries · ${fmtInt(s.brokerConnections)} broker links`} />
      </div>
      <div className="mt-6 grid gap-6 lg:grid-cols-3">
        <Card title="Activity" description="Last 30 days (UTC)" className="lg:col-span-2">
          <div className="grid gap-8 md:grid-cols-2">
            <DailyChart data={o.daily} dataKey="signups" label="New sign-ups per day" />
            <DailyChart data={o.daily} dataKey="trades" label="Trades logged per day" />
          </div>
        </Card>
        <Card title="Integrations" actions={<Link to="/integrations" className="text-sm font-medium text-brand-600 hover:text-brand-700">Manage</Link>}>
          <ul className="space-y-4">
            {integ.map((i) => (
              <li key={i.name} className="flex items-center gap-3">
                <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                  <i.icon className="h-4 w-4" aria-hidden />
                </span>
                <div className="min-w-0 flex-1">
                  <p className="text-sm font-medium">{i.name}</p>
                  <p className="truncate text-xs text-slate-500">{i.detail}</p>
                </div>
                {i.ok ? <Badge tone="green" dot>Active</Badge> : <Badge tone="amber" dot>Action needed</Badge>}
              </li>
            ))}
            <li className="flex items-center gap-3">
              <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                <Globe className="h-4 w-4" aria-hidden />
              </span>
              <div className="flex-1">
                <p className="text-sm font-medium">Website</p>
                <p className="text-xs text-slate-500">
                  Sign-ups {o.site.registrationOpen ? 'open' : 'closed'} · banner {o.site.announcement ? 'on' : 'off'}
                </p>
              </div>
              {o.site.maintenance ? <Badge tone="amber" dot>Maintenance</Badge> : <Badge tone="green" dot>Online</Badge>}
            </li>
          </ul>
        </Card>
      </div>
      <div className="mt-6 grid gap-6 lg:grid-cols-3">
        <Card title="Recent administrator activity" className="lg:col-span-2" actions={<Link to="/audit" className="text-sm font-medium text-brand-600 hover:text-brand-700">View all</Link>} bodyClassName="p-0">
          {audit.isLoading ? (
            <Spinner />
          ) : (
            <ul className="divide-y divide-slate-100">
              {(audit.data?.items ?? []).map((a) => (
                <li key={a.id} className="flex flex-wrap items-center gap-x-4 gap-y-1 px-5 py-3 text-sm">
                  <span className="font-mono text-xs text-slate-700">{a.action}</span>
                  <span className="text-slate-500">{a.actor ?? 'system'}</span>
                  {a.target && <span className="truncate text-slate-400">{a.target}</span>}
                  <span className="ml-auto text-xs text-slate-400">{fmtDateTime(a.at)}</span>
                </li>
              ))}
              {audit.data?.items.length === 0 && <li className="px-5 py-6 text-sm text-slate-500">No activity yet.</li>}
            </ul>
          )}
        </Card>
        <Card title="Quick actions">
          <div className="space-y-2">
            {[
              { to: '/integrations', label: 'Add Google / AI / market-data keys' },
              { to: '/website', label: 'Maintenance mode & announcements' },
              { to: '/users', label: 'Manage traders' },
              { to: '/account', label: 'Enable two-factor authentication' },
            ].map((l) => (
              <Link key={l.to} to={l.to} className="flex items-center justify-between rounded-lg border border-slate-200 px-4 py-3 text-sm hover:border-brand-500 hover:bg-brand-50">
                {l.label} <ArrowRight className="h-4 w-4 text-slate-400" aria-hidden />
              </Link>
            ))}
            <p className="flex items-center gap-2 pt-2 text-xs text-slate-500">
              <Users className="h-3.5 w-3.5" aria-hidden /> {fmtInt(s.usersOnboarded)} of {fmtInt(s.users)} traders completed onboarding
            </p>
          </div>
        </Card>
      </div>
    </>
  );
}
