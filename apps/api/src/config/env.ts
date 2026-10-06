import { z } from 'zod';

const bool = z
  .union([z.boolean(), z.string()])
  .optional()
  .transform((v) => v === true || v === 'true' || v === '1');

const optionalString = z
  .string()
  .optional()
  .transform((v) => (v && v.trim() !== '' ? v.trim() : undefined));

const envSchema = z.object({
  NODE_ENV: z.enum(['development', 'test', 'production']).default('development'),
  PORT: z.coerce.number().int().default(4000),
  LOG_LEVEL: z.enum(['fatal', 'error', 'warn', 'info', 'debug', 'trace', 'silent']).default('info'),
  DATABASE_URL: z.string().min(1, 'DATABASE_URL is required'),
  DATABASE_SSL: bool,
  APP_URL: z.string().url().default('http://localhost:3000'),
  API_URL: z.string().url().default('http://localhost:3000/api'),
  CORS_ORIGINS: optionalString,
  TRUST_PROXY: optionalString,
  SESSION_SECRET: z.string().min(32, 'SESSION_SECRET must be at least 32 characters'),
  SESSION_TTL_HOURS: z.coerce.number().int().min(1).max(24 * 90).default(24 * 7),
  ENCRYPTION_KEY: z
    .string()
    .refine((v) => Buffer.from(v, 'base64').length === 32, 'ENCRYPTION_KEY must be 32 bytes, base64-encoded'),
  GOOGLE_CLIENT_ID: optionalString,
  GOOGLE_CLIENT_SECRET: optionalString,
  GOOGLE_CALLBACK_URL: optionalString,
  DEV_AUTH_BYPASS: bool,
  AI_API_KEY: optionalString,
  AI_MODEL: z.string().default('claude-opus-5-5'),
  MARKET_DATA_PROVIDER: z.enum(['twelvedata']).default('twelvedata'),
  MARKET_DATA_API_KEY: optionalString,
  STORAGE_PROVIDER: z.enum(['local', 's3']).default('local'),
  STORAGE_BUCKET: optionalString,
  STORAGE_REGION: optionalString,
  STORAGE_ENDPOINT: optionalString,
  STORAGE_ACCESS_KEY: optionalString,
  STORAGE_SECRET_KEY: optionalString,
  UPLOAD_DIR: z.string().default('./uploads'),
  WEB_DIST_DIR: optionalString,
  SENTRY_DSN: optionalString,
  /** Built control-panel app (apps/admin/dist) served at /control-panel/. */
  ADMIN_DIST_DIR: optionalString,
  /** Optional comma-separated client IPs allowed to reach /control-panel and its API. */
  ADMIN_IP_ALLOWLIST: optionalString,
  ADMIN_SESSION_IDLE_MINUTES: z.coerce.number().int().min(5).max(24 * 60).default(30),
  ADMIN_SESSION_MAX_HOURS: z.coerce.number().int().min(1).max(72).default(12),
  /** First-run bootstrap: creates this owner account if no administrator exists yet. */
  ADMIN_BOOTSTRAP_EMAIL: optionalString,
  ADMIN_BOOTSTRAP_PASSWORD: optionalString,
  BINANCE_API_BASE: z.string().url().default('https://api.binance.com'),
});

export type Env = z.infer<typeof envSchema>;

export function loadEnv(source: NodeJS.ProcessEnv = process.env): Env {
  const parsed = envSchema.safeParse(source);
  if (!parsed.success) {
    const issues = parsed.error.issues.map((i) => `  - ${i.path.join('.')}: ${i.message}`).join('\n');
    throw new Error(`Invalid environment configuration:\n${issues}`);
  }
  const env = parsed.data;
  if (env.NODE_ENV === 'production' && env.DEV_AUTH_BYPASS) {
    throw new Error('DEV_AUTH_BYPASS must never be enabled in production');
  }
  if (env.STORAGE_PROVIDER === 's3' && (!env.STORAGE_BUCKET || !env.STORAGE_ACCESS_KEY || !env.STORAGE_SECRET_KEY)) {
    throw new Error('STORAGE_PROVIDER=s3 requires STORAGE_BUCKET, STORAGE_ACCESS_KEY and STORAGE_SECRET_KEY');
  }
  return env;
}

export const isGoogleConfigured = (env: Env) =>
  Boolean(env.GOOGLE_CLIENT_ID && env.GOOGLE_CLIENT_SECRET && env.GOOGLE_CALLBACK_URL);
