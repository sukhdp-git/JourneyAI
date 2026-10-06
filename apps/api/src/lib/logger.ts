import { createRequire } from 'node:module';
import pino from 'pino';

/** pino-pretty is a dev dependency: only use it when installed and writing to a terminal. */
function prettyAvailable(): boolean {
  if (!process.stdout.isTTY) return false;
  try {
    createRequire(import.meta.url).resolve('pino-pretty');
    return true;
  } catch {
    return false;
  }
}

/**
 * Structured JSON logger. Sensitive fields are redacted at the serializer level so
 * secrets, cookies and tokens can never reach log sinks (stdout, Logtail, Datadog...).
 */
export const REDACT_PATHS = [
  'req.headers.cookie',
  'req.headers.authorization',
  'req.headers["x-csrf-token"]',
  'req.headers["x-journzey-signature"]',
  'res.headers["set-cookie"]',
  '*.password',
  '*.apiKey',
  '*.apiSecret',
  '*.secret',
  '*.token',
  '*.accessToken',
  '*.refreshToken',
  '*.idToken',
  '*.code',
  '*.clientSecret',
];

export function createLogger(level: string, pretty = false) {
  return pino({
    level,
    redact: { paths: REDACT_PATHS, censor: '[REDACTED]' },
    base: { service: 'journzey-api' },
    timestamp: pino.stdTimeFunctions.isoTime,
    ...(pretty && prettyAvailable() ? { transport: { target: 'pino-pretty', options: { colorize: true } } } : {}),
  });
}

export type Logger = ReturnType<typeof createLogger>;
