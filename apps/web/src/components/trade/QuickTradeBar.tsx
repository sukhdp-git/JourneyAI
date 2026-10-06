import { useEffect, useMemo, useRef, useState, type KeyboardEvent } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { Lock, Terminal } from 'lucide-react';
import clsx from 'clsx';
import { parseQuickTrade, type TradeDto } from '@journzey/shared';
import { Badge, Button } from '../ui';
import { ApiError, post } from '../../lib/api';
import { fmtMoney, humanize } from '../../lib/format';
import { invalidateTradeData, useAccounts, useScope, useSettings } from '../../lib/queries';
import { useToast } from '../ui';
import { useI18n } from '../../lib/i18n';

export function useTerminalLock() {
  const settings = useSettings();
  const until = settings.terminalLockUntil ? new Date(settings.terminalLockUntil).getTime() : 0;
  const [now, setNow] = useState(Date.now());
  useEffect(() => {
    if (until <= Date.now()) return;
    const id = setInterval(() => setNow(Date.now()), 1000);
    return () => clearInterval(id);
  }, [until]);
  const remainingMs = Math.max(0, until - now);
  return { locked: remainingMs > 0, remainingMs };
}

export const fmtCountdown = (ms: number) => {
  const s = Math.ceil(ms / 1000);
  return `${String(Math.floor(s / 60)).padStart(2, '0')}:${String(s % 60).padStart(2, '0')}`;
};

/** Natural-language quick trade command with a parsed preview. The server re-parses and validates. */
export function QuickTradeBar({ autoFocus, compact }: { autoFocus?: boolean; compact?: boolean }) {
  const { t } = useI18n();
  const [cmd, setCmd] = useState('');
  const accounts = useAccounts();
  const { scope } = useScope();
  const qc = useQueryClient();
  const toast = useToast();
  const { locked, remainingMs } = useTerminalLock();
  const input = useRef<HTMLInputElement>(null);
  const writable = (accounts.data ?? []).filter((a) => !a.archived && !a.sampleData);
  const preferred = writable.find((a) => a.id === scope) ?? writable.find((a) => (scope === 'demo' ? a.demo : !a.demo)) ?? writable[0];
  const [accountId, setAccountId] = useState<string>('');
  const activeAccount = writable.find((a) => a.id === accountId) ?? preferred;
  const parsed = useMemo(() => (cmd.trim().length >= 3 ? parseQuickTrade(cmd) : null), [cmd]);

  const submit = useMutation({
    mutationFn: () => post<{ trade: TradeDto }>('/trades/quick', { command: cmd, tradingAccountId: activeAccount!.id }),
    onSuccess: ({ trade }) => {
      invalidateTradeData(qc);
      setCmd('');
      toast('success', `${trade.symbol} ${trade.side} logged${trade.pnl ? ` · ${fmtMoney(trade.pnl, trade.currency, { sign: true })}` : ' (open)'}`);
    },
    onError: (e) => toast('error', e instanceof ApiError && e.fields?.command ? e.fields.command.join(' · ') : e instanceof Error ? e.message : 'Could not log trade'),
  });

  const onKey = (e: KeyboardEvent<HTMLInputElement>) => {
    if (e.key === 'Escape') setCmd('');
    if (e.key === 'Enter' && parsed && parsed.errors.length === 0 && activeAccount && !locked && !submit.isPending) submit.mutate();
  };

  if (locked) {
    return (
      <div role="status" className="flex items-center gap-3 rounded-md border border-loss/50 bg-loss/10 px-3 py-2 text-sm">
        <Lock className="h-4 w-4 text-loss" aria-hidden />
        <span>
          <b>{t('quick.locked')}</b> · resumes in <span className="num">{fmtCountdown(remainingMs)}</span>. Your broker account is <b>not</b> locked.
        </span>
      </div>
    );
  }

  const chips: Array<[string, string | null, string?]> = parsed
    ? [
        ['', parsed.symbol, 'accent'],
        ['', parsed.side, parsed.side === 'LONG' ? 'profit' : 'loss'],
        ['Entry', parsed.entryPrice],
        ['SL', parsed.stopLoss],
        ['TP', parsed.takeProfit],
        ['Exit', parsed.exitPrice],
        ['', parsed.rr ? `${Number(parsed.rr) > 0 ? '+' : ''}${parsed.rr}R` : null, Number(parsed.rr) >= 0 ? 'profit' : 'loss'],
        ['', parsed.lotSize ? `${Number(parsed.lotSize).toFixed(2)} Lot` : null],
        ['', parsed.setup],
        ['', parsed.mistakeTag ? humanize(parsed.mistakeTag) : null, 'warn'],
      ]
    : [];

  return (
    <div className="space-y-2">
      <div className="flex items-center gap-2">
        <div className="relative flex-1">
          <Terminal className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-accent" aria-hidden />
          <input
            ref={input}
            autoFocus={autoFocus}
            value={cmd}
            onChange={(e) => setCmd(e.target.value)}
            onKeyDown={onKey}
            placeholder={t('quick.placeholder')}
            aria-label="Quick trade command"
            aria-describedby="quick-help"
            spellCheck={false}
            autoCapitalize="off"
            autoComplete="off"
            enterKeyHint="send"
            className="num h-11 w-full rounded-md border border-line bg-panel2 pl-9 pr-3 text-sm focus:border-accent focus:outline-none"
          />
        </div>
        <Button variant="primary" className="h-11" onClick={() => submit.mutate()} loading={submit.isPending} disabled={!parsed || parsed.errors.length > 0 || !activeAccount}>
          Log
        </Button>
      </div>
      {!compact && writable.length > 1 && (
        <label className="flex items-center gap-2 text-2xs text-muted">
          Into
          <select value={activeAccount?.id ?? ''} onChange={(e) => setAccountId(e.target.value)} className="h-7 rounded border border-line bg-panel2 px-1 text-2xs">
            {writable.map((a) => (
              <option key={a.id} value={a.id}>
                {a.accountName}
              </option>
            ))}
          </select>
        </label>
      )}
      <div id="quick-help" aria-live="polite" className="flex min-h-6 flex-wrap items-center gap-1.5">
        {parsed &&
          chips
            .filter(([, v]) => v)
            .map(([k, v, tone], i) => (
              <Badge key={i} tone={(tone as 'accent') ?? 'neutral'} className="num normal-case">
                {k ? `${k} ${v}` : v}
              </Badge>
            ))}
        {parsed?.errors.map((e) => (
          <span key={e} className="text-2xs text-loss">
            {e}
          </span>
        ))}
        {parsed?.errors.length === 0 &&
          parsed.warnings.map((w) => (
            <span key={w} className="text-2xs text-warn">
              {w}
            </span>
          ))}
        {!parsed && <span className={clsx('text-2xs text-muted', compact && 'hidden sm:inline')}>{t('quick.hint')}{activeAccount ? ` · logs into ${activeAccount.accountName}` : ''}</span>}
        {!activeAccount && accounts.data && <span className="text-2xs text-warn">Create a trading account in Settings to log trades.</span>}
      </div>
    </div>
  );
}
