import { useEffect, useRef, useState, type FormEvent } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import clsx from 'clsx';
import { Bot, Plus, Send, Trash2, User } from 'lucide-react';
import { LANGUAGES, type AiConversationDto, type AiMessageDto, type Language } from '@journzey/shared';
import { Button, EmptyState, ErrorState, Panel, Select, Spinner, useToast } from '../../components/ui';
import { PageHeader } from '../../components/common';
import { PerformanceMessenger } from '../../components/PerformanceMessenger';
import { ApiError, del, get, post } from '../../lib/api';
import { qk, useMe, useScope } from '../../lib/queries';

const PROMPTS = [
  'Analyze my emotional leaks',
  'What is my highest-edge setup?',
  'Review my risk management',
  'What changed this month?',
  'Which trading session is hurting me?',
  'Compare disciplined vs emotional trades',
];
const LANG_NAMES: Record<Language, string> = { en: 'English', ru: 'Русский', zh: '中文', pt: 'Português' };

export default function AiCoach() {
  const { data: me } = useMe();
  const { scope } = useScope();
  const qc = useQueryClient();
  const toast = useToast();
  const configured = !!me?.features.ai;
  const [conversationId, setConversationId] = useState<string | null>(null);
  const [input, setInput] = useState('');
  const [lang, setLang] = useState<Language>(me?.settings?.language ?? 'en');
  const [pending, setPending] = useState<string | null>(null);
  const endRef = useRef<HTMLDivElement>(null);

  const convs = useQuery({ queryKey: [...qk.ai, 'conversations'], queryFn: () => get<AiConversationDto[]>('/ai/conversations'), enabled: configured });
  const msgs = useQuery({
    queryKey: [...qk.ai, 'messages', conversationId],
    queryFn: () => get<AiMessageDto[]>(`/ai/conversations/${conversationId}/messages`),
    enabled: configured && !!conversationId,
  });
  const send = useMutation({
    mutationFn: (message: string) => post<{ conversationId: string; messages: AiMessageDto[] }>('/ai/chat', { conversationId: conversationId ?? undefined, message, language: lang, account: scope }),
    onMutate: (m) => setPending(m),
    onSuccess: (res) => {
      setConversationId(res.conversationId);
      setInput('');
      void qc.invalidateQueries({ queryKey: qk.ai });
    },
    onSettled: () => setPending(null),
  });
  const remove = useMutation({
    mutationFn: (id: string) => del(`/ai/conversations/${id}`),
    onSuccess: (_d, id) => {
      if (id === conversationId) setConversationId(null);
      void qc.invalidateQueries({ queryKey: qk.ai });
      toast('success', 'Conversation deleted');
    },
  });
  useEffect(() => endRef.current?.scrollIntoView({ behavior: 'smooth', block: 'end' }), [msgs.data, pending]);

  const submit = (e?: FormEvent, text?: string) => {
    e?.preventDefault();
    const m = (text ?? input).trim();
    if (m && !send.isPending) send.mutate(m);
  };

  if (!configured) {
    return (
      <>
        <PageHeader title="AI Coach" subtitle="Journal-aware, trade-aware analysis" />
        <Panel>
          <EmptyState icon={<Bot className="h-8 w-8" />} title="AI Coach requires server configuration.">
            <p className="max-w-md text-2xs text-muted">An administrator must set AI_API_KEY on the API server. Your journal and analytics remain fully available, and the Performance Messenger below still generates reviews from your data.</p>
          </EmptyState>
        </Panel>
        <div className="mt-4">
          <PerformanceMessenger />
        </div>
      </>
    );
  }

  const messages = msgs.data ?? [];
  return (
    <>
      <PageHeader
        title="AI Coach"
        subtitle="Grounded in server-aggregated statistics from your account scope — not financial advice"
        actions={
          <>
            <label htmlFor="ai-lang" className="sr-only">
              Response language
            </label>
            <Select id="ai-lang" className="h-9 w-auto text-xs" value={lang} onChange={(e) => setLang(e.target.value as Language)}>
              {LANGUAGES.map((l) => (
                <option key={l} value={l}>
                  {LANG_NAMES[l]}
                </option>
              ))}
            </Select>
            <Button size="sm" icon={<Plus className="h-4 w-4" />} onClick={() => setConversationId(null)}>
              New chat
            </Button>
          </>
        }
      />
      <div className="grid gap-4 lg:grid-cols-[16rem_1fr]">
        <Panel title="Conversations" bodyClassName="p-0" className="hidden lg:block">
          {convs.isLoading ? (
            <Spinner />
          ) : (convs.data ?? []).length === 0 ? (
            <p className="p-3 text-xs text-muted">No conversations yet.</p>
          ) : (
            <ul className="max-h-[65vh] divide-y divide-line overflow-y-auto">
              {convs.data!.map((c) => (
                <li key={c.id} className={clsx('flex items-center', c.id === conversationId && 'bg-accent/10')}>
                  <button onClick={() => setConversationId(c.id)} className="min-w-0 flex-1 truncate px-3 py-2.5 text-left text-xs">
                    {c.title}
                  </button>
                  <button onClick={() => remove.mutate(c.id)} aria-label={`Delete conversation ${c.title}`} className="p-2 text-muted hover:text-loss">
                    <Trash2 className="h-3.5 w-3.5" />
                  </button>
                </li>
              ))}
            </ul>
          )}
        </Panel>
        <Panel bodyClassName="flex min-h-[60vh] flex-col p-0">
          <div className="flex-1 space-y-3 overflow-y-auto p-4" aria-live="polite">
            {!conversationId && messages.length === 0 && !pending && (
              <div>
                <p className="text-sm text-muted">Ask about your own trading. Suggested prompts:</p>
                <div className="mt-3 flex flex-wrap gap-2">
                  {PROMPTS.map((p) => (
                    <button key={p} onClick={() => submit(undefined, p)} className="rounded-full border border-line px-3 py-2 text-xs hover:border-accent hover:text-accent">
                      {p}
                    </button>
                  ))}
                </div>
              </div>
            )}
            {msgs.isError && <ErrorState error={msgs.error} onRetry={() => msgs.refetch()} />}
            {messages.map((m) => (
              <div key={m.id} className={clsx('flex gap-2', m.role === 'user' && 'flex-row-reverse')}>
                <span className={clsx('flex h-7 w-7 shrink-0 items-center justify-center rounded-full', m.role === 'user' ? 'bg-panel2' : 'bg-accent/20 text-accent')} aria-hidden>
                  {m.role === 'user' ? <User className="h-4 w-4" /> : <Bot className="h-4 w-4" />}
                </span>
                <div className={clsx('max-w-[85%] whitespace-pre-wrap rounded-lg px-3 py-2 text-sm leading-relaxed', m.role === 'user' ? 'bg-accent text-accent-fg' : 'bg-panel2')}>
                  <span className="sr-only">{m.role === 'user' ? 'You said:' : 'AI Coach said:'}</span>
                  {m.content}
                </div>
              </div>
            ))}
            {pending && (
              <>
                <div className="flex flex-row-reverse gap-2">
                  <div className="max-w-[85%] whitespace-pre-wrap rounded-lg bg-accent px-3 py-2 text-sm text-accent-fg">{pending}</div>
                </div>
                <Spinner label="AI Coach is analyzing your statistics…" />
              </>
            )}
            {send.error && (
              <p role="alert" className="text-sm text-loss">
                {send.error instanceof ApiError ? send.error.message : 'AI Coach is unavailable right now. Your journal and analytics remain available.'}
              </p>
            )}
            <div ref={endRef} />
          </div>
          <form onSubmit={submit} className="flex gap-2 border-t border-line p-3">
            <label htmlFor="ai-input" className="sr-only">
              Message the AI Coach
            </label>
            <textarea
              id="ai-input"
              value={input}
              onChange={(e) => setInput(e.target.value)}
              onKeyDown={(e) => {
                if (e.key === 'Enter' && !e.shiftKey) submit(e);
              }}
              maxLength={4000}
              rows={2}
              placeholder="Ask about your trades, journal or risk… (Enter to send, Shift+Enter for a new line)"
              className="min-h-11 flex-1 resize-none rounded-md border border-line bg-panel2 px-3 py-2 text-sm focus:border-accent focus:outline-none"
            />
            <Button type="submit" variant="primary" loading={send.isPending} disabled={!input.trim()} aria-label="Send message" icon={<Send className="h-4 w-4" />} />
          </form>
        </Panel>
      </div>
      <div className="mt-4">
        <PerformanceMessenger />
      </div>
    </>
  );
}
