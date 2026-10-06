import { Router } from 'express';
import { z } from 'zod';
import {
  capitalTransactionSchema,
  checklistUpdateSchema,
  journalUpdateSchema,
  journalUpsertSchema,
  strategyCreateSchema,
  strategyUpdateSchema,
  tradingAccountCreateSchema,
  tradingAccountUpdateSchema,
} from '@journzey/shared';
import type { AppContext } from '../context.js';
import { ah } from '../lib/asyncHandler.js';
import { parse } from '../lib/validate.js';
import { currentUserId } from '../middleware/auth.js';
import { dateQuery, idParam, journalListQuery } from '../validators/index.js';
import { audit } from '../services/audit.js';

export function accountsRouter(ctx: AppContext) {
  const r = Router();
  r.get('/', ah(async (req, res) => res.json(await ctx.accounts.list(currentUserId(req)))));
  r.post(
    '/',
    ah(async (req, res) => {
      const userId = currentUserId(req);
      const acc = await ctx.accounts.create(userId, parse(tradingAccountCreateSchema, req.body));
      await audit(ctx.db, req, 'account.created', userId, { accountId: acc.id });
      res.status(201).json(acc);
    }),
  );
  r.patch(
    '/:id',
    ah(async (req, res) => {
      const userId = currentUserId(req);
      const { id } = parse(idParam, req.params);
      const acc = await ctx.accounts.update(userId, id, parse(tradingAccountUpdateSchema, req.body));
      await audit(ctx.db, req, 'account.updated', userId, { accountId: id });
      res.json(acc);
    }),
  );
  r.delete(
    '/:id',
    ah(async (req, res) => {
      const userId = currentUserId(req);
      const { id } = parse(idParam, req.params);
      const result = await ctx.accounts.delete(userId, id);
      for (const key of result.screenshotKeys) await ctx.storage.delete(key).catch(() => undefined);
      await audit(ctx.db, req, 'account.deleted', userId, { accountId: id, trades: result.trades });
      res.status(204).end();
    }),
  );
  r.get('/:id/transactions', ah(async (req, res) => res.json(await ctx.accounts.listTransactions(currentUserId(req), parse(idParam, req.params).id))));
  r.post(
    '/:id/transactions',
    ah(async (req, res) => {
      const userId = currentUserId(req);
      const { id } = parse(idParam, req.params);
      const input = parse(capitalTransactionSchema, req.body);
      const acc = await ctx.accounts.addTransaction(userId, id, input);
      await audit(ctx.db, req, 'capital.transaction_created', userId, { accountId: id, type: input.type });
      res.status(201).json(acc);
    }),
  );
  r.delete(
    '/:id/transactions/:txId',
    ah(async (req, res) => {
      const userId = currentUserId(req);
      const { id, txId } = parse(z.object({ id: z.string().uuid(), txId: z.string().uuid() }), req.params);
      await ctx.accounts.deleteTransaction(userId, id, txId);
      await audit(ctx.db, req, 'capital.transaction_deleted', userId, { accountId: id });
      res.status(204).end();
    }),
  );
  return r;
}

export function strategiesRouter(ctx: AppContext) {
  const r = Router();
  r.get('/', ah(async (req, res) => res.json(await ctx.strategies.list(currentUserId(req)))));
  r.post('/', ah(async (req, res) => res.status(201).json(await ctx.strategies.create(currentUserId(req), parse(strategyCreateSchema, req.body)))));
  r.patch(
    '/:id',
    ah(async (req, res) => res.json(await ctx.strategies.update(currentUserId(req), parse(idParam, req.params).id, parse(strategyUpdateSchema, req.body)))),
  );
  r.delete(
    '/:id',
    ah(async (req, res) => {
      const userId = currentUserId(req);
      const { id } = parse(idParam, req.params);
      await ctx.strategies.delete(userId, id);
      await audit(ctx.db, req, 'strategy.deleted', userId, { strategyId: id });
      res.status(204).end();
    }),
  );
  return r;
}

export function journalRouter(ctx: AppContext) {
  const r = Router();
  r.get('/', ah(async (req, res) => res.json(await ctx.journal.list(currentUserId(req), parse(journalListQuery, req.query)))));
  r.post(
    '/',
    ah(async (req, res) => {
      const { journalDate, ...fields } = parse(journalUpsertSchema, req.body);
      res.status(201).json(await ctx.journal.upsert(currentUserId(req), journalDate, fields));
    }),
  );
  r.patch(
    '/:id',
    ah(async (req, res) => res.json(await ctx.journal.update(currentUserId(req), parse(idParam, req.params).id, parse(journalUpdateSchema, req.body)))),
  );
  r.delete(
    '/:id',
    ah(async (req, res) => {
      const userId = currentUserId(req);
      const { id } = parse(idParam, req.params);
      await ctx.journal.delete(userId, id);
      await audit(ctx.db, req, 'journal.deleted', userId, { journalId: id });
      res.status(204).end();
    }),
  );
  return r;
}

export function checklistRouter(ctx: AppContext) {
  const r = Router();
  r.get('/', ah(async (req, res) => res.json(await ctx.journal.checklist(currentUserId(req), parse(dateQuery, req.query).date))));
  r.put(
    '/',
    ah(async (req, res) => {
      const input = parse(checklistUpdateSchema, req.body);
      res.json(await ctx.journal.setChecklist(currentUserId(req), input.date, input.itemKey, input.completed));
    }),
  );
  return r;
}
