import pino from 'pino';

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
    ...(pretty ? { transport: { target: 'pino-pretty', options: { colorize: true } } } : {}),
  });
}

export type Logger = ReturnType<typeof createLogger>;
