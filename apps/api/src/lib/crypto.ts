import { createCipheriv, createDecipheriv, createHash, createHmac, randomBytes, timingSafeEqual } from 'node:crypto';

/**
 * AES-256-GCM envelope for secrets at rest (broker API keys, webhook secrets).
 * Format: v1:<iv b64>:<auth tag b64>:<ciphertext b64>
 */
export class SecretBox {
  private readonly key: Buffer;
  constructor(base64Key: string) {
    this.key = Buffer.from(base64Key, 'base64');
    if (this.key.length !== 32) throw new Error('Encryption key must be 32 bytes');
  }
  encrypt(plaintext: string): string {
    const iv = randomBytes(12);
    const cipher = createCipheriv('aes-256-gcm', this.key, iv);
    const ct = Buffer.concat([cipher.update(plaintext, 'utf8'), cipher.final()]);
    const tag = cipher.getAuthTag();
    return ['v1', iv.toString('base64'), tag.toString('base64'), ct.toString('base64')].join(':');
  }
  decrypt(envelope: string): string {
    const [v, iv, tag, ct] = envelope.split(':');
    if (v !== 'v1' || !iv || !tag || !ct) throw new Error('Unsupported secret envelope');
    const decipher = createDecipheriv('aes-256-gcm', this.key, Buffer.from(iv, 'base64'));
    decipher.setAuthTag(Buffer.from(tag, 'base64'));
    return Buffer.concat([decipher.update(Buffer.from(ct, 'base64')), decipher.final()]).toString('utf8');
  }
}

export const randomToken = (bytes = 32) => randomBytes(bytes).toString('base64url');

export const sha256 = (v: string) => createHash('sha256').update(v).digest('hex');

export const hmacSha256Hex = (secret: string, payload: string | Buffer) =>
  createHmac('sha256', secret).update(payload).digest('hex');

export function safeEqual(a: string, b: string): boolean {
  const ab = Buffer.from(a);
  const bb = Buffer.from(b);
  if (ab.length !== bb.length) return false;
  return timingSafeEqual(ab, bb);
}
