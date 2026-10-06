import { lazy, Suspense, type ReactNode } from 'react';
import { Navigate, Route, Routes, useLocation } from 'react-router';
import { AuthProvider, useAuth } from './lib/auth';
import { Spinner, ToastProvider } from './components/ui';
import Layout from './components/Layout';
import type { Role } from './lib/types';

const Login = lazy(() => import('./pages/Login'));
const Dashboard = lazy(() => import('./pages/Dashboard'));
const Users = lazy(() => import('./pages/Users'));
const UserDetail = lazy(() => import('./pages/UserDetail'));
const Integrations = lazy(() => import('./pages/Integrations'));
const Website = lazy(() => import('./pages/Website'));
const Admins = lazy(() => import('./pages/Admins'));
const Audit = lazy(() => import('./pages/Audit'));
const System = lazy(() => import('./pages/System'));
const Account = lazy(() => import('./pages/Account'));

function Protected({ children, min }: { children: ReactNode; min?: Role }) {
  const { admin, loading, can } = useAuth();
  const location = useLocation();
  if (loading) return <Spinner label="Checking session…" />;
  if (!admin) return <Navigate to={`/login?next=${encodeURIComponent(location.pathname)}`} replace />;
  if (min && !can(min)) return <p className="text-sm text-slate-500">You need the {min} role to view this page.</p>;
  return <>{children}</>;
}

export function App() {
  return (
    <AuthProvider>
      <ToastProvider>
        <Suspense fallback={<Spinner />}>
          <Routes>
            <Route path="/login" element={<Login />} />
            <Route
              element={
                <Protected>
                  <Layout />
                </Protected>
              }
            >
              <Route index element={<Dashboard />} />
              <Route path="dashboard" element={<Navigate to="/" replace />} />
              <Route path="users" element={<Users />} />
              <Route path="users/:id" element={<UserDetail />} />
              <Route path="integrations" element={<Integrations />} />
              <Route path="settings" element={<Navigate to="/integrations" replace />} />
              <Route path="website" element={<Website />} />
              <Route
                path="admins"
                element={
                  <Protected min="owner">
                    <Admins />
                  </Protected>
                }
              />
              <Route path="audit" element={<Audit />} />
              <Route path="system" element={<System />} />
              <Route path="account" element={<Account />} />
              <Route path="*" element={<p className="text-sm text-slate-500">Page not found.</p>} />
            </Route>
          </Routes>
        </Suspense>
      </ToastProvider>
    </AuthProvider>
  );
}
