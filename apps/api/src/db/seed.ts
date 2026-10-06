import { createDb, createPool } from './client.js';
import { seedReferenceData } from './referenceData.js';

/**
 * ORM seed: reference data only (strategy templates + instruments).
 * Demo trades/journals are never attached to fabricated users — they are created
 * per real user through onboarding ("Load demo data") or Settings → Reset Demo Data.
 */
const url = process.env.DATABASE_URL;
if (!url) {
  console.error('DATABASE_URL is required');
  process.exit(1);
}
const pool = createPool(url, process.env.DATABASE_SSL === 'true');
try {
  const db = createDb(pool);
  const counts = await db.transaction((tx) => seedReferenceData(tx));
  console.warn(`✔ seeded ${counts.templates} strategy templates and ${counts.instruments} instruments`);
} catch (err) {
  console.error('✖ seed failed', err);
  process.exitCode = 1;
} finally {
  await pool.end();
}
