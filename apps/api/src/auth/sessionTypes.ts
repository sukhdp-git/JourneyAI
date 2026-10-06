import 'express-session';

declare module 'express-session' {
  interface SessionData {
    userId?: string;
    csrfToken?: string;
    authenticatedAt?: number;
    oauth?: { state: string; nonce: string; codeVerifier: string; createdAt: number };
  }
}

declare global {
  // eslint-disable-next-line @typescript-eslint/no-namespace
  namespace Express {
    interface Request {
      user?: { id: string; email: string };
      rawBody?: Buffer;
    }
  }
}

export {};
