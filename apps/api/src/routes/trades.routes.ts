import { Router } from 'express';
import multer from 'multer';
import { MAX_SCREENSHOT_BYTES, quickTradeSchema, tradeCreateSchema, tradeListQuerySchema, tradeUpdateSchema, type TradeDto } from '@journzey/shared';
import type { AppContext } from '../context.js';
import { ah } from '../lib/asyncHandler.js';
import { badRequest, notFound } from '../lib/errors.js';
import { parse } from '../lib/validate.js';
import { currentUserId } from '../middleware/auth.js';
import { idParam, runnerAuditSchema } from '../validators/index.js';
import { audit } from '../services/audit.js';
import { screenshotKey, validateScreenshot } from '../services/storage.js';

const CSV_COLUMNS: Array<[string, (t: TradeDto) => string | null]> = [
  ['Date', (t) => t.executedAt.slice(0, 10)],
  ['Time (UTC)', (t) => t.executedAt.slice(11, 19)],
  ['Symbol', (t) => t.symbol],
  ['Side', (t) => t.side],
  ['Status', (t) => t.status],
  ['Entry', (t) => t.entryPrice],
  ['Exit', (t) => t.exitPrice],
  ['Stop', (t) => t.stopLoss],
  ['Take Profit', (t) => t.takeProfit],
  ['Lot Size', (t) => t.lotSize],
  ['P&L', (t) => t.pnl],
  ['Currency', (t) => t.currency],
  ['Fees', (t) => t.fees],
  ['R', (t) => t.rr],
  ['Strategy', (t) => t.strategyName],
  ['Setup', (t) => t.setupTag],
  ['Session', (t) => t.session],
  ['Emotion', (t) => t.emotion],
  ['Mistake', (t) => t.mistakeTag],
  ['Rules Followed', (t) => (t.rulesFollowed ? 'yes' : 'no')],
  ['Source', (t) => t.source],
  ['Demo', (t) => (t.demo ? 'DEMO DATA' : '')],
  ['Notes', (t) => t.notes],
];

/**
 * RFC 4180 escaping plus spreadsheet formula-injection protection: cells starting with
 * = + - @ or control characters are prefixed with an apostrophe (numbers are left untouched).
 */
export function csvCell(value: string | null): string {
  if (value === null || value === undefined) return '';
  let v = String(value);
  if (/^[=+\-@\t\r]/.test(v) && !/^-?\d+(\.\d+)?$/.test(v)) v = `'${v}`;
  if (/[",\r\n]/.test(v)) v = `"${v.replace(/"/g, '""')}"`;
  return v;
}

export function tradesRouter(ctx: AppContext, limiters: { upload: import('express').RequestHandler }) {
  const r = Router();
  const upload = multer({ storage: multer.memoryStorage(), limits: { fileSize: MAX_SCREENSHOT_BYTES, files: 1, fields: 5 } });

  r.get(
    '/',
    ah(async (req, res) => {
      res.json(await ctx.trades.list(currentUserId(req), parse(tradeListQuerySchema, req.query)));
    }),
  );

  r.get(
    '/export.csv',
    ah(async (req, res) => {
      const userId = currentUserId(req);
      const q = parse(tradeListQuerySchema, req.query);
      const today = new Date().toISOString().slice(0, 10);
      res.setHeader('Content-Type', 'text/csv; charset=utf-8');
      res.setHeader('Content-Disposition', `attachment; filename="JournzeyAI_Trades_${today}.csv"`);
      res.setHeader('Cache-Control', 'no-store');
      res.write('﻿' + CSV_COLUMNS.map(([h]) => csvCell(h)).join(',') + '\r\n');
      let count = 0;
      for await (const chunk of ctx.trades.iterateForExport(userId, q)) {
        for (const t of chunk) res.write(CSV_COLUMNS.map(([, f]) => csvCell(f(t))).join(',') + '\r\n');
        count += chunk.length;
      }
      await audit(ctx.db, req, 'trades.exported', userId, { count });
      res.end();
    }),
  );

  r.post(
    '/',
    ah(async (req, res) => {
      res.status(201).json(await ctx.trades.create(currentUserId(req), parse(tradeCreateSchema, req.body, 'Invalid trade parameters')));
    }),
  );

  r.post(
    '/quick',
    ah(async (req, res) => {
      const input = parse(quickTradeSchema, req.body);
      res.status(201).json(await ctx.trades.createFromCommand(currentUserId(req), input.command, input.tradingAccountId, input.executedAt));
    }),
  );

  r.get(
    '/:id',
    ah(async (req, res) => {
      res.json(await ctx.trades.get(currentUserId(req), parse(idParam, req.params).id));
    }),
  );

  r.patch(
    '/:id',
    ah(async (req, res) => {
      const { id } = parse(idParam, req.params);
      res.json(await ctx.trades.update(currentUserId(req), id, parse(tradeUpdateSchema, req.body, 'Invalid trade parameters')));
    }),
  );

  r.delete(
    '/:id',
    ah(async (req, res) => {
      const userId = currentUserId(req);
      const { id } = parse(idParam, req.params);
      const { screenshotKey: key } = await ctx.trades.delete(userId, id);
      if (key) await ctx.storage.delete(key).catch((err) => req.log.warn({ err: (err as Error).message }, 'screenshot cleanup failed'));
      await audit(ctx.db, req, 'trade.deleted', userId, { tradeId: id });
      res.status(204).end();
    }),
  );

  r.post(
    '/:id/screenshot',
    limiters.upload,
    upload.single('file'),
    ah(async (req, res) => {
      const userId = currentUserId(req);
      const { id } = parse(idParam, req.params);
      if (!req.file) throw badRequest('No file uploaded', { file: ['A PNG, JPEG or WebP image is required'] });
      await ctx.trades.getRow(userId, id); // ownership check before storing anything
      const { ext, contentType } = validateScreenshot(req.file);
      const key = screenshotKey(userId, ext);
      await ctx.storage.put(key, req.file.buffer, contentType);
      const previous = await ctx.trades.setScreenshot(userId, id, key);
      if (previous) await ctx.storage.delete(previous).catch(() => undefined);
      res.status(201).json(await ctx.trades.get(userId, id));
    }),
  );

  r.get(
    '/:id/screenshot',
    ah(async (req, res) => {
      const userId = currentUserId(req);
      const t = await ctx.trades.getRow(userId, parse(idParam, req.params).id);
      if (!t.screenshotUrl) throw notFound('No screenshot attached');
      const obj = await ctx.storage.get(t.screenshotUrl);
      if (!obj) throw notFound('Screenshot not found');
      res.setHeader('Cache-Control', 'private, max-age=300');
      res.setHeader('X-Content-Type-Options', 'nosniff');
      if (obj.kind === 'redirect') return res.redirect(302, obj.url);
      res.setHeader('Content-Type', obj.contentType);
      res.setHeader('Content-Length', String(obj.size));
      obj.stream.pipe(res);
    }),
  );

  r.delete(
    '/:id/screenshot',
    ah(async (req, res) => {
      const userId = currentUserId(req);
      const { id } = parse(idParam, req.params);
      const previous = await ctx.trades.setScreenshot(userId, id, null);
      if (previous) await ctx.storage.delete(previous).catch(() => undefined);
      res.status(204).end();
    }),
  );

  r.post(
    '/:id/runner-audit',
    ah(async (req, res) => {
      const { id } = parse(idParam, req.params);
      const { windowHours } = parse(runnerAuditSchema, req.body ?? {});
      res.json(await ctx.runner.audit(currentUserId(req), id, windowHours));
    }),
  );

  return r;
}
