import { Router } from 'express';
import { sql } from 'drizzle-orm';
import type { AppContext } from '../context.js';

/** Liveness and readiness probes. Responses intentionally reveal no infrastructure details. */
export function healthRouter(ctx: AppContext) {
  const r = Router();
  r.get('/health', (_req, res) => res.json({ status: 'ok', service: 'journzey-api', time: new Date().toISOString() }));
  r.get('/ready', async (_req, res) => {
    try {
      await ctx.db.execute(sql`select 1`);
      res.json({ status: 'ready' });
    } catch {
      res.status(503).json({ status: 'unavailable' });
    }
  });
  return r;
}
