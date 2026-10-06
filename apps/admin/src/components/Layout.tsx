import { useEffect, useState } from 'react';
import { NavLink, Outlet, useLocation, useNavigate } from 'react-router';
import { useQueryClient } from '@tanstack/react-query';
import clsx from 'clsx';
import { Activity, ExternalLink, Globe, KeyRound, LayoutDashboard, LogOut, Menu, ScrollText, Server, Shield, UserCog, Users, X } from 'lucide-react';
import { post, setCsrf } from '../lib/api';
import { useAuth } from '../lib/auth';
import type { Role } from '../lib/types';

const NAV: Array<{ to: string; label: string; icon: typeof Users; end?: boolean; min?: Role }> = [
  { to: '/', label: 'Dashboard', icon: LayoutDashboard, end: true },
  { to: '/users', label: 'Users', icon: Users },
  { to: '/integrations', label: 'Integrations & API keys', icon: KeyRound },
  { to: '/website', label: 'Website controls', icon: Globe },
  { to: '/admins', label: 'Administrators', icon: UserCog, min: 'owner' },
  { to: '/audit', label: 'Audit log', icon: ScrollText },
  { to: '/system', label: 'System health', icon: Server },
];

function Sidebar({ onNavigate }: { onNavigate?: () => void }) {
  const { can } = useAuth();
  return (
    <div className="flex h-full flex-col">
      <div className="flex items-center gap-3 px-5 py-5">
        <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-600 text-sm font-bold text-white">J</span>
        <div>
          <p className="text-sm font-semibold text-white">journzey.ai</p>
          <p className="text-xs text-slate-400">Control Panel</p>
        </div>
      </div>
      <nav aria-label="Control panel" className="flex-1 space-y-1 px-3">
        {NAV.filter((n) => !n.min || can(n.min)).map((n) => (
          <NavLink
            key={n.to}
            to={n.to}
            end={n.end}
            onClick={onNavigate}
            className={({ isActive }) => clsx('flex min-h-10 items-center gap-3 rounded-lg px-3 text-sm font-medium', isActive ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white')}
          >
            <n.icon className="h-4 w-4" aria-hidden />
            {n.label}
          </NavLink>
        ))}
      </nav>
      <div className="space-y-1 border-t border-white/10 p-3">
        <a href="/" target="_blank" rel="noreferrer" className="flex min-h-10 items-center gap-3 rounded-lg px-3 text-sm text-slate-300 hover:bg-white/5 hover:text-white">
          <ExternalLink className="h-4 w-4" aria-hidden /> Open live website
        </a>
      </div>
    </div>
  );
}

export default function Layout() {
  const { admin } = useAuth();
  const [open, setOpen] = useState(false);
  const [menu, setMenu] = useState(false);
  const location = useLocation();
  const navigate = useNavigate();
  const qc = useQueryClient();
  useEffect(() => {
    setOpen(false);
    setMenu(false);
  }, [location.pathname]);
  const signOut = async () => {
    await post('/auth/logout').catch(() => undefined);
    setCsrf(null);
    // Mark signed-out first (live observers keep this query), then drop all other cached data.
    qc.setQueryData(['me'], null);
    qc.removeQueries({ predicate: (q) => q.queryKey[0] !== 'me' });
    navigate('/login', { replace: true });
  };
  return (
    <div className="flex min-h-full">
      <aside className="hidden w-64 shrink-0 bg-slate-900 lg:block">
        <div className="sticky top-0 h-screen overflow-y-auto">
          <Sidebar />
        </div>
      </aside>
      {open && (
        <div className="fixed inset-0 z-40 lg:hidden" role="dialog" aria-modal="true" aria-label="Navigation">
          <div className="absolute inset-0 bg-slate-900/60" onClick={() => setOpen(false)} aria-hidden />
          <div className="absolute inset-y-0 left-0 w-72 bg-slate-900">
            <button onClick={() => setOpen(false)} className="absolute right-3 top-4 rounded-md p-2 text-slate-300" aria-label="Close navigation">
              <X className="h-5 w-5" />
            </button>
            <Sidebar onNavigate={() => setOpen(false)} />
          </div>
        </div>
      )}
      <div className="flex min-w-0 flex-1 flex-col">
        <header className="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-slate-200 bg-white/90 px-4 backdrop-blur sm:px-6">
          <button onClick={() => setOpen(true)} className="rounded-md p-2 text-slate-600 hover:bg-slate-100 lg:hidden" aria-label="Open navigation">
            <Menu className="h-5 w-5" />
          </button>
          <div className="flex items-center gap-2 text-sm text-slate-500">
            <Shield className="h-4 w-4 text-brand-600" aria-hidden /> Secure administration area
          </div>
          <div className="relative ml-auto">
            <button onClick={() => setMenu((m) => !m)} aria-haspopup="menu" aria-expanded={menu} className="flex items-center gap-3 rounded-lg px-2 py-1.5 hover:bg-slate-100">
              <span className="flex h-8 w-8 items-center justify-center rounded-full bg-brand-100 text-sm font-semibold text-brand-700" aria-hidden>
                {admin?.name.slice(0, 1).toUpperCase()}
              </span>
              <span className="hidden text-left sm:block">
                <span className="block text-sm font-medium text-slate-900">{admin?.name}</span>
                <span className="block text-xs capitalize text-slate-500">{admin?.role}</span>
              </span>
            </button>
            {menu && (
              <div role="menu" className="absolute right-0 mt-2 w-56 overflow-hidden rounded-lg border border-slate-200 bg-white py-1 shadow-lg">
                <p className="truncate px-4 py-2 text-xs text-slate-500">{admin?.email}</p>
                <NavLink role="menuitem" to="/account" className="flex items-center gap-2 px-4 py-2 text-sm hover:bg-slate-50">
                  <Activity className="h-4 w-4" aria-hidden /> Account & security
                </NavLink>
                <button role="menuitem" onClick={signOut} className="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-rose-600 hover:bg-slate-50">
                  <LogOut className="h-4 w-4" aria-hidden /> Sign out
                </button>
              </div>
            )}
          </div>
        </header>
        <main id="main" className="mx-auto w-full max-w-7xl flex-1 px-4 py-8 sm:px-6">
          <Outlet />
        </main>
      </div>
    </div>
  );
}
