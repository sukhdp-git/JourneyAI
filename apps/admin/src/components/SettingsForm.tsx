import { useEffect, useMemo, useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { RotateCcw } from 'lucide-react';
import { del, errMsg, patch } from '../lib/api';
import { useAuth } from '../lib/auth';
import type { Setting, SettingsResponse } from '../lib/types';
import { Badge, Button, Field, Switch, fmtDateTime, useToast } from './ui';

export function SourceBadge({ s }: { s: Setting }) {
  if (s.source === 'control-panel') return <Badge tone="indigo">Set in control panel</Badge>;
  if (s.source === 'environment') return <Badge tone="blue">From environment ({s.envVar})</Badge>;
  if (s.source === 'default') return <Badge>Default</Badge>;
  return <Badge tone="amber">Not set</Badge>;
}

type Draft = Record<string, string | boolean>;

/** Edits a group of runtime settings. Secret fields are write-only: they are never pre-filled. */
export function SettingsForm({ settings, keys, onSaved }: { settings: Setting[]; keys: string[]; onSaved?: () => void }) {
  const { can } = useAuth();
  const readOnly = !can('admin');
  const qc = useQueryClient();
  const toast = useToast();
  const items = useMemo(() => keys.map((k) => settings.find((s) => s.key === k)).filter((s): s is Setting => !!s), [settings, keys]);
  const initial = useMemo(() => Object.fromEntries(items.filter((s) => s.kind !== 'secret').map((s) => [s.key, s.value ?? (s.kind === 'boolean' ? false : '')])) as Draft, [items]);
  const [draft, setDraft] = useState<Draft>(initial);
  const [secrets, setSecrets] = useState<Record<string, string>>({});
  useEffect(() => {
    setDraft(initial);
    setSecrets({});
  }, [initial]);

  const changes = useMemo(() => {
    const c: Draft = {};
    for (const s of items) {
      if (s.kind === 'secret') {
        if (secrets[s.key]) c[s.key] = secrets[s.key]!;
      } else if (draft[s.key] !== initial[s.key]) c[s.key] = draft[s.key]!;
    }
    return c;
  }, [items, draft, secrets, initial]);

  const save = useMutation({
    mutationFn: () => patch<SettingsResponse>('/settings', changes),
    onSuccess: (data) => {
      qc.setQueryData(['settings'], data);
      void qc.invalidateQueries({ queryKey: ['overview'] });
      toast(true, 'Saved — changes are live on the website now');
      onSaved?.();
    },
    onError: (e) => toast(false, errMsg(e)),
  });
  const reset = useMutation({
    mutationFn: (key: string) => del<SettingsResponse>(`/settings/${encodeURIComponent(key)}`),
    onSuccess: (data) => {
      qc.setQueryData(['settings'], data);
      toast(true, 'Reverted to environment / default value');
    },
    onError: (e) => toast(false, errMsg(e)),
  });

  return (
    <div>
      <div className="divide-y divide-slate-100">
        {items.map((s) => {
          const id = `set-${s.key.replace(/\./g, '-')}`;
          const resetBtn =
            s.source === 'control-panel' && !readOnly ? (
              <button type="button" onClick={() => reset.mutate(s.key)} className="inline-flex items-center gap-1 text-xs text-slate-500 hover:text-slate-800">
                <RotateCcw className="h-3 w-3" aria-hidden /> Revert
              </button>
            ) : null;
          if (s.kind === 'boolean') {
            return (
              <div key={s.key}>
                <Switch id={id} checked={draft[s.key] === true} onChange={(v) => setDraft({ ...draft, [s.key]: v })} label={s.label} description={s.help} disabled={readOnly} />
              </div>
            );
          }
          const meta = (
            <span className="flex flex-wrap items-center gap-2">
              <SourceBadge s={s} />
              {s.updatedAt && <span className="text-xs text-slate-400">updated {fmtDateTime(s.updatedAt)}</span>}
              {resetBtn}
            </span>
          );
          return (
            <div key={s.key} className="py-4">
              <Field label={s.label} htmlFor={id} hint={s.help}>
                {s.kind === 'secret' ? (
                  <div className="space-y-2">
                    <input
                      id={id}
                      type="password"
                      autoComplete="new-password"
                      className="input font-mono"
                      disabled={readOnly}
                      placeholder={s.configured ? `Configured (${s.hint}) — type to replace` : 'Not configured — paste the key'}
                      value={secrets[s.key] ?? ''}
                      onChange={(e) => setSecrets({ ...secrets, [s.key]: e.target.value })}
                    />
                    <p className="text-xs text-slate-500">Write-only. Stored encrypted (AES-256-GCM); never shown again after saving.</p>
                  </div>
                ) : s.kind === 'enum' ? (
                  <select id={id} className="input" disabled={readOnly} value={String(draft[s.key] ?? '')} onChange={(e) => setDraft({ ...draft, [s.key]: e.target.value })}>
                    {s.options?.map((o) => (
                      <option key={o} value={o}>
                        {o}
                      </option>
                    ))}
                  </select>
                ) : s.kind === 'text' ? (
                  <textarea id={id} rows={3} className="input h-auto py-2" disabled={readOnly} value={String(draft[s.key] ?? '')} onChange={(e) => setDraft({ ...draft, [s.key]: e.target.value })} />
                ) : (
                  <input
                    id={id}
                    type={s.kind === 'email' ? 'email' : s.kind === 'url' ? 'url' : 'text'}
                    className="input"
                    disabled={readOnly}
                    value={String(draft[s.key] ?? '')}
                    onChange={(e) => setDraft({ ...draft, [s.key]: e.target.value })}
                  />
                )}
              </Field>
              <div className="mt-2">{meta}</div>
            </div>
          );
        })}
      </div>
      {!readOnly && (
        <div className="mt-4 flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
          {Object.keys(changes).length > 0 && <span className="text-xs text-slate-500">{Object.keys(changes).length} unsaved change(s)</span>}
          <Button
            onClick={() => {
              setDraft(initial);
              setSecrets({});
            }}
            disabled={Object.keys(changes).length === 0}
          >
            Discard
          </Button>
          <Button variant="primary" loading={save.isPending} disabled={Object.keys(changes).length === 0} onClick={() => save.mutate()}>
            Save changes
          </Button>
        </div>
      )}
      {readOnly && <p className="mt-4 text-xs text-slate-500">Viewer role: read-only.</p>}
    </div>
  );
}
