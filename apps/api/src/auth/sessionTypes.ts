import 'express-session';

declare module 'express-session' {
  interface SessionData {
    userId?: string;
    csrfToken?: string;
    authenticatedAt?: number;
    oauth?: { state: string; nonce: string; codeVerifier: string; createdAt: number };
    /** Control-panel session fields (separate cookie, separate session id). */
    adminId?: string;
    adminAuthenticatedAt?: number;
    adminTotpSetupSecret?: string;
  }
}

declare global {
  // eslint-disable-next-line @typescript-eslint/no-namespace
  namespace Express {
    interface Request {
      user?: { id: string; email: string };
      admin?: import('../db/schema.js').AdminUserRow;
      rawBody?: Buffer;
    }
  }
}

export {};
