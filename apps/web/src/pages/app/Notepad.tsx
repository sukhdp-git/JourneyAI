import { useEffect, useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import clsx from 'clsx';
import { ChevronLeft, ChevronRight, Mic, MicOff, Trash2 } from 'lucide-react';
import { EMOTIONS, LANGUAGES, VOICE_LANGUAGES, type JournalEntryDto, type Language } from '@journzey/shared';
import { Button, ConfirmDialog, DemoBadge, EmptyState, ErrorState, Field, Input, Panel, Select, Spinner, Textarea, useToast } from '../../components/ui';
import { PageHeader } from '../../components/common';
import { PerformanceMessenger } from '../../components/PerformanceMessenger';
import { del, get, post } from '../../lib/api';
import { humanize, shiftDate, todayIn } from '../../lib/format';
import { qk, useScope, useSettings } from '../../lib/queries';
import { useDictation } from '../../lib/speech';

interface Draft {
  compliance: number | null;
  emotionalState: string;
  disciplineRating: number | null;
  reflection: string;
  keyLesson: string;
  voiceTranscript: string;
}
const EMPTY: Draft = { compliance: null, emotionalState: '', disciplineRating: null, reflection: '', keyLesson: '', voiceTranscript: '' };
const LANG_NAMES: Record<Language, string> = { en: 'English', ru: 'Русский', zh: '中文', pt: 'Português' };
const draftKey = (date: string) => `jz.journal-draft.${date}`;

export default function Notepad() {
  const settings = useSettings();
  const { scope } = useScope();
  const qc = useQueryClient();
  const toast = useToast();
  const showDemo = scope === 'demo';
  const [date, setDate] = useState(() => todayIn(settings.timezone));
  const [form, setForm] = useState<Draft>(EMPTY);
  const [dirty, setDirty] = useState(false);
  const [voiceLang, setVoiceLang] = useState<Language>(settings.language);
  const [toDelete, setToDelete] = useState<JournalEntryDto | null>(null);
  const history = useQuery({ queryKey: qk.journal({ demo: showDemo }), queryFn: () => get<JournalEntryDto[]>('/journal', { demo: showDemo, limit: 120 }) });
  const entry = useMemo(() => history.data?.find((j) => j.journalDate === date) ?? null, [history.data, date]);

  // Load server entry, or a locally cached unsaved draft (harmless convenience cache only).
  useEffect(() => {
    let draft: Draft | null = null;
    try {
      const raw = localStorage.getItem(draftKey(date));
      if (raw) draft = JSON.parse(raw) as Draft;
    } catch {
      /* ignore */
    }
    if (draft) {
      setForm(draft);
      setDirty(true);
    } else if (entry) {
      setForm({
        compliance: entry.compliance,
        emotionalState: entry.emotionalState ?? '',
        disciplineRating: entry.disciplineRating,
        reflection: entry.reflection ?? '',
        keyLesson: entry.keyLesson ?? '',
        voiceTranscript: entry.voiceTranscript ?? '',
      });
      setDirty(false);
    } else {
      setForm(EMPTY);
      setDirty(false);
    }
  }, [date, entry]);

  const update = (patch: Partial<Draft>) => {
    setForm((f) => {
      const next = { ...f, ...patch };
      try {
        localStorage.setItem(draftKey(date), JSON.stringify(next));
      } catch {
        /* ignore */
      }
      return next;
    });
    setDirty(true);
  };

  const dictation = useDictation(VOICE_LANGUAGES[voiceLang], (text) => {
    setForm((f) => {
      const next = { ...f, voiceTranscript: [f.voiceTranscript, text].filter(Boolean).join(' '), reflection: [f.reflection, text].filter(Boolean).join(f.reflection ? ' ' : '') };
      try {
        localStorage.setItem(draftKey(date), JSON.stringify(next));
      } catch {
        /* ignore */
      }
      return next;
    });
    setDirty(true);
  });

  const save = useMutation({
    mutationFn: () =>
      post<JournalEntryDto>('/journal', {
        journalDate: date,
        compliance: form.compliance,
        emotionalState: form.emotionalState || null,
        disciplineRating: form.disciplineRating,
        reflection: form.reflection || null,
        keyLesson: form.keyLesson || null,
        voiceTranscript: form.voiceTranscript || null,
        voiceLanguage: form.voiceTranscript ? voiceLang : null,
      }),
    onSuccess: () => {
      try {
        localStorage.removeItem(draftKey(date));
      } catch {
        /* ignore */
      }
      setDirty(false);
      void qc.invalidateQueries({ queryKey: ['journal'] });
      void qc.invalidateQueries({ queryKey: ['analytics'] });
      toast('success', `Journal saved for ${date}`);
    },
    onError: (e) => toast('error', e instanceof Error ? e.message : 'Save failed'),
  });

  const remove = useMutation({
    mutationFn: (j: JournalEntryDto) => del(`/journal/${j.id}`),
    onSuccess: () => {
      setToDelete(null);
      void qc.invalidateQueries({ queryKey: ['journal'] });
      toast('success', 'Entry deleted');
    },
  });

  const readOnly = showDemo;
  return (
    <>
      <PageHeader title="Daily Notepad" subtitle="Voice/text journal · psychology questionnaire · reviews" demo={showDemo} />
      <div className="grid gap-4 xl:grid-cols-3">
        <div className="space-y-4 xl:col-span-2">
          <Panel
            title="Entry"
            actions={
              <div className="flex items-center gap-1">
                <Button size="sm" variant="ghost" aria-label="Previous day" onClick={() => setDate(shiftDate(date, -1))}>
                  <ChevronLeft className="h-4 w-4" />
                </Button>
                <Input aria-label="Journal date" type="date" value={date} onChange={(e) => e.target.value && setDate(e.target.value)} className="h-8 w-auto text-xs" />
                <Button size="sm" variant="ghost" aria-label="Next day" onClick={() => setDate(shiftDate(date, 1))}>
                  <ChevronRight className="h-4 w-4" />
                </Button>
              </div>
            }
          >
            {readOnly && <p className="mb-3 text-2xs text-warn">Demo journal entries are read-only. Switch Account mode to your real accounts to write your own journal.</p>}
            <fieldset disabled={readOnly} className="space-y-4">
              <div className="grid gap-4 sm:grid-cols-3">
                <div>
                  <span className="label" id="compliance-label">
                    Plan compliance
                  </span>
                  <div role="radiogroup" aria-labelledby="compliance-label" className="mt-1 flex gap-1">
                    {[1, 2, 3, 4, 5].map((n) => (
                      <button
                        key={n}
                        type="button"
                        role="radio"
                        aria-checked={form.compliance === n}
                        onClick={() => update({ compliance: n })}
                        className={clsx('num h-10 w-10 rounded-md border text-sm', form.compliance === n ? 'border-accent bg-accent/15 text-accent' : 'border-line')}
                      >
                        {n}
                      </button>
                    ))}
                  </div>
                </div>
                <Field label="Emotional state" htmlFor="j-emo">
                  <Select id="j-emo" value={form.emotionalState} onChange={(e) => update({ emotionalState: e.target.value })}>
                    <option value="">—</option>
                    {EMOTIONS.map((e) => (
                      <option key={e} value={e}>
                        {humanize(e)}
                      </option>
                    ))}
                  </Select>
                </Field>
                <Field label={`Discipline rating ${form.disciplineRating ?? '–'}/10`} htmlFor="j-disc">
                  <input
                    id="j-disc"
                    type="range"
                    min={1}
                    max={10}
                    value={form.disciplineRating ?? 5}
                    onChange={(e) => update({ disciplineRating: Number(e.target.value) })}
                    className="mt-3 w-full accent-[rgb(var(--accent))]"
                  />
                </Field>
              </div>
              <Field label="Reflection" htmlFor="j-refl">
                <Textarea id="j-refl" rows={8} maxLength={10000} value={form.reflection} onChange={(e) => update({ reflection: e.target.value })} placeholder="What did you see, feel and do today? Where did you follow or break the plan?" />
              </Field>
              <Field label="Key lesson" htmlFor="j-lesson">
                <Input id="j-lesson" maxLength={1000} value={form.keyLesson} onChange={(e) => update({ keyLesson: e.target.value })} />
              </Field>
              <div className="flex flex-wrap items-center gap-2 rounded-md border border-line p-3">
                <label htmlFor="j-voice-lang" className="label">
                  Voice
                </label>
                <Select id="j-voice-lang" className="h-9 w-auto text-xs" value={voiceLang} onChange={(e) => setVoiceLang(e.target.value as Language)}>
                  {LANGUAGES.map((l) => (
                    <option key={l} value={l}>
                      {LANG_NAMES[l]}
                    </option>
                  ))}
                </Select>
                {dictation.listening ? (
                  <Button size="sm" variant="danger" icon={<MicOff className="h-4 w-4" />} onClick={dictation.stop}>
                    Stop dictation
                  </Button>
                ) : (
                  <Button size="sm" icon={<Mic className="h-4 w-4" />} onClick={dictation.start} disabled={!dictation.supported} title={dictation.supported ? undefined : 'Not supported in this browser'}>
                    Dictate
                  </Button>
                )}
                {!dictation.supported && <span className="text-2xs text-muted">Voice dictation isn’t supported in this browser — typing works everywhere.</span>}
                {dictation.listening && <span className="text-2xs text-loss">● Listening… {dictation.interim}</span>}
                {dictation.error && (
                  <span role="alert" className="text-2xs text-loss">
                    {dictation.error}
                  </span>
                )}
              </div>
              <div className="flex items-center justify-between">
                <span className="text-2xs text-muted">{dirty ? 'Unsaved draft (cached on this device)' : entry ? `Saved ${new Date(entry.updatedAt).toLocaleString()}` : 'No entry for this date yet'}</span>
                <Button variant="primary" loading={save.isPending} onClick={() => save.mutate()} disabled={!dirty}>
                  Save entry
                </Button>
              </div>
            </fieldset>
          </Panel>
          <PerformanceMessenger />
        </div>
        <Panel title="History" bodyClassName="p-0">
          {history.isLoading ? (
            <Spinner />
          ) : history.isError ? (
            <ErrorState error={history.error} onRetry={() => history.refetch()} />
          ) : history.data!.length === 0 ? (
            <EmptyState title="No journal entries yet. Reflection is where the edge compounds." />
          ) : (
            <ul className="max-h-[70vh] divide-y divide-line overflow-y-auto">
              {history.data!.map((j) => (
                <li key={j.id} className={clsx('flex items-start gap-2 px-3 py-2', j.journalDate === date && 'bg-accent/10')}>
                  <button onClick={() => setDate(j.journalDate)} className="min-w-0 flex-1 text-left">
                    <span className="num text-xs font-semibold">{j.journalDate}</span> {j.demo && <DemoBadge />}
                    <span className="mt-0.5 block text-2xs text-muted">
                      {humanize(j.emotionalState)} · discipline {j.disciplineRating ?? '–'}/10 · compliance {j.compliance ?? '–'}/5
                    </span>
                    {j.keyLesson && <span className="mt-0.5 block truncate text-xs">“{j.keyLesson}”</span>}
                  </button>
                  {!j.demo && (
                    <button onClick={() => setToDelete(j)} aria-label={`Delete entry ${j.journalDate}`} className="rounded p-2 text-muted hover:text-loss">
                      <Trash2 className="h-4 w-4" />
                    </button>
                  )}
                </li>
              ))}
            </ul>
          )}
        </Panel>
      </div>
      <ConfirmDialog open={!!toDelete} onClose={() => setToDelete(null)} onConfirm={() => toDelete && remove.mutate(toDelete)} loading={remove.isPending} title="Delete journal entry?" message={<p>The entry for {toDelete?.journalDate} will be permanently deleted.</p>} />
    </>
  );
}
