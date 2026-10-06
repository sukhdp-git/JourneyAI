// Builds database/schema.sql from the ordered migration files (the migrations are the source of truth).
import { readFileSync, writeFileSync } from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const dir = path.join(root, 'database/migrations');
const journal = JSON.parse(readFileSync(path.join(dir, 'meta/_journal.json'), 'utf8'));
const parts = journal.entries.map((e) => {
  const sql = readFileSync(path.join(dir, `${e.tag}.sql`), 'utf8').replace(/--> statement-breakpoint\n?/g, '');
  return `-- ── migration ${e.tag} ─────────────────────────────────────────────\n${sql.trim()}\n`;
});
const header = `-- journzey.ai — PostgreSQL schema (PostgreSQL 13+; gen_random_uuid() is built in)
-- GENERATED from database/migrations by \`npm run db:schema-sql\`. Do not edit by hand.
--
-- Conventions:
--   * Money and prices use NUMERIC (never floating point); timestamps are TIMESTAMPTZ in UTC.
--   * Every user-owned table has user_id REFERENCES users(id) ON DELETE CASCADE.
--   * audit_logs.user_id is ON DELETE SET NULL so the security trail survives account deletion.
--
-- Prefer \`npm run db:migrate\` (tracks applied migrations). This file is for review, DBA tooling,
-- or bootstrapping an empty database with: psql "$DATABASE_URL" -f database/schema.sql

BEGIN;
`;
writeFileSync(path.join(root, 'database/schema.sql'), `${header}\n${parts.join('\n')}\nCOMMIT;\n`);
console.log('wrote database/schema.sql');
