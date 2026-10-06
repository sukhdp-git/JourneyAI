import { createDb, createPool } from './client.js';
import { runMigrations } from './migrations.js';

const url = process.env.DATABASE_URL;
if (!url) {
  console.error('DATABASE_URL is required');
  process.exit(1);
}
const pool = createPool(url, process.env.DATABASE_SSL === 'true');
try {
  await runMigrations(createDb(pool));
  console.warn('✔ migrations applied');
} catch (err) {
  console.error('✖ migration failed', err);
  process.exitCode = 1;
} finally {
  await pool.end();
}
