import { describe, expect, it, vi, afterEach } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import { MemoryRouter } from 'react-router';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { ConfirmDialog, Pnl } from '../components/ui';
import { fmtMoney, zonedLocalToUtc, utcToZonedLocal } from '../lib/format';
import { resolveRange } from '../lib/dateRange';
import { buildQuery } from '../lib/api';
import { validateScreenshotFile } from '../components/trade/TradeFormModal';
import Login from '../pages/Login';

afterEach(() => vi.restoreAllMocks());

describe('formatting & helpers', () => {
  it('formats money with explicit sign', () => {
    expect(fmtMoney('-300', 'USD')).toBe('−$300.00');
    expect(fmtMoney('600', 'USD', { sign: true })).toBe('+$600.00');
  });
  it('round-trips user-time-zone datetimes through UTC', () => {
    const utc = zonedLocalToUtc('2026-08-03T09:14', 'Europe/London');
    expect(utc).toBe('2026-08-03T08:14:00.000Z');
    expect(utcToZonedLocal(utc, 'Europe/London')).toBe('2026-08-03T09:14');
  });
  it('resolves date presets', () => {
    expect(resolveRange('all', 'UTC')).toEqual({ preset: 'all' });
    const m = resolveRange('lastMonth', 'UTC');
    expect(m.from?.endsWith('-01')).toBe(true);
  });
  it('omits empty query params', () => {
    expect(buildQuery({ a: 1, b: '', c: undefined, d: 'x' })).toBe('?a=1&d=x');
  });
  it('validates screenshot files client-side', () => {
    expect(validateScreenshotFile(new File(['x'], 'a.gif', { type: 'image/gif' }))).toMatch(/Only PNG/);
    expect(validateScreenshotFile(new File(['x'], 'a.png', { type: 'image/png' }))).toBeNull();
  });
});

describe('components', () => {
  it('Pnl never relies on colour alone', () => {
    render(<Pnl value="-120.5" currency="USD" />);
    expect(screen.getByText('loss')).toBeInTheDocument();
    expect(screen.getByText(/−\$120\.50/)).toBeInTheDocument();
  });

  it('ConfirmDialog requires typed confirmation for destructive actions', () => {
    const onConfirm = vi.fn();
    render(<ConfirmDialog open onClose={() => undefined} onConfirm={onConfirm} title="Delete account?" message="gone" requireText="me@example.com" />);
    const btn = screen.getByRole('button', { name: 'Delete' });
    expect(btn).toBeDisabled();
    fireEvent.change(screen.getByLabelText(/Type me@example.com/), { target: { value: 'me@example.com' } });
    expect(btn).not.toBeDisabled();
    fireEvent.click(btn);
    expect(onConfirm).toHaveBeenCalled();
  });

  it('Login renders a real Google OAuth link and a configuration warning when disabled', async () => {
    vi.spyOn(globalThis, 'fetch').mockImplementation(async (input) => {
      const url = String(input);
      if (url.includes('/auth/me')) return new Response(JSON.stringify({ error: { code: 'UNAUTHENTICATED', message: 'x' } }), { status: 401, headers: { 'content-type': 'application/json' } });
      return new Response(JSON.stringify({ google: false, devLogin: false }), { status: 200, headers: { 'content-type': 'application/json' } });
    });
    const qc = new QueryClient({ defaultOptions: { queries: { retry: false } } });
    render(
      <QueryClientProvider client={qc}>
        <MemoryRouter initialEntries={['/login']}>
          <Login />
        </MemoryRouter>
      </QueryClientProvider>,
    );
    const link = await screen.findByRole('link', { name: /Continue with Google/ });
    expect(link).toHaveAttribute('href', '/api/v1/auth/google');
    expect(await screen.findByText(/Google sign-in is not configured/)).toBeInTheDocument();
  });
});
