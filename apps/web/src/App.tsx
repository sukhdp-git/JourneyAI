import { lazy, Suspense, useEffect, type ReactNode } from 'react';
import { Navigate, Route, Routes, useLocation } from 'react-router';
import { useQueryClient } from '@tanstack/react-query';
import { useMe, qk } from './lib/queries';
import { I18nProvider } from './lib/i18n';
import { Spinner, ToastProvider } from './components/ui';
import { ErrorBoundary } from './components/ErrorBoundary';

const Landing = lazy(() => import('./pages/Landing'));
const Login = lazy(() => import('./pages/Login'));
const AuthCallback = lazy(() => import('./pages/AuthCallback'));
const Onboarding = lazy(() => import('./pages/Onboarding'));
const Legal = lazy(() => import('./pages/legal/Legal'));
const NotFound = lazy(() => import('./pages/NotFound'));
const AppShell = lazy(() => import('./components/layout/AppShell'));
const Home = lazy(() => import('./pages/app/Home'));
const Dashboard = lazy(() => import('./pages/app/Dashboard'));
const CalendarPage = lazy(() => import('./pages/app/Calendar'));
const TradeLog = lazy(() => import('./pages/app/TradeLog'));
const Strategies = lazy(() => import('./pages/app/Strategies'));
const EdgeMatrix = lazy(() => import('./pages/app/EdgeMatrix'));
const Notepad = lazy(() => import('./pages/app/Notepad'));
const AiCoach = lazy(() => import('./pages/app/AiCoach'));
const Settings = lazy(() => import('./pages/app/Settings'));

function RequireAuth({ children, onboarded = true }: { children: ReactNode; onboarded?: boolean }) {
  const { data, isLoading, error } = useMe();
  const location = useLocation();
  if (isLoading) return <Spinner label="Authenticating…" />;
  if (error) return <Navigate to="/login?error=session" replace />;
  if (!data) return <Navigate to={`/login?next=${encodeURIComponent(location.pathname)}`} replace />;
  if (onboarded && !data.user.onboarded) return <Navigate to="/onboarding" replace />;
  return <>{children}</>;
}

function ThemeSync() {
  const { data } = useMe();
  const theme = data?.settings?.theme;
  useEffect(() => {
    if (!theme) return;
    document.documentElement.dataset.theme = theme;
    try {
      localStorage.setItem('jz.theme', theme);
    } catch {
      /* ignore */
    }
  }, [theme]);
  return null;
}

export function App() {
  const { data } = useMe();
  const qc = useQueryClient();
  useEffect(() => {
    // Any 401 from the API means the server-side session ended: drop cached user data.
    const onUnauth = () => qc.setQueryData(qk.me, null);
    window.addEventListener('journzey:unauthenticated', onUnauth);
    return () => window.removeEventListener('journzey:unauthenticated', onUnauth);
  }, [qc]);

  return (
    <I18nProvider lang={data?.settings?.language ?? 'en'}>
      <ToastProvider>
        <ThemeSync />
        <a href="#main" className="sr-only focus:not-sr-only focus:fixed focus:left-2 focus:top-2 focus:z-[100] focus:rounded focus:bg-accent focus:px-3 focus:py-2 focus:text-accent-fg">
          Skip to content
        </a>
        <ErrorBoundary>
        <Suspense fallback={<Spinner />}>
          <Routes>
            <Route path="/" element={<Landing />} />
            <Route path="/login" element={<Login />} />
            <Route path="/auth/callback" element={<AuthCallback />} />
            <Route path="/terms" element={<Legal page="terms" />} />
            <Route path="/privacy" element={<Legal page="privacy" />} />
            <Route path="/security" element={<Legal page="security" />} />
            <Route path="/disclaimer" element={<Legal page="disclaimer" />} />
            <Route
              path="/onboarding"
              element={
                <RequireAuth onboarded={false}>
                  <Onboarding />
                </RequireAuth>
              }
            />
            <Route
              path="/app"
              element={
                <RequireAuth>
                  <AppShell />
                </RequireAuth>
              }
            >
              <Route index element={<Home />} />
              <Route path="dashboard" element={<Dashboard />} />
              <Route path="calendar" element={<CalendarPage />} />
              <Route path="trades" element={<TradeLog />} />
              <Route path="strategies" element={<Strategies />} />
              <Route path="edge" element={<EdgeMatrix />} />
              <Route path="notepad" element={<Notepad />} />
              <Route path="coach" element={<AiCoach />} />
              <Route path="settings" element={<Settings />} />
              <Route path="*" element={<NotFound inApp />} />
            </Route>
            <Route path="*" element={<NotFound />} />
          </Routes>
        </Suspense>
        </ErrorBoundary>
      </ToastProvider>
    </I18nProvider>
  );
}
