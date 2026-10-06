import { useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { ArrowLeft, Ban, LogOut, RotateCcw, Trash2 } from 'lucide-react';
import { del, errMsg, get, post } from '../lib/api';
import { useAuth } from '../lib/auth';
import type { UserDetail as Detail } from '../lib/types';
import { Badge, Button, Card, Confirm, ErrorBox, PageHeader, Spinner, fmtDateTime, useToast } from '../components/ui';

export default function UserDetail() {
  const { id } = useParams();
  const { can } = useAuth();
  const qc = useQueryClient();
  const toast = useToast();
  const navigate = useNavigate();
  const [confirm, setConfirm] = useState<'suspend' | 'delete' | null>(null);
  const q = useQuery({ queryKey: ['user', id], queryFn: () => get<Detail>(`/users/${id}`) });
  const done = (msg: string) => (d: Detail | void) => {
    if (d) qc.setQueryData(['user', id], d);
    void qc.invalidateQueries({ queryKey: ['users'] });
    setConfirm(null);
    toast(true, msg);
  };
  const suspend = useMutation({ mutationFn: () => post<Detail>(`/users/${id}/suspend`), onSuccess: done('User suspended and signed out'), onError: (e) => toast(false, errMsg(e)) });
  const unsuspend = useMutation({ mutationFn: () => post<Detail>(`/users/${id}/unsuspend`), onSuccess: done('User restored'), onError: (e) => toast(false, errMsg(e)) });
  const signOut = useMutation({
    mutationFn: () => post<{ sessionsRevoked: number }>(`/users/${id}/sign-out`),
    onSuccess: (r) => {
      void q.refetch();
      toast(true, `${r.sessionsRevoked} session(s) revoked`);
    },
    onError: (e) => toast(false, errMsg(e)),
  });
  const remove = useMutation({
    mutationFn: () => del(`/users/${id}`, { confirmation: q.data!.email }),
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: ['users'] });
      toast(true, 'User and all their data deleted');
      navigate('/users', { replace: true });
    },
    onError: (e) => toast(false, errMsg(e)),
  });
  if (q.isLoading) return <Spinner />;
  if (q.isError) return <ErrorBox error={q.error} onRetry={() => q.refetch()} />;
  const u = q.data!;
  return (
    <>
      <Link to="/users" className="mb-4 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-800">
        <ArrowLeft className="h-4 w-4" aria-hidden /> All users
      </Link>
      <PageHeader
        title={u.name ?? u.email}
        description={u.email}
        actions={
          can('admin') && (
            <>
              <Button icon={<LogOut className="h-4 w-4" />} onClick={() => signOut.mutate()} loading={signOut.isPending}>
                Force sign-out
              </Button>
              {u.suspendedAt ? (
                <Button icon={<RotateCcw className="h-4 w-4" />} onClick={() => unsuspend.mutate()} loading={unsuspend.isPending}>
                  Restore access
                </Button>
              ) : (
                <Button icon={<Ban className="h-4 w-4" />} onClick={() => setConfirm('suspend')}>
                  Suspend
                </Button>
              )}
              {can('owner') && (
                <Button variant="danger" icon={<Trash2 className="h-4 w-4" />} onClick={() => setConfirm('delete')}>
                  Delete
                </Button>
              )}
            </>
          )
        }
      />
      <div className="grid gap-6 lg:grid-cols-3">
        <Card title="Profile" className="lg:col-span-1">
          <dl className="space-y-3 text-sm">
            {[
              ['Status', u.suspendedAt ? <Badge tone="red" dot>Suspended {fmtDateTime(u.suspendedAt)}</Badge> : <Badge tone="green" dot>Active</Badge>],
              ['Sign-in', u.authProvider === 'google' ? 'Google' : 'Developer (local only)'],
              ['Email verified', u.emailVerified ? 'Yes' : 'No'],
              ['Onboarded', u.onboarded ? 'Yes' : 'No'],
              ['Joined', fmtDateTime(u.createdAt)],
              ['Last sign-in', fmtDateTime(u.lastLoginAt)],
              ['Live sessions', u.counts.sessions],
            ].map(([k, v]) => (
              <div key={String(k)} className="flex justify-between gap-4">
                <dt className="text-slate-500">{k}</dt>
                <dd className="text-right font-medium">{v}</dd>
              </div>
            ))}
          </dl>
        </Card>
        <Card title="Usage" className="lg:col-span-2">
          <dl className="grid grid-cols-3 gap-4 text-center">
            {[
              ['Trades', u.counts.trades],
              ['Journal entries', u.counts.journals],
              ['Broker connections', u.counts.brokerConnections],
            ].map(([k, v]) => (
              <div key={String(k)} className="rounded-lg bg-slate-50 p-4">
                <dt className="text-xs text-slate-500">{k}</dt>
                <dd className="tnum mt-1 text-2xl font-semibold">{v}</dd>
              </div>
            ))}
          </dl>
          <h3 className="mt-6 text-sm font-medium">Trading accounts</h3>
          <ul className="mt-2 divide-y divide-slate-100 text-sm">
            {u.accounts.map((a) => (
              <li key={a.id} className="flex items-center justify-between py-2">
                <span>
                  {a.name} {a.sampleData ? <Badge tone="amber">Demo data</Badge> : a.demo ? <Badge>Practice</Badge> : <Badge tone="green">Real</Badge>}
                </span>
                <span className="tnum text-slate-600">
                  {Number(a.currentCapital).toLocaleString(undefined, { minimumFractionDigits: 2 })} {a.currency}
                </span>
              </li>
            ))}
            {u.accounts.length === 0 && <li className="py-2 text-slate-500">No accounts yet.</li>}
          </ul>
        </Card>
        <Card title="Security events" className="lg:col-span-3" bodyClassName="p-0">
          <ul className="divide-y divide-slate-100 text-sm">
            {u.events.map((e, i) => (
              <li key={i} className="flex gap-4 px-5 py-2.5">
                <span className="font-mono text-xs">{e.action}</span>
                <span className="text-slate-500">{e.ip ?? ''}</span>
                <span className="ml-auto text-xs text-slate-400">{fmtDateTime(e.at)}</span>
              </li>
            ))}
            {u.events.length === 0 && <li className="px-5 py-4 text-slate-500">No events.</li>}
          </ul>
        </Card>
      </div>
      <Confirm
        open={confirm === 'suspend'}
        onClose={() => setConfirm(null)}
        onConfirm={() => suspend.mutate()}
        loading={suspend.isPending}
        title="Suspend this user?"
        confirmLabel="Suspend"
        danger
        message={<p>They are signed out everywhere immediately and cannot sign in until restored. Their data is kept.</p>}
      />
      <Confirm
        open={confirm === 'delete'}
        onClose={() => setConfirm(null)}
        onConfirm={() => remove.mutate()}
        loading={remove.isPending}
        title="Permanently delete this user?"
        confirmLabel="Delete permanently"
        danger
        requireText={u.email}
        message={<p>Deletes the account and all trades, journals, strategies, screenshots, broker connections and AI history. This cannot be undone.</p>}
      />
    </>
  );
}
