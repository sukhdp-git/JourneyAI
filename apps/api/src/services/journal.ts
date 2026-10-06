import { and, desc, eq, sql } from 'drizzle-orm';
import { CHECKLIST_ITEMS, type JournalEntryDto, type Language, type Emotion } from '@journzey/shared';
import type { Database } from '../db/client.js';
import { checklistEntries, journalEntries } from '../db/schema.js';
import { notFound } from '../lib/errors.js';

type Row = typeof journalEntries.$inferSelect;
const toDto = (j: Row): JournalEntryDto => ({
  id: j.id,
  journalDate: j.journalDate,
  compliance: j.compliance,
  emotionalState: j.emotionalState as Emotion | null,
  disciplineRating: j.disciplineRating,
  reflection: j.reflection,
  keyLesson: j.keyLesson,
  voiceTranscript: j.voiceTranscript,
  voiceLanguage: j.voiceLanguage as Language | null,
  demo: j.demo,
  createdAt: j.createdAt.toISOString(),
  updatedAt: j.updatedAt.toISOString(),
});

type JournalFields = {
  compliance?: number | null;
  emotionalState?: string | null;
  disciplineRating?: number | null;
  reflection?: string | null;
  keyLesson?: string | null;
  voiceTranscript?: string | null;
  voiceLanguage?: string | null;
};

export class JournalService {
  constructor(private readonly db: Database) {}

  async list(userId: string, q: { from?: string; to?: string; demo: boolean; limit: number }): Promise<JournalEntryDto[]> {
    const conds = [eq(journalEntries.userId, userId), eq(journalEntries.demo, q.demo)];
    if (q.from) conds.push(sql`${journalEntries.journalDate} >= ${q.from}::date`);
    if (q.to) conds.push(sql`${journalEntries.journalDate} <= ${q.to}::date`);
    const rows = await this.db
      .select()
      .from(journalEntries)
      .where(and(...conds))
      .orderBy(desc(journalEntries.journalDate))
      .limit(q.limit);
    return rows.map(toDto);
  }

  /** Create-or-update the (real) entry for a given date. Demo entries are read-only. */
  async upsert(userId: string, journalDate: string, fields: JournalFields): Promise<JournalEntryDto> {
    const values = { userId, journalDate, demo: false, ...fields };
    const [row] = await this.db
      .insert(journalEntries)
      .values(values)
      .onConflictDoUpdate({
        target: [journalEntries.userId, journalEntries.journalDate, journalEntries.demo],
        set: { ...fields, updatedAt: new Date() },
      })
      .returning();
    return toDto(row!);
  }

  async update(userId: string, id: string, fields: JournalFields): Promise<JournalEntryDto> {
    const [row] = await this.db
      .update(journalEntries)
      .set({ ...fields, updatedAt: new Date() })
      .where(and(eq(journalEntries.id, id), eq(journalEntries.userId, userId)))
      .returning();
    if (!row) throw notFound('Journal entry not found');
    return toDto(row);
  }

  async delete(userId: string, id: string): Promise<void> {
    const rows = await this.db
      .delete(journalEntries)
      .where(and(eq(journalEntries.id, id), eq(journalEntries.userId, userId)))
      .returning({ id: journalEntries.id });
    if (rows.length === 0) throw notFound('Journal entry not found');
  }

  async checklist(userId: string, date: string) {
    const rows = await this.db
      .select()
      .from(checklistEntries)
      .where(and(eq(checklistEntries.userId, userId), eq(checklistEntries.entryDate, date)));
    const done = new Map(rows.map((r) => [r.itemKey, r]));
    return {
      date,
      items: CHECKLIST_ITEMS.map((i) => ({
        key: i.key,
        phase: i.phase,
        completed: done.get(i.key)?.completed ?? false,
        completedAt: done.get(i.key)?.completedAt?.toISOString() ?? null,
      })),
    };
  }

  async setChecklist(userId: string, date: string, itemKey: string, completed: boolean) {
    await this.db
      .insert(checklistEntries)
      .values({ userId, entryDate: date, itemKey, completed, completedAt: completed ? new Date() : null })
      .onConflictDoUpdate({
        target: [checklistEntries.userId, checklistEntries.entryDate, checklistEntries.itemKey],
        set: { completed, completedAt: completed ? new Date() : null, updatedAt: new Date() },
      });
    return this.checklist(userId, date);
  }
}
