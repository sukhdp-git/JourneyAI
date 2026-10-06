/** Control-panel API client: same-origin, HttpOnly admin cookie, CSRF header on writes. */
export class ApiError extends Error {
  constructor(
    readonly status: number,
    readonly code: string,
    message: string,
    readonly fields?: Record<string, string[]>,
  ) {
    super(message);
  }
}

const BASE = '/api/v1/admin';
let csrf: string | null = null;

export const setCsrf = (t: string | null) => {
  csrf = t;
};

async function ensureCsrf(): Promise<string> {
  if (csrf) return csrf;
  const r = await fetch(`${BASE}/auth/csrf`, { credentials: 'include' });
  const b = (await r.json()) as { csrfToken: string };
  csrf = b.csrfToken;
  return csrf;
}

export async function api<T>(method: 'GET' | 'POST' | 'PATCH' | 'DELETE', path: string, body?: unknown, retried = false): Promise<T> {
  const headers: Record<string, string> = { Accept: 'application/json' };
  if (method !== 'GET') headers['X-CSRF-Token'] = await ensureCsrf();
  if (body !== undefined) headers['Content-Type'] = 'application/json';
  const res = await fetch(`${BASE}${path}`, { method, headers, credentials: 'include', body: body === undefined ? undefined : JSON.stringify(body) });
  if (res.status === 204) return undefined as T;
  const data = await res.json().catch(() => null);
  if (!res.ok) {
    const err = (data as { error?: { code: string; message: string; fields?: Record<string, string[]> } } | null)?.error;
    if (res.status === 403 && err?.code?.startsWith('CSRF') && !retried) {
      csrf = null;
      return api<T>(method, path, body, true);
    }
    if (res.status === 401 && !path.startsWith('/auth/')) window.dispatchEvent(new CustomEvent('cp:unauthenticated'));
    throw new ApiError(res.status, err?.code ?? 'HTTP_ERROR', err?.message ?? `Request failed (${res.status})`, err?.fields);
  }
  return data as T;
}

export const get = <T>(p: string) => api<T>('GET', p);
export const post = <T>(p: string, b?: unknown) => api<T>('POST', p, b ?? {});
export const patch = <T>(p: string, b: unknown) => api<T>('PATCH', p, b);
export const del = <T = void>(p: string, b?: unknown) => api<T>('DELETE', p, b);

export const errMsg = (e: unknown) =>
  e instanceof ApiError && e.fields ? `${e.message}: ${Object.values(e.fields).flat().join('; ')}` : e instanceof Error ? e.message : 'Something went wrong';
