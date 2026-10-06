/**
 * Request validators. Domain schemas are shared with the web client (packages/shared) so that
 * client-side and server-side validation can never drift; the server always re-validates.
 */
import { z } from 'zod';
export * from '@journzey/shared';

export const idParam = z.object({ id: z.string().uuid() });
export const monthQuery = z.object({ month: z.string().regex(/^\d{4}-\d{2}$/), account: z.string().max(40).optional() });
export const dateQuery = z.object({ date: z.string().regex(/^\d{4}-\d{2}-\d{2}$/) });
export const journalListQuery = z.object({
  from: z.string().regex(/^\d{4}-\d{2}-\d{2}$/).optional(),
  to: z.string().regex(/^\d{4}-\d{2}-\d{2}$/).optional(),
  demo: z.enum(['true', 'false']).default('false').transform((v) => v === 'true'),
  limit: z.coerce.number().int().min(1).max(366).default(60),
});
export const terminalLockSchema = z.object({ minutes: z.number().int().min(1).max(24 * 60) }).strict();
export const devLoginSchema = z.object({ email: z.string().email().max(320), name: z.string().trim().min(1).max(120) }).strict();
export const runnerAuditSchema = z.object({ windowHours: z.number().int().min(1).max(72).default(4) }).strict();
export const csvImportQuery = z.object({ serverUtcOffsetMinutes: z.coerce.number().int().min(-720).max(840).default(0) });
