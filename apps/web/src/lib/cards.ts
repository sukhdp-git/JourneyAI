import type { TradeDto } from '@journzey/shared';
import { fmtMoney, fmtPrice, fmtR, humanize } from './format';

export const CARD_THEMES = {
  'neon-cyan': { name: 'Neon Cyan', bg: ['#020617', '#0b1d2e'], accent: '#22d3ee', text: '#e0f2fe', muted: '#7dd3fc' },
  'matrix-emerald': { name: 'Matrix Emerald', bg: ['#010b05', '#052e16'], accent: '#34d399', text: '#dcfce7', muted: '#86efac' },
  'cyber-amethyst': { name: 'Cyber Amethyst', bg: ['#0f0518', '#2e1065'], accent: '#c084fc', text: '#f3e8ff', muted: '#d8b4fe' },
  'gold-bullion': { name: 'Gold Bullion', bg: ['#140f02', '#3b2a05'], accent: '#facc15', text: '#fef9c3', muted: '#fde68a' },
} as const;
export type CardTheme = keyof typeof CARD_THEMES;

const W = 1080;
const H = 1350;

/**
 * Renders a shareable trade card to a canvas. Deliberately excludes any account name,
 * account id, broker id, email or trade id — only the trade's market facts and results.
 */
export function drawTradeCard(canvas: HTMLCanvasElement, trade: TradeDto, theme: CardTheme, opts: { showMoney: boolean }) {
  const t = CARD_THEMES[theme];
  canvas.width = W;
  canvas.height = H;
  const c = canvas.getContext('2d')!;
  const g = c.createLinearGradient(0, 0, W, H);
  g.addColorStop(0, t.bg[0]);
  g.addColorStop(1, t.bg[1]);
  c.fillStyle = g;
  c.fillRect(0, 0, W, H);
  // grid
  c.strokeStyle = `${t.accent}14`;
  c.lineWidth = 1;
  for (let x = 0; x < W; x += 54) {
    c.beginPath();
    c.moveTo(x, 0);
    c.lineTo(x, H);
    c.stroke();
  }
  for (let y = 0; y < H; y += 54) {
    c.beginPath();
    c.moveTo(0, y);
    c.lineTo(W, y);
    c.stroke();
  }
  c.strokeStyle = t.accent;
  c.lineWidth = 6;
  c.strokeRect(36, 36, W - 72, H - 72);

  const win = Number(trade.pnl ?? 0) >= 0;
  c.fillStyle = t.muted;
  c.font = '600 34px "JetBrains Mono", monospace';
  c.fillText('JOURNZEY.AI // EXECUTION', 84, 130);
  c.fillStyle = t.text;
  c.font = '800 120px "Plus Jakarta Sans", Inter, sans-serif';
  c.fillText(trade.symbol, 84, 290);
  c.fillStyle = trade.side === 'LONG' ? '#22c55e' : '#f43f5e';
  c.font = '700 52px "JetBrains Mono", monospace';
  c.fillText(trade.side === 'LONG' ? '▲ LONG' : '▼ SHORT', 84, 370);

  c.fillStyle = win ? '#22c55e' : '#f43f5e';
  c.font = '800 150px "JetBrains Mono", monospace';
  c.fillText(fmtR(trade.rr), 84, 560);
  if (opts.showMoney) {
    c.font = '700 64px "JetBrains Mono", monospace';
    c.fillText(fmtMoney(trade.pnl, trade.currency, { sign: true }), 84, 650);
  }

  const rows: Array<[string, string]> = [
    ['ENTRY', fmtPrice(trade.symbol, trade.entryPrice)],
    ['EXIT', fmtPrice(trade.symbol, trade.exitPrice)],
    ['STOP', fmtPrice(trade.symbol, trade.stopLoss)],
    ['SIZE', `${Number(trade.lotSize).toFixed(2)} lot`],
    ['STRATEGY', trade.strategyName ?? trade.setupTag ?? '—'],
    ['SESSION', humanize(trade.session)],
  ];
  let y = 780;
  for (const [k, v] of rows) {
    c.fillStyle = t.muted;
    c.font = '600 32px "JetBrains Mono", monospace';
    c.fillText(k, 84, y);
    c.fillStyle = t.text;
    c.font = '600 40px "JetBrains Mono", monospace';
    c.fillText(v.slice(0, 28), 400, y);
    y += 70;
  }
  c.fillStyle = t.accent;
  c.font = '800 44px "Plus Jakarta Sans", Inter, sans-serif';
  c.fillText('journzey.ai', 84, H - 96);
  c.fillStyle = t.muted;
  c.font = '500 24px Inter, sans-serif';
  c.fillText(trade.demo ? 'DEMO DATA · not a real trade' : 'Past performance does not guarantee future results', 420, H - 100);
}

/** Discipline-leak summary card for PNG export. */
export function drawLeakCard(canvas: HTMLCanvasElement, data: { leak: string; actual: string; flawless: string; violations: number; currency: string; period: string; demo: boolean }) {
  canvas.width = 1200;
  canvas.height = 630;
  const c = canvas.getContext('2d')!;
  c.fillStyle = '#090c10';
  c.fillRect(0, 0, 1200, 630);
  c.strokeStyle = '#f43f5e';
  c.lineWidth = 4;
  c.strokeRect(24, 24, 1152, 582);
  c.fillStyle = '#8b98a8';
  c.font = '600 26px "JetBrains Mono", monospace';
  c.fillText(`DISCIPLINE LEAK MIRROR · ${data.period}`, 64, 92);
  c.fillStyle = '#e4eaf1';
  c.font = '700 40px Inter, sans-serif';
  c.fillText('Execution mistakes cost approximately', 64, 180);
  c.fillStyle = '#f43f5e';
  c.font = '800 120px "JetBrains Mono", monospace';
  c.fillText(fmtMoney(data.leak, data.currency), 64, 320);
  c.fillStyle = '#8b98a8';
  c.font = '500 28px "JetBrains Mono", monospace';
  c.fillText(`Actual ${fmtMoney(data.actual, data.currency, { sign: true })}   ·   Rule-compliant hypothetical ${fmtMoney(data.flawless, data.currency, { sign: true })}`, 64, 400);
  c.fillText(`${data.violations} rule violation(s)`, 64, 450);
  c.font = '500 20px Inter, sans-serif';
  c.fillText('Hypothetical, conservative estimate — not a guarantee of what rule-compliant trading would have earned.', 64, 540);
  c.fillStyle = '#22d3ee';
  c.font = '800 30px "Plus Jakarta Sans", Inter, sans-serif';
  c.fillText(data.demo ? 'journzey.ai · DEMO DATA' : 'journzey.ai', 64, 590);
}

export function downloadCanvas(canvas: HTMLCanvasElement, filename: string) {
  canvas.toBlob((blob) => {
    if (!blob) return;
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
    setTimeout(() => URL.revokeObjectURL(url), 1000);
  }, 'image/png');
}

export async function copyCanvas(canvas: HTMLCanvasElement): Promise<boolean> {
  if (!('ClipboardItem' in window) || !navigator.clipboard?.write) return false;
  const blob = await new Promise<Blob | null>((r) => canvas.toBlob(r, 'image/png'));
  if (!blob) return false;
  await navigator.clipboard.write([new ClipboardItem({ 'image/png': blob })]);
  return true;
}
