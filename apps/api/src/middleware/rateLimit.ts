import rateLimit, { ipKeyGenerator } from 'express-rate-limit';
import type { Request } from 'express';

/**
 * In-memory rate limiting (per process). For multi-instance deployments put the API
 * behind a shared limiter (load balancer / WAF) — see DEPLOYMENT.md.
 */
const userOrIp = (req: Request) => req.session?.userId ?? ipKeyGenerator(req.ip ?? 'unknown');

const handler = (code: string, message: string) => ({
  standardHeaders: 'draft-7' as const,
  legacyHeaders: false,
  message: { error: { code, message } },
});

export const createLimiters = (isTest: boolean) => {
  const skip = () => isTest && process.env.RATE_LIMIT_IN_TESTS !== 'true';
  return {
    api: rateLimit({ windowMs: 60_000, limit: 600, keyGenerator: userOrIp, skip, ...handler('RATE_LIMITED', 'Too many requests, slow down') }),
    auth: rateLimit({ windowMs: 15 * 60_000, limit: 30, skip, ...handler('AUTH_RATE_LIMITED', 'Too many sign-in attempts, try again later') }),
    ai: rateLimit({ windowMs: 60_000, limit: 15, keyGenerator: userOrIp, skip, ...handler('AI_RATE_LIMITED', 'AI Coach rate limit reached, try again in a minute') }),
    upload: rateLimit({ windowMs: 10 * 60_000, limit: 40, keyGenerator: userOrIp, skip, ...handler('UPLOAD_RATE_LIMITED', 'Too many uploads, try again later') }),
    webhook: rateLimit({ windowMs: 60_000, limit: 120, skip, ...handler('WEBHOOK_RATE_LIMITED', 'Webhook rate limit exceeded') }),
  };
};
