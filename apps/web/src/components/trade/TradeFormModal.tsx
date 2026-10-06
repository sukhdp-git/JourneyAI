import { useEffect, useMemo, useRef, useState, type ClipboardEvent, type DragEvent } from 'react';
import { useForm, useWatch } from 'react-hook-form';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { ImagePlus, X } from 'lucide-react';
import {
  ALLOWED_SCREENSHOT_MIME,
  EMOTIONS,
  INSTRUMENTS,
  MAX_SCREENSHOT_BYTES,
  MISTAKE_TAGS,
  TRADING_SESSIONS,
  computeTradeMath,
  tradeCreateSchema,
  type TradeDto,
} from '@journzey/shared';
import { Button, Field, Input, Modal, Select, Textarea, useToast } from '../ui';
import { ApiError, patch, post, upload } from '../../lib/api';
import { fmtMoney, fmtR, humanize, utcToZonedLocal, zonedLocalToUtc } from '../../lib/format';
import { invalidateTradeData, useAccounts, useSettings, useStrategies } from '../../lib/queries';

interface FormValues {
  tradingAccountId: string;
  executedAt: string;
  symbol: string;
  side: 'LONG' | 'SHORT';
  entryPrice: string;
  stopLoss: string;
  takeProfit: string;
  exitPrice: string;
  lotSize: string;
  fees: string;
  pnl: string;
  strategyId: string;
  setupTag: string;
  session: string;
  emotion: string;
  mistakeTag: string;
  rulesFollowed: boolean;
  notes: string;
}

const blank = (v: string) => (v.trim() === '' ? null : v.trim());

export function validateScreenshotFile(f: File): string | null {
  if (!(ALLOWED_SCREENSHOT_MIME as readonly string[]).includes(f.type)) return 'Only PNG, JPEG or WebP images are allowed';
  if (f.size > MAX_SCREENSHOT_BYTES) return 'Image must be 5 MB or smaller';
  if (!/\.(png|jpe?g|webp)$/i.test(f.name || 'paste.png')) return 'Unsupported file extension';
  return null;
}

/** Manual Trade Modal: create or edit. The server recomputes and is authoritative for all numbers. */
export function TradeFormModal({ open, onClose, trade, defaultAccountId }: { open: boolean; onClose: () => void; trade?: TradeDto | null; defaultAccountId?: string }) {
  const settings = useSettings();
  const tz = settings.timezone;
  const accounts = useAccounts();
  const strategies = useStrategies();
  const qc = useQueryClient();
  const toast = useToast();
  const [file, setFile] = useState<File | null>(null);
  const [fileError, setFileError] = useState<string | null>(null);
  const fileInput = useRef<HTMLInputElement>(null);
  const writable = (accounts.data ?? []).filter((a) => !a.archived && !a.sampleData);

  const defaults = useMemo<FormValues>(
    () => ({
      tradingAccountId: trade?.tradingAccountId ?? defaultAccountId ?? writable[0]?.id ?? '',
      executedAt: utcToZonedLocal(trade?.executedAt ?? new Date().toISOString(), tz),
      symbol: trade?.symbol ?? 'XAUUSD',
      side: trade?.side ?? 'LONG',
      entryPrice: trade?.entryPrice ?? '',
      stopLoss: trade?.stopLoss ?? '',
      takeProfit: trade?.takeProfit ?? '',
      exitPrice: trade?.exitPrice ?? '',
      lotSize: trade?.lotSize ?? '1',
      fees: trade && trade.fees !== '0.00' ? trade.fees : '',
      pnl: '',
      strategyId: trade?.strategyId ?? '',
      setupTag: trade?.setupTag ?? '',
      session: trade?.session ?? '',
      emotion: trade?.emotion ?? '',
      mistakeTag: trade?.mistakeTag ?? '',
      rulesFollowed: trade?.rulesFollowed ?? true,
      notes: trade?.notes ?? '',
    }),
    // eslint-disable-next-line react-hooks/exhaustive-deps
    [trade, defaultAccountId, open, accounts.data],
  );
  const { register, handleSubmit, reset, setError, control, formState } = useForm<FormValues>({ defaultValues: defaults });
  useEffect(() => {
    if (open) {
      reset(defaults);
      setFile(null);
      setFileError(null);
    }
  }, [open, defaults, reset]);

  const w = useWatch({ control });
  const account = accounts.data?.find((a) => a.id === w.tradingAccountId);
  const preview = useMemo(() => {
    try {
      if (!w.symbol || !w.side || !(Number(w.entryPrice) > 0) || !(Number(w.lotSize) > 0)) return null;
      return computeTradeMath({
        symbol: w.symbol,
        side: w.side,
        entryPrice: w.entryPrice!,
        exitPrice: blank(w.exitPrice ?? ''),
        stopLoss: blank(w.stopLoss ?? ''),
        lotSize: w.lotSize!,
        fees: blank(w.fees ?? '') ?? '0',
        accountCurrency: account?.currency ?? 'USD',
      });
    } catch {
      return null;
    }
  }, [w.symbol, w.side, w.entryPrice, w.exitPrice, w.stopLoss, w.lotSize, w.fees, account?.currency]);

  const save = useMutation({
    mutationFn: async (v: FormValues) => {
      const body = {
        tradingAccountId: v.tradingAccountId,
        executedAt: zonedLocalToUtc(v.executedAt, tz),
        symbol: v.symbol,
        side: v.side,
        entryPrice: v.entryPrice.trim(),
        stopLoss: blank(v.stopLoss),
        takeProfit: blank(v.takeProfit),
        exitPrice: blank(v.exitPrice),
        lotSize: v.lotSize.trim(),
        fees: blank(v.fees),
        ...(blank(v.pnl) ? { pnl: blank(v.pnl) } : {}),
        strategyId: blank(v.strategyId),
        setupTag: blank(v.setupTag),
        session: (blank(v.session) as FormValues['session'] | null) ?? undefined,
        emotion: blank(v.emotion),
        mistakeTag: blank(v.mistakeTag),
        rulesFollowed: v.rulesFollowed,
        notes: blank(v.notes),
      };
      const check = tradeCreateSchema.safeParse(body);
      if (!check.success) {
        const fields: Record<string, string[]> = {};
        for (const i of check.error.issues) (fields[String(i.path[0])] ??= []).push(i.message);
        throw new ApiError(400, 'VALIDATION_ERROR', 'Please fix the highlighted fields', fields);
      }
      // Edits send only changed fields so untouched values (e.g. a broker-reported P&L) are preserved server-side.
      const dirty = Object.keys(formState.dirtyFields) as Array<keyof typeof body>;
      const changes = Object.fromEntries(dirty.filter((k) => k in body).map((k) => [k, body[k]]));
      let saved = trade
        ? dirty.length
          ? await patch<TradeDto>(`/trades/${trade.id}`, changes)
          : trade
        : await post<TradeDto>('/trades', body);
      if (file) {
        const form = new FormData();
        form.append('file', file);
        saved = await upload<TradeDto>(`/trades/${saved.id}/screenshot`, form);
      }
      return saved;
    },
    onSuccess: (t) => {
      invalidateTradeData(qc);
      toast('success', `${t.symbol} ${t.side} ${trade ? 'updated' : 'logged'}${t.pnl ? ` · ${fmtMoney(t.pnl, t.currency, { sign: true })}` : ''}`);
      onClose();
    },
    onError: (e) => {
      if (e instanceof ApiError && e.fields) for (const [k, msgs] of Object.entries(e.fields)) setError(k as keyof FormValues, { message: msgs[0] });
      toast('error', e instanceof Error ? e.message : 'Could not save trade');
    },
  });

  const takeFile = (f: File | undefined | null) => {
    if (!f) return;
    const err = validateScreenshotFile(f);
    setFileError(err);
    setFile(err ? null : f);
  };
  const onDrop = (e: DragEvent) => {
    e.preventDefault();
    takeFile(e.dataTransfer.files?.[0]);
  };
  const onPaste = (e: ClipboardEvent) => {
    const item = Array.from(e.clipboardData.items).find((i) => i.type.startsWith('image/'));
    if (item) {
      e.preventDefault();
      const blob = item.getAsFile();
      if (blob) takeFile(new File([blob], `pasted.${blob.type.split('/')[1] ?? 'png'}`, { type: blob.type }));
    }
  };

  const err = (k: keyof FormValues) => formState.errors[k]?.message;
  const ids = (k: string) => `tf-${k}`;

  return (
    <Modal
      open={open}
      onClose={onClose}
      wide
      title={trade ? `Edit trade · ${trade.symbol}` : 'Log trade'}
      footer={
        <>
          <Button onClick={onClose}>Cancel</Button>
          <Button variant="primary" loading={save.isPending} onClick={handleSubmit((v) => save.mutate(v))} disabled={writable.length === 0}>
            {trade ? 'Save changes' : 'Log trade'}
          </Button>
        </>
      }
    >
      {writable.length === 0 ? (
        <p className="text-sm text-muted">Create a real or practice trading account in Settings → Accounts first. Demo-data accounts are read-only.</p>
      ) : (
        <form className="grid grid-cols-2 gap-3 sm:grid-cols-4" onPaste={onPaste} onSubmit={handleSubmit((v) => save.mutate(v))} noValidate>
          <Field label="Account" htmlFor={ids('acc')} className="col-span-2" error={err('tradingAccountId')}>
            <Select id={ids('acc')} {...register('tradingAccountId')}>
              {writable.map((a) => (
                <option key={a.id} value={a.id}>
                  {a.accountName} ({a.currency}
                  {a.demo ? ', demo' : ''})
                </option>
              ))}
            </Select>
          </Field>
          <Field label={`Executed (${tz})`} htmlFor={ids('time')} className="col-span-2" error={err('executedAt')}>
            <Input id={ids('time')} type="datetime-local" {...register('executedAt', { required: 'Required' })} />
          </Field>
          <Field label="Symbol" htmlFor={ids('sym')} error={err('symbol')}>
            <Select id={ids('sym')} {...register('symbol')}>
              {INSTRUMENTS.map((i) => (
                <option key={i.symbol} value={i.symbol}>
                  {i.symbol}
                </option>
              ))}
            </Select>
          </Field>
          <Field label="Side" htmlFor={ids('side')}>
            <Select id={ids('side')} {...register('side')}>
              <option value="LONG">LONG</option>
              <option value="SHORT">SHORT</option>
            </Select>
          </Field>
          <Field label="Lot size" htmlFor={ids('lot')} error={err('lotSize')}>
            <Input id={ids('lot')} className="num" inputMode="decimal" invalid={!!err('lotSize')} {...register('lotSize', { required: 'Required' })} />
          </Field>
          <Field label="Entry" htmlFor={ids('entry')} error={err('entryPrice')}>
            <Input id={ids('entry')} className="num" inputMode="decimal" invalid={!!err('entryPrice')} {...register('entryPrice', { required: 'Required' })} />
          </Field>
          <Field label="Stop loss" htmlFor={ids('sl')} error={err('stopLoss')}>
            <Input id={ids('sl')} className="num" inputMode="decimal" invalid={!!err('stopLoss')} {...register('stopLoss')} />
          </Field>
          <Field label="Take profit" htmlFor={ids('tp')} error={err('takeProfit')}>
            <Input id={ids('tp')} className="num" inputMode="decimal" invalid={!!err('takeProfit')} {...register('takeProfit')} />
          </Field>
          <Field label="Exit (blank = open)" htmlFor={ids('exit')} error={err('exitPrice')}>
            <Input id={ids('exit')} className="num" inputMode="decimal" invalid={!!err('exitPrice')} {...register('exitPrice')} />
          </Field>
          <Field label="Fees" htmlFor={ids('fees')} error={err('fees')}>
            <Input id={ids('fees')} className="num" inputMode="decimal" {...register('fees')} />
          </Field>
          <Field label="Broker P&L override" htmlFor={ids('pnl')} error={err('pnl')} hint="Optional — use the broker-reported net figure">
            <Input id={ids('pnl')} className="num" inputMode="decimal" placeholder={trade?.pnl ?? ''} {...register('pnl')} />
          </Field>
          <Field label="Strategy" htmlFor={ids('strat')} className="col-span-2" error={err('strategyId')}>
            <Select id={ids('strat')} {...register('strategyId')}>
              <option value="">— Unassigned —</option>
              {(strategies.data ?? []).map((s) => (
                <option key={s.id} value={s.id}>
                  {s.name}
                </option>
              ))}
            </Select>
          </Field>
          <Field label="Setup tag" htmlFor={ids('setup')} className="col-span-2">
            <Input id={ids('setup')} maxLength={120} {...register('setupTag')} />
          </Field>
          <Field label="Session" htmlFor={ids('sess')}>
            <Select id={ids('sess')} {...register('session')}>
              <option value="">Auto-detect</option>
              {TRADING_SESSIONS.map((s) => (
                <option key={s} value={s}>
                  {humanize(s)}
                </option>
              ))}
            </Select>
          </Field>
          <Field label="Emotion" htmlFor={ids('emo')}>
            <Select id={ids('emo')} {...register('emotion')}>
              <option value="">—</option>
              {EMOTIONS.map((s) => (
                <option key={s} value={s}>
                  {humanize(s)}
                </option>
              ))}
            </Select>
          </Field>
          <Field label="Mistake" htmlFor={ids('mis')}>
            <Select id={ids('mis')} {...register('mistakeTag')}>
              <option value="">—</option>
              {MISTAKE_TAGS.map((s) => (
                <option key={s} value={s}>
                  {humanize(s)}
                </option>
              ))}
            </Select>
          </Field>
          <label className="flex min-h-10 items-center gap-2 self-end text-sm">
            <input type="checkbox" className="h-4 w-4 accent-[rgb(var(--accent))]" {...register('rulesFollowed')} /> Rules followed
          </label>
          <Field label="Notes" htmlFor={ids('notes')} className="col-span-2 sm:col-span-4" error={err('notes')}>
            <Textarea id={ids('notes')} maxLength={5000} {...register('notes')} />
          </Field>
          <div
            className="col-span-2 flex flex-col items-center justify-center gap-2 rounded-md border border-dashed border-line p-4 text-center text-sm text-muted sm:col-span-4"
            onDragOver={(e) => e.preventDefault()}
            onDrop={onDrop}
          >
            <ImagePlus className="h-5 w-5" aria-hidden />
            {file ? (
              <span className="flex items-center gap-2 text-fg">
                {file.name} ({Math.round(file.size / 1024)} KB)
                <button type="button" onClick={() => setFile(null)} aria-label="Remove screenshot" className="rounded p-1 hover:bg-panel2">
                  <X className="h-3.5 w-3.5" />
                </button>
              </span>
            ) : (
              <span>
                Screenshot: drag & drop, paste (Ctrl/⌘+V) or{' '}
                <button type="button" className="text-accent underline" onClick={() => fileInput.current?.click()}>
                  choose a file
                </button>{' '}
                · PNG/JPEG/WebP ≤ 5 MB{trade?.hasScreenshot ? ' · replaces the current image' : ''}
              </span>
            )}
            <input ref={fileInput} type="file" accept="image/png,image/jpeg,image/webp" className="sr-only" aria-label="Choose screenshot" onChange={(e) => takeFile(e.target.files?.[0])} />
            {fileError && (
              <p role="alert" className="text-2xs text-loss">
                {fileError}
              </p>
            )}
          </div>
          <div className="col-span-2 flex flex-wrap gap-4 rounded-md bg-panel2 px-3 py-2 text-xs sm:col-span-4" aria-live="polite">
            <span>
              Preview P&L: <b className="num">{preview?.pnl ? fmtMoney(preview.pnl, account?.currency) : w.exitPrice ? 'broker P&L required' : 'open'}</b>
            </span>
            <span>
              R: <b className="num">{fmtR(preview?.rr)}</b>
            </span>
            <span>
              1R risk: <b className="num">{preview?.riskAmount ? fmtMoney(preview.riskAmount, account?.currency) : '—'}</b>
            </span>
            <span className="text-muted">Server recalculates on save.</span>
          </div>
        </form>
      )}
    </Modal>
  );
}
