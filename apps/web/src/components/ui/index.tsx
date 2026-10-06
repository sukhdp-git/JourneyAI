import {
  createContext,
  forwardRef,
  useCallback,
  useContext,
  useEffect,
  useId,
  useRef,
  useState,
  type ButtonHTMLAttributes,
  type InputHTMLAttributes,
  type ReactNode,
  type SelectHTMLAttributes,
  type TextareaHTMLAttributes,
} from 'react';
import { createPortal } from 'react-dom';
import clsx from 'clsx';
import { AlertTriangle, CheckCircle2, Info, Loader2, RefreshCw, TrendingDown, TrendingUp, X, Minus } from 'lucide-react';
import { fmtMoney, pnlTone } from '../../lib/format';

/* ------------------------------------------------------------------ Button */
type Variant = 'primary' | 'secondary' | 'ghost' | 'danger';
export const Button = forwardRef<
  HTMLButtonElement,
  ButtonHTMLAttributes<HTMLButtonElement> & { variant?: Variant; size?: 'sm' | 'md'; loading?: boolean; icon?: ReactNode }
>(function Button({ variant = 'secondary', size = 'md', loading, icon, className, children, disabled, ...rest }, ref) {
  return (
    <button
      ref={ref}
      disabled={disabled || loading}
      className={clsx(
        'inline-flex items-center justify-center gap-2 rounded-md font-medium transition-colors disabled:cursor-not-allowed disabled:opacity-50',
        size === 'sm' ? 'h-8 px-2.5 text-xs' : 'h-10 px-3.5 text-sm',
        variant === 'primary' && 'bg-accent text-accent-fg hover:bg-accent/90',
        variant === 'secondary' && 'border border-line bg-panel2 text-fg hover:border-accent/60',
        variant === 'ghost' && 'text-muted hover:bg-panel2 hover:text-fg',
        variant === 'danger' && 'bg-loss text-white hover:bg-loss/90',
        className,
      )}
      {...rest}
    >
      {loading ? <Loader2 className="h-4 w-4 animate-spin" aria-hidden /> : icon}
      {children}
    </button>
  );
});

/* ------------------------------------------------------------------ Panel */
export function Panel({ title, actions, children, className, bodyClassName, id }: { title?: ReactNode; actions?: ReactNode; children: ReactNode; className?: string; bodyClassName?: string; id?: string }) {
  return (
    <section className={clsx('panel', className)} aria-labelledby={title && id ? `${id}-title` : undefined}>
      {(title || actions) && (
        <header className="flex items-center justify-between gap-2 border-b border-line px-4 py-2.5">
          {title && (
            <h2 id={id ? `${id}-title` : undefined} className="label">
              {title}
            </h2>
          )}
          {actions && <div className="flex items-center gap-2">{actions}</div>}
        </header>
      )}
      <div className={clsx('p-4', bodyClassName)}>{children}</div>
    </section>
  );
}

/* ------------------------------------------------------------------ Badge */
export function Badge({ tone = 'neutral', children, className }: { tone?: 'neutral' | 'profit' | 'loss' | 'warn' | 'accent' | 'info'; children: ReactNode; className?: string }) {
  return (
    <span
      className={clsx(
        'inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-2xs font-semibold uppercase tracking-wide',
        tone === 'neutral' && 'bg-panel2 text-muted',
        tone === 'profit' && 'bg-profit/15 text-profit',
        tone === 'loss' && 'bg-loss/15 text-loss',
        tone === 'warn' && 'bg-warn/15 text-warn',
        tone === 'accent' && 'bg-accent/15 text-accent',
        tone === 'info' && 'bg-info/15 text-info',
        className,
      )}
    >
      {children}
    </span>
  );
}

export const DemoBadge = () => (
  <Badge tone="warn" className="whitespace-nowrap">
    <AlertTriangle className="h-3 w-3" aria-hidden /> DEMO DATA
  </Badge>
);

/* ------------------------------------------------------------------ P&L value (icon + sign + colour, never colour alone) */
export function Pnl({ value, currency = 'USD', className, compact }: { value: string | number | null | undefined; currency?: string; className?: string; compact?: boolean }) {
  const n = Number(value);
  const Icon = value === null || value === undefined || n === 0 ? Minus : n > 0 ? TrendingUp : TrendingDown;
  return (
    <span className={clsx('num inline-flex items-center gap-1', pnlTone(value), className)}>
      <Icon className="h-3.5 w-3.5 shrink-0" aria-hidden />
      {fmtMoney(value, currency, { sign: true, compact })}
      <span className="sr-only">{n > 0 ? 'profit' : n < 0 ? 'loss' : ''}</span>
    </span>
  );
}

export function Stat({ label, value, sub, tone }: { label: string; value: ReactNode; sub?: ReactNode; tone?: string }) {
  return (
    <div className="panel px-3 py-2.5">
      <div className="label">{label}</div>
      <div className={clsx('num mt-1 text-lg font-semibold', tone)}>{value}</div>
      {sub && <div className="mt-0.5 text-2xs text-muted">{sub}</div>}
    </div>
  );
}

/* ------------------------------------------------------------------ States */
export function Spinner({ label = 'Loading…' }: { label?: string }) {
  return (
    <div role="status" className="flex items-center justify-center gap-2 p-6 text-sm text-muted">
      <Loader2 className="h-4 w-4 animate-spin" aria-hidden /> {label}
    </div>
  );
}

export function EmptyState({ icon, title, children }: { icon?: ReactNode; title: string; children?: ReactNode }) {
  return (
    <div className="flex flex-col items-center justify-center gap-2 px-4 py-10 text-center">
      {icon && <div className="text-muted">{icon}</div>}
      <p className="max-w-md text-sm text-muted">{title}</p>
      {children}
    </div>
  );
}

export function ErrorState({ error, onRetry, message }: { error?: unknown; onRetry?: () => void; message?: string }) {
  const text = message ?? (error instanceof Error ? error.message : 'Something went wrong.');
  return (
    <div role="alert" className="flex flex-col items-center gap-3 px-4 py-8 text-center">
      <AlertTriangle className="h-5 w-5 text-warn" aria-hidden />
      <p className="max-w-md text-sm text-muted">{text}</p>
      {onRetry && (
        <Button size="sm" onClick={onRetry} icon={<RefreshCw className="h-3.5 w-3.5" />}>
          Retry
        </Button>
      )}
    </div>
  );
}

/* ------------------------------------------------------------------ Form fields (labels + accessible errors) */
export function Field({ label, error, hint, children, htmlFor, className }: { label: string; error?: string; hint?: string; children: ReactNode; htmlFor: string; className?: string }) {
  return (
    <div className={clsx('flex flex-col gap-1', className)}>
      <label htmlFor={htmlFor} className="label">
        {label}
      </label>
      {children}
      {hint && !error && <p className="text-2xs text-muted">{hint}</p>}
      {error && (
        <p id={`${htmlFor}-error`} role="alert" className="text-2xs text-loss">
          {error}
        </p>
      )}
    </div>
  );
}

const inputCls =
  'h-10 w-full rounded-md border border-line bg-panel2 px-3 text-sm text-fg placeholder:text-muted/70 focus:border-accent focus:outline-none disabled:opacity-60';

export const Input = forwardRef<HTMLInputElement, InputHTMLAttributes<HTMLInputElement> & { invalid?: boolean }>(function Input({ className, invalid, ...rest }, ref) {
  return <input ref={ref} aria-invalid={invalid || undefined} aria-describedby={invalid && rest.id ? `${rest.id}-error` : undefined} className={clsx(inputCls, invalid && 'border-loss', className)} {...rest} />;
});

export const Select = forwardRef<HTMLSelectElement, SelectHTMLAttributes<HTMLSelectElement> & { invalid?: boolean }>(function Select({ className, invalid, children, ...rest }, ref) {
  return (
    <select ref={ref} aria-invalid={invalid || undefined} className={clsx(inputCls, 'pr-8', invalid && 'border-loss', className)} {...rest}>
      {children}
    </select>
  );
});

export const Textarea = forwardRef<HTMLTextAreaElement, TextareaHTMLAttributes<HTMLTextAreaElement>>(function Textarea({ className, ...rest }, ref) {
  return <textarea ref={ref} className={clsx(inputCls, 'h-auto min-h-24 py-2', className)} {...rest} />;
});

/* ------------------------------------------------------------------ Modal (dialog on desktop, bottom sheet on mobile) */
export function Modal({ open, onClose, title, children, footer, wide }: { open: boolean; onClose: () => void; title: string; children: ReactNode; footer?: ReactNode; wide?: boolean }) {
  const ref = useRef<HTMLDivElement>(null);
  const titleId = useId();
  useEffect(() => {
    if (!open) return;
    const previouslyFocused = document.activeElement as HTMLElement | null;
    const el = ref.current;
    const focusables = () => Array.from(el?.querySelectorAll<HTMLElement>('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])') ?? []).filter((x) => !x.hasAttribute('disabled'));
    (focusables()[0] ?? el)?.focus();
    const onKey = (e: KeyboardEvent) => {
      if (e.key === 'Escape') onClose();
      if (e.key === 'Tab') {
        const f = focusables();
        if (f.length === 0) return;
        const first = f[0]!;
        const last = f[f.length - 1]!;
        if (e.shiftKey && document.activeElement === first) {
          e.preventDefault();
          last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
          e.preventDefault();
          first.focus();
        }
      }
    };
    document.addEventListener('keydown', onKey);
    const overflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
    return () => {
      document.removeEventListener('keydown', onKey);
      document.body.style.overflow = overflow;
      previouslyFocused?.focus?.();
    };
  }, [open, onClose]);
  if (!open) return null;
  return createPortal(
    <div className="fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-4">
      <div className="absolute inset-0 bg-black/60" onClick={onClose} aria-hidden />
      <div
        ref={ref}
        role="dialog"
        aria-modal="true"
        aria-labelledby={titleId}
        tabIndex={-1}
        className={clsx(
          'relative flex max-h-[92vh] w-full flex-col rounded-t-xl border border-line bg-panel shadow-2xl sm:rounded-xl',
          wide ? 'sm:max-w-3xl' : 'sm:max-w-lg',
        )}
      >
        <header className="flex items-center justify-between border-b border-line px-4 py-3">
          <h2 id={titleId} className="text-sm font-semibold">
            {title}
          </h2>
          <button onClick={onClose} className="rounded p-1.5 text-muted hover:bg-panel2 hover:text-fg" aria-label="Close dialog">
            <X className="h-4 w-4" />
          </button>
        </header>
        <div className="overflow-y-auto px-4 py-4">{children}</div>
        {footer && <footer className="flex flex-wrap justify-end gap-2 border-t border-line px-4 py-3 pb-[max(0.75rem,env(safe-area-inset-bottom))]">{footer}</footer>}
      </div>
    </div>,
    document.body,
  );
}

export function ConfirmDialog({
  open,
  onClose,
  onConfirm,
  title,
  message,
  confirmLabel = 'Delete',
  loading,
  requireText,
}: {
  open: boolean;
  onClose: () => void;
  onConfirm: () => void;
  title: string;
  message: ReactNode;
  confirmLabel?: string;
  loading?: boolean;
  requireText?: string;
}) {
  const [typed, setTyped] = useState('');
  const id = useId();
  useEffect(() => {
    if (!open) setTyped('');
  }, [open]);
  const blocked = requireText !== undefined && typed.trim().toLowerCase() !== requireText.toLowerCase();
  return (
    <Modal
      open={open}
      onClose={onClose}
      title={title}
      footer={
        <>
          <Button onClick={onClose}>Cancel</Button>
          <Button variant="danger" onClick={onConfirm} loading={loading} disabled={blocked}>
            {confirmLabel}
          </Button>
        </>
      }
    >
      <div className="space-y-3 text-sm text-muted">{message}</div>
      {requireText !== undefined && (
        <Field label={`Type ${requireText} to confirm`} htmlFor={id} className="mt-4">
          <Input id={id} value={typed} onChange={(e) => setTyped(e.target.value)} autoComplete="off" />
        </Field>
      )}
    </Modal>
  );
}

/* ------------------------------------------------------------------ Tabs */
export function Tabs<T extends string>({ value, onChange, items, label }: { value: T; onChange: (v: T) => void; items: Array<{ value: T; label: string }>; label: string }) {
  return (
    <div role="tablist" aria-label={label} className="scrollbar-thin flex gap-1 overflow-x-auto rounded-md border border-line bg-panel p-1">
      {items.map((it) => (
        <button
          key={it.value}
          role="tab"
          aria-selected={value === it.value}
          onClick={() => onChange(it.value)}
          className={clsx('min-h-8 whitespace-nowrap rounded px-3 py-1.5 text-xs font-medium', value === it.value ? 'bg-accent text-accent-fg' : 'text-muted hover:text-fg')}
        >
          {it.label}
        </button>
      ))}
    </div>
  );
}

/* ------------------------------------------------------------------ Toasts */
interface Toast {
  id: number;
  tone: 'success' | 'error' | 'info';
  text: string;
}
const ToastCtx = createContext<(tone: Toast['tone'], text: string) => void>(() => undefined);

export function ToastProvider({ children }: { children: ReactNode }) {
  const [toasts, setToasts] = useState<Toast[]>([]);
  const push = useCallback((tone: Toast['tone'], text: string) => {
    const id = Date.now() + Math.random();
    setToasts((t) => [...t, { id, tone, text }]);
    setTimeout(() => setToasts((t) => t.filter((x) => x.id !== id)), 4500);
  }, []);
  return (
    <ToastCtx.Provider value={push}>
      {children}
      <div aria-live="polite" className="pointer-events-none fixed bottom-20 right-4 z-[60] flex w-[min(92vw,22rem)] flex-col gap-2 lg:bottom-4">
        {toasts.map((t) => (
          <div key={t.id} className="pointer-events-auto flex items-start gap-2 rounded-md border border-line bg-panel px-3 py-2 text-sm shadow-xl" role={t.tone === 'error' ? 'alert' : 'status'}>
            {t.tone === 'success' ? <CheckCircle2 className="mt-0.5 h-4 w-4 text-profit" aria-hidden /> : t.tone === 'error' ? <AlertTriangle className="mt-0.5 h-4 w-4 text-loss" aria-hidden /> : <Info className="mt-0.5 h-4 w-4 text-info" aria-hidden />}
            <span>{t.text}</span>
          </div>
        ))}
      </div>
    </ToastCtx.Provider>
  );
}

export const useToast = () => useContext(ToastCtx);
