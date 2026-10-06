import { useEffect } from 'react';
import { useNavigate, useSearchParams } from 'react-router';
import { useQueryClient } from '@tanstack/react-query';
import type { MeResponse } from '@journzey/shared';
import { get, resetCsrf } from '../lib/api';
import { qk } from '../lib/queries';
import { Spinner } from '../components/ui';

/** Landing route after the API completes the Google OAuth callback and creates the session. */
export default function AuthCallback() {
  const [params] = useSearchParams();
  const navigate = useNavigate();
  const qc = useQueryClient();
  useEffect(() => {
    if (params.get('status') !== 'success') {
      navigate('/login?error=oauth_failed', { replace: true });
      return;
    }
    resetCsrf();
    get<MeResponse>('/auth/me')
      .then((me) => {
        qc.setQueryData(qk.me, me);
        navigate(me.user.onboarded ? '/app' : '/onboarding', { replace: true });
      })
      .catch(() => navigate('/login?error=session', { replace: true }));
  }, [params, navigate, qc]);
  return <Spinner label="Completing sign-in…" />;
}
