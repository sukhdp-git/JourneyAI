import { useState, type FormEvent } from 'react';
import { Link, Navigate, useNavigate, useSearchParams } from 'react-router';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { AlertTriangle, Brain, Gauge, LineChart, Shield } from 'lucide-react';
import { GoogleLogo, Logo } from '../components/Logo';
import { Button, Field, Input } from '../components/ui';
import { get, post, resetCsrf, errorMessage } from '../lib/api';
import { qk, useMe } from '../lib/queries';
import { useI18n } from '../lib/i18n';

const ERRORS: Record<string, string> = {
  google_not_configured: 'Google sign-in is not configured on this server. An administrator must set GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET and GOOGLE_CALLBACK_URL.',
  access_denied: 'Google sign-in was cancelled.',
  invalid_state: 'Your sign-in session expired or was tampered with. Please try again.',
  oauth_failed: 'Google could not verify your sign-in. Please try again.',
  oauth_error: 'Google returned an error during sign-in.',
  email_unverified: 'Your Google account email is not verified.',
  account_conflict: 'An account with this email already exists under a different sign-in identity.',
  session: 'Your session could not be verified. Please sign in again.',
};

export default function Login() {
  const { t } = useI18n();
  const { data: me } = useMe();
  const [params] = useSearchParams();
  const providers = useQuery({ queryKey: ['auth-providers'], queryFn: () => get<{ google: boolean; devLogin: boolean }>('/auth/providers') });
  const error = params.get('error');
  const next = params.get('next');
  if (me) return <Navigate to={me.user.onboarded ? (next?.startsWith('/app') ? next : '/app') : '/onboarding'} replace />;

  return (
    <div className="grid min-h-full lg:grid-cols-2">
      <aside className="hidden flex-col justify-between border-r border-line bg-panel p-10 lg:flex">
        <Logo />
        <div>
          <h1 className="font-display text-4xl font-extrabold leading-tight">{t('login.headline')}</h1>
          <ul className="mt-8 space-y-4 text-sm text-muted">
            <li className="flex gap-3">
              <Gauge className="h-5 w-5 shrink-0 text-accent" aria-hidden /> Execution analytics — equity, drawdown, expectancy and session edge from your own trades.
            </li>
            <li className="flex gap-3">
              <Brain className="h-5 w-5 shrink-0 text-accent" aria-hidden /> Psychology tracking — emotions, mistakes and the measurable cost of every rule break.
            </li>
            <li className="flex gap-3">
              <LineChart className="h-5 w-5 shrink-0 text-accent" aria-hidden /> Risk analysis — Monte Carlo drawdown probabilities and tilt detection.
            </li>
            <li className="flex gap-3">
              <Shield className="h-5 w-5 shrink-0 text-accent" aria-hidden /> AI coaching — grounded in aggregated statistics, with keys kept server-side.
            </li>
          </ul>
        </div>
        <p className="text-2xs text-muted">Historical analytics only. Not investment advice.</p>
      </aside>
      <main id="main" className="flex items-center justify-center p-4 sm:p-10">
        <div className="w-full max-w-sm">
          <div className="mb-8 lg:hidden">
            <Logo />
            <p className="mt-3 font-display text-2xl font-bold leading-snug">{t('login.headline')}</p>
          </div>
          <div className="panel p-6">
            <h2 className="text-lg font-semibold">Sign in to the terminal</h2>
            <p className="mt-1 text-sm text-muted">Use your Google account. No password to remember.</p>
            {error && (
              <div role="alert" className="mt-4 flex gap-2 rounded-md border border-warn/40 bg-warn/10 p-3 text-sm">
                <AlertTriangle className="h-4 w-4 shrink-0 text-warn" aria-hidden />
                <span>{ERRORS[error] ?? 'Sign-in failed. Please try again.'}</span>
              </div>
            )}
            {providers.data && !providers.data.google && !error && (
              <div role="alert" className="mt-4 flex gap-2 rounded-md border border-warn/40 bg-warn/10 p-3 text-sm">
                <AlertTriangle className="h-4 w-4 shrink-0 text-warn" aria-hidden />
                <span>{ERRORS.google_not_configured}</span>
              </div>
            )}
            {/* A real navigation (not fetch): the API redirects the browser to Google's authorization endpoint. */}
            <a
              href="/api/v1/auth/google"
              aria-disabled={providers.data?.google === false}
              className="mt-5 flex h-11 w-full items-center justify-center gap-3 rounded-md border border-line bg-white text-sm font-semibold text-[#1f1f1f] shadow-sm hover:bg-gray-50 aria-disabled:pointer-events-none aria-disabled:opacity-50"
            >
              <GoogleLogo /> {t('login.google')}
            </a>
            {providers.data?.devLogin && <DevLogin />}
            <p className="mt-5 text-2xs leading-relaxed text-muted">
              {t('legal.consent').split('Terms of Service')[0]}
              <Link to="/terms" className="underline">
                Terms of Service
              </Link>{' '}
              and{' '}
              <Link to="/privacy" className="underline">
                Privacy Policy
              </Link>
              .
            </p>
          </div>
          <nav className="mt-4 flex justify-center gap-4 text-2xs text-muted" aria-label="Legal">
            <Link to="/terms">Terms</Link>
            <Link to="/privacy">Privacy</Link>
            <Link to="/security">Security</Link>
            <Link to="/disclaimer">Disclaimer</Link>
          </nav>
        </div>
      </main>
    </div>
  );
}

/** Local development only (DEV_AUTH_BYPASS). Clearly labelled as NOT Google authentication. */
function DevLogin() {
  const qc = useQueryClient();
  const navigate = useNavigate();
  const [email, setEmail] = useState('dev.trader@example.com');
  const [name, setName] = useState('Dev Trader');
  const [busy, setBusy] = useState(false);
  const [err, setErr] = useState<string | null>(null);
  const submit = async (e: FormEvent) => {
    e.preventDefault();
    setBusy(true);
    setErr(null);
    try {
      await post('/auth/dev-login', { email, name });
      resetCsrf();
      await qc.invalidateQueries({ queryKey: qk.me });
      navigate('/app');
    } catch (ex) {
      setErr(errorMessage(ex));
    } finally {
      setBusy(false);
    }
  };
  return (
    <form onSubmit={submit} className="mt-5 space-y-3 rounded-md border border-dashed border-warn/50 p-3" aria-label="Developer sign-in">
      <p className="text-2xs font-semibold uppercase tracking-wide text-warn">Developer sign-in — local only, not Google</p>
      <Field label="Email" htmlFor="dev-email">
        <Input id="dev-email" type="email" value={email} onChange={(e) => setEmail(e.target.value)} required />
      </Field>
      <Field label="Name" htmlFor="dev-name" error={err ?? undefined}>
        <Input id="dev-name" value={name} onChange={(e) => setName(e.target.value)} required />
      </Field>
      <Button type="submit" size="sm" loading={busy} className="w-full">
        Sign in as local developer
      </Button>
    </form>
  );
}
