import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { existsSync } from 'node:fs';
import { migrate } from 'drizzle-orm/node-postgres/migrator';
import type { Database } from './client.js';

/** Locate database/migrations both from source (tsx) and from the compiled dist or Docker image. */
export function migrationsFolder(): string {
  if (process.env.MIGRATIONS_DIR) return path.resolve(process.env.MIGRATIONS_DIR);
  const here = path.dirname(fileURLToPath(import.meta.url));
  const candidates = [
    path.resolve(here, '../../../../database/migrations'),
    path.resolve(here, '../../../database/migrations'),
    path.resolve(process.cwd(), 'database/migrations'),
    path.resolve(process.cwd(), '../../database/migrations'),
  ];
  const found = candidates.find((c) => existsSync(path.join(c, 'meta', '_journal.json')));
  if (!found) throw new Error(`Could not locate database/migrations (looked in: ${candidates.join(', ')})`);
  return found;
}

export async function runMigrations(db: Database): Promise<void> {
  await migrate(db, { migrationsFolder: migrationsFolder() });
}
