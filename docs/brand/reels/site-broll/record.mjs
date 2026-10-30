// Records short real-time clips of journzey terminal animations at phone size (CDP screencast, 3× density).
import { chromium } from '@playwright/test';
import fs from 'fs';
const S = process.env.S, B = 'http://localhost:8080', OUT = S + '/broll/raw';
const only = process.argv[2];
const b = await chromium.launch({ executablePath: '/opt/pw-browsers/chromium' });
const c = await b.newContext({ viewport: { width: 390, height: 693 }, deviceScaleFactor: 3, storageState: S + '/mind-state.json' });
await c.addInitScript(() => document.addEventListener('DOMContentLoaded', () => { const st = document.createElement('style'); st.textContent = '.tm-tabs{display:none!important}'; document.head.appendChild(st); }));
await c.route(/tradingview|google|gstatic|razorpay|stripe/, r => r.abort());
const p = await c.newPage(); const cdp = await c.newCDPSession(p);
const sleep = ms => new Promise(r => setTimeout(r, ms));
const center = (sel, inst = true) => p.evaluate(([s, i]) => { const e = typeof s === 'string' && s.startsWith('text=') ? [...document.querySelectorAll('h2,h3,.panel-head,b,strong')].find(x => x.textContent.includes(s.slice(5)))?.closest('section,.panel,.ex-card') : document.querySelector(s); if (!e) return false; e.scrollIntoView({ block: 'center', behavior: i ? 'instant' : 'smooth' }); return true; }, [sel, inst]);
async function rec(name, prep, act, dur = 1800) {
  if (only && !name.startsWith(only)) return;
  const dir = `${OUT}/${name}`; fs.rmSync(dir, { recursive: true, force: true }); fs.mkdirSync(dir, { recursive: true });
  await prep(); await sleep(400);
  const frames = []; let t0 = null;
  const on = async (f) => { const ts = f.metadata.timestamp; if (t0 === null) t0 = ts; frames.push([ts, f.data]); cdp.send('Page.screencastFrameAck', { sessionId: f.sessionId }).catch(() => {}); };
  cdp.on('Page.screencastFrame', on);
  await cdp.send('Page.startScreencast', { format: 'jpeg', quality: 92, maxWidth: 1170, maxHeight: 2079, everyNthFrame: 1 });
  const start = Date.now(); await sleep(60);
  await act(); const left = dur - (Date.now() - start); if (left > 0) await sleep(left);
  await cdp.send('Page.stopScreencast'); cdp.off('Page.screencastFrame', on);
  const times = frames.map(([ts, d], i) => { fs.writeFileSync(`${dir}/${String(i).padStart(4, '0')}.jpg`, Buffer.from(d, 'base64')); return +(ts - t0).toFixed(4); });
  fs.writeFileSync(`${dir}/times.json`, JSON.stringify(times));
  console.log(name, frames.length, 'frames', times.at(-1));
}
const go = async (u) => { await p.goto(B + u); await p.waitForLoadState('networkidle'); await sleep(600); };
// ---- video 1: the problem
await rec('v1a-deathclock', async () => { await go('/terminal/mind'); await center('[data-death-clock]'); }, async () => {}, 2200);
await rec('v1b-radar', async () => { await go('/terminal/edge'); await p.evaluate(() => scrollTo(0, 0)); }, async () => { await center('text=Blow-up probability radar'); });
await rec('v1c-boss', async () => { await go('/terminal/mind'); await p.evaluate(() => scrollTo(0, 0)); }, async () => { await center('.mind-boss'); });
await rec('v1d-roast', async () => { await p.goto('about:blank'); }, async () => { await p.goto(B + '/terminal/mind'); }, 2400);
// ---- video 2: the fix
await rec('v2a-quicklog', async () => { await go('/terminal'); await center('.ql-step'); }, async () => {
  await sleep(250); await p.locator('.ql-chip').first().click(); await sleep(450); const s = p.locator('[data-ql-side]').first(); if (await s.count()) await s.click(); });
await rec('v2b-equity', async () => { await go('/terminal/dashboard'); await p.evaluate(() => scrollTo(0, document.body.scrollHeight)); await sleep(300); await p.evaluate(() => scrollTo(0, 0)); }, async () => { await center('.dash-equity'); }, 2400);
await rec('v2c-like', async () => { await go('/terminal/confessions'); await center('[data-cf]:not(:first-child) [data-cf-like]'); }, async () => {
  await sleep(300); const l = p.locator('[data-cf-like]').nth(1); await l.click(); });
await rec('v2d-ghost', async () => { await go('/terminal/mind'); await p.evaluate(() => scrollTo(0, 0)); }, async () => { await center('.mind-ghost'); }, 2400);
await b.close();
