import { Router } from 'express';
import { deleteAccountSchema, onboardingSchema, profileUpdateSchema, settingsUpdateSchema } from '@journzey/shared';
import type { AppContext } from '../context.js';
import { ah } from '../lib/asyncHandler.js';
import { badRequest } from '../lib/errors.js';
import { parse } from '../lib/validate.js';
import { currentUserId } from '../middleware/auth.js';
import { terminalLockSchema } from '../validators/index.js';
import { audit } from '../services/audit.js';
import { SESSION_COOKIE } from './auth.routes.js';

export function settingsRouter(ctx: AppContext) {
  const r = Router();
  r.get('/', ah(async (req, res) => res.json(await ctx.users.getSettings(currentUserId(req)))));
  r.patch(
    '/',
    ah(async (req, res) => {
      const userId = currentUserId(req);
      const patch = parse(settingsUpdateSchema, req.body);
      const s = await ctx.users.updateSettings(userId, patch);
      await audit(ctx.db, req, 'user.settings_updated', userId, { fields: Object.keys(patch) });
      res.json(s);
    }),
  );
  r.post(
    '/terminal-lock',
    ah(async (req, res) => {
      const userId = currentUserId(req);
      const { minutes } = parse(terminalLockSchema, req.body);
      const s = await ctx.users.lockTerminal(userId, minutes);
      await audit(ctx.db, req, 'terminal.locked', userId, { minutes });
      res.json(s);
    }),
  );
  return r;
}

export function accountRouter(ctx: AppContext) {
  const r = Router();
  r.patch(
    '/profile',
    ah(async (req, res) => {
      const userId = currentUserId(req);
      const { name } = parse(profileUpdateSchema, req.body);
      const u = await ctx.users.updateProfile(userId, name);
      await audit(ctx.db, req, 'user.profile_updated', userId);
      res.json(u);
    }),
  );
  r.get(
    '/export',
    ah(async (req, res) => {
      const userId = currentUserId(req);
      const data = await ctx.users.exportData(userId);
      await audit(ctx.db, req, 'user.data_exported', userId);
      res.setHeader('Content-Disposition', `attachment; filename="journzey-export-${new Date().toISOString().slice(0, 10)}.json"`);
      res.setHeader('Cache-Control', 'no-store');
      res.json(data);
    }),
  );
  /** Permanent deletion. The client must echo the account email as confirmation. */
  r.delete(
    '/',
    ah(async (req, res) => {
      const userId = currentUserId(req);
      const { confirmation } = parse(deleteAccountSchema, req.body);
      if (confirmation.trim().toLowerCase() !== req.user!.email.toLowerCase()) {
        throw badRequest('Confirmation does not match your account email', { confirmation: ['Type your account email to confirm'] });
      }
      const { screenshotKeys } = await ctx.users.deleteAccount(userId);
      for (const key of screenshotKeys) await ctx.storage.delete(key).catch(() => undefined);
      await audit(ctx.db, req, 'user.deleted', null, { deletedUser: 'redacted' });
      await new Promise<void>((resolve) => req.session.destroy(() => resolve()));
      res.clearCookie(SESSION_COOKIE, { path: '/' });
      res.status(204).end();
    }),
  );
  return r;
}

export function onboardingRouter(ctx: AppContext) {
  const r = Router();
  r.post(
    '/',
    ah(async (req, res) => {
      const userId = currentUserId(req);
      const result = await ctx.users.completeOnboarding(userId, parse(onboardingSchema, req.body), { demoAllowed: ctx.runtime.features().demoMode });
      await audit(ctx.db, req, 'user.onboarded', userId);
      res.status(201).json({ ...result, me: await ctx.users.me(userId, ctx.features()) });
    }),
  );
  return r;
}

export function demoRouter(ctx: AppContext) {
  const r = Router();
  r.post(
    '/reset',
    ah(async (req, res) => {
      const userId = currentUserId(req);
      const result = await ctx.demo.reset(userId);
      await audit(ctx.db, req, 'demo.reset', userId);
      res.json(result);
    }),
  );
  r.delete(
    '/',
    ah(async (req, res) => {
      const userId = currentUserId(req);
      await ctx.demo.clear(userId);
      await audit(ctx.db, req, 'demo.removed', userId);
      res.status(204).end();
    }),
  );
  return r;
}
