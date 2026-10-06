import pg from 'pg';
import { drizzle, type NodePgDatabase } from 'drizzle-orm/node-postgres';
import * as schema from './schema.js';

export type Database = NodePgDatabase<typeof schema>;
/** Transaction handle type, usable wherever a Database is expected for queries. */
export type Tx = Parameters<Parameters<Database['transaction']>[0]>[0];
export type DbOrTx = Database | Tx;

export function createPool(databaseUrl: string, ssl = false): pg.Pool {
  return new pg.Pool({
    connectionString: databaseUrl,
    max: 10,
    idleTimeoutMillis: 30_000,
    ssl: ssl ? { rejectUnauthorized: true } : undefined,
  });
}

export function createDb(pool: pg.Pool): Database {
  return drizzle(pool, { schema });
}

export { schema };
