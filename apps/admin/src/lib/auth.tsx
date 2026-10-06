import { createContext, useContext, useEffect, type ReactNode } from 'react';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { ApiError, get, setCsrf } from './api';
import type { Admin, Role } from './types';

const RANK: Record<Role, number> = { viewer: 0, admin: 1, owner: 2 };
interface Ctx {
  admin: Admin | null;
  loading: boolean;
  can: (role: Role) => boolean;
  refresh: () => Promise<unknown>;
}
const AuthCtx = createContext<Ctx>({ admin: null, loading: true, can: () => false, refresh: async () => undefined });

export function AuthProvider({ children }: { children: ReactNode }) {
  const qc = useQueryClient();
  const q = useQuery({
    queryKey: ['me'],
    queryFn: async () => {
      try {
        const r = await get<{ admin: Admin; csrfToken: string }>('/auth/me');
        setCsrf(r.csrfToken);
        return r.admin;
      } catch (e) {
        if (e instanceof ApiError && e.status === 401) return null;
        throw e;
      }
    },
    retry: false,
    staleTime: 60_000,
  });
  useEffect(() => {
    const h = () => qc.setQueryData(['me'], null);
    window.addEventListener('cp:unauthenticated', h);
    return () => window.removeEventListener('cp:unauthenticated', h);
  }, [qc]);
  const admin = q.data ?? null;
  return (
    <AuthCtx.Provider value={{ admin, loading: q.isLoading, can: (r) => !!admin && RANK[admin.role] >= RANK[r], refresh: () => q.refetch() }}>{children}</AuthCtx.Provider>
  );
}

export const useAuth = () => useContext(AuthCtx);
