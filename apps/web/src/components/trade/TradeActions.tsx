import { createContext, useCallback, useContext, useMemo, useState, type ReactNode } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import type { TradeDto } from '@journzey/shared';
import { ConfirmDialog, useToast } from '../ui';
import { del } from '../../lib/api';
import { invalidateTradeData } from '../../lib/queries';
import { TradeFormModal } from './TradeFormModal';
import { TradeDetailModal } from './TradeDetailModal';
import { ShareCardModal } from './ShareCardModal';
import { LotSizeCalculator } from './LotSizeCalculator';

interface Actions {
  openNew: () => void;
  openEdit: (t: TradeDto) => void;
  openView: (t: TradeDto) => void;
  openShare: (t: TradeDto) => void;
  confirmDelete: (t: TradeDto) => void;
  openCalculator: () => void;
}

const Ctx = createContext<Actions | null>(null);

/** Hosts the trade modals once for the whole app shell. */
export function TradeActionsProvider({ children }: { children: ReactNode }) {
  const [form, setForm] = useState<{ open: boolean; trade: TradeDto | null }>({ open: false, trade: null });
  const [view, setView] = useState<TradeDto | null>(null);
  const [share, setShare] = useState<TradeDto | null>(null);
  const [toDelete, setToDelete] = useState<TradeDto | null>(null);
  const [calc, setCalc] = useState(false);
  const qc = useQueryClient();
  const toast = useToast();
  const remove = useMutation({
    mutationFn: (t: TradeDto) => del(`/trades/${t.id}`),
    onSuccess: () => {
      invalidateTradeData(qc);
      toast('success', 'Trade deleted');
      setToDelete(null);
      setView(null);
    },
    onError: (e) => toast('error', e instanceof Error ? e.message : 'Delete failed'),
  });

  const actions = useMemo<Actions>(
    () => ({
      openNew: () => setForm({ open: true, trade: null }),
      openEdit: (t) => {
        setView(null);
        setForm({ open: true, trade: t });
      },
      openView: setView,
      openShare: setShare,
      confirmDelete: setToDelete,
      openCalculator: () => setCalc(true),
    }),
    [],
  );
  const closeForm = useCallback(() => setForm({ open: false, trade: null }), []);

  return (
    <Ctx.Provider value={actions}>
      {children}
      <TradeFormModal open={form.open} trade={form.trade} onClose={closeForm} />
      <TradeDetailModal trade={view} onClose={() => setView(null)} onEdit={actions.openEdit} onDelete={setToDelete} onShare={setShare} />
      <ShareCardModal trade={share} onClose={() => setShare(null)} />
      <LotSizeCalculator open={calc} onClose={() => setCalc(false)} />
      <ConfirmDialog
        open={!!toDelete}
        onClose={() => setToDelete(null)}
        onConfirm={() => toDelete && remove.mutate(toDelete)}
        loading={remove.isPending}
        title="Delete trade?"
        message={
          <p>
            This permanently deletes the {toDelete?.symbol} {toDelete?.side} trade, its screenshot, and recalculates your account capital. This cannot be undone.
          </p>
        }
      />
    </Ctx.Provider>
  );
}

export function useTradeActions(): Actions {
  const ctx = useContext(Ctx);
  if (!ctx) throw new Error('useTradeActions must be used inside TradeActionsProvider');
  return ctx;
}
