import type { ErrorRequestHandler, RequestHandler } from 'express';
import { ZodError } from 'zod';
import type { ApiErrorBody } from '@journzey/shared';

export class AppError extends Error {
  constructor(
    public readonly status: number,
    public readonly code: string,
    message: string,
    public readonly fields?: Record<string, string[]>,
  ) {
    super(message);
  }
}

export const badRequest = (message: string, fields?: Record<string, string[]>) =>
  new AppError(400, 'VALIDATION_ERROR', message, fields);
export const unauthorized = (message = 'Authentication required') => new AppError(401, 'UNAUTHENTICATED', message);
export const forbidden = (message = 'Forbidden') => new AppError(403, 'FORBIDDEN', message);
export const notFound = (message = 'Not found') => new AppError(404, 'NOT_FOUND', message);
export const conflict = (message: string) => new AppError(409, 'CONFLICT', message);
export const notConfigured = (code: string, message: string) => new AppError(503, code, message);

export function zodFields(err: ZodError): Record<string, string[]> {
  const fields: Record<string, string[]> = {};
  for (const issue of err.issues) {
    const key = issue.path.join('.') || '_';
    (fields[key] ??= []).push(issue.message);
  }
  return fields;
}

export const notFoundHandler: RequestHandler = (req, res) => {
  const body: ApiErrorBody = {
    error: { code: 'NOT_FOUND', message: `Route ${req.method} ${req.path} not found`, requestId: req.id as string },
  };
  res.status(404).json(body);
};

export function errorHandler(isProduction: boolean): ErrorRequestHandler {
  return (err, req, res, _next) => {
    const requestId = req.id as string | undefined;
    let status = 500;
    let body: ApiErrorBody;
    if (err instanceof AppError) {
      status = err.status;
      body = { error: { code: err.code, message: err.message, ...(err.fields ? { fields: err.fields } : {}), requestId } };
    } else if (err instanceof ZodError) {
      status = 400;
      body = { error: { code: 'VALIDATION_ERROR', message: 'Invalid request parameters', fields: zodFields(err), requestId } };
    } else if (err && typeof err === 'object' && 'type' in err && err.type === 'entity.parse.failed') {
      status = 400;
      body = { error: { code: 'INVALID_JSON', message: 'Malformed JSON body', requestId } };
    } else if (err && typeof err === 'object' && 'type' in err && err.type === 'entity.too.large') {
      status = 413;
      body = { error: { code: 'PAYLOAD_TOO_LARGE', message: 'Request body too large', requestId } };
    } else if (err && typeof err === 'object' && 'code' in err && err.code === 'LIMIT_FILE_SIZE') {
      status = 413;
      body = { error: { code: 'FILE_TOO_LARGE', message: 'File exceeds the maximum allowed size', requestId } };
    } else {
      req.log?.error({ err }, 'unhandled error');
      body = {
        error: {
          code: 'INTERNAL_ERROR',
          message: isProduction ? 'An unexpected error occurred' : String((err as Error)?.message ?? err),
          requestId,
        },
      };
    }
    if (status >= 500 && err instanceof AppError) req.log?.warn({ code: err.code }, err.message);
    res.status(status).json(body);
  };
}
