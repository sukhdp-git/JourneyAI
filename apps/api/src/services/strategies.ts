import { and, asc, eq } from 'drizzle-orm';
import type { StrategyDto } from '@journzey/shared';
import type { Database } from '../db/client.js';
import { strategies } from '../db/schema.js';
import { conflict, notFound } from '../lib/errors.js';

type Row = typeof strategies.$inferSelect;
const toDto = (s: Row): StrategyDto => ({
  id: s.id,
  name: s.name,
  description: s.description,
  targetRr: s.targetRr,
  checklist: s.checklist,
  active: s.active,
  createdAt: s.createdAt.toISOString(),
});

const isUniqueViolation = (e: unknown) => typeof e === 'object' && e !== null && 'code' in e && (e as { code: string }).code === '23505';
const uniqueCause = (e: unknown) => isUniqueViolation(e) || isUniqueViolation((e as { cause?: unknown })?.cause);

export class StrategyService {
  constructor(private readonly db: Database) {}

  async list(userId: string): Promise<StrategyDto[]> {
    const rows = await this.db.select().from(strategies).where(eq(strategies.userId, userId)).orderBy(asc(strategies.name));
    return rows.map(toDto);
  }

  async create(userId: string, input: { name: string; description?: string | null; targetRr?: string | null; checklist: string[]; active: boolean }) {
    try {
      const [s] = await this.db
        .insert(strategies)
        .values({ userId, ...input, description: input.description ?? null, targetRr: input.targetRr ?? null })
        .returning();
      return toDto(s!);
    } catch (e) {
      if (uniqueCause(e)) throw conflict('A strategy with this name already exists');
      throw e;
    }
  }

  async update(userId: string, id: string, patch: Partial<{ name: string; description: string | null; targetRr: string | null; checklist: string[]; active: boolean }>) {
    try {
      const [s] = await this.db
        .update(strategies)
        .set({ ...patch, updatedAt: new Date() })
        .where(and(eq(strategies.id, id), eq(strategies.userId, userId)))
        .returning();
      if (!s) throw notFound('Strategy not found');
      return toDto(s);
    } catch (e) {
      if (uniqueCause(e)) throw conflict('A strategy with this name already exists');
      throw e;
    }
  }

  async delete(userId: string, id: string): Promise<void> {
    const rows = await this.db
      .delete(strategies)
      .where(and(eq(strategies.id, id), eq(strategies.userId, userId)))
      .returning({ id: strategies.id });
    if (rows.length === 0) throw notFound('Strategy not found');
  }
}
