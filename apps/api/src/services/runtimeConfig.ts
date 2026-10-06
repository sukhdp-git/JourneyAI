import { eq } from 'drizzle-orm';
import { z } from 'zod';
import type { Env } from '../config/env.js';
import type { Database } from '../db/client.js';
import { appSettings } from '../db/schema.js';
import type { SecretBox } from '../lib/crypto.js';
import type { Logger } from '../lib/logger.js';
import { badRequest } from '../lib/errors.js';

/**
 * Runtime configuration controlled from the control panel.
 *
 * Precedence: value saved in the control panel → environment variable → built-in default.
 * Secrets (API keys, OAuth client secret) are AES-256-GCM encrypted in app_settings and are
 * never returned to any client — the control panel only ever sees "configured · …abcd".
 * Every API instance re-reads settings every 30 s, and immediately after a local change.
 */

type Kind = 'string' | 'secret' | 'boolean' | 'url' | 'email' | 'enum' | 'text';

export interface SettingDef {
  key: string;
  group: 'google' | 'ai' | 'marketData' | 'site' | 'features';
  label: string;
  kind: Kind;
  help?: string;
  options?: readonly string[];
  envVar?: keyof Env;
  default?: string | boolean;
  maxLength?: number;
}

export const SETTINGS: readonly SettingDef[] = [
  { key: 'google.enabled', group: 'google', label: 'Enable Google sign-in', kind: 'boolean', default: true },
  { key: 'google.clientId', group: 'google', label: 'OAuth client ID', kind: 'string', envVar: 'GOOGLE_CLIENT_ID', maxLength: 200, help: 'Google Cloud Console → APIs & Services → Credentials → OAuth 2.0 Client IDs (Web application).' },
  { key: 'google.clientSecret', group: 'google', label: 'OAuth client secret', kind: 'secret', envVar: 'GOOGLE_CLIENT_SECRET', maxLength: 200 },
  { key: 'google.callbackUrl', group: 'google', label: 'Authorised redirect URI', kind: 'url', envVar: 'GOOGLE_CALLBACK_URL', maxLength: 300, help: 'Must exactly match an Authorised redirect URI on the Google OAuth client.' },
  { key: 'ai.enabled', group: 'ai', label: 'Enable AI Coach', kind: 'boolean', default: true },
  { key: 'ai.apiKey', group: 'ai', label: 'Anthropic API key', kind: 'secret', envVar: 'AI_API_KEY', maxLength: 300 },
  { key: 'ai.model', group: 'ai', label: 'Model', kind: 'string', envVar: 'AI_MODEL', default: 'claude-opus-5-5', maxLength: 80 },
  { key: 'marketData.enabled', group: 'marketData', label: 'Enable live market data', kind: 'boolean', default: true },
  { key: 'marketData.apiKey', group: 'marketData', label: 'Twelve Data API key', kind: 'secret', envVar: 'MARKET_DATA_API_KEY', maxLength: 200 },
  { key: 'site.name', group: 'site', label: 'Site name', kind: 'string', default: 'journzey.ai', maxLength: 60 },
  { key: 'site.tagline', group: 'site', label: 'Tagline', kind: 'string', default: 'Institutional Trading Journal & AI Discipline Terminal', maxLength: 140 },
  { key: 'site.supportEmail', group: 'site', label: 'Support email', kind: 'email', default: '', maxLength: 320 },
  { key: 'site.registrationOpen', group: 'site', label: 'Allow new sign-ups', kind: 'boolean', default: true, help: 'When off, existing users can still sign in; new Google accounts are refused.' },
  { key: 'site.maintenance.enabled', group: 'site', label: 'Maintenance mode', kind: 'boolean', default: false, help: 'Blocks the trading terminal and API for all users. The control panel stays available.' },
  { key: 'site.maintenance.message', group: 'site', label: 'Maintenance message', kind: 'text', default: 'journzey.ai is undergoing scheduled maintenance. Your data is safe — please check back shortly.', maxLength: 500 },
  { key: 'site.announcement.enabled', group: 'site', label: 'Show announcement banner', kind: 'boolean', default: false },
  { key: 'site.announcement.text', group: 'site', label: 'Announcement text', kind: 'text', default: '', maxLength: 300 },
  { key: 'site.announcement.tone', group: 'site', label: 'Announcement style', kind: 'enum', options: ['info', 'success', 'warning'], default: 'info' },
  { key: 'features.aiCoach', group: 'features', label: 'AI Coach & AI reviews', kind: 'boolean', default: true },
  { key: 'features.brokerSync', group: 'features', label: 'Broker Sync Hub & webhooks', kind: 'boolean', default: true },
  { key: 'features.demoMode', group: 'features', label: 'Demo data', kind: 'boolean', default: true },
  { key: 'features.marketTicker', group: 'features', label: 'Market ticker', kind: 'boolean', default: true },
];

const DEFS = new Map(SETTINGS.map((d) => [d.key, d]));

export type SettingSource = 'control-panel' | 'environment' | 'default' | 'unset';

export interface SettingView {
  key: string;
  group: SettingDef['group'];
  label: string;
  kind: Kind;
  help?: string;
  options?: readonly string[];
  source: SettingSource;
  /** Present for non-secret settings only. */
  value?: string | boolean | null;
  /** Secrets only: whether a value is configured and a short hint (last 4 chars). */
  configured?: boolean;
  hint?: string | null;
  updatedAt?: string | null;
  envVar?: string;
}

function validate(def: SettingDef, raw: unknown): string | boolean {
  if (def.kind === 'boolean') {
    if (typeof raw !== 'boolean') throw badRequest(`${def.label} must be true or false`, { [def.key]: ['Expected a boolean'] });
    return raw;
  }
  if (typeof raw !== 'string') throw badRequest(`${def.label} must be text`, { [def.key]: ['Expected a string'] });
  const v = raw.trim();
  if (def.maxLength && v.length > def.maxLength) throw badRequest(`${def.label} is too long`, { [def.key]: [`Maximum ${def.maxLength} characters`] });
  // eslint-disable-next-line no-control-regex -- intentionally rejects control characters in settings
  if (/[\u0000-\u0008\u000b\u000c\u000e-\u001f]/.test(v)) throw badRequest(`${def.label} contains control characters`, { [def.key]: ['Invalid characters'] });
  if (def.kind === 'url' && v && !z.string().url().safeParse(v).success) throw badRequest(`${def.label} must be a valid URL`, { [def.key]: ['Invalid URL'] });
  if (def.kind === 'url' && v && !/^https?:\/\//.test(v)) throw badRequest(`${def.label} must use http(s)`, { [def.key]: ['Invalid URL'] });
  if (def.kind === 'email' && v && !z.string().email().safeParse(v).success) throw badRequest(`${def.label} must be a valid email`, { [def.key]: ['Invalid email'] });
  if (def.kind === 'enum' && !def.options!.includes(v)) throw badRequest(`${def.label} has an invalid value`, { [def.key]: [`One of ${def.options!.join(', ')}`] });
  if (def.key === 'google.clientId' && v && !/^[\w.-]+\.apps\.googleusercontent\.com$/.test(v)) {
    throw badRequest('Google client ID should end with .apps.googleusercontent.com', { [def.key]: ['Unexpected format'] });
  }
  return v;
}

interface Stored {
  value: unknown;
  secret: string | null;
  updatedAt: Date;
}

export class RuntimeConfig {
  private stored = new Map<string, Stored>();
  private loaded: Promise<void> | null = null;
  private timer: NodeJS.Timeout | null = null;
  /** Bumped on every change so dependent clients (AI, market data) can rebuild. */
  version = 0;

  constructor(
    private readonly db: Database,
    private readonly env: Env,
    private readonly box: SecretBox,
    private readonly log: Logger,
  ) {}

  /** Loads settings once; safe to await on every request. */
  ready(): Promise<void> {
    if (!this.loaded) {
      this.loaded = this.reload().catch((err) => {
        this.loaded = null;
        throw err;
      });
      if (this.env.NODE_ENV !== 'test' && !this.timer) {
        this.timer = setInterval(() => void this.reload().catch((e) => this.log.warn({ err: (e as Error).message }, 'settings refresh failed')), 30_000);
        this.timer.unref();
      }
    }
    return this.loaded;
  }

  async reload(): Promise<void> {
    const rows = await this.db.select().from(appSettings);
    const next = new Map<string, Stored>();
    for (const r of rows) {
      let secret: string | null = null;
      if (r.encryptedValue) {
        try {
          secret = this.box.decrypt(r.encryptedValue);
        } catch {
          this.log.error({ key: r.key }, 'could not decrypt setting (ENCRYPTION_KEY changed?)');
        }
      }
      next.set(r.key, { value: r.value, secret, updatedAt: r.updatedAt });
    }
    const changed = JSON.stringify([...next.entries()].map(([k, v]) => [k, v.value, v.secret])) !== JSON.stringify([...this.stored.entries()].map(([k, v]) => [k, v.value, v.secret]));
    this.stored = next;
    if (changed) this.version++;
  }

  stop() {
    if (this.timer) clearInterval(this.timer);
  }

  private raw(key: string): { value: string | boolean | null; source: SettingSource } {
    const def = DEFS.get(key);
    if (!def) throw new Error(`Unknown setting ${key}`);
    const s = this.stored.get(key);
    if (s) {
      const v = def.kind === 'secret' ? s.secret : (s.value as string | boolean | null);
      if (v !== null && v !== undefined && v !== '') return { value: v, source: 'control-panel' };
    }
    if (def.envVar) {
      const e = this.env[def.envVar];
      if (e !== undefined && e !== null && e !== '') return { value: e as string, source: 'environment' };
    }
    if (def.default !== undefined) return { value: def.default, source: 'default' };
    return { value: null, source: 'unset' };
  }

  str(key: string): string | undefined {
    const v = this.raw(key).value;
    return typeof v === 'string' && v !== '' ? v : undefined;
  }

  bool(key: string): boolean {
    return this.raw(key).value === true;
  }

  google() {
    const callbackUrl = this.str('google.callbackUrl') ?? `${this.env.APP_URL.replace(/\/$/, '')}/api/v1/auth/google/callback`;
    return {
      enabled: this.bool('google.enabled'),
      clientId: this.str('google.clientId'),
      clientSecret: this.str('google.clientSecret'),
      callbackUrl,
    };
  }

  ai() {
    return { enabled: this.bool('ai.enabled') && this.bool('features.aiCoach'), apiKey: this.str('ai.apiKey'), model: this.str('ai.model') ?? 'claude-opus-5-5' };
  }

  marketData() {
    return { enabled: this.bool('marketData.enabled') && this.bool('features.marketTicker'), apiKey: this.str('marketData.apiKey') };
  }

  site() {
    return {
      name: this.str('site.name') ?? 'journzey.ai',
      tagline: this.str('site.tagline') ?? '',
      supportEmail: this.str('site.supportEmail') ?? null,
      registrationOpen: this.bool('site.registrationOpen'),
      maintenance: { enabled: this.bool('site.maintenance.enabled'), message: this.str('site.maintenance.message') ?? '' },
      announcement: {
        enabled: this.bool('site.announcement.enabled') && !!this.str('site.announcement.text'),
        text: this.str('site.announcement.text') ?? '',
        tone: (this.str('site.announcement.tone') ?? 'info') as 'info' | 'success' | 'warning',
      },
    };
  }

  features() {
    return {
      aiCoach: this.bool('features.aiCoach'),
      brokerSync: this.bool('features.brokerSync'),
      demoMode: this.bool('features.demoMode'),
      marketTicker: this.bool('features.marketTicker'),
    };
  }

  /** Safe view for the control panel: secret values are never included. */
  list(): SettingView[] {
    return SETTINGS.map((def) => {
      const { value, source } = this.raw(def.key);
      const base: SettingView = {
        key: def.key,
        group: def.group,
        label: def.label,
        kind: def.kind,
        help: def.help,
        options: def.options,
        source,
        updatedAt: this.stored.get(def.key)?.updatedAt.toISOString() ?? null,
        envVar: def.envVar,
      };
      if (def.kind === 'secret') {
        const s = typeof value === 'string' ? value : null;
        return { ...base, configured: !!s, hint: s ? `…${s.slice(-4)}` : null };
      }
      return { ...base, value };
    });
  }

  /** Validates and saves several settings atomically. Returns the keys that changed. */
  async update(patch: Record<string, unknown>, adminId: string): Promise<string[]> {
    const entries = Object.entries(patch);
    if (entries.length === 0) return [];
    const rows = entries.map(([key, raw]) => {
      const def = DEFS.get(key);
      if (!def) throw badRequest(`Unknown setting ${key}`, { [key]: ['Unknown setting'] });
      const v = validate(def, raw);
      return def.kind === 'secret'
        ? { key, value: null, encryptedValue: v === '' ? null : this.box.encrypt(String(v)), updatedBy: adminId, updatedAt: new Date() }
        : { key, value: v as unknown, encryptedValue: null, updatedBy: adminId, updatedAt: new Date() };
    });
    await this.db.transaction(async (tx) => {
      for (const row of rows) {
        await tx.insert(appSettings).values(row).onConflictDoUpdate({ target: appSettings.key, set: row });
      }
    });
    await this.reload();
    return rows.map((r) => r.key);
  }

  /** Removes the control-panel value so the environment variable / default applies again. */
  async reset(key: string): Promise<void> {
    if (!DEFS.has(key)) throw badRequest(`Unknown setting ${key}`);
    await this.db.delete(appSettings).where(eq(appSettings.key, key));
    await this.reload();
  }
}
