import type { ZodTypeAny, z } from 'zod';
import { AppError, zodFields } from './errors.js';

export function parse<S extends ZodTypeAny>(schema: S, data: unknown, message = 'Invalid request parameters'): z.output<S> {
  const r = schema.safeParse(data);
  if (!r.success) throw new AppError(400, 'VALIDATION_ERROR', message, zodFields(r.error));
  return r.data;
}
