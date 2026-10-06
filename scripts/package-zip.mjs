// Packages the source tree into journzey-ai-production.zip (no dependencies).
// Includes every file git tracks or would track (respects .gitignore), so node_modules, dist,
// .env, uploads, caches and test artefacts are excluded automatically.
import { execFileSync } from 'node:child_process';
import { readFileSync, statSync, writeFileSync } from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { deflateRawSync, crc32 } from 'node:zlib';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const out = path.join(root, process.argv[2] ?? 'journzey-ai-production.zip');
const files = execFileSync('git', ['ls-files', '--cached', '--others', '--exclude-standard', '-z'], { cwd: root })
  .toString('utf8')
  .split('\0')
  .filter(Boolean)
  .filter((f) => !f.endsWith('.zip'))
  .sort();

const FORBIDDEN = [/(^|\/)\.env$/, /(^|\/)node_modules\//, /(^|\/)dist\//, /(^|\/)\.git\//];
for (const f of files) if (FORBIDDEN.some((re) => re.test(f))) throw new Error(`refusing to package ${f}`);
for (const required of ['database/schema.sql', 'database/seed.sql', 'database/migrations/meta/_journal.json', '.env.example', 'docker-compose.yml', 'README.md', 'DEPLOYMENT.md', 'package-lock.json']) {
  if (!files.includes(required)) throw new Error(`missing required file ${required}`);
}

const dosTime = (d) => ((d.getHours() << 11) | (d.getMinutes() << 5) | Math.floor(d.getSeconds() / 2)) & 0xffff;
const dosDate = (d) => (((d.getFullYear() - 1980) << 9) | ((d.getMonth() + 1) << 5) | d.getDate()) & 0xffff;
const locals = [];
const centrals = [];
let offset = 0;
for (const rel of files) {
  const name = Buffer.from(`journzey-ai/${rel}`, 'utf8');
  const data = readFileSync(path.join(root, rel));
  const mtime = statSync(path.join(root, rel)).mtime;
  const comp = deflateRawSync(data, { level: 9 });
  const crc = crc32(data);
  const lh = Buffer.alloc(30);
  lh.writeUInt32LE(0x04034b50, 0);
  lh.writeUInt16LE(20, 4);
  lh.writeUInt16LE(0x0800, 6); // UTF-8 names
  lh.writeUInt16LE(8, 8);
  lh.writeUInt16LE(dosTime(mtime), 10);
  lh.writeUInt16LE(dosDate(mtime), 12);
  lh.writeUInt32LE(crc, 14);
  lh.writeUInt32LE(comp.length, 18);
  lh.writeUInt32LE(data.length, 22);
  lh.writeUInt16LE(name.length, 26);
  locals.push(lh, name, comp);
  const ch = Buffer.alloc(46);
  ch.writeUInt32LE(0x02014b50, 0);
  ch.writeUInt16LE(0x0314, 4);
  ch.writeUInt16LE(20, 6);
  ch.writeUInt16LE(0x0800, 8);
  ch.writeUInt16LE(8, 10);
  ch.writeUInt16LE(dosTime(mtime), 12);
  ch.writeUInt16LE(dosDate(mtime), 14);
  ch.writeUInt32LE(crc, 16);
  ch.writeUInt32LE(comp.length, 20);
  ch.writeUInt32LE(data.length, 24);
  ch.writeUInt16LE(name.length, 28);
  ch.writeUInt32LE((0o100644 << 16) >>> 0, 38);
  ch.writeUInt32LE(offset, 42);
  centrals.push(ch, name);
  offset += lh.length + name.length + comp.length;
}
const cd = Buffer.concat(centrals);
const end = Buffer.alloc(22);
end.writeUInt32LE(0x06054b50, 0);
end.writeUInt16LE(files.length, 8);
end.writeUInt16LE(files.length, 10);
end.writeUInt32LE(cd.length, 12);
end.writeUInt32LE(offset, 16);
writeFileSync(out, Buffer.concat([...locals, cd, end]));
console.log(`wrote ${path.relative(root, out)} — ${files.length} files, ${(statSync(out).size / 1024).toFixed(0)} KB`);
