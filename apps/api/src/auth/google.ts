import { createHash } from 'node:crypto';
import { createRemoteJWKSet, jwtVerify } from 'jose';
import { randomToken } from '../lib/crypto.js';

export interface VerifiedIdentity {
  subject: string;
  email: string;
  emailVerified: boolean;
  name: string | null;
  picture: string | null;
}

export interface AuthorizationRequest {
  url: string;
  state: string;
  nonce: string;
  codeVerifier: string;
}

/** Abstraction over the identity provider so tests can substitute a fake IdP. */
export interface IdentityProvider {
  readonly configured: boolean;
  createAuthorizationRequest(): AuthorizationRequest;
  exchangeCode(code: string, codeVerifier: string, expectedNonce: string): Promise<VerifiedIdentity>;
}

const GOOGLE_AUTH_ENDPOINT = 'https://accounts.google.com/o/oauth2/v2/auth';
const GOOGLE_TOKEN_ENDPOINT = 'https://oauth2.googleapis.com/token';
const GOOGLE_JWKS = 'https://www.googleapis.com/oauth2/v3/certs';
const GOOGLE_ISSUERS = ['https://accounts.google.com', 'accounts.google.com'];

/**
 * Google OAuth 2.0 / OpenID Connect authorization-code flow with PKCE (S256),
 * state (CSRF) and nonce (replay) protection. The ID token signature is verified
 * against Google's published JWKS; issuer, audience, expiry and nonce are enforced.
 */
export class GoogleIdentityProvider implements IdentityProvider {
  private readonly jwks = createRemoteJWKSet(new URL(GOOGLE_JWKS));
  constructor(
    private readonly clientId: string | undefined,
    private readonly clientSecret: string | undefined,
    private readonly callbackUrl: string | undefined,
  ) {}

  get configured(): boolean {
    return Boolean(this.clientId && this.clientSecret && this.callbackUrl);
  }

  createAuthorizationRequest(): AuthorizationRequest {
    if (!this.configured) throw new Error('Google OAuth is not configured');
    const state = randomToken(32);
    const nonce = randomToken(32);
    const codeVerifier = randomToken(48);
    const codeChallenge = createHash('sha256').update(codeVerifier).digest('base64url');
    const params = new URLSearchParams({
      client_id: this.clientId!,
      redirect_uri: this.callbackUrl!,
      response_type: 'code',
      scope: 'openid email profile',
      state,
      nonce,
      code_challenge: codeChallenge,
      code_challenge_method: 'S256',
      prompt: 'select_account',
      access_type: 'online',
    });
    return { url: `${GOOGLE_AUTH_ENDPOINT}?${params.toString()}`, state, nonce, codeVerifier };
  }

  async exchangeCode(code: string, codeVerifier: string, expectedNonce: string): Promise<VerifiedIdentity> {
    const res = await fetch(GOOGLE_TOKEN_ENDPOINT, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', Accept: 'application/json' },
      body: new URLSearchParams({
        code,
        client_id: this.clientId!,
        client_secret: this.clientSecret!,
        redirect_uri: this.callbackUrl!,
        grant_type: 'authorization_code',
        code_verifier: codeVerifier,
      }),
      signal: AbortSignal.timeout(10_000),
    });
    if (!res.ok) {
      // Never log the response body: it may echo sensitive parameters.
      throw new Error(`Google token exchange failed with HTTP ${res.status}`);
    }
    const tokens = (await res.json()) as { id_token?: string };
    if (!tokens.id_token) throw new Error('Google token response did not include an id_token');
    const { payload } = await jwtVerify(tokens.id_token, this.jwks, {
      issuer: GOOGLE_ISSUERS,
      audience: this.clientId!,
      clockTolerance: 30,
    });
    if (payload.nonce !== expectedNonce) throw new Error('ID token nonce mismatch');
    if (typeof payload.sub !== 'string' || typeof payload.email !== 'string') {
      throw new Error('ID token missing subject or email');
    }
    return {
      subject: payload.sub,
      email: payload.email.toLowerCase(),
      emailVerified: payload.email_verified === true,
      name: typeof payload.name === 'string' ? payload.name : null,
      picture: typeof payload.picture === 'string' ? payload.picture : null,
    };
  }
}
