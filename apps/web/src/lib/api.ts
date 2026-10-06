import type { ApiErrorBody } from '@journzey/shared';

/** Error thrown for any non-2xx API response, carrying the standardised error envelope. */
export class ApiError extends Error {
  constructor(
    readonly status: number,
    readonly code: string,
    message: string,
    readonly fields?: Record<string, string[]>,
    readonly requestId?: string,
  ) {
    super(message);
  }
}

const BASE = '/api/v1';
let csrfToken: string | null = null;
let csrfPromise: Promise<string> | null = null;

async function fetchCsrf(): Promise<string> {
  if (!csrfPromise) {
    csrfPromise = fetch(`${BASE}/auth/csrf`, { credentials: 'include' })
      .then(async (r) => {
        if (!r.ok) throw new ApiError(r.status, 'CSRF_UNAVAILABLE', 'Could not obtain a security token');
        const body = (await r.json()) as { csrfToken: string };
        csrfToken = body.csrfToken;
        return body.csrfToken;
      })
      .finally(() => {
        csrfPromise = null;
      });
  }
  return csrfPromise;
}

export function resetCsrf() {
  csrfToken = null;
}

type Method = 'GET' | 'POST' | 'PATCH' | 'PUT' | 'DELETE';

export interface RequestOptions {
  query?: Record<string, string | number | boolean | undefined | null>;
  body?: unknown;
  form?: FormData;
  signal?: AbortSignal;
}

export function buildQuery(query?: RequestOptions['query']): string {
  if (!query) return '';
  const params = new URLSearchParams();
  for (const [k, v] of Object.entries(query)) if (v !== undefined && v !== null && v !== '') params.set(k, String(v));
  const s = params.toString();
  return s ? `?${s}` : '';
}

/**
 * Typed fetch wrapper: same-origin cookies (HttpOnly session — no tokens in JS storage),
 * automatic CSRF header on unsafe methods with one transparent refresh/retry.
 */
export async function api<T>(method: Method, path: string, opts: RequestOptions = {}, retried = false): Promise<T> {
  const headers: Record<string, string> = { Accept: 'application/json' };
  if (method !== 'GET') headers['X-CSRF-Token'] = csrfToken ?? (await fetchCsrf());
  let body: BodyInit | undefined;
  if (opts.form) body = opts.form;
  else if (opts.body !== undefined) {
    headers['Content-Type'] = 'application/json';
    body = JSON.stringify(opts.body);
  }
  const res = await fetch(`${BASE}${path}${buildQuery(opts.query)}`, { method, headers, body, credentials: 'include', signal: opts.signal });
  if (res.status === 204) return undefined as T;
  const isJson = res.headers.get('content-type')?.includes('application/json');
  const payload = isJson ? await res.json().catch(() => null) : null;
  if (!res.ok) {
    const err = (payload as ApiErrorBody | null)?.error;
    if (res.status === 403 && err?.code?.startsWith('CSRF') && !retried) {
      await fetchCsrf();
      return api<T>(method, path, opts, true);
    }
    if (res.status === 401 && typeof window !== 'undefined') window.dispatchEvent(new CustomEvent('journzey:unauthenticated'));
    throw new ApiError(res.status, err?.code ?? 'HTTP_ERROR', err?.message ?? `Request failed (${res.status})`, err?.fields, err?.requestId);
  }
  return payload as T;
}

export const get = <T>(path: string, query?: RequestOptions['query'], signal?: AbortSignal) => api<T>('GET', path, { query, signal });
export const post = <T>(path: string, body?: unknown) => api<T>('POST', path, { body });
export const patch = <T>(path: string, body?: unknown) => api<T>('PATCH', path, { body });
export const put = <T>(path: string, body?: unknown) => api<T>('PUT', path, { body });
export const del = <T = void>(path: string, body?: unknown) => api<T>('DELETE', path, { body });
export const upload = <T>(path: string, form: FormData) => api<T>('POST', path, { form });

/** Triggers a browser download of an authenticated GET endpoint (cookies are sent). */
export async function download(path: string, query: RequestOptions['query'], fallbackName: string) {
  const res = await fetch(`${BASE}${path}${buildQuery(query)}`, { credentials: 'include' });
  if (!res.ok) {
    const err = (await res.json().catch(() => null)) as ApiErrorBody | null;
    throw new ApiError(res.status, err?.error.code ?? 'HTTP_ERROR', err?.error.message ?? 'Download failed');
  }
  const blob = await res.blob();
  const disposition = res.headers.get('content-disposition') ?? '';
  const name = /filename="([^"]+)"/.exec(disposition)?.[1] ?? fallbackName;
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = name;
  document.body.appendChild(a);
  a.click();
  a.remove();
  setTimeout(() => URL.revokeObjectURL(url), 1000);
}

export const errorMessage = (e: unknown) => (e instanceof Error ? e.message : 'Something went wrong');
