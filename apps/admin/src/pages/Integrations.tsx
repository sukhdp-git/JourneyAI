import { useState } from 'react';
import { useMutation, useQuery } from '@tanstack/react-query';
import { Bot, CandlestickChart, CheckCircle2, Copy, KeyRound, XCircle } from 'lucide-react';
import { errMsg, get, post } from '../lib/api';
import { useAuth } from '../lib/auth';
import type { SettingsResponse } from '../lib/types';
import { SettingsForm } from '../components/SettingsForm';
import { Badge, Button, Card, ErrorBox, PageHeader, Spinner, useToast } from '../components/ui';

function TestButton({ name }: { name: 'google' | 'ai' | 'market-data' }) {
  const { can } = useAuth();
  const [result, setResult] = useState<{ ok: boolean; message: string } | null>(null);
  const toast = useToast();
  const m = useMutation({
    mutationFn: () => post<{ ok: boolean; message: string }>(`/integrations/${name}/test`),
    onSuccess: setResult,
    onError: (e) => toast(false, errMsg(e)),
  });
  return (
    <div className="space-y-2">
      <Button size="sm" onClick={() => m.mutate()} loading={m.isPending} disabled={!can('admin')}>
        Test connection
      </Button>
      {result && (
        <p role="status" className={`flex items-start gap-1.5 text-xs ${result.ok ? 'text-emerald-700' : 'text-rose-700'}`}>
          {result.ok ? <CheckCircle2 className="mt-0.5 h-3.5 w-3.5 shrink-0" aria-hidden /> : <XCircle className="mt-0.5 h-3.5 w-3.5 shrink-0" aria-hidden />}
          {result.message}
        </p>
      )}
    </div>
  );
}

export default function Integrations() {
  const toast = useToast();
  const q = useQuery({ queryKey: ['settings'], queryFn: () => get<SettingsResponse>('/settings') });
  if (q.isLoading) return <Spinner />;
  if (q.isError) return <ErrorBox error={q.error} onRetry={() => q.refetch()} />;
  const { settings, derived } = q.data!;
  const by = (k: string) => settings.find((s) => s.key === k);
  const googleReady = by('google.enabled')?.value === true && !!by('google.clientId')?.value && !!by('google.clientSecret')?.configured;
  const aiReady = by('ai.enabled')?.value === true && !!by('ai.apiKey')?.configured;
  const mdReady = by('marketData.enabled')?.value === true && !!by('marketData.apiKey')?.configured;
  const status = (ok: boolean) => (ok ? <Badge tone="green" dot>Active</Badge> : <Badge tone="amber" dot>Not configured</Badge>);

  return (
    <>
      <PageHeader title="Integrations & API keys" description="Keys saved here override environment variables and take effect immediately on the live website — no redeploy needed." />
      <div className="space-y-6">
        <Card
          title={
            <span className="flex items-center gap-2">
              <KeyRound className="h-4 w-4 text-brand-600" aria-hidden /> Google sign-in (OAuth 2.0 / OpenID Connect)
            </span>
          }
          description="Powers “Continue with Google” for traders."
          actions={status(googleReady)}
        >
          <div className="grid gap-6 lg:grid-cols-[1fr_20rem]">
            <SettingsForm settings={settings} keys={['google.enabled', 'google.clientId', 'google.clientSecret', 'google.callbackUrl']} />
            <aside className="space-y-4 rounded-lg bg-slate-50 p-4 text-sm">
              <div>
                <p className="font-medium text-slate-900">Authorised redirect URI</p>
                <p className="mt-1 text-xs text-slate-500">Add exactly this value to your OAuth client in Google Cloud Console.</p>
                <div className="mt-2 flex items-center gap-2">
                  <code className="flex-1 break-all rounded bg-white px-2 py-1.5 font-mono text-xs ring-1 ring-slate-200">{derived.googleRedirectUri}</code>
                  <button
                    onClick={() => navigator.clipboard?.writeText(derived.googleRedirectUri).then(() => toast(true, 'Copied'))}
                    className="rounded-md p-2 text-slate-500 hover:bg-white"
                    aria-label="Copy redirect URI"
                  >
                    <Copy className="h-4 w-4" />
                  </button>
                </div>
              </div>
              <ol className="list-decimal space-y-1 pl-4 text-xs text-slate-600">
                <li>Google Cloud Console → APIs & Services → OAuth consent screen (scopes: openid, email, profile).</li>
                <li>Credentials → Create credentials → OAuth client ID → Web application.</li>
                <li>Authorised JavaScript origin: {derived.appUrl}</li>
                <li>Authorised redirect URI: the value above.</li>
                <li>Paste the client ID and secret here, save, then Test connection.</li>
              </ol>
              <TestButton name="google" />
            </aside>
          </div>
        </Card>

        <Card
          title={
            <span className="flex items-center gap-2">
              <Bot className="h-4 w-4 text-brand-600" aria-hidden /> AI Coach (Anthropic Claude)
            </span>
          }
          description="Journal-aware coaching and AI review narratives. Only aggregated statistics are sent to the model."
          actions={status(aiReady)}
        >
          <div className="grid gap-6 lg:grid-cols-[1fr_20rem]">
            <SettingsForm settings={settings} keys={['ai.enabled', 'ai.apiKey', 'ai.model']} />
            <aside className="space-y-3 rounded-lg bg-slate-50 p-4 text-xs text-slate-600">
              <p>Create a key at console.anthropic.com → API keys. Default model: <code className="font-mono">claude-opus-5-5</code>.</p>
              <p>The test sends one tiny request (a few tokens) to confirm the key and model.</p>
              <TestButton name="ai" />
            </aside>
          </div>
        </Card>

        <Card
          title={
            <span className="flex items-center gap-2">
              <CandlestickChart className="h-4 w-4 text-brand-600" aria-hidden /> Market data (Twelve Data)
            </span>
          }
          description="Live ticker prices and historical candles for the post-trade runner auditor. Without a key the ticker shows clearly labelled DEMO DATA."
          actions={status(mdReady)}
        >
          <div className="grid gap-6 lg:grid-cols-[1fr_20rem]">
            <SettingsForm settings={settings} keys={['marketData.enabled', 'marketData.apiKey']} />
            <aside className="space-y-3 rounded-lg bg-slate-50 p-4 text-xs text-slate-600">
              <p>Get a key at twelvedata.com. Symbol coverage depends on your plan; unavailable symbols show “n/a” rather than invented prices.</p>
              <TestButton name="market-data" />
            </aside>
          </div>
        </Card>
      </div>
    </>
  );
}
