import { createDb, createPool } from '../src/db/client.js';
import { runMigrations } from '../src/db/migrations.js';
import { seedReferenceData } from '../src/db/referenceData.js';

export const TEST_DATABASE_URL =
  process.env.TEST_DATABASE_URL ?? 'postgres://journzey:journzey@localhost:5432/journzey_test';

/** Rebuilds the test database from migrations before the suite runs. */
export default async function setup() {
  const pool = createPool(TEST_DATABASE_URL);
  try {
    await pool.query('DROP SCHEMA IF EXISTS public CASCADE; DROP SCHEMA IF EXISTS drizzle CASCADE; CREATE SCHEMA public;');
    const db = createDb(pool);
    await runMigrations(db);
    await db.transaction((tx) => seedReferenceData(tx));
  } finally {
    await pool.end();
  }
}
