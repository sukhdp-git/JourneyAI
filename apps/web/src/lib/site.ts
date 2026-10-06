import { useQuery } from '@tanstack/react-query';
import type { SiteConfig } from '@journzey/shared';
import { get } from './api';

/** Public site configuration controlled from the control panel (polled so changes go live quickly). */
export function useSite() {
  return useQuery({ queryKey: ['site'], queryFn: () => get<SiteConfig>('/site'), refetchInterval: 60_000, staleTime: 30_000, retry: 1 });
}
