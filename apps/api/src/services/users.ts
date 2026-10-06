import { and, asc, eq, sql } from 'drizzle-orm';
import type { MeResponse, OnboardingInput, SettingsDto, Theme, Language, UserDto } from '@journzey/shared';
import { onboardingSchema, STRATEGY_TEMPLATES } from '@journzey/shared';
import type { Database, DbOrTx } from '../db/client.js';
import {
  brokerConnections,
  capitalTransactions,
  checklistEntries,
  journalEntries,
  strategies,
  strategyTemplates,
  tradingAccounts,
  trades,
  userSettings,
  users,
} from '../db/schema.js';
import { conflict, notFound } from '../lib/errors.js';
import type { VerifiedIdentity } from '../auth/google.js';
import { DemoService } from './demo.js';

type SettingsRow = typeof userSettings.$inferSelect;
type UserRow = typeof users.$inferSelect;

export const toUserDto = (u: UserRow): UserDto => ({
  id: u.id,
  email: u.email,
  name: u.name,
  avatarUrl: u.avatarUrl,
  emailVerified: u.emailVerified,
  onboarded: Boolean(u.onboardedAt),
  createdAt: u.createdAt.toISOString(),
  lastLoginAt: u.lastLoginAt?.toISOString() ?? null,
});

export const toSettingsDto = (s: SettingsRow): SettingsDto => ({
  theme: s.theme as Theme,
  language: s.language as Language,
  timezone: s.timezone,
  baseCurrency: s.baseCurrency,
  defaultRiskPercentage: s.defaultRiskPercentage,
  maxDailyLoss: s.maxDailyLoss,
  maxWeeklyLoss: s.maxWeeklyLoss,
  defaultTargetRr: s.defaultTargetRr,
  primaryMarkets: s.primaryMarkets,
  activeAccountScope: s.activeAccountScope,
  tiltLossCount: s.tiltLossCount,
  tiltWindowMinutes: s.tiltWindowMinutes,
  tiltCooldownMinutes: s.tiltCooldownMinutes,
  terminalLockUntil: s.terminalLockUntil?.toISOString() ?? null,
});

/** Copies the playbook templates into the user's own strategies (idempotent by name). */
export async function copyStrategyTemplates(db: DbOrTx, userId: string): Promise<void> {
  const templates = await db.select().from(strategyTemplates).orderBy(asc(strategyTemplates.sortOrder));
  const source = templates.length
    ? templates.map((t) => ({ name: t.name, description: t.description, targetRr: t.targetRr, checklist: t.checklist }))
    : STRATEGY_TEMPLATES.map((t) => ({ name: t.name, description: t.description, targetRr: t.targetRr, checklist: [...t.checklist] }));
  for (const t of source) {
    await db
      .insert(strategies)
      .values({ userId, ...t })
      .onConflictDoNothing();
  }
}

export class UserService {
  constructor(private readonly db: Database) {}

  /**
   * Finds or creates the user for a verified Google identity. Matching is by the stable
   * Google subject id. An existing account with the same email but a different subject is
   * refused rather than silently merged (prevents account takeover via email reuse).
   */
  async upsertFromGoogle(raw: VerifiedIdentity): Promise<{ user: UserRow; created: boolean }> {
    const identity = { ...raw, email: raw.email.trim().toLowerCase() };
    return this.db.transaction(async (tx) => {
      const [bySubject] = await tx.select().from(users).where(eq(users.googleSubjectId, identity.subject)).limit(1);
      if (bySubject) {
        const [updated] = await tx
          .update(users)
          .set({
            // Name/avatar refresh from Google; email only changes when Google asserts it verified.
            name: bySubject.name ?? identity.name,
            avatarUrl: identity.picture ?? bySubject.avatarUrl,
            ...(identity.emailVerified ? { email: identity.email, emailVerified: true } : {}),
            lastLoginAt: new Date(),
            updatedAt: new Date(),
          })
          .where(eq(users.id, bySubject.id))
          .returning();
        return { user: updated!, created: false };
      }
      const [byEmail] = await tx
        .select()
        .from(users)
        .where(sql`lower(${users.email}) = ${identity.email.toLowerCase()}`)
        .limit(1);
      if (byEmail) throw conflict('An account with this email already exists under a different sign-in identity');
      const [user] = await tx
        .insert(users)
        .values({
          email: identity.email,
          name: identity.name,
          avatarUrl: identity.picture,
          googleSubjectId: identity.subject,
          authProvider: 'google',
          emailVerified: identity.emailVerified,
          lastLoginAt: new Date(),
        })
        .returning();
      await tx.insert(userSettings).values({ userId: user!.id }).onConflictDoNothing();
      return { user: user!, created: true };
    });
  }

  /** Local development-only identity (DEV_AUTH_BYPASS). Never available in production. */
  async upsertDevUser(email: string, name: string): Promise<{ user: UserRow; created: boolean }> {
    return this.db.transaction(async (tx) => {
      const [existing] = await tx.select().from(users).where(sql`lower(${users.email}) = ${email.toLowerCase()}`).limit(1);
      if (existing) {
        if (existing.authProvider !== 'dev') throw conflict('This email belongs to a Google account');
        const [u] = await tx.update(users).set({ lastLoginAt: new Date() }).where(eq(users.id, existing.id)).returning();
        return { user: u!, created: false };
      }
      const [user] = await tx
        .insert(users)
        .values({ email: email.toLowerCase(), name, authProvider: 'dev', emailVerified: false, lastLoginAt: new Date() })
        .returning();
      await tx.insert(userSettings).values({ userId: user!.id }).onConflictDoNothing();
      return { user: user!, created: true };
    });
  }

  async me(userId: string, features: MeResponse['features']): Promise<MeResponse> {
    const [u] = await this.db.select().from(users).where(eq(users.id, userId)).limit(1);
    if (!u) throw notFound('User not found');
    const [s] = await this.db.select().from(userSettings).where(eq(userSettings.userId, userId)).limit(1);
    return { user: toUserDto(u), settings: s ? toSettingsDto(s) : null, features };
  }

  async updateProfile(userId: string, name: string): Promise<UserDto> {
    const [u] = await this.db.update(users).set({ name, updatedAt: new Date() }).where(eq(users.id, userId)).returning();
    if (!u) throw notFound('User not found');
    return toUserDto(u);
  }

  async getSettings(userId: string): Promise<SettingsDto> {
    const [s] = await this.db
      .insert(userSettings)
      .values({ userId })
      .onConflictDoUpdate({ target: userSettings.userId, set: { userId } })
      .returning();
    return toSettingsDto(s!);
  }

  async updateSettings(userId: string, patch: Partial<typeof userSettings.$inferInsert>): Promise<SettingsDto> {
    if (patch.activeAccountScope && !['real', 'demo', 'all'].includes(patch.activeAccountScope)) {
      const [acc] = await this.db
        .select({ id: tradingAccounts.id })
        .from(tradingAccounts)
        .where(and(eq(tradingAccounts.id, patch.activeAccountScope), eq(tradingAccounts.userId, userId)))
        .limit(1);
      if (!acc) throw notFound('Trading account not found');
    }
    await this.getSettings(userId);
    const [s] = await this.db
      .update(userSettings)
      .set({ ...patch, updatedAt: new Date() })
      .where(eq(userSettings.userId, userId))
      .returning();
    return toSettingsDto(s!);
  }

  async lockTerminal(userId: string, minutes: number): Promise<SettingsDto> {
    const [current] = await this.db.select().from(userSettings).where(eq(userSettings.userId, userId)).limit(1);
    const until = new Date(Date.now() + minutes * 60_000);
    // A lock can be extended but never shortened before it expires.
    const effective = current?.terminalLockUntil && current.terminalLockUntil > until ? current.terminalLockUntil : until;
    return this.updateSettings(userId, { terminalLockUntil: effective });
  }

  /** Onboarding is atomic: settings, first account, playbooks and optional demo data. */
  async completeOnboarding(userId: string, raw: OnboardingInput) {
    const input = onboardingSchema.parse(raw);
    return this.db.transaction(async (tx) => {
      await tx.insert(userSettings).values({ userId }).onConflictDoNothing();
      await tx
        .update(userSettings)
        .set({
          primaryMarkets: input.primaryMarkets,
          timezone: input.timezone,
          baseCurrency: input.currency,
          defaultRiskPercentage: input.defaultRiskPercentage,
          maxDailyLoss: input.maxDailyLoss ?? null,
          maxWeeklyLoss: input.maxWeeklyLoss ?? null,
          defaultTargetRr: input.defaultTargetRr,
          activeAccountScope: input.demo ? 'demo' : 'real',
          updatedAt: new Date(),
        })
        .where(eq(userSettings.userId, userId));
      const [account] = await tx
        .insert(tradingAccounts)
        .values({
          userId,
          accountName: input.accountName,
          currency: input.currency,
          startingCapital: input.startingCapital,
          currentCapital: input.startingCapital,
          demo: input.demo,
        })
        .returning();
      await copyStrategyTemplates(tx, userId);
      if (input.loadDemoData) {
        await new DemoService(this.db).createDemoData(tx, userId);
        await tx.update(userSettings).set({ activeAccountScope: 'demo' }).where(eq(userSettings.userId, userId));
      }
      await tx.update(users).set({ onboardedAt: new Date(), updatedAt: new Date() }).where(eq(users.id, userId));
      return { accountId: account!.id };
    });
  }

  /** Portable export of everything the user owns (JSON). Secrets are never included. */
  async exportData(userId: string) {
    const [u] = await this.db.select().from(users).where(eq(users.id, userId)).limit(1);
    if (!u) throw notFound('User not found');
    const [settings, accounts, tradeRows, strategyRows, journals, flows, checklist, connections] = await Promise.all([
      this.db.select().from(userSettings).where(eq(userSettings.userId, userId)),
      this.db.select().from(tradingAccounts).where(eq(tradingAccounts.userId, userId)),
      this.db.select().from(trades).where(eq(trades.userId, userId)).orderBy(asc(trades.executedAt)),
      this.db.select().from(strategies).where(eq(strategies.userId, userId)),
      this.db.select().from(journalEntries).where(eq(journalEntries.userId, userId)).orderBy(asc(journalEntries.journalDate)),
      this.db.select().from(capitalTransactions).where(eq(capitalTransactions.userId, userId)),
      this.db.select().from(checklistEntries).where(eq(checklistEntries.userId, userId)),
      this.db
        .select({ id: brokerConnections.id, provider: brokerConnections.provider, label: brokerConnections.label, status: brokerConnections.status, createdAt: brokerConnections.createdAt })
        .from(brokerConnections)
        .where(eq(brokerConnections.userId, userId)),
    ]);
    return {
      exportedAt: new Date().toISOString(),
      format: 'journzey.ai/export@1',
      profile: toUserDto(u),
      settings: settings[0] ? toSettingsDto(settings[0]) : null,
      tradingAccounts: accounts.map(({ userId: _u, ...a }) => a),
      trades: tradeRows.map(({ userId: _u, ...t }) => t),
      strategies: strategyRows.map(({ userId: _u, ...s }) => s),
      journalEntries: journals.map(({ userId: _u, ...j }) => j),
      capitalTransactions: flows.map(({ userId: _u, ...f }) => f),
      checklistEntries: checklist.map(({ userId: _u, ...c }) => c),
      brokerConnections: connections,
    };
  }

  /** Returns every stored screenshot key so the caller can purge object storage after deletion. */
  async deleteAccount(userId: string): Promise<{ screenshotKeys: string[] }> {
    return this.db.transaction(async (tx) => {
      const shots = await tx
        .select({ key: trades.screenshotUrl })
        .from(trades)
        .where(and(eq(trades.userId, userId), sql`${trades.screenshotUrl} IS NOT NULL`));
      // ON DELETE CASCADE removes settings, accounts, trades, journals, strategies, checklist,
      // capital flows, broker connections (+ encrypted secrets), webhook events, AI history and snapshots.
      // audit_logs.user_id is SET NULL so the security trail survives without personal linkage.
      const deleted = await tx.delete(users).where(eq(users.id, userId)).returning({ id: users.id });
      if (deleted.length === 0) throw notFound('User not found');
      return { screenshotKeys: shots.map((s) => s.key!).filter(Boolean) };
    });
  }
}
