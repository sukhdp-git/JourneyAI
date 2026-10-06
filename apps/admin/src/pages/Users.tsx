import { useEffect, useState } from 'react';
import { Link } from 'react-router';
import { keepPreviousData, useQuery } from '@tanstack/react-query';
import { Search } from 'lucide-react';
import { get } from '../lib/api';
import type { UserRow } from '../lib/types';
import { Badge, Button, Card, ErrorBox, PageHeader, Spinner, fmtDateTime } from '../components/ui';

export default function Users() {
  const [input, setInput] = useState('');
  const [search, setSearch] = useState('');
  const [status, setStatus] = useState<'all' | 'active' | 'suspended'>('all');
  const [page, setPage] = useState(1);
  useEffect(() => {
    const id = setTimeout(() => {
      setSearch(input);
      setPage(1);
    }, 300);
    return () => clearTimeout(id);
  }, [input]);
  const qs = new URLSearchParams({ page: String(page), pageSize: '25', status, ...(search ? { search } : {}) });
  const q = useQuery({ queryKey: ['users', qs.toString()], queryFn: () => get<{ items: UserRow[]; total: number }>(`/users?${qs}`), placeholderData: keepPreviousData });
  const pages = Math.max(1, Math.ceil((q.data?.total ?? 0) / 25));
  return (
    <>
      <PageHeader title="Users" description="Traders registered on the live website." />
      <Card bodyClassName="p-0">
        <div className="flex flex-wrap gap-3 border-b border-slate-100 p-4">
          <div className="relative min-w-60 flex-1">
            <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" aria-hidden />
            <input aria-label="Search users" className="input pl-9" placeholder="Search by name or email" value={input} onChange={(e) => setInput(e.target.value)} />
          </div>
          <select aria-label="Status filter" className="input w-auto" value={status} onChange={(e) => (setStatus(e.target.value as typeof status), setPage(1))}>
            <option value="all">All users</option>
            <option value="active">Active</option>
            <option value="suspended">Suspended</option>
          </select>
        </div>
        {q.isLoading ? (
          <Spinner />
        ) : q.isError ? (
          <div className="p-4">
            <ErrorBox error={q.error} onRetry={() => q.refetch()} />
          </div>
        ) : q.data!.items.length === 0 ? (
          <p className="px-5 py-10 text-center text-sm text-slate-500">No users match.</p>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <caption className="sr-only">Users</caption>
              <thead className="bg-slate-50 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                <tr>
                  <th scope="col" className="px-5 py-3">User</th>
                  <th scope="col" className="px-5 py-3">Status</th>
                  <th scope="col" className="px-5 py-3 text-right">Trades</th>
                  <th scope="col" className="px-5 py-3">Joined</th>
                  <th scope="col" className="px-5 py-3">Last sign-in</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {q.data!.items.map((u) => (
                  <tr key={u.id} className="hover:bg-slate-50">
                    <td className="px-5 py-3">
                      <Link to={`/users/${u.id}`} className="flex items-center gap-3">
                        {u.avatarUrl ? (
                          <img src={u.avatarUrl} alt="" referrerPolicy="no-referrer" className="h-8 w-8 rounded-full" />
                        ) : (
                          <span className="flex h-8 w-8 items-center justify-center rounded-full bg-slate-200 text-xs font-semibold" aria-hidden>
                            {(u.name ?? u.email).slice(0, 1).toUpperCase()}
                          </span>
                        )}
                        <span>
                          <span className="block font-medium text-slate-900 hover:text-brand-600">{u.name ?? '—'}</span>
                          <span className="block text-xs text-slate-500">{u.email}</span>
                        </span>
                      </Link>
                    </td>
                    <td className="px-5 py-3">
                      {u.suspended ? <Badge tone="red" dot>Suspended</Badge> : u.onboarded ? <Badge tone="green" dot>Active</Badge> : <Badge tone="amber" dot>Onboarding</Badge>}
                      {u.authProvider === 'dev' && <span className="ml-2"><Badge>dev login</Badge></span>}
                    </td>
                    <td className="tnum px-5 py-3 text-right">{u.trades.toLocaleString()}</td>
                    <td className="px-5 py-3 text-slate-500">{fmtDateTime(u.createdAt)}</td>
                    <td className="px-5 py-3 text-slate-500">{fmtDateTime(u.lastLoginAt)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
        <div className="flex items-center justify-between border-t border-slate-100 px-5 py-3 text-sm text-slate-500">
          <span>{q.data?.total ?? 0} users</span>
          <div className="flex gap-2">
            <Button size="sm" disabled={page <= 1} onClick={() => setPage((p) => p - 1)}>Previous</Button>
            <Button size="sm" disabled={page >= pages} onClick={() => setPage((p) => p + 1)}>Next</Button>
          </div>
        </div>
      </Card>
    </>
  );
}
