import { useEffect, useRef, useState } from 'react';
import { Copy, Download } from 'lucide-react';
import type { TradeDto } from '@journzey/shared';
import { Button, Modal, Tabs, useToast } from '../ui';
import { CARD_THEMES, copyCanvas, downloadCanvas, drawTradeCard, type CardTheme } from '../../lib/cards';

export function ShareCardModal({ trade, onClose }: { trade: TradeDto | null; onClose: () => void }) {
  const ref = useRef<HTMLCanvasElement>(null);
  const [theme, setTheme] = useState<CardTheme>('neon-cyan');
  const [showMoney, setShowMoney] = useState(false);
  const toast = useToast();
  const clipboardSupported = typeof window !== 'undefined' && 'ClipboardItem' in window;

  useEffect(() => {
    if (!trade || !ref.current) return;
    const canvas = ref.current;
    void document.fonts?.ready.then(() => drawTradeCard(canvas, trade, theme, { showMoney }));
    drawTradeCard(canvas, trade, theme, { showMoney });
  }, [trade, theme, showMoney]);

  return (
    <Modal
      open={!!trade}
      onClose={onClose}
      title="Cyberpunk Flex Card"
      wide
      footer={
        <>
          <Button
            icon={<Copy className="h-4 w-4" />}
            disabled={!clipboardSupported}
            title={clipboardSupported ? undefined : 'Image clipboard is not supported by this browser — use Download PNG'}
            onClick={async () => {
              try {
                toast((await copyCanvas(ref.current!)) ? 'success' : 'error', 'Card copied to clipboard');
              } catch {
                toast('error', 'Clipboard permission denied — use Download PNG');
              }
            }}
          >
            Copy image
          </Button>
          <Button variant="primary" icon={<Download className="h-4 w-4" />} onClick={() => downloadCanvas(ref.current!, `journzey-${trade?.symbol ?? 'trade'}.png`)}>
            Download PNG
          </Button>
        </>
      }
    >
      <div className="space-y-3">
        <Tabs label="Card theme" value={theme} onChange={setTheme} items={(Object.keys(CARD_THEMES) as CardTheme[]).map((k) => ({ value: k, label: CARD_THEMES[k].name }))} />
        <label className="flex items-center gap-2 text-sm">
          <input type="checkbox" checked={showMoney} onChange={(e) => setShowMoney(e.target.checked)} className="h-4 w-4 accent-[rgb(var(--accent))]" />
          Show P&L amount (off by default — R multiple only)
        </label>
        <canvas ref={ref} className="mx-auto h-auto w-full max-w-sm rounded-md border border-line" aria-label={`Share card preview for ${trade?.symbol}`} />
        <p className="text-2xs text-muted">Cards never include account names, account ids, broker ids or your email.</p>
      </div>
    </Modal>
  );
}
