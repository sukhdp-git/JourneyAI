import { Link } from 'react-router';
import { Activity, Brain, CalendarDays, Gauge, LineChart, ShieldCheck } from 'lucide-react';
import { Logo } from '../components/Logo';
import { useMe } from '../lib/queries';
import { LegalFooter } from './legal/Legal';

const FEATURES = [
  { icon: Activity, title: 'Execution analytics', text: 'Equity, drawdown, profit factor and expectancy computed server-side from your stored trades.' },
  { icon: Gauge, title: 'Discipline Leak Mirror', text: 'Quantify what rule violations cost — conservatively, with no invented upside.' },
  { icon: LineChart, title: 'Risk of ruin', text: 'Monte Carlo drawdown probabilities from your own historical statistics.' },
  { icon: CalendarDays, title: 'Psychology journal', text: 'Daily notepad with voice dictation, compliance and emotional-state tracking.' },
  { icon: Brain, title: 'AI coaching', text: 'Journal-aware, trade-aware analysis built on aggregated statistics — never raw dumps.' },
  { icon: ShieldCheck, title: 'Private by design', text: 'Google sign-in, HttpOnly sessions, per-user isolation and encrypted broker secrets.' },
];

export default function Landing() {
  const { data } = useMe();
  return (
    <div className="min-h-full">
      <header className="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
        <Logo />
        <nav className="flex items-center gap-2">
          <Link to="/disclaimer" className="hidden text-sm text-muted hover:text-fg sm:inline">
            Disclaimer
          </Link>
          <Link to={data ? '/app' : '/login'} className="rounded-md bg-accent px-3.5 py-2 text-sm font-semibold text-accent-fg">
            {data ? 'Open terminal' : 'Sign in'}
          </Link>
        </nav>
      </header>
      <main id="main" className="mx-auto max-w-6xl px-4">
        <section className="py-14 sm:py-20">
          <p className="label text-accent">Institutional Trading Journal & AI Discipline Terminal</p>
          <h1 className="mt-3 max-w-3xl font-display text-3xl font-extrabold leading-tight sm:text-5xl">Trade the plan. Journal the execution. Eliminate the leak.</h1>
          <p className="mt-4 max-w-2xl text-muted">
            journzey.ai is a professional trading cockpit for gold, forex, indices, crypto and commodities: millisecond trade logging, institutional analytics, psychology tracking and
            risk modelling in one dense, fast terminal.
          </p>
          <div className="mt-8 flex flex-wrap gap-3">
            <Link to={data ? '/app' : '/login'} className="rounded-md bg-accent px-5 py-3 text-sm font-semibold text-accent-fg">
              {data ? 'Open terminal' : 'Get started — Continue with Google'}
            </Link>
            <Link to="/security" className="rounded-md border border-line px-5 py-3 text-sm text-fg">
              How we protect your data
            </Link>
          </div>
        </section>
        <section className="grid gap-3 pb-16 sm:grid-cols-2 lg:grid-cols-3">
          {FEATURES.map((f) => (
            <div key={f.title} className="panel p-4">
              <f.icon className="h-5 w-5 text-accent" aria-hidden />
              <h2 className="mt-3 text-sm font-semibold">{f.title}</h2>
              <p className="mt-1 text-sm text-muted">{f.text}</p>
            </div>
          ))}
        </section>
        <p className="pb-10 text-2xs text-muted">
          journzey.ai is journaling and analytics software. It does not provide investment advice or guarantee any trading outcome. Past performance does not predict future results.
        </p>
      </main>
      <LegalFooter />
    </div>
  );
}
