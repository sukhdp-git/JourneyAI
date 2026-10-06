import { useEffect, useState } from 'react';
import { useMe } from './queries';

const read = () => {
  const s = getComputedStyle(document.documentElement);
  const v = (n: string) => `rgb(${s.getPropertyValue(`--${n}`).trim().split(/\s+/).join(',')})`;
  return { accent: v('accent'), profit: v('profit'), loss: v('loss'), muted: v('muted'), line: v('line'), panel: v('panel'), fg: v('fg'), info: v('info'), warn: v('warn') };
};

/** Resolved theme colours for chart libraries that need concrete colour strings. */
export function useThemeColors() {
  const { data } = useMe();
  const [colors, setColors] = useState(read);
  useEffect(() => {
    const id = requestAnimationFrame(() => setColors(read()));
    return () => cancelAnimationFrame(id);
  }, [data?.settings?.theme]);
  return colors;
}
