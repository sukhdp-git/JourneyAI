/**
 * Control-panel administrator CLI.
 *   npm run admin:create -- --email you@example.com --name "Your Name" [--role owner|admin|viewer]
 *   npm run admin:reset-password -- --email you@example.com
 * The password is read from ADMIN_PASSWORD or prompted (input hidden). It is never echoed or logged.
 */
import { createInterface } from 'node:readline';
import { Writable } from 'node:stream';
import { sql } from 'drizzle-orm';
import { createDb, createPool } from '../db/client.js';
import { adminUsers } from '../db/schema.js';
import { AdminService, type AdminRole } from '../services/admin.js';
import { SecretBox } from '../lib/crypto.js';
import { createLogger } from '../lib/logger.js';

const args = process.argv.slice(2);
const arg = (name: string) => {
  const i = args.indexOf(`--${name}`);
  return i >= 0 ? args[i + 1] : undefined;
};
const command = args[0];

async function promptHidden(question: string): Promise<string> {
  let muted = false;
  const out = new Writable({ write: (chunk, _enc, cb) => (muted ? cb() : process.stdout.write(chunk, cb)) });
  const rl = createInterface({ input: process.stdin, output: out, terminal: true });
  return new Promise((resolve) => {
    rl.question(question, (answer) => {
      rl.close();
      process.stdout.write('\n');
      resolve(answer);
    });
    muted = true;
  });
}

async function main() {
  const url = process.env.DATABASE_URL;
  const key = process.env.ENCRYPTION_KEY;
  if (!url || !key) throw new Error('DATABASE_URL and ENCRYPTION_KEY are required');
  const email = arg('email');
  if (!email || !['create', 'reset-password'].includes(command ?? '')) {
    console.error('Usage: admin:create -- --email <email> --name <name> [--role owner|admin|viewer]\n       admin:reset-password -- --email <email>');
    process.exit(2);
  }
  const password = process.env.ADMIN_PASSWORD ?? (await promptHidden('New password (min 12 chars, upper/lower/digit): '));
  const pool = createPool(url, process.env.DATABASE_SSL === 'true');
  try {
    const svc = new AdminService(createDb(pool), new SecretBox(key), createLogger('silent'));
    if (command === 'create') {
      const role = (arg('role') ?? ((await svc.count()) === 0 ? 'owner' : 'admin')) as AdminRole;
      const a = await svc.create({ email, name: arg('name') ?? email.split('@')[0]!, password, role });
      console.warn(`✔ created ${a.role} ${a.email} — sign in at <APP_URL>/control-panel/login`);
    } else {
      const db = createDb(pool);
      const [a] = await db.select().from(adminUsers).where(sql`lower(${adminUsers.email}) = ${email.toLowerCase()}`).limit(1);
      if (!a) throw new Error('No administrator with that email');
      await svc.resetPassword(a.id, password);
      console.warn(`✔ password reset for ${a.email}`);
    }
  } finally {
    await pool.end();
  }
}

main().catch((err) => {
  const fields = (err as { fields?: Record<string, string[]> }).fields;
  console.error(`✖ ${(err as Error).message}${fields ? `: ${Object.values(fields).flat().join('; ')}` : ''}`);
  process.exit(1);
});
