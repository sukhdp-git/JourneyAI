import type { Request } from 'express';
import type { DbOrTx } from '../db/client.js';
import { auditLogs } from '../db/schema.js';

export type AuditAction =
  | 'auth.login'
  | 'auth.login_failed'
  | 'auth.logout'
  | 'auth.dev_login'
  | 'user.created'
  | 'user.onboarded'
  | 'user.profile_updated'
  | 'user.settings_updated'
  | 'user.data_exported'
  | 'user.deleted'
  | 'account.created'
  | 'account.updated'
  | 'account.deleted'
  | 'capital.transaction_created'
  | 'capital.transaction_deleted'
  | 'trade.deleted'
  | 'trades.exported'
  | 'strategy.deleted'
  | 'journal.deleted'
  | 'broker.connected'
  | 'broker.disconnected'
  | 'broker.secret_rotated'
  | 'broker.sync'
  | 'broker.import'
  | 'demo.reset'
  | 'demo.removed'
  | 'terminal.locked';

/** Records security-sensitive events. Metadata must never contain secrets. */
export async function audit(
  db: DbOrTx,
  req: Request | null,
  action: AuditAction,
  userId: string | null,
  metadata: Record<string, unknown> = {},
): Promise<void> {
  await db.insert(auditLogs).values({
    userId,
    action,
    ip: req?.ip?.slice(0, 64) ?? null,
    userAgent: req?.get('user-agent')?.slice(0, 400) ?? null,
    requestId: (req?.id as string | undefined)?.slice(0, 64) ?? null,
    metadata,
  });
}
