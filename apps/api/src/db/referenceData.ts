import { sql } from 'drizzle-orm';
import { INSTRUMENTS, STRATEGY_TEMPLATES } from '@journzey/shared';
import type { DbOrTx } from './client.js';
import { instruments, strategyTemplates } from './schema.js';

/** Idempotently upsert reference data (strategy templates + supported instruments). */
export async function seedReferenceData(db: DbOrTx): Promise<{ templates: number; instruments: number }> {
  for (const [i, t] of STRATEGY_TEMPLATES.entries()) {
    await db
      .insert(strategyTemplates)
      .values({ name: t.name, description: t.description, targetRr: t.targetRr, checklist: [...t.checklist], sortOrder: i })
      .onConflictDoUpdate({
        target: strategyTemplates.name,
        set: { description: t.description, targetRr: t.targetRr, checklist: [...t.checklist], sortOrder: i },
      });
  }
  for (const inst of INSTRUMENTS) {
    const row = {
      symbol: inst.symbol,
      displayName: inst.displayName,
      assetClass: inst.assetClass,
      baseCurrency: inst.baseCurrency,
      quoteCurrency: inst.quoteCurrency,
      contractSize: inst.contractSize,
      tickSize: inst.tickSize,
      tickValue: inst.tickValue,
      pipSize: inst.pipSize,
      decimals: inst.decimals,
      aliases: [...inst.aliases],
    };
    await db
      .insert(instruments)
      .values(row)
      .onConflictDoUpdate({ target: instruments.symbol, set: { ...row, symbol: sql`excluded.symbol` } });
  }
  return { templates: STRATEGY_TEMPLATES.length, instruments: INSTRUMENTS.length };
}
