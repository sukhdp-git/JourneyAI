import type { RequestHandler } from 'express';
import { eq } from 'drizzle-orm';
import type { Database } from '../db/client.js';
import { users } from '../db/schema.js';
import { unauthorized } from '../lib/errors.js';

/**
 * Resolves the authenticated user from the server-side session on EVERY request.
 * The user id is never taken from the client. Sessions pointing at deleted users are destroyed.
 */
export function requireAuth(db: Database): RequestHandler {
  return (req, _res, next) => {
    const userId = req.session?.userId;
    if (!userId) return next(unauthorized());
    db.select({ id: users.id, email: users.email })
      .from(users)
      .where(eq(users.id, userId))
      .limit(1)
      .then(([row]) => {
        if (!row) {
          req.session.destroy(() => next(unauthorized('Session expired')));
          return;
        }
        req.user = row;
        next();
      })
      .catch(next);
  };
}

/** Helper for handlers behind requireAuth. */
export function currentUserId(req: Parameters<RequestHandler>[0]): string {
  if (!req.user) throw unauthorized();
  return req.user.id;
}
