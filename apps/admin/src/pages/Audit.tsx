import { useEffect, useState } from 'react';
import { keepPreviousData, useQuery } from '@tanstack/react-query';
import { get } from '../lib/api';
import type { AuditItem } from '../lib/types';
import { Button, Card, ErrorBox, PageHeader, Spinner, fmtDateTime } from '../components/ui';

export default function Audit() {
  const [source, setSource] = useState<'admin' | 'users'>('admin');
  const [input, setInput] = useState('');
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);
  useEffect(() => {
    const id = setTimeout(() => (setSearch(input), setPage(1)), 300);
    return () => clearTimeout(id);
  }, [input]);
  const qs = new URLSearchParams({ source, page: String(page), pageSize: '50', ...(search ? { search } : {}) });
  const q = useQuery({ queryKey: ['audit', qs.toString()], queryFn: () => get<{ items: AuditItem[]; total: number }>(`/audit?${qs}`), placeholderData: keepPreviousData });
  const pages = Math.max(1, Math.ceil((q.data?.total ?? 0) / 50));
  return (
    <>
      <PageHeader title="Audit log" description="Tamper-evident record of security-sensitive actions. Secret values are never recorded." />
      <Card bodyClassName="p-0">
        <div className="flex flex-wrap gap-3 border-b border-slate-100 p-4">
          <div role="tablist" aria-label="Audit source" className="inline-flex rounded-lg bg-slate-100 p-1">
            {(['admin', 'users'] as const).map((s) => (
              <button key={s} role="tab" aria-selected={source === s} onClick={() => (setSource(s), setPage(1))} className={`rounded-md px-3 py-1.5 text-sm font-medium ${source === s ? 'bg-white shadow-sm' : 'text-slate-600'}`}>
                {s === 'admin' ? 'Control panel' : 'Trader security events'}
              </button>
            ))}
          </div>
          <input aria-label="Filter audit log" className="input max-w-xs" placeholder="Filter by action, email…" value={input} onChange={(e) => setInput(e.target.value)} />
        </div>
        {q.isLoading ? (
          <Spinner />
        ) : q.isError ? (
          <div className="p-4"><ErrorBox error={q.error} onRetry={() => q.refetch()} /></div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <caption className="sr-only">Audit events</caption>
              <thead className="bg-slate-50 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                <tr>
                  <th scope="col" className="px-5 py-3">Time</th>
                  <th scope="col" className="px-5 py-3">Action</th>
                  <th scope="col" className="px-5 py-3">Actor</th>
                  <th scope="col" className="px-5 py-3">Target</th>
                  <th scope="col" className="px-5 py-3">IP</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {q.data!.items.map((a) => (
                  <tr key={a.id}>
                    <td className="whitespace-nowrap px-5 py-2.5 text-slate-500">{fmtDateTime(a.at)}</td>
                    <td className="px-5 py-2.5 font-mono text-xs">{a.action}</td>
                    <td className="px-5 py-2.5">{a.actor ?? 'system'}</td>
                    <td className="max-w-xs truncate px-5 py-2.5 text-slate-500" title={JSON.stringify(a.metadata)}>{a.target ?? ''}</td>
                    <td className="px-5 py-2.5 font-mono text-xs text-slate-500">{a.ip ?? ''}</td>
                  </tr>
                ))}
                {q.data!.items.length === 0 && (
                  <tr><td colSpan={5} className="px-5 py-8 text-center text-slate-500">No events.</td></tr>
                )}
              </tbody>
            </table>
          </div>
        )}
        <div className="flex items-center justify-between border-t border-slate-100 px-5 py-3 text-sm text-slate-500">
          <span>{q.data?.total ?? 0} events</span>
          <div className="flex gap-2">
            <Button size="sm" disabled={page <= 1} onClick={() => setPage((p) => p - 1)}>Previous</Button>
            <Button size="sm" disabled={page >= pages} onClick={() => setPage((p) => p + 1)}>Next</Button>
          </div>
        </div>
      </Card>
    </>
  );
}
