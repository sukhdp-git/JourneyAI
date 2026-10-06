import { useState } from 'react';
import { useMutation, useQuery } from '@tanstack/react-query';
import { Copy, FileDown, Sparkles } from 'lucide-react';
import type { PerformanceReview } from '@journzey/shared';
import { Button, ErrorState, Input, Panel, Spinner, Tabs, useToast } from './ui';
import { get, post, ApiError } from '../lib/api';
import { todayIn } from '../lib/format';
import { qk, useMe, useScope } from '../lib/queries';

/** Weekly / monthly review generator. Deterministic server-side review; optional AI narrative. */
export function PerformanceMessenger() {
  const { data: me } = useMe();
  const { scope } = useScope();
  const toast = useToast();
  const [period, setPeriod] = useState<'week' | 'month'>('week');
  const [anchor, setAnchor] = useState(() => todayIn(me?.settings?.timezone ?? 'UTC'));
  const params = { period, anchorDate: anchor, account: scope };
  const q = useQuery({ queryKey: qk.analytics('review', params), queryFn: () => get<PerformanceReview>('/analytics/review', params) });
  const ai = useMutation({
    mutationFn: () => post<{ narrative: string }>('/ai/monthly-review', { period, anchorDate: anchor, account: scope, language: me?.settings?.language ?? 'en' }),
  });
  const text = ai.data ? `${q.data?.text ?? ''}\n\n— AI Coach narrative —\n${ai.data.narrative}` : q.data?.text ?? '';
  const copy = async () => {
    try {
      await navigator.clipboard.writeText(text);
      toast('success', 'Review copied');
    } catch {
      toast('error', 'Clipboard unavailable — select the text and copy manually');
    }
  };
  return (
    <Panel
      title="Performance Messenger"
      actions={
        <div className="no-print flex gap-1">
          <Button size="sm" icon={<Copy className="h-3.5 w-3.5" />} onClick={copy} disabled={!q.data}>
            Copy review
          </Button>
          <Button size="sm" icon={<FileDown className="h-3.5 w-3.5" />} onClick={() => window.print()} disabled={!q.data} title="Opens the print dialog — choose “Save as PDF”">
            Export PDF
          </Button>
        </div>
      }
      className="print-area"
    >
      <div className="no-print mb-3 flex flex-wrap items-center gap-2">
        <Tabs label="Review period" value={period} onChange={setPeriod} items={[{ value: 'week', label: 'Weekly review' }, { value: 'month', label: 'Monthly review' }]} />
        <Input aria-label="Any date in the period" type="date" className="h-9 w-auto text-xs" value={anchor} onChange={(e) => e.target.value && setAnchor(e.target.value)} />
        <Button
          size="sm"
          variant="primary"
          icon={<Sparkles className="h-3.5 w-3.5" />}
          onClick={() => ai.mutate()}
          loading={ai.isPending}
          disabled={!me?.features.ai}
          title={me?.features.ai ? 'Ask the AI Coach to narrate this review' : 'AI Coach requires server configuration.'}
        >
          AI narrative
        </Button>
        {!me?.features.ai && <span className="text-2xs text-muted">AI Coach requires server configuration.</span>}
      </div>
      {q.isLoading ? (
        <Spinner />
      ) : q.isError ? (
        <ErrorState error={q.error} onRetry={() => q.refetch()} />
      ) : (
        <>
          <pre className="whitespace-pre-wrap break-words rounded-md bg-panel2 p-3 font-mono text-xs leading-relaxed">{q.data!.text}</pre>
          {ai.data && (
            <div className="mt-3 rounded-md border border-accent/40 p-3">
              <p className="label text-accent">AI Coach narrative</p>
              <p className="mt-2 whitespace-pre-wrap text-sm leading-relaxed">{ai.data.narrative}</p>
            </div>
          )}
          {ai.error && <p role="alert" className="mt-2 text-2xs text-loss">{ai.error instanceof ApiError ? ai.error.message : 'AI Coach is unavailable right now. Your journal and analytics remain available.'}</p>}
        </>
      )}
    </Panel>
  );
}
