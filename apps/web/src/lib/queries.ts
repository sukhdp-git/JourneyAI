import { useMutation, useQuery, useQueryClient, type QueryClient } from '@tanstack/react-query';
import type { MeResponse, SettingsDto, StrategyDto, TradingAccountDto } from '@journzey/shared';
import { ApiError, get, patch } from './api';

export const qk = {
  me: ['me'] as const,
  accounts: ['accounts'] as const,
  strategies: ['strategies'] as const,
  trades: (params: unknown) => ['trades', params] as const,
  dashboard: (params: unknown) => ['dashboard', params] as const,
  calendar: (params: unknown) => ['calendar', params] as const,
  analytics: (kind: string, params: unknown) => ['analytics', kind, params] as const,
  journal: (params: unknown) => ['journal', params] as const,
  checklist: (date: string) => ['checklist', date] as const,
  market: ['market'] as const,
  brokers: ['brokers'] as const,
  ai: ['ai'] as const,
};

export type AccountWithFlags = TradingAccountDto & { sampleData: boolean };

export function useMe() {
  return useQuery({
    queryKey: qk.me,
    queryFn: async () => {
      try {
        return await get<MeResponse>('/auth/me');
      } catch (e) {
        if (e instanceof ApiError && e.status === 401) return null;
        throw e;
      }
    },
    staleTime: 60_000,
    retry: false,
  });
}

/** The authenticated user's settings (guaranteed inside the protected app shell). */
export function useSettings(): SettingsDto {
  const { data } = useMe();
  if (!data?.settings) throw new Error('Settings not loaded');
  return data.settings;
}

export function useAccounts() {
  return useQuery({ queryKey: qk.accounts, queryFn: () => get<AccountWithFlags[]>('/accounts'), staleTime: 30_000 });
}

export function useStrategies() {
  return useQuery({ queryKey: qk.strategies, queryFn: () => get<StrategyDto[]>('/strategies'), staleTime: 60_000 });
}

/** Active account scope ("real" | "demo" | "all" | account uuid), persisted server-side per user. */
export function useScope() {
  const settings = useSettings();
  const qc = useQueryClient();
  const m = useMutation({
    mutationFn: (scope: string) => patch<SettingsDto>('/settings', { activeAccountScope: scope }),
    onSuccess: (s) => {
      qc.setQueryData<MeResponse | null>(qk.me, (me) => (me ? { ...me, settings: s } : me));
      invalidateTradeData(qc);
    },
  });
  return { scope: settings.activeAccountScope, setScope: m.mutate, pending: m.isPending };
}

/** Invalidate everything derived from trades after a write. */
export function invalidateTradeData(qc: QueryClient) {
  for (const key of ['trades', 'dashboard', 'calendar', 'analytics', 'accounts']) void qc.invalidateQueries({ queryKey: [key] });
}

export function useUpdateSettings() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (body: Partial<SettingsDto>) => patch<SettingsDto>('/settings', body),
    onSuccess: (s) => qc.setQueryData<MeResponse | null>(qk.me, (me) => (me ? { ...me, settings: s } : me)),
  });
}
