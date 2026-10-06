import { hash, verify } from '@node-rs/argon2';
import { and, desc, eq, ilike, isNotNull, isNull, or, sql, type SQL } from 'drizzle-orm';
import type { Request } from 'express';
import type { Database } from '../db/client.js';
import {
  adminAuditLogs,
  adminUsers,
  aiMessages,
  auditLogs,
  brokerConnections,
  journalEntries,
  trades,
  tradingAccounts,
  userSessions,
  users,
} from '../db/schema.js';
import { AppError, badRequest, conflict, forbidden, notFound } from '../lib/errors.js';
import type { SecretBox } from '../lib/crypto.js';
import type { Logger } from '../lib/logger.js';
import { verifyTotp } from '../lib/totp.js';

export type AdminRole = 'owner' | 'admin' | 'viewer';
type AdminRow = typeof adminUsers.$inferSelect;

export interface AdminDto {
  id: string;
  email: string;
  name: string;
  role: AdminRole;
  totpEnabled: boolean;
  disabled: boolean;
  lastLoginAt: string | null;
  createdAt: string;
}

export const toAdminDto = (a: AdminRow): AdminDto => ({
  id: a.id,
  email: a.email,
  name: a.name,
  role: a.role as AdminRole,
  totpEnabled: a.totpEnabled,
  disabled: a.disabled,
  lastLoginAt: a.lastLoginAt?.toISOString() ?? null,
  createdAt: a.createdAt.toISOString(),
});

const MAX_FAILED = 5;
const LOCK_MINUTES = 15;
// Argon2id parameters (OWASP recommended minimum: m=19 MiB, t=2, p=1).
const ARGON = { memoryCost: 19_456, timeCost: 2, parallelism: 1 } as const;
// Pre-computed hash used to keep timing constant when the email does not exist.
let dummyHash: string | null = null;

export function validatePassword(password: string, email: string): void {
  const problems: string[] = [];
  if (password.length < 12) problems.push('At least 12 characters');
  if (password.length > 200) problems.push('At most 200 characters');
  if (!/[a-z]/.test(password) || !/[A-Z]/.test(password) || !/\d/.test(password)) problems.push('Mix upper-case, lower-case and digits');
  if (password.toLowerCase().includes(email.split('@')[0]!.toLowerCase())) problems.push('Must not contain your email name');
  if (problems.length) throw badRequest('Password does not meet the policy', { password: problems });
}

export const hashPassword = (p: string) => hash(p, ARGON);

export class AdminService {
  constructor(
    private readonly db: Database,
    private readonly box: SecretBox,
    private readonly log: Logger,
  ) {}

  async count(): Promise<number> {
    const [r] = await this.db.select({ n: sql<number>`count(*)::int` }).from(adminUsers);
    return r?.n ?? 0;
  }

  async get(id: string): Promise<AdminRow | null> {
    const [a] = await this.db.select().from(adminUsers).where(eq(adminUsers.id, id)).limit(1);
    return a ?? null;
  }

  async create(input: { email: string; name: string; password: string; role: AdminRole }): Promise<AdminDto> {
    const email = input.email.trim().toLowerCase();
    validatePassword(input.password, email);
    const [existing] = await this.db.select({ id: adminUsers.id }).from(adminUsers).where(sql`lower(${adminUsers.email}) = ${email}`).limit(1);
    if (existing) throw conflict('An administrator with this email already exists');
    const [a] = await this.db
      .insert(adminUsers)
      .values({ email, name: input.name.trim(), role: input.role, passwordHash: await hashPassword(input.password) })
      .returning();
    return toAdminDto(a!);
  }

  /**
   * Password (+ optional TOTP) authentication with per-account lockout.
   * Returns 'totp_required' when the password is right but a second factor is needed.
   */
  async authenticate(email: string, password: string, totp?: string): Promise<{ admin: AdminRow } | 'totp_required'> {
    const [a] = await this.db.select().from(adminUsers).where(sql`lower(${adminUsers.email}) = ${email.trim().toLowerCase()}`).limit(1);
    const invalid = () => new AppError(401, 'INVALID_CREDENTIALS', 'Invalid email, password or verification code');
    if (!a) {
      dummyHash ??= await hashPassword('timing-equaliser-password-0000');
      await verify(dummyHash, password).catch(() => false);
      throw invalid();
    }
    if (a.lockedUntil && a.lockedUntil > new Date()) {
      throw new AppError(423, 'ACCOUNT_LOCKED', `Too many failed attempts. Try again after ${a.lockedUntil.toISOString().slice(11, 16)} UTC.`);
    }
    const ok = await verify(a.passwordHash, password).catch(() => false);
    if (!ok) {
      await this.recordFailure(a);
      throw invalid();
    }
    if (a.disabled) throw new AppError(403, 'ADMIN_DISABLED', 'This administrator account is disabled');
    if (a.totpEnabled) {
      if (!totp) return 'totp_required';
      const secret = this.box.decrypt(a.totpSecretEncrypted!);
      if (!verifyTotp(secret, totp)) {
        await this.recordFailure(a);
        throw invalid();
      }
    }
    const [updated] = await this.db
      .update(adminUsers)
      .set({ failedLogins: 0, lockedUntil: null, lastLoginAt: new Date() })
      .where(eq(adminUsers.id, a.id))
      .returning();
    return { admin: updated! };
  }

  private async recordFailure(a: AdminRow) {
    const failed = a.failedLogins + 1;
    await this.db
      .update(adminUsers)
      .set({ failedLogins: failed >= MAX_FAILED ? 0 : failed, lockedUntil: failed >= MAX_FAILED ? new Date(Date.now() + LOCK_MINUTES * 60_000) : a.lockedUntil })
      .where(eq(adminUsers.id, a.id));
  }

  async changePassword(id: string, current: string, next: string): Promise<void> {
    const a = await this.get(id);
    if (!a) throw notFound();
    if (!(await verify(a.passwordHash, current).catch(() => false))) throw badRequest('Current password is incorrect', { currentPassword: ['Incorrect password'] });
    validatePassword(next, a.email);
    await this.db.update(adminUsers).set({ passwordHash: await hashPassword(next), passwordChangedAt: new Date(), updatedAt: new Date() }).where(eq(adminUsers.id, id));
  }

  async enableTotp(id: string, secret: string, code: string): Promise<void> {
    if (!verifyTotp(secret, code)) throw badRequest('Verification code is incorrect', { code: ['Incorrect code — check your device clock'] });
    await this.db.update(adminUsers).set({ totpSecretEncrypted: this.box.encrypt(secret), totpEnabled: true, updatedAt: new Date() }).where(eq(adminUsers.id, id));
  }

  async disableTotp(id: string, password: string): Promise<void> {
    const a = await this.get(id);
    if (!a) throw notFound();
    if (!(await verify(a.passwordHash, password).catch(() => false))) throw badRequest('Password is incorrect', { password: ['Incorrect password'] });
    await this.db.update(adminUsers).set({ totpSecretEncrypted: null, totpEnabled: false, updatedAt: new Date() }).where(eq(adminUsers.id, id));
  }

  async list(): Promise<AdminDto[]> {
    return (await this.db.select().from(adminUsers).orderBy(adminUsers.createdAt)).map(toAdminDto);
  }

  async update(actor: AdminRow, id: string, patch: { role?: AdminRole; disabled?: boolean; name?: string }): Promise<AdminDto> {
    const target = await this.get(id);
    if (!target) throw notFound('Administrator not found');
    if (target.id === actor.id && (patch.role !== undefined || patch.disabled)) throw forbidden('You cannot change your own role or disable yourself');
    await this.assertOwnerRemains(target, patch.role, patch.disabled);
    const [a] = await this.db.update(adminUsers).set({ ...patch, updatedAt: new Date() }).where(eq(adminUsers.id, id)).returning();
    return toAdminDto(a!);
  }

  async remove(actor: AdminRow, id: string): Promise<void> {
    const target = await this.get(id);
    if (!target) throw notFound('Administrator not found');
    if (target.id === actor.id) throw forbidden('You cannot delete your own account');
    await this.assertOwnerRemains(target, 'viewer', true);
    await this.db.delete(adminUsers).where(eq(adminUsers.id, id));
  }

  /** There must always be at least one active owner. */
  private async assertOwnerRemains(target: AdminRow, newRole?: AdminRole, disabling?: boolean) {
    if (target.role !== 'owner' || (newRole === undefined && !disabling) || newRole === 'owner') return;
    const [r] = await this.db
      .select({ n: sql<number>`count(*)::int` })
      .from(adminUsers)
      .where(and(eq(adminUsers.role, 'owner'), eq(adminUsers.disabled, false)));
    if ((r?.n ?? 0) <= 1) throw forbidden('At least one active owner is required');
  }

  async resetPassword(id: string, password: string): Promise<void> {
    const a = await this.get(id);
    if (!a) throw notFound('Administrator not found');
    validatePassword(password, a.email);
    await this.db
      .update(adminUsers)
      .set({ passwordHash: await hashPassword(password), passwordChangedAt: new Date(), failedLogins: 0, lockedUntil: null, updatedAt: new Date() })
      .where(eq(adminUsers.id, id));
  }

  /* ---------------------------------------------------------------- audit */

  async audit(req: Request | null, admin: { id: string; email: string } | null, action: string, target?: string, metadata: Record<string, unknown> = {}) {
    await this.db.insert(adminAuditLogs).values({
      adminId: admin?.id ?? null,
      adminEmail: admin?.email ?? null,
      action,
      target: target?.slice(0, 200) ?? null,
      ip: req?.ip?.slice(0, 64) ?? null,
      userAgent: req?.get('user-agent')?.slice(0, 400) ?? null,
      metadata,
    });
  }

  async auditLog(q: { source: 'admin' | 'users'; page: number; pageSize: number; search?: string }) {
    const offset = (q.page - 1) * q.pageSize;
    if (q.source === 'admin') {
      const where = q.search ? or(ilike(adminAuditLogs.action, `%${q.search}%`), ilike(adminAuditLogs.adminEmail, `%${q.search}%`), ilike(adminAuditLogs.target, `%${q.search}%`)) : undefined;
      const [c] = await this.db.select({ n: sql<number>`count(*)::int` }).from(adminAuditLogs).where(where);
      const rows = await this.db.select().from(adminAuditLogs).where(where).orderBy(desc(adminAuditLogs.createdAt)).limit(q.pageSize).offset(offset);
      return {
        total: c?.n ?? 0,
        items: rows.map((r) => ({ id: r.id, at: r.createdAt.toISOString(), actor: r.adminEmail, action: r.action, target: r.target, ip: r.ip, metadata: r.metadata })),
      };
    }
    const where = q.search ? or(ilike(auditLogs.action, `%${q.search}%`), ilike(users.email, `%${q.search}%`)) : undefined;
    const [c] = await this.db.select({ n: sql<number>`count(*)::int` }).from(auditLogs).leftJoin(users, eq(auditLogs.userId, users.id)).where(where);
    const rows = await this.db
      .select({ a: auditLogs, email: users.email })
      .from(auditLogs)
      .leftJoin(users, eq(auditLogs.userId, users.id))
      .where(where)
      .orderBy(desc(auditLogs.createdAt))
      .limit(q.pageSize)
      .offset(offset);
    return {
      total: c?.n ?? 0,
      items: rows.map((r) => ({ id: r.a.id, at: r.a.createdAt.toISOString(), actor: r.email ?? '(deleted user)', action: r.a.action, target: null, ip: r.a.ip, metadata: r.a.metadata })),
    };
  }

  /* ---------------------------------------------------------------- platform overview */

  async overview() {
    const n = (q: SQL) => sql<number>`(${q})::int`;
    const [stats] = await this.db
      .select({
        users: n(sql`SELECT count(*) FROM ${users}`),
        usersOnboarded: n(sql`SELECT count(*) FROM ${users} WHERE onboarded_at IS NOT NULL`),
        usersSuspended: n(sql`SELECT count(*) FROM ${users} WHERE suspended_at IS NOT NULL`),
        newUsers7d: n(sql`SELECT count(*) FROM ${users} WHERE created_at > now() - interval '7 days'`),
        newUsers30d: n(sql`SELECT count(*) FROM ${users} WHERE created_at > now() - interval '30 days'`),
        activeUsers7d: n(sql`SELECT count(*) FROM ${users} WHERE last_login_at > now() - interval '7 days'`),
        trades: n(sql`SELECT count(*) FROM ${trades} t JOIN ${tradingAccounts} a ON a.id = t.trading_account_id WHERE NOT a.sample_data`),
        trades24h: n(sql`SELECT count(*) FROM ${trades} t JOIN ${tradingAccounts} a ON a.id = t.trading_account_id WHERE NOT a.sample_data AND t.created_at > now() - interval '24 hours'`),
        journals: n(sql`SELECT count(*) FROM ${journalEntries} WHERE NOT demo`),
        brokerConnections: n(sql`SELECT count(*) FROM ${brokerConnections}`),
        aiMessages30d: n(sql`SELECT count(*) FROM ${aiMessages} WHERE role = 'assistant' AND created_at > now() - interval '30 days'`),
        activeSessions: n(sql`SELECT count(*) FROM ${userSessions} WHERE expire > now() AND sess->>'userId' IS NOT NULL`),
      })
      .from(sql`(SELECT 1) AS one`);
    const signups = await this.db.execute<{ day: string; signups: number; trades: number }>(sql`
      SELECT to_char(d, 'YYYY-MM-DD') AS day,
        (SELECT count(*) FROM ${users} WHERE created_at >= d AND created_at < d + interval '1 day')::int AS signups,
        (SELECT count(*) FROM ${trades} t JOIN ${tradingAccounts} a ON a.id = t.trading_account_id
           WHERE NOT a.sample_data AND t.created_at >= d AND t.created_at < d + interval '1 day')::int AS trades
      FROM generate_series(date_trunc('day', now() AT TIME ZONE 'UTC') - interval '29 days', date_trunc('day', now() AT TIME ZONE 'UTC'), interval '1 day') AS d`);
    return { stats: stats!, daily: signups.rows };
  }

  /* ---------------------------------------------------------------- trader user management */

  async listUsers(q: { search?: string; status?: 'active' | 'suspended' | 'all'; page: number; pageSize: number }) {
    const conds: SQL[] = [];
    if (q.search) conds.push(or(ilike(users.email, `%${q.search}%`), ilike(users.name, `%${q.search}%`))!);
    if (q.status === 'suspended') conds.push(isNotNull(users.suspendedAt));
    if (q.status === 'active') conds.push(isNull(users.suspendedAt));
    const where = conds.length ? and(...conds) : undefined;
    const [c] = await this.db.select({ n: sql<number>`count(*)::int` }).from(users).where(where);
    const rows = await this.db
      .select({
        u: users,
        trades: sql<number>`(SELECT count(*)::int FROM ${trades} t WHERE t.user_id = "users"."id")`,
        accounts: sql<number>`(SELECT count(*)::int FROM ${tradingAccounts} a WHERE a.user_id = "users"."id" AND NOT a.sample_data)`,
      })
      .from(users)
      .where(where)
      .orderBy(desc(users.createdAt))
      .limit(q.pageSize)
      .offset((q.page - 1) * q.pageSize);
    return {
      total: c?.n ?? 0,
      page: q.page,
      pageSize: q.pageSize,
      items: rows.map((r) => ({
        id: r.u.id,
        email: r.u.email,
        name: r.u.name,
        avatarUrl: r.u.avatarUrl,
        authProvider: r.u.authProvider,
        onboarded: !!r.u.onboardedAt,
        suspended: !!r.u.suspendedAt,
        createdAt: r.u.createdAt.toISOString(),
        lastLoginAt: r.u.lastLoginAt?.toISOString() ?? null,
        trades: r.trades,
        accounts: r.accounts,
      })),
    };
  }

  async userDetail(id: string) {
    const list = await this.db.select().from(users).where(eq(users.id, id)).limit(1);
    const u = list[0];
    if (!u) throw notFound('User not found');
    const [counts] = await this.db
      .select({
        trades: sql<number>`(SELECT count(*)::int FROM ${trades} WHERE user_id = ${id})`,
        journals: sql<number>`(SELECT count(*)::int FROM ${journalEntries} WHERE user_id = ${id} AND NOT demo)`,
        brokerConnections: sql<number>`(SELECT count(*)::int FROM ${brokerConnections} WHERE user_id = ${id})`,
        sessions: sql<number>`(SELECT count(*)::int FROM ${userSessions} WHERE expire > now() AND sess->>'userId' = ${id})`,
      })
      .from(sql`(SELECT 1) AS one`);
    const accounts = await this.db
      .select({ id: tradingAccounts.id, name: tradingAccounts.accountName, currency: tradingAccounts.currency, currentCapital: tradingAccounts.currentCapital, demo: tradingAccounts.demo, sampleData: tradingAccounts.sampleData })
      .from(tradingAccounts)
      .where(eq(tradingAccounts.userId, id));
    const events = await this.db.select().from(auditLogs).where(eq(auditLogs.userId, id)).orderBy(desc(auditLogs.createdAt)).limit(20);
    return {
      id: u.id,
      email: u.email,
      name: u.name,
      avatarUrl: u.avatarUrl,
      authProvider: u.authProvider,
      emailVerified: u.emailVerified,
      onboarded: !!u.onboardedAt,
      suspendedAt: u.suspendedAt?.toISOString() ?? null,
      createdAt: u.createdAt.toISOString(),
      lastLoginAt: u.lastLoginAt?.toISOString() ?? null,
      counts: counts!,
      accounts,
      events: events.map((e) => ({ at: e.createdAt.toISOString(), action: e.action, ip: e.ip })),
    };
  }

  /** Deletes every server-side session belonging to a trader. */
  async signOutUser(id: string): Promise<number> {
    const res = await this.db.delete(userSessions).where(sql`${userSessions.sess}->>'userId' = ${id}`).returning({ sid: userSessions.sid });
    return res.length;
  }

  async setSuspended(id: string, suspended: boolean): Promise<void> {
    const res = await this.db.update(users).set({ suspendedAt: suspended ? new Date() : null, updatedAt: new Date() }).where(eq(users.id, id)).returning({ id: users.id });
    if (res.length === 0) throw notFound('User not found');
    if (suspended) await this.signOutUser(id);
  }
}
