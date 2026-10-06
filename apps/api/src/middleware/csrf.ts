import type { RequestHandler } from 'express';
import { AppError } from '../lib/errors.js';
import { safeEqual } from '../lib/crypto.js';

const SAFE_METHODS = new Set(['GET', 'HEAD', 'OPTIONS']);

/**
 * CSRF defence in depth (cookies are already SameSite=Lax):
 *  1. Origin/Referer must match an allowed origin when present.
 *  2. A per-session synchronizer token must be echoed in the X-CSRF-Token header.
 * Signed machine-to-machine endpoints (broker webhooks) are exempt — they authenticate by HMAC.
 */
export function csrfProtection(allowedOrigins: string[], exemptPrefixes: string[]): RequestHandler {
  return (req, _res, next) => {
    if (SAFE_METHODS.has(req.method)) return next();
    if (exemptPrefixes.some((p) => req.originalUrl.startsWith(p))) return next();

    const origin = req.get('origin') ?? (req.get('referer') ? new URL(req.get('referer')!).origin : undefined);
    if (origin && !allowedOrigins.includes(origin)) {
      return next(new AppError(403, 'CSRF_ORIGIN_MISMATCH', 'Cross-origin request rejected'));
    }
    const header = req.get('x-csrf-token');
    const expected = req.session?.csrfToken;
    if (!header || !expected || !safeEqual(header, expected)) {
      return next(new AppError(403, 'CSRF_TOKEN_INVALID', 'Missing or invalid CSRF token'));
    }
    next();
  };
}
