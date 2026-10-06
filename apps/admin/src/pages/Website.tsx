import { useQuery } from '@tanstack/react-query';
import { AlertTriangle, ExternalLink } from 'lucide-react';
import { get } from '../lib/api';
import type { SettingsResponse } from '../lib/types';
import { SettingsForm } from '../components/SettingsForm';
import { Badge, Card, ErrorBox, PageHeader, Spinner } from '../components/ui';

export default function Website() {
  const q = useQuery({ queryKey: ['settings'], queryFn: () => get<SettingsResponse>('/settings') });
  if (q.isLoading) return <Spinner />;
  if (q.isError) return <ErrorBox error={q.error} onRetry={() => q.refetch()} />;
  const { settings } = q.data!;
  const maintenance = settings.find((s) => s.key === 'site.maintenance.enabled')?.value === true;
  return (
    <>
      <PageHeader
        title="Website controls"
        description="Control the live trading website. Changes apply to every visitor within seconds."
        actions={
          <a href="/" target="_blank" rel="noreferrer" className="inline-flex h-10 items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 text-sm font-medium shadow-sm hover:bg-slate-50">
            <ExternalLink className="h-4 w-4" aria-hidden /> View live site
          </a>
        }
      />
      {maintenance && (
        <div role="status" className="mb-6 flex items-center gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
          <AlertTriangle className="h-5 w-5 shrink-0" aria-hidden />
          <span>
            <b>Maintenance mode is ON.</b> Traders currently see the maintenance screen and the trading API returns 503. The control panel remains available.
          </span>
        </div>
      )}
      <div className="grid gap-6 xl:grid-cols-2">
        <Card title="Availability" description="Take the terminal offline or close new registrations." actions={maintenance ? <Badge tone="amber" dot>Maintenance</Badge> : <Badge tone="green" dot>Online</Badge>}>
          <SettingsForm settings={settings} keys={['site.maintenance.enabled', 'site.maintenance.message', 'site.registrationOpen']} />
        </Card>
        <Card title="Announcement banner" description="A dismissible banner shown at the top of the trading terminal.">
          <SettingsForm settings={settings} keys={['site.announcement.enabled', 'site.announcement.text', 'site.announcement.tone']} />
        </Card>
        <Card title="Branding" description="Name, tagline and support contact shown on the public pages.">
          <SettingsForm settings={settings} keys={['site.name', 'site.tagline', 'site.supportEmail']} />
        </Card>
        <Card title="Feature switches" description="Turn product areas on or off. Enforced by the API, not only hidden in the UI.">
          <SettingsForm settings={settings} keys={['features.aiCoach', 'features.brokerSync', 'features.demoMode', 'features.marketTicker']} />
        </Card>
      </div>
    </>
  );
}
