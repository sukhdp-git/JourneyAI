import { createContext, useCallback, useContext, useMemo, type ReactNode } from 'react';
import type { Language } from '@journzey/shared';
import { en, type MessageKey, type Messages } from '../locales/en';
import { ru } from '../locales/ru';
import { zh } from '../locales/zh';
import { pt } from '../locales/pt';

const DICTS: Record<Language, Messages> = { en, ru, zh, pt };

interface I18n {
  lang: Language;
  t: (key: MessageKey) => string;
}

const Ctx = createContext<I18n>({ lang: 'en', t: (k) => en[k] });

export function I18nProvider({ lang, children }: { lang: Language; children: ReactNode }) {
  const t = useCallback((key: MessageKey) => DICTS[lang]?.[key] ?? en[key], [lang]);
  const value = useMemo(() => ({ lang, t }), [lang, t]);
  return <Ctx.Provider value={value}>{children}</Ctx.Provider>;
}

export const useI18n = () => useContext(Ctx);
