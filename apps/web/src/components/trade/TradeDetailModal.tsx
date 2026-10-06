import { useState } from 'react';
import { useMutation } from '@tanstack/react-query';
import { Pencil, Share2, Trash2, Wand2 } from 'lucide-react';
import type { TradeDto } from '@journzey/shared';
import { Badge, Button, DemoBadge, Modal, Pnl } from '../ui';
import { ApiError, post } from '../../lib/api';
import { fmtDate, fmtMoney, fmtPrice, fmtR, fmtTime, humanize } from '../../lib/format';
import { useMe } from '../../lib/queries';

interface RunnerAudit {
  label: string;
  runnerExit: string;
  exitReason: string;
  additionalR: number | null;
  additionalPnl: string | null;
  candles: number;
  windowStart: string;
  windowEnd: string;
  provider: string;
  currency: string;
}

export function TradeDetailModal({
  trade,
  onClose,
  onEdit,
  onDelete,
  onShare,
}: {
  trade: TradeDto | null;
  onClose: () => void;
  onEdit: (t: TradeDto) => void;
  onDelete: (t: TradeDto) => void;
  onShare: (t: TradeDto) => void;
}) {
  const { data: me } = useMe();
  const tz = me?.settings?.timezone ?? 'UTC';
  const [hours, setHours] = useState(4);
  const audit = useMutation({ mutationFn: (t: TradeDto) => post<RunnerAudit>(`/trades/${t.id}/runner-audit`, { windowHours: hours }) });
  if (!trade) return null;
  const rows: Array<[string, React.ReactNode]> = [
    ['Executed', `${fmtDate(trade.executedAt, tz)} ${fmtTime(trade.executedAt, tz)}`],
    ['Status', trade.status],
    ['Entry', <span className="num">{fmtPrice(trade.symbol, trade.entryPrice)}</span>],
    ['Exit', <span className="num">{fmtPrice(trade.symbol, trade.exitPrice)}</span>],
    ['Stop', <span className="num">{fmtPrice(trade.symbol, trade.stopLoss)}</span>],
    ['Take profit', <span className="num">{fmtPrice(trade.symbol, trade.takeProfit)}</span>],
    ['Lot size', <span className="num">{Number(trade.lotSize).toFixed(2)}</span>],
    ['1R risk', <span className="num">{fmtMoney(trade.riskAmount, trade.currency)}</span>],
    ['Fees', <span className="num">{fmtMoney(trade.fees, trade.currency)}</span>],
    ['Strategy', trade.strategyName ?? '—'],
    ['Setup', trade.setupTag ?? '—'],
    ['Session', humanize(trade.session)],
    ['Emotion', humanize(trade.emotion)],
    ['Mistake', humanize(trade.mistakeTag)],
    ['Rules followed', trade.rulesFollowed ? 'Yes' : 'No'],
    ['Source', humanize(trade.source)],
  ];
  const auditError = audit.error instanceof ApiError ? audit.error : null;
  return (
    <Modal
      open
      onClose={onClose}
      wide
      title={`${trade.symbol} · ${trade.side}`}
      footer={
        <>
          <Button variant="ghost" icon={<Trash2 className="h-4 w-4" />} onClick={() => onDelete(trade)} disabled={trade.demo} title={trade.demo ? 'Demo data is read-only — use Reset Demo Data' : undefined}>
            Delete
          </Button>
          <Button icon={<Share2 className="h-4 w-4" />} onClick={() => onShare(trade)}>
            Share card
          </Button>
          <Button variant="primary" icon={<Pencil className="h-4 w-4" />} onClick={() => onEdit(trade)} disabled={trade.demo} title={trade.demo ? 'Demo data is read-only' : undefined}>
            Edit
          </Button>
        </>
      }
    >
      <div className="flex flex-wrap items-center gap-3">
        <Pnl value={trade.pnl} currency={trade.currency} className="text-2xl font-bold" />
        <span className="num text-lg">{fmtR(trade.rr)}</span>
        {trade.demo && <DemoBadge />}
        {!trade.rulesFollowed && <Badge tone="loss">Rule violation</Badge>}
      </div>
      <dl className="mt-4 grid grid-cols-2 gap-x-6 gap-y-2 text-sm sm:grid-cols-4">
        {rows.map(([k, v]) => (
          <div key={k}>
            <dt className="label">{k}</dt>
            <dd className="mt-0.5">{v}</dd>
          </div>
        ))}
      </dl>
      {trade.notes && <p className="mt-4 whitespace-pre-wrap rounded-md bg-panel2 p-3 text-sm">{trade.notes}</p>}
      {trade.hasScreenshot && (
        <a href={`/api/v1/trades/${trade.id}/screenshot`} target="_blank" rel="noreferrer" className="mt-4 block">
          <img src={`/api/v1/trades/${trade.id}/screenshot?v=${encodeURIComponent(trade.updatedAt)}`} alt={`${trade.symbol} trade screenshot`} loading="lazy" className="max-h-80 w-full rounded-md border border-line object-contain" />
        </a>
      )}
      <section className="mt-5 rounded-md border border-line p-3">
        <h3 className="label flex items-center gap-2">
          <Wand2 className="h-3.5 w-3.5" aria-hidden /> Post-trade runner auditor (hypothetical)
        </h3>
        <p className="mt-1 text-2xs text-muted">What if 20% of the position had been held as a runner with its stop at breakeven? Uses real historical candles from the configured market-data provider — never simulated prices.</p>
        <div className="mt-2 flex flex-wrap items-center gap-2">
          <label className="text-xs text-muted" htmlFor="runner-hours">
            Window
          </label>
          <select id="runner-hours" value={hours} onChange={(e) => setHours(Number(e.target.value))} className="h-8 rounded border border-line bg-panel2 px-2 text-xs">
            {[1, 2, 4, 8, 24].map((h) => (
              <option key={h} value={h}>
                {h}h after exit
              </option>
            ))}
          </select>
          <Button size="sm" loading={audit.isPending} onClick={() => audit.mutate(trade)} disabled={trade.status !== 'CLOSED' || !trade.stopLoss || !me?.features.marketData}>
            Run audit
          </Button>
          {!me?.features.marketData && <span className="text-2xs text-warn">Requires MARKET_DATA_API_KEY on the server.</span>}
        </div>
        {audit.data && (
          <div className="mt-3 grid grid-cols-2 gap-2 text-sm sm:grid-cols-4" aria-live="polite">
            <div>
              <div className="label">Runner exit</div>
              <div className="num">{fmtPrice(trade.symbol, audit.data.runnerExit)}</div>
            </div>
            <div>
              <div className="label">Exit reason</div>
              <div>{humanize(audit.data.exitReason)}</div>
            </div>
            <div>
              <div className="label">Additional R</div>
              <div className="num">{fmtR(audit.data.additionalR)}</div>
            </div>
            <div>
              <div className="label">Additional P&L</div>
              <Pnl value={audit.data.additionalPnl} currency={audit.data.currency} />
            </div>
            <p className="col-span-2 text-2xs text-muted sm:col-span-4">
              HYPOTHETICAL · {audit.data.candles} candles from {audit.data.provider}. {audit.data.label}
            </p>
          </div>
        )}
        {auditError && (
          <p role="alert" className="mt-2 text-2xs text-loss">
            {auditError.message}
          </p>
        )}
      </section>
    </Modal>
  );
}
