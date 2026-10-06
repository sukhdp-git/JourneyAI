export type Role = 'owner' | 'admin' | 'viewer';

export interface Admin {
  id: string;
  email: string;
  name: string;
  role: Role;
  totpEnabled: boolean;
  disabled: boolean;
  lastLoginAt: string | null;
  createdAt: string;
}

export interface Setting {
  key: string;
  group: 'google' | 'ai' | 'marketData' | 'site' | 'features';
  label: string;
  kind: 'string' | 'secret' | 'boolean' | 'url' | 'email' | 'enum' | 'text';
  help?: string;
  options?: string[];
  source: 'control-panel' | 'environment' | 'default' | 'unset';
  value?: string | boolean | null;
  configured?: boolean;
  hint?: string | null;
  updatedAt?: string | null;
  envVar?: string;
}

export interface SettingsResponse {
  settings: Setting[];
  derived: { googleRedirectUri: string; appUrl: string };
}

export interface Overview {
  stats: Record<
    | 'users'
    | 'usersOnboarded'
    | 'usersSuspended'
    | 'newUsers7d'
    | 'newUsers30d'
    | 'activeUsers7d'
    | 'trades'
    | 'trades24h'
    | 'journals'
    | 'brokerConnections'
    | 'aiMessages30d'
    | 'activeSessions',
    number
  >;
  daily: Array<{ day: string; signups: number; trades: number }>;
  integrations: {
    google: { configured: boolean; enabled: boolean };
    ai: { configured: boolean; active: boolean; model: string };
    marketData: { configured: boolean; active: boolean };
    storage: string;
  };
  site: { maintenance: boolean; registrationOpen: boolean; announcement: boolean };
}

export interface UserRow {
  id: string;
  email: string;
  name: string | null;
  avatarUrl: string | null;
  authProvider: string;
  onboarded: boolean;
  suspended: boolean;
  createdAt: string;
  lastLoginAt: string | null;
  trades: number;
  accounts: number;
}

export interface UserDetail {
  id: string;
  email: string;
  name: string | null;
  avatarUrl: string | null;
  authProvider: string;
  emailVerified: boolean;
  onboarded: boolean;
  suspendedAt: string | null;
  createdAt: string;
  lastLoginAt: string | null;
  counts: { trades: number; journals: number; brokerConnections: number; sessions: number };
  accounts: Array<{ id: string; name: string; currency: string; currentCapital: string; demo: boolean; sampleData: boolean }>;
  events: Array<{ at: string; action: string; ip: string | null }>;
}

export interface AuditItem {
  id: number;
  at: string;
  actor: string | null;
  action: string;
  target: string | null;
  ip: string | null;
  metadata: Record<string, unknown>;
}

export interface SystemInfo {
  nodeVersion: string;
  environment: string;
  uptimeSeconds: number;
  memoryMb: number;
  dbLatencyMs: number;
  migrations: { available: number; applied: number };
  storage: string;
  devAuthBypass: boolean;
  ipAllowlist: boolean;
  sentry: boolean;
}
