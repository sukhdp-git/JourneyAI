import { useQuery } from '@tanstack/react-query';
import { get } from '../lib/api';
import type { SystemInfo } from '../lib/types';
import { Badge, Card, ErrorBox, PageHeader, Spinner } from '../components/ui';

const uptime = (s: number) => `${Math.floor(s / 86400)}d ${Math.floor((s % 86400) / 3600)}h ${Math.floor((s % 3600) / 60)}m`;

export default function System() {
  const q = useQuery({ queryKey: ['system'], queryFn: () => get<SystemInfo>('/system'), refetchInterval: 30_000 });
  if (q.isLoading) return <Spinner />;
  if (q.isError) return <ErrorBox error={q.error} onRetry={() => q.refetch()} />;
  const s = q.data!;
  const migrationsOk = s.migrations.applied === s.migrations.available;
  const rows: Array<[string, React.ReactNode]> = [
    ['Environment', <Badge tone={s.environment === 'production' ? 'green' : 'amber'}>{s.environment}</Badge>],
    ['Uptime', uptime(s.uptimeSeconds)],
    ['Node.js', s.nodeVersion],
    ['Memory (RSS)', `${s.memoryMb} MB`],
    ['Database latency', <Badge tone={s.dbLatencyMs < 50 ? 'green' : 'amber'}>{s.dbLatencyMs} ms</Badge>],
    ['Migrations', <Badge tone={migrationsOk ? 'green' : 'red'}>{s.migrations.applied} / {s.migrations.available} applied</Badge>],
    ['Screenshot storage', s.storage === 's3' ? 'S3-compatible bucket' : 'Local filesystem'],
    ['Error reporting (Sentry)', s.sentry ? <Badge tone="green">Enabled</Badge> : <Badge>Off</Badge>],
    ['Control-panel IP allow-list', s.ipAllowlist ? <Badge tone="green">Enabled</Badge> : <Badge tone="amber">Off</Badge>],
    ['Developer sign-in bypass', s.devAuthBypass ? <Badge tone="red">ON — development only</Badge> : <Badge tone="green">Off</Badge>],
  ];
  return (
    <>
      <PageHeader title="System health" description="Runtime status of this API instance (refreshes every 30 s)." />
      <Card>
        <dl className="divide-y divide-slate-100">
          {rows.map(([k, v]) => (
            <div key={k} className="flex items-center justify-between gap-4 py-3 text-sm">
              <dt className="text-slate-500">{k}</dt>
              <dd className="font-medium">{v}</dd>
            </div>
          ))}
        </dl>
      </Card>
    </>
  );
}
