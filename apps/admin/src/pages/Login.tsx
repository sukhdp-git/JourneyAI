import { useState, type FormEvent } from 'react';
import { Navigate, useNavigate, useSearchParams } from 'react-router';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { Lock, ShieldCheck } from 'lucide-react';
import { ApiError, get, post, setCsrf } from '../lib/api';
import { useAuth } from '../lib/auth';
import type { Admin } from '../lib/types';
import { Button, Field } from '../components/ui';

export default function Login() {
  const { admin } = useAuth();
  const [params] = useSearchParams();
  const navigate = useNavigate();
  const qc = useQueryClient();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [totp, setTotp] = useState('');
  const [needTotp, setNeedTotp] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);
  const status = useQuery({ queryKey: ['setup-status'], queryFn: () => get<{ setupRequired: boolean }>('/auth/status') });
  const next = params.get('next');
  if (admin) return <Navigate to={next && next.startsWith('/') ? next : '/'} replace />;

  const submit = async (e: FormEvent) => {
    e.preventDefault();
    setBusy(true);
    setError(null);
    try {
      const res = await post<{ totpRequired?: boolean; admin?: Admin; csrfToken?: string }>('/auth/login', { email, password, ...(needTotp ? { totp } : {}) });
      if (res.totpRequired) {
        setNeedTotp(true);
        return;
      }
      setCsrf(res.csrfToken ?? null);
      qc.setQueryData(['me'], res.admin);
      navigate(next && next.startsWith('/') ? next : '/', { replace: true });
    } catch (err) {
      setError(err instanceof ApiError ? err.message : 'Sign-in failed');
      if (needTotp) setTotp('');
    } finally {
      setBusy(false);
    }
  };

  return (
    <div className="flex min-h-full items-center justify-center bg-gradient-to-br from-slate-100 via-white to-brand-50 p-4">
      <div className="w-full max-w-md">
        <div className="mb-8 flex items-center justify-center gap-3">
          <span className="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-600 text-lg font-bold text-white shadow-sm">J</span>
          <div>
            <p className="text-lg font-semibold">journzey.ai</p>
            <p className="text-sm text-slate-500">Control Panel</p>
          </div>
        </div>
        <main id="main" className="card p-8">
          <h1 className="flex items-center gap-2 text-xl font-semibold">
            <Lock className="h-5 w-5 text-brand-600" aria-hidden /> Administrator sign-in
          </h1>
          <p className="mt-1 text-sm text-slate-500">Restricted area. All activity is recorded in the audit log.</p>
          {status.data?.setupRequired && (
            <div className="mt-5 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">
              <p className="font-medium">No administrator exists yet.</p>
              <p className="mt-1">
                Create the first owner on the server: <code className="rounded bg-amber-100 px-1 font-mono text-xs">npm run admin:create -- --email you@example.com --name "Your Name"</code>, or set{' '}
                <code className="font-mono text-xs">ADMIN_BOOTSTRAP_EMAIL</code> / <code className="font-mono text-xs">ADMIN_BOOTSTRAP_PASSWORD</code> and restart.
              </p>
            </div>
          )}
          <form onSubmit={submit} className="mt-6 space-y-4" noValidate>
            {!needTotp ? (
              <>
                <Field label="Email" htmlFor="email">
                  <input id="email" type="email" autoComplete="username" required className="input" value={email} onChange={(e) => setEmail(e.target.value)} />
                </Field>
                <Field label="Password" htmlFor="password">
                  <input id="password" type="password" autoComplete="current-password" required className="input" value={password} onChange={(e) => setPassword(e.target.value)} />
                </Field>
              </>
            ) : (
              <Field label="Authentication code" htmlFor="totp" hint="Enter the 6-digit code from your authenticator app.">
                <input id="totp" inputMode="numeric" autoComplete="one-time-code" maxLength={6} autoFocus className="input tnum text-center text-lg tracking-[0.5em]" value={totp} onChange={(e) => setTotp(e.target.value.replace(/\D/g, ''))} />
              </Field>
            )}
            {error && (
              <p role="alert" className="rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-700">
                {error}
              </p>
            )}
            <Button type="submit" variant="primary" className="w-full" loading={busy} disabled={needTotp ? totp.length !== 6 : !email || !password}>
              {needTotp ? 'Verify and sign in' : 'Sign in'}
            </Button>
            {needTotp && (
              <button type="button" className="w-full text-center text-sm text-slate-500 hover:text-slate-700" onClick={() => (setNeedTotp(false), setTotp(''))}>
                Back
              </button>
            )}
          </form>
        </main>
        <p className="mt-6 flex items-center justify-center gap-1.5 text-xs text-slate-500">
          <ShieldCheck className="h-3.5 w-3.5" aria-hidden /> Protected by Argon2id, rate limiting, lockout and optional two-factor authentication
        </p>
      </div>
    </div>
  );
}
