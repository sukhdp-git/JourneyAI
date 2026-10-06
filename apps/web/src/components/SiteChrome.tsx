import { useState } from 'react';
import { Link } from 'react-router';
import clsx from 'clsx';
import { Megaphone, Wrench, X } from 'lucide-react';
import { useSite } from '../lib/site';
import { Logo } from './Logo';

/** Full-screen notice shown to traders while the control panel has maintenance mode on. */
export function MaintenanceScreen({ message }: { message: string }) {
  return (
    <main id="main" className="flex min-h-full flex-col items-center justify-center gap-4 p-6 text-center">
      <Logo />
      <Wrench className="h-8 w-8 text-warn" aria-hidden />
      <h1 className="font-display text-2xl font-bold">Scheduled maintenance</h1>
      <p className="max-w-md text-sm text-muted">{message}</p>
      <nav className="flex gap-4 text-2xs text-muted" aria-label="Legal">
        <Link to="/terms">Terms</Link>
        <Link to="/privacy">Privacy</Link>
        <Link to="/disclaimer">Disclaimer</Link>
      </nav>
    </main>
  );
}

/** Dismissible announcement banner configured in the control panel. */
export function AnnouncementBanner() {
  const { data } = useSite();
  const text = data?.announcement.enabled ? data.announcement.text : '';
  const key = `jz.announcement-dismissed.${text}`;
  const [dismissed, setDismissed] = useState(() => {
    try {
      return localStorage.getItem(key) === '1';
    } catch {
      return false;
    }
  });
  if (!text || dismissed) return null;
  const tone = data!.announcement.tone;
  return (
    <div
      role="status"
      className={clsx(
        'no-print flex items-center gap-3 border-b px-4 py-2 text-sm',
        tone === 'warning' ? 'border-warn/40 bg-warn/15' : tone === 'success' ? 'border-profit/40 bg-profit/15' : 'border-info/40 bg-info/15',
      )}
    >
      <Megaphone className="h-4 w-4 shrink-0" aria-hidden />
      <span className="flex-1">{text}</span>
      <button
        onClick={() => {
          setDismissed(true);
          try {
            localStorage.setItem(key, '1');
          } catch {
            /* ignore */
          }
        }}
        className="rounded p-1 hover:bg-black/10"
        aria-label="Dismiss announcement"
      >
        <X className="h-4 w-4" />
      </button>
    </div>
  );
}
