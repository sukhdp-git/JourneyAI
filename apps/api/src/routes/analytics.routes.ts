import { Router } from 'express';
import { analyticsQuerySchema, reviewRequestSchema, riskOfRuinQuerySchema } from '@journzey/shared';
import type { AppContext } from '../context.js';
import { ah } from '../lib/asyncHandler.js';
import { parse } from '../lib/validate.js';
import { currentUserId } from '../middleware/auth.js';
import { monthQuery } from '../validators/index.js';

export function dashboardRouter(ctx: AppContext) {
  const r = Router();
  r.get('/', ah(async (req, res) => res.json(await ctx.analytics.dashboard(currentUserId(req), parse(analyticsQuerySchema, req.query)))));
  return r;
}

export function calendarRouter(ctx: AppContext) {
  const r = Router();
  r.get('/', ah(async (req, res) => res.json(await ctx.analytics.calendar(currentUserId(req), parse(monthQuery, req.query)))));
  return r;
}

export function analyticsRouter(ctx: AppContext) {
  const r = Router();
  r.get('/strategies', ah(async (req, res) => res.json(await ctx.analytics.strategies(currentUserId(req), parse(analyticsQuerySchema, req.query)))));
  r.get('/sessions', ah(async (req, res) => res.json(await ctx.analytics.sessions(currentUserId(req), parse(analyticsQuerySchema, req.query)))));
  r.get('/discipline', ah(async (req, res) => res.json(await ctx.analytics.discipline(currentUserId(req), parse(analyticsQuerySchema, req.query)))));
  r.get('/edge-matrix', ah(async (req, res) => res.json(await ctx.analytics.edgeMatrix(currentUserId(req), parse(analyticsQuerySchema, req.query)))));
  r.get('/risk-of-ruin', ah(async (req, res) => res.json(await ctx.analytics.riskOfRuin(currentUserId(req), parse(riskOfRuinQuerySchema, req.query)))));
  r.get(
    '/review',
    ah(async (req, res) => {
      const q = parse(reviewRequestSchema.omit({ language: true }).extend({}).strict(), {
        period: req.query.period,
        anchorDate: req.query.anchorDate,
        account: req.query.account,
      });
      res.json(await ctx.analytics.review(currentUserId(req), q));
    }),
  );
  return r;
}
