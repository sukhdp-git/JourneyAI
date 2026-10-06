import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Plus } from 'lucide-react';
import { del, errMsg, get, patch, post } from '../lib/api';
import { useAuth } from '../lib/auth';
import type { Admin, Role } from '../lib/types';
import { Badge, Button, Card, Confirm, ErrorBox, Field, Modal, PageHeader, Spinner, fmtDateTime, useToast } from '../components/ui';

const ROLE_HELP: Record<Role, string> = {
  owner: 'Everything, including administrators and deleting users',
  admin: 'Change settings and API keys, manage users',
  viewer: 'Read-only access',
};

export default function Admins() {
  const { admin: me } = useAuth();
  const qc = useQueryClient();
  const toast = useToast();
  const q = useQuery({ queryKey: ['admins'], queryFn: () => get<Admin[]>('/admins') });
  const [open, setOpen] = useState(false);
  const [form, setForm] = useState({ email: '', name: '', password: '', role: 'admin' as Role });
  const [removing, setRemoving] = useState<Admin | null>(null);
  const [resetting, setResetting] = useState<Admin | null>(null);
  const [newPassword, setNewPassword] = useState('');
  const refresh = () => void qc.invalidateQueries({ queryKey: ['admins'] });
  const create = useMutation({
    mutationFn: () => post<Admin>('/admins', form),
    onSuccess: () => {
      refresh();
      setOpen(false);
      setForm({ email: '', name: '', password: '', role: 'admin' });
      toast(true, 'Administrator created');
    },
    onError: (e) => toast(false, errMsg(e)),
  });
  const update = useMutation({ mutationFn: ({ id, body }: { id: string; body: Partial<Admin> }) => patch<Admin>(`/admins/${id}`, body), onSuccess: () => (refresh(), toast(true, 'Updated')), onError: (e) => toast(false, errMsg(e)) });
  const remove = useMutation({ mutationFn: (a: Admin) => del(`/admins/${a.id}`), onSuccess: () => (refresh(), setRemoving(null), toast(true, 'Administrator removed')), onError: (e) => toast(false, errMsg(e)) });
  const reset = useMutation({
    mutationFn: () => post(`/admins/${resetting!.id}/reset-password`, { password: newPassword }),
    onSuccess: () => (setResetting(null), setNewPassword(''), toast(true, 'Password reset — their existing sessions were signed out')),
    onError: (e) => toast(false, errMsg(e)),
  });
  return (
    <>
      <PageHeader title="Administrators" description="People who can access this control panel." actions={<Button variant="primary" icon={<Plus className="h-4 w-4" />} onClick={() => setOpen(true)}>Add administrator</Button>} />
      <Card bodyClassName="p-0">
        {q.isLoading ? (
          <Spinner />
        ) : q.isError ? (
          <div className="p-4"><ErrorBox error={q.error} /></div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <caption className="sr-only">Administrators</caption>
              <thead className="bg-slate-50 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                <tr>
                  <th scope="col" className="px-5 py-3">Administrator</th>
                  <th scope="col" className="px-5 py-3">Role</th>
                  <th scope="col" className="px-5 py-3">2FA</th>
                  <th scope="col" className="px-5 py-3">Last sign-in</th>
                  <th scope="col" className="px-5 py-3 text-right">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {q.data!.map((a) => {
                  const self = a.id === me?.id;
                  return (
                    <tr key={a.id}>
                      <td className="px-5 py-3">
                        <p className="font-medium">{a.name} {self && <Badge tone="indigo">You</Badge>} {a.disabled && <Badge tone="red">Disabled</Badge>}</p>
                        <p className="text-xs text-slate-500">{a.email}</p>
                      </td>
                      <td className="px-5 py-3">
                        <select aria-label={`Role for ${a.email}`} className="input h-9 w-auto" value={a.role} disabled={self} onChange={(e) => update.mutate({ id: a.id, body: { role: e.target.value as Role } })}>
                          <option value="owner">Owner</option>
                          <option value="admin">Admin</option>
                          <option value="viewer">Viewer</option>
                        </select>
                      </td>
                      <td className="px-5 py-3">{a.totpEnabled ? <Badge tone="green">Enabled</Badge> : <Badge tone="amber">Off</Badge>}</td>
                      <td className="px-5 py-3 text-slate-500">{fmtDateTime(a.lastLoginAt)}</td>
                      <td className="px-5 py-3">
                        {!self && (
                          <div className="flex justify-end gap-2">
                            <Button size="sm" onClick={() => setResetting(a)}>Reset password</Button>
                            <Button size="sm" onClick={() => update.mutate({ id: a.id, body: { disabled: !a.disabled } })}>{a.disabled ? 'Enable' : 'Disable'}</Button>
                            <Button size="sm" variant="ghost" onClick={() => setRemoving(a)}>Remove</Button>
                          </div>
                        )}
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        )}
      </Card>
      <Modal
        open={open}
        onClose={() => setOpen(false)}
        title="Add administrator"
        footer={
          <>
            <Button onClick={() => setOpen(false)}>Cancel</Button>
            <Button variant="primary" loading={create.isPending} onClick={() => create.mutate()} disabled={!form.email || !form.name || !form.password}>Create</Button>
          </>
        }
      >
        <div className="space-y-4">
          <Field label="Name" htmlFor="ad-name"><input id="ad-name" className="input" value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} /></Field>
          <Field label="Email" htmlFor="ad-email"><input id="ad-email" type="email" className="input" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} /></Field>
          <Field label="Temporary password" htmlFor="ad-pass" hint="At least 12 characters with upper-case, lower-case and digits. Share it securely; they should change it after signing in.">
            <input id="ad-pass" type="password" autoComplete="new-password" className="input" value={form.password} onChange={(e) => setForm({ ...form, password: e.target.value })} />
          </Field>
          <Field label="Role" htmlFor="ad-role" hint={ROLE_HELP[form.role]}>
            <select id="ad-role" className="input" value={form.role} onChange={(e) => setForm({ ...form, role: e.target.value as Role })}>
              <option value="admin">Admin</option>
              <option value="viewer">Viewer</option>
              <option value="owner">Owner</option>
            </select>
          </Field>
        </div>
      </Modal>
      <Modal
        open={!!resetting}
        onClose={() => setResetting(null)}
        title={`Reset password · ${resetting?.email ?? ''}`}
        footer={
          <>
            <Button onClick={() => setResetting(null)}>Cancel</Button>
            <Button variant="primary" loading={reset.isPending} disabled={!newPassword} onClick={() => reset.mutate()}>Reset password</Button>
          </>
        }
      >
        <Field label="New password" htmlFor="rp-pass" hint="Signs them out of every active control-panel session.">
          <input id="rp-pass" type="password" autoComplete="new-password" className="input" value={newPassword} onChange={(e) => setNewPassword(e.target.value)} />
        </Field>
      </Modal>
      <Confirm open={!!removing} onClose={() => setRemoving(null)} onConfirm={() => removing && remove.mutate(removing)} loading={remove.isPending} danger confirmLabel="Remove" title="Remove administrator?" message={<p>{removing?.email} will lose access immediately.</p>} />
    </>
  );
}
