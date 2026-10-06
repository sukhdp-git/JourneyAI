import { useEffect, useRef, useState } from 'react';
import { NavLink, Outlet, useLocation, useNavigate } from 'react-router';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import clsx from 'clsx';
import {
  Bot,
  Calculator,
  CalendarDays,
  ChevronUp,
  Grid3x3,
  Home,
  LayoutDashboard,
  LogOut,
  Menu,
  NotebookPen,
  Plus,
  Radio,
  ScrollText,
  Settings as SettingsIcon,
  Target,
  X,
} from 'lucide-react';
import type { DashboardResponse, MarketQuotesResponse } from '@journzey/shared';
import { Logo } from '../Logo';
import { DemoBadge, Pnl } from '../ui';
import { post, resetCsrf, get } from '../../lib/api';
import { fmtMoney, todayIn } from '../../lib/format';
import { qk, useAccounts, useMe, useScope } from '../../lib/queries';
import { useI18n } from '../../lib/i18n';
import type { MessageKey } from '../../locales/en';
import { DateRangeProvider } from '../../lib/dateRange';
import { TradeActionsProvider, useTradeActions } from '../trade/TradeActions';
import { ErrorBoundary } from '../ErrorBoundary';

const NAV: Array<{ to: string; key: MessageKey; icon: typeof Home; end?: boolean }> = [
  { to: '/app', key: 'nav.home', icon: Home, end: true },
  { to: '/app/dashboard', key: 'nav.dashboard', icon: LayoutDashboard },
  { to: '/app/calendar', key: 'nav.calendar', icon: CalendarDays },
  { to: '/app/trades', key: 'nav.trades', icon: ScrollText },
  { to: '/app/strategies', key: 'nav.strategies', icon: Target },
  { to: '/app/edge', key: 'nav.edge', icon: Grid3x3 },
  { to: '/app/notepad', key: 'nav.notepad', icon: NotebookPen },
  { to: '/app/coach', key: 'nav.coach', icon: Bot },
];

function useSignOut() {
  const qc = useQueryClient();
  const navigate = useNavigate();
  return async () => {
    try {
      await post('/auth/logout');
    } finally {
      resetCsrf();
      qc.clear();
      qc.setQueryData(qk.me, null);
      navigate('/login', { replace: true });
    }
  };
}

function BrokerFeedIndicator() {
  const { t } = useI18n();
  const q = useQuery({ queryKey: qk.market, queryFn: () => get<MarketQuotesResponse>('/market/quotes'), refetchInterval: 60_000 });
  const state = q.isError ? 'down' : q.data?.source === 'live' ? (q.data.quotes.some((x) => x.price) ? 'live' : 'down') : q.data ? 'demo' : 'loading';
  return (
    <div className="flex items-center gap-2 text-2xs" role="status">
      <Radio className={clsx('h-3.5 w-3.5', state === 'live' ? 'text-profit' : state === 'demo' ? 'text-warn' : 'text-loss')} aria-hidden />
      <span className="text-muted">{state === 'live' ? t('feed.live') : state === 'demo' ? t('feed.demo') : state === 'down' ? t('feed.down') : '…'}</span>
    </div>
  );
}

function NavEquityHud() {
  const { t } = useI18n();
  const { scope } = useScope();
  const { data: me } = useMe();
  const accounts = useAccounts();
  const tz = me?.settings?.timezone ?? 'UTC';
  const today = todayIn(tz);
  const dash = useQuery({ queryKey: qk.dashboard({ account: scope, from: today, to: today }), queryFn: () => get<DashboardResponse>('/dashboard', { account: scope, from: today, to: today }) });
  const inScope = (accounts.data ?? []).filter((a) => (scope === 'real' ? !a.demo : scope === 'demo' ? a.demo : scope === 'all' ? true : a.id === scope));
  const currencies = [...new Set(inScope.map((a) => a.currency))];
  const nav = inScope.reduce((s, a) => s + Number(a.currentCapital), 0);
  return (
    <div className="panel px-3 py-2">
      <div className="flex items-center justify-between">
        <span className="label">{t('hud.nav')}</span>
        {dash.data?.scope.demo && <DemoBadge />}
      </div>
      <div className="num mt-0.5 text-xl font-bold">{currencies.length === 1 ? fmtMoney(nav, currencies[0]) : currencies.length ? 'Mixed currencies' : '—'}</div>
      <div className="mt-0.5 flex items-center gap-2 text-2xs text-muted">
        {t('hud.today')} <Pnl value={dash.data?.today.pnl ?? 0} currency={dash.data?.scope.currency} className="text-2xs" />
        <span>· {dash.data?.today.trades ?? 0} trades</span>
      </div>
    </div>
  );
}

function AccountScopeSelect({ id }: { id: string }) {
  const { t } = useI18n();
  const { scope, setScope, pending } = useScope();
  const accounts = useAccounts();
  return (
    <div>
      <label htmlFor={id} className="label">
        {t('scope.label')}
      </label>
      <select id={id} value={scope} disabled={pending} onChange={(e) => setScope(e.target.value)} className="mt-1 h-9 w-full rounded-md border border-line bg-panel2 px-2 text-xs">
        <option value="real">{t('scope.real')}</option>
        <option value="demo">{t('scope.demo')}</option>
        <option value="all">{t('scope.all')}</option>
        {(accounts.data ?? []).map((a) => (
          <option key={a.id} value={a.id}>
            {a.accountName} {a.sampleData ? '(DEMO DATA)' : a.demo ? '(demo)' : ''}
          </option>
        ))}
      </select>
    </div>
  );
}

function UserMenu() {
  const { t } = useI18n();
  const { data } = useMe();
  const [open, setOpen] = useState(false);
  const signOut = useSignOut();
  const ref = useRef<HTMLDivElement>(null);
  useEffect(() => {
    const onDoc = (e: MouseEvent) => ref.current && !ref.current.contains(e.target as Node) && setOpen(false);
    document.addEventListener('mousedown', onDoc);
    return () => document.removeEventListener('mousedown', onDoc);
  }, []);
  const u = data?.user;
  return (
    <div ref={ref} className="relative">
      {open && (
        <div role="menu" className="absolute bottom-full left-0 right-0 mb-2 overflow-hidden rounded-md border border-line bg-panel shadow-xl">
          <NavLink role="menuitem" to="/app/settings" onClick={() => setOpen(false)} className="flex items-center gap-2 px-3 py-2.5 text-sm hover:bg-panel2">
            <SettingsIcon className="h-4 w-4" aria-hidden /> {t('nav.settings')}
          </NavLink>
          <button role="menuitem" onClick={signOut} className="flex w-full items-center gap-2 px-3 py-2.5 text-left text-sm text-loss hover:bg-panel2">
            <LogOut className="h-4 w-4" aria-hidden /> {t('nav.signOut')}
          </button>
        </div>
      )}
      <button onClick={() => setOpen((o) => !o)} aria-haspopup="menu" aria-expanded={open} className="flex w-full items-center gap-2 rounded-md p-2 text-left hover:bg-panel2">
        {u?.avatarUrl ? (
          <img src={u.avatarUrl} alt="" referrerPolicy="no-referrer" className="h-8 w-8 rounded-full" />
        ) : (
          <span className="flex h-8 w-8 items-center justify-center rounded-full bg-accent/20 text-xs font-bold text-accent" aria-hidden>
            {(u?.name ?? u?.email ?? '?').slice(0, 1).toUpperCase()}
          </span>
        )}
        <span className="min-w-0 flex-1">
          <span className="block truncate text-sm font-medium">{u?.name ?? 'Trader'}</span>
          <span className="block truncate text-2xs text-muted">{u?.email}</span>
        </span>
        <ChevronUp className={clsx('h-4 w-4 text-muted transition-transform', !open && 'rotate-180')} aria-hidden />
      </button>
    </div>
  );
}

function SidebarContent({ onNavigate }: { onNavigate?: () => void }) {
  const { t } = useI18n();
  const actions = useTradeActions();
  return (
    <div className="flex h-full flex-col gap-3 p-3">
      <div className="flex items-center justify-between px-1 pt-1">
        <Logo />
      </div>
      <BrokerFeedIndicator />
      <NavEquityHud />
      <nav aria-label="Main" className="flex flex-col gap-0.5">
        {NAV.map((n) => (
          <NavLink
            key={n.to}
            to={n.to}
            end={n.end}
            onClick={onNavigate}
            className={({ isActive }) => clsx('flex min-h-10 items-center gap-3 rounded-md px-3 text-sm', isActive ? 'bg-accent/15 font-semibold text-accent' : 'text-muted hover:bg-panel2 hover:text-fg')}
          >
            <n.icon className="h-4 w-4" aria-hidden />
            {t(n.key)}
          </NavLink>
        ))}
      </nav>
      <div>
        <p className="label px-1">{t('nav.quickExecution')}</p>
        <div className="mt-1 grid grid-cols-2 gap-2">
          <button onClick={() => (onNavigate?.(), actions.openNew())} className="flex min-h-10 items-center justify-center gap-1.5 rounded-md bg-accent text-xs font-semibold text-accent-fg">
            <Plus className="h-4 w-4" aria-hidden /> Log trade
          </button>
          <button onClick={() => (onNavigate?.(), actions.openCalculator())} className="flex min-h-10 items-center justify-center gap-1.5 rounded-md border border-line text-xs">
            <Calculator className="h-4 w-4" aria-hidden /> Lot size
          </button>
        </div>
      </div>
      <AccountScopeSelect id={onNavigate ? 'scope-mobile' : 'scope-desktop'} />
      <div className="mt-auto space-y-1">
        <NavLink to="/app/settings" onClick={onNavigate} className={({ isActive }) => clsx('flex min-h-10 items-center gap-3 rounded-md px-3 text-sm', isActive ? 'text-accent' : 'text-muted hover:text-fg')}>
          <SettingsIcon className="h-4 w-4" aria-hidden /> {t('nav.settings')}
        </NavLink>
        <UserMenu />
      </div>
    </div>
  );
}

function MobileChrome() {
  const { t } = useI18n();
  const [open, setOpen] = useState(false);
  const location = useLocation();
  const actions = useTradeActions();
  useEffect(() => setOpen(false), [location.pathname]);
  useEffect(() => {
    if (!open) return;
    const onKey = (e: KeyboardEvent) => e.key === 'Escape' && setOpen(false);
    document.addEventListener('keydown', onKey);
    return () => document.removeEventListener('keydown', onKey);
  }, [open]);
  const tabs = [NAV[0]!, NAV[1]!, null, NAV[3]!, NAV[7]!];
  return (
    <>
      <header className="no-print sticky top-0 z-30 flex h-14 items-center justify-between border-b border-line bg-bg/95 px-3 backdrop-blur lg:hidden">
        <button onClick={() => setOpen(true)} aria-label={t('nav.menu')} aria-expanded={open} className="flex h-11 w-11 items-center justify-center rounded-md hover:bg-panel2">
          <Menu className="h-5 w-5" />
        </button>
        <Logo />
        <button onClick={actions.openCalculator} aria-label="Lot size calculator" className="flex h-11 w-11 items-center justify-center rounded-md hover:bg-panel2">
          <Calculator className="h-5 w-5" />
        </button>
      </header>
      {open && (
        <div className="fixed inset-0 z-40 lg:hidden" role="dialog" aria-modal="true" aria-label="Navigation">
          <div className="absolute inset-0 bg-black/60" onClick={() => setOpen(false)} aria-hidden />
          <div className="absolute inset-y-0 left-0 w-[85vw] max-w-xs overflow-y-auto border-r border-line bg-panel">
            <button onClick={() => setOpen(false)} aria-label={t('nav.close')} className="absolute right-2 top-2 z-10 flex h-11 w-11 items-center justify-center rounded-md hover:bg-panel2">
              <X className="h-5 w-5" />
            </button>
            <SidebarContent onNavigate={() => setOpen(false)} />
          </div>
        </div>
      )}
      <nav aria-label="Quick tabs" className="no-print fixed inset-x-0 bottom-0 z-30 grid grid-cols-5 border-t border-line bg-panel/95 pb-[env(safe-area-inset-bottom)] backdrop-blur lg:hidden">
        {tabs.map((n, i) =>
          n ? (
            <NavLink key={n.to} to={n.to} end={n.end} className={({ isActive }) => clsx('flex min-h-14 flex-col items-center justify-center gap-0.5 text-2xs', isActive ? 'text-accent' : 'text-muted')}>
              <n.icon className="h-5 w-5" aria-hidden />
              <span className="truncate">{t(n.key).split(' ')[0]}</span>
            </NavLink>
          ) : (
            <button key={i} onClick={actions.openNew} aria-label="Log trade" className="flex min-h-14 items-center justify-center">
              <span className="flex h-11 w-11 items-center justify-center rounded-full bg-accent text-accent-fg shadow-lg">
                <Plus className="h-6 w-6" aria-hidden />
              </span>
            </button>
          ),
        )}
      </nav>
    </>
  );
}

export default function AppShell() {
  const location = useLocation();
  return (
    <DateRangeProvider>
      <TradeActionsProvider>
        <div className="flex min-h-full">
          <aside className="no-print hidden shrink-0 border-r border-line bg-panel lg:block lg:w-64 xl:w-72">
            <div className="sticky top-0 h-screen overflow-y-auto">
              <SidebarContent />
            </div>
          </aside>
          <div className="flex min-w-0 flex-1 flex-col">
            <MobileChrome />
            <main id="main" className="mx-auto w-full max-w-[1600px] flex-1 px-3 pb-24 pt-4 sm:px-5 lg:pb-8">
              <ErrorBoundary resetKey={location.pathname}>
                <Outlet />
              </ErrorBoundary>
            </main>
          </div>
        </div>
      </TradeActionsProvider>
    </DateRangeProvider>
  );
}
