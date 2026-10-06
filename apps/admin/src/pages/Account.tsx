import { useState } from 'react';
import { useMutation } from '@tanstack/react-query';
import { ShieldCheck } from 'lucide-react';
import { errMsg, post } from '../lib/api';
import { useAuth } from '../lib/auth';
import { Badge, Button, Card, Field, PageHeader, useToast } from '../components/ui';

export default function Account() {
  const { admin, refresh } = useAuth();
  const toast = useToast();
  const [pw, setPw] = useState({ currentPassword: '', newPassword: '', confirm: '' });
  const [setup, setSetup] = useState<{ secret: string; qrDataUrl: string } | null>(null);
  const [code, setCode] = useState('');
  const [disablePw, setDisablePw] = useState('');
  const change = useMutation({
    mutationFn: () => post('/auth/password', { currentPassword: pw.currentPassword, newPassword: pw.newPassword }),
    onSuccess: () => (setPw({ currentPassword: '', newPassword: '', confirm: '' }), toast(true, 'Password changed — other sessions were signed out')),
    onError: (e) => toast(false, errMsg(e)),
  });
  const start = useMutation({ mutationFn: () => post<{ secret: string; qrDataUrl: string }>('/auth/totp/setup'), onSuccess: setSetup, onError: (e) => toast(false, errMsg(e)) });
  const enable = useMutation({
    mutationFn: () => post('/auth/totp/enable', { code }),
    onSuccess: async () => (setSetup(null), setCode(''), await refresh(), toast(true, 'Two-factor authentication enabled')),
    onError: (e) => toast(false, errMsg(e)),
  });
  const disable = useMutation({
    mutationFn: () => post('/auth/totp/disable', { password: disablePw }),
    onSuccess: async () => (setDisablePw(''), await refresh(), toast(true, 'Two-factor authentication disabled')),
    onError: (e) => toast(false, errMsg(e)),
  });
  return (
    <>
      <PageHeader title="Account & security" description={`${admin?.name} · ${admin?.email} · ${admin?.role}`} />
      <div className="grid gap-6 lg:grid-cols-2">
        <Card title="Change password" description="Minimum 12 characters with upper-case, lower-case and digits.">
          <form className="space-y-4" onSubmit={(e) => (e.preventDefault(), change.mutate())}>
            <Field label="Current password" htmlFor="pw-cur"><input id="pw-cur" type="password" autoComplete="current-password" className="input" value={pw.currentPassword} onChange={(e) => setPw({ ...pw, currentPassword: e.target.value })} /></Field>
            <Field label="New password" htmlFor="pw-new"><input id="pw-new" type="password" autoComplete="new-password" className="input" value={pw.newPassword} onChange={(e) => setPw({ ...pw, newPassword: e.target.value })} /></Field>
            <Field label="Confirm new password" htmlFor="pw-conf" error={pw.confirm && pw.confirm !== pw.newPassword ? 'Passwords do not match' : undefined}>
              <input id="pw-conf" type="password" autoComplete="new-password" className="input" value={pw.confirm} onChange={(e) => setPw({ ...pw, confirm: e.target.value })} />
            </Field>
            <Button type="submit" variant="primary" loading={change.isPending} disabled={!pw.currentPassword || !pw.newPassword || pw.newPassword !== pw.confirm}>Update password</Button>
          </form>
        </Card>
        <Card title="Two-factor authentication" description="Require a 6-digit code from an authenticator app at sign-in." actions={admin?.totpEnabled ? <Badge tone="green" dot>Enabled</Badge> : <Badge tone="amber" dot>Off</Badge>}>
          {admin?.totpEnabled ? (
            <form className="space-y-4" onSubmit={(e) => (e.preventDefault(), disable.mutate())}>
              <p className="flex items-center gap-2 text-sm text-emerald-700"><ShieldCheck className="h-4 w-4" aria-hidden /> Your account is protected with two-factor authentication.</p>
              <Field label="Confirm password to disable" htmlFor="tf-dis"><input id="tf-dis" type="password" className="input" value={disablePw} onChange={(e) => setDisablePw(e.target.value)} /></Field>
              <Button type="submit" variant="danger" loading={disable.isPending} disabled={!disablePw}>Disable two-factor</Button>
            </form>
          ) : setup ? (
            <div className="space-y-4">
              <p className="text-sm text-slate-600">Scan with Google Authenticator, 1Password, Authy or similar — or enter the key manually.</p>
              <img src={setup.qrDataUrl} alt="QR code for authenticator app" className="h-44 w-44 rounded-lg ring-1 ring-slate-200" />
              <code className="block break-all rounded bg-slate-50 p-2 font-mono text-xs">{setup.secret}</code>
              <Field label="6-digit code" htmlFor="tf-code">
                <input id="tf-code" inputMode="numeric" maxLength={6} className="input tnum w-40 tracking-widest" value={code} onChange={(e) => setCode(e.target.value.replace(/\D/g, ''))} />
              </Field>
              <div className="flex gap-2">
                <Button onClick={() => setSetup(null)}>Cancel</Button>
                <Button variant="primary" loading={enable.isPending} disabled={code.length !== 6} onClick={() => enable.mutate()}>Verify & enable</Button>
              </div>
            </div>
          ) : (
            <Button variant="primary" loading={start.isPending} onClick={() => start.mutate()}>Set up two-factor</Button>
          )}
        </Card>
      </div>
    </>
  );
}
