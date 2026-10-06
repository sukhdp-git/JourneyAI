import { Router } from 'express';
import multer from 'multer';
import { aiChatSchema, reviewRequestSchema } from '@journzey/shared';
import type { AppContext } from '../context.js';
import { ah } from '../lib/asyncHandler.js';
import { badRequest } from '../lib/errors.js';
import { parse } from '../lib/validate.js';
import { currentUserId } from '../middleware/auth.js';
import { csvImportQuery, idParam } from '../validators/index.js';
import { audit } from '../services/audit.js';

export function aiRouter(ctx: AppContext, limiters: { ai: import('express').RequestHandler }) {
  const r = Router();
  r.get('/status', (_req, res) =>
    res.json({ configured: ctx.ai.configured, message: ctx.ai.configured ? null : 'AI Coach requires server configuration.' }),
  );
  r.get('/conversations', ah(async (req, res) => res.json(await ctx.ai.conversations(currentUserId(req)))));
  r.get('/conversations/:id/messages', ah(async (req, res) => res.json(await ctx.ai.messages(currentUserId(req), parse(idParam, req.params).id))));
  r.delete(
    '/conversations/:id',
    ah(async (req, res) => {
      await ctx.ai.deleteConversation(currentUserId(req), parse(idParam, req.params).id);
      res.status(204).end();
    }),
  );
  r.post('/chat', limiters.ai, ah(async (req, res) => res.json(await ctx.ai.chat(currentUserId(req), parse(aiChatSchema, req.body)))));
  r.post(
    '/monthly-review',
    limiters.ai,
    ah(async (req, res) => res.json(await ctx.ai.narrativeReview(currentUserId(req), parse(reviewRequestSchema, req.body ?? {})))),
  );
  return r;
}

export function marketRouter(ctx: AppContext) {
  const r = Router();
  r.get('/quotes', ah(async (_req, res) => res.json(await ctx.market.quotes())));
  return r;
}

export function brokersRouter(ctx: AppContext, limiters: { upload: import('express').RequestHandler }) {
  const r = Router();
  const upload = multer({ storage: multer.memoryStorage(), limits: { fileSize: 5 * 1024 * 1024, files: 1, fields: 5 } });
  r.get('/providers', (_req, res) => res.json(ctx.brokers.providers()));
  r.get('/', ah(async (req, res) => res.json(await ctx.brokers.list(currentUserId(req)))));
  r.post(
    '/',
    ah(async (req, res) => {
      const userId = currentUserId(req);
      const result = await ctx.brokers.create(userId, req.body);
      await audit(ctx.db, req, 'broker.connected', userId, { provider: result.connection.provider, connectionId: result.connection.id });
      res.status(201).json(result);
    }),
  );
  r.delete(
    '/:id',
    ah(async (req, res) => {
      const userId = currentUserId(req);
      const { id } = parse(idParam, req.params);
      await ctx.brokers.remove(userId, id);
      await audit(ctx.db, req, 'broker.disconnected', userId, { connectionId: id });
      res.status(204).end();
    }),
  );
  r.post(
    '/:id/rotate-secret',
    ah(async (req, res) => {
      const userId = currentUserId(req);
      const { id } = parse(idParam, req.params);
      const result = await ctx.brokers.rotateWebhookSecret(userId, id);
      await audit(ctx.db, req, 'broker.secret_rotated', userId, { connectionId: id });
      res.json(result);
    }),
  );
  r.post(
    '/:id/sync',
    ah(async (req, res) => {
      const userId = currentUserId(req);
      const { id } = parse(idParam, req.params);
      const summary = await ctx.brokers.sync(userId, id);
      await audit(ctx.db, req, 'broker.sync', userId, { connectionId: id, imported: summary.imported });
      res.json(summary);
    }),
  );
  r.post(
    '/:id/import',
    limiters.upload,
    upload.single('file'),
    ah(async (req, res) => {
      const userId = currentUserId(req);
      const { id } = parse(idParam, req.params);
      if (!req.file) throw badRequest('No file uploaded', { file: ['Upload a CSV export'] });
      const name = req.file.originalname.toLowerCase();
      if (!name.endsWith('.csv') && !name.endsWith('.txt')) throw badRequest('Only .csv files are accepted', { file: ['Upload a .csv file'] });
      const content = req.file.buffer.toString('utf8');
      if (content.includes('\u0000')) throw badRequest('File does not look like text CSV', { file: ['Binary files are not accepted'] });
      const { serverUtcOffsetMinutes } = parse(csvImportQuery, req.body ?? {});
      const summary = await ctx.brokers.importCsv(userId, id, content, serverUtcOffsetMinutes);
      await audit(ctx.db, req, 'broker.import', userId, { connectionId: id, imported: summary.imported });
      res.json(summary);
    }),
  );
  return r;
}

/** Public, signature-authenticated machine endpoint (no session, no CSRF). */
export function webhooksRouter(ctx: AppContext) {
  const r = Router();
  r.post(
    '/broker/:connectionId',
    ah(async (req, res) => {
      const result = await ctx.brokers.handleWebhook(
        String(req.params.connectionId),
        {
          timestamp: req.get('x-journzey-timestamp'),
          signature: req.get('x-journzey-signature'),
          eventId: req.get('x-journzey-event-id'),
        },
        req.rawBody,
        req.body,
      );
      res.status(result.status === 'imported' ? 201 : 200).json(result);
    }),
  );
  return r;
}
