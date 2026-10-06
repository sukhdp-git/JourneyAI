import { createReadStream } from 'node:fs';
import { mkdir, unlink, writeFile, stat } from 'node:fs/promises';
import path from 'node:path';
import type { Readable } from 'node:stream';
import { randomUUID } from 'node:crypto';
import { DeleteObjectCommand, GetObjectCommand, PutObjectCommand, S3Client } from '@aws-sdk/client-s3';
import { getSignedUrl } from '@aws-sdk/s3-request-presigner';
import { ALLOWED_SCREENSHOT_EXT, ALLOWED_SCREENSHOT_MIME, MAX_SCREENSHOT_BYTES } from '@journzey/shared';
import type { Env } from '../config/env.js';
import { badRequest } from '../lib/errors.js';

export type StoredObject = { kind: 'stream'; stream: Readable; contentType: string; size: number } | { kind: 'redirect'; url: string };

export interface StorageProvider {
  readonly kind: 'local' | 's3';
  put(key: string, body: Buffer, contentType: string): Promise<void>;
  get(key: string): Promise<StoredObject | null>;
  delete(key: string): Promise<void>;
}

const KEY_PATTERN = /^screenshots\/[0-9a-f-]{36}\/[0-9a-f-]{36}\.(png|jpg|webp)$/;
const assertKey = (key: string) => {
  if (!KEY_PATTERN.test(key)) throw new Error('Invalid storage key');
};
const contentTypeFor = (key: string) =>
  key.endsWith('.png') ? 'image/png' : key.endsWith('.webp') ? 'image/webp' : 'image/jpeg';

/** Local filesystem storage — development / single-node only. Objects are served through an authenticated route. */
export class LocalStorage implements StorageProvider {
  readonly kind = 'local' as const;
  constructor(private readonly root: string) {}
  private full(key: string) {
    assertKey(key);
    const p = path.resolve(this.root, key);
    if (!p.startsWith(path.resolve(this.root) + path.sep)) throw new Error('Path traversal rejected');
    return p;
  }
  async put(key: string, body: Buffer): Promise<void> {
    const p = this.full(key);
    await mkdir(path.dirname(p), { recursive: true });
    await writeFile(p, body, { mode: 0o600 });
  }
  async get(key: string): Promise<StoredObject | null> {
    const p = this.full(key);
    try {
      const s = await stat(p);
      return { kind: 'stream', stream: createReadStream(p), contentType: contentTypeFor(key), size: s.size };
    } catch {
      return null;
    }
  }
  async delete(key: string): Promise<void> {
    await unlink(this.full(key)).catch(() => undefined);
  }
}

/** S3-compatible private bucket (AWS S3, Cloudflare R2, Supabase Storage S3 API). Access via short-lived signed URLs. */
export class S3Storage implements StorageProvider {
  readonly kind = 's3' as const;
  private readonly client: S3Client;
  constructor(private readonly env: Env) {
    this.client = new S3Client({
      region: env.STORAGE_REGION ?? 'auto',
      endpoint: env.STORAGE_ENDPOINT,
      forcePathStyle: Boolean(env.STORAGE_ENDPOINT),
      credentials: { accessKeyId: env.STORAGE_ACCESS_KEY!, secretAccessKey: env.STORAGE_SECRET_KEY! },
    });
  }
  async put(key: string, body: Buffer, contentType: string): Promise<void> {
    assertKey(key);
    await this.client.send(
      new PutObjectCommand({ Bucket: this.env.STORAGE_BUCKET, Key: key, Body: body, ContentType: contentType, CacheControl: 'private, max-age=300' }),
    );
  }
  async get(key: string): Promise<StoredObject | null> {
    assertKey(key);
    const url = await getSignedUrl(this.client, new GetObjectCommand({ Bucket: this.env.STORAGE_BUCKET, Key: key }), { expiresIn: 300 });
    return { kind: 'redirect', url };
  }
  async delete(key: string): Promise<void> {
    assertKey(key);
    await this.client.send(new DeleteObjectCommand({ Bucket: this.env.STORAGE_BUCKET, Key: key }));
  }
}

export function createStorage(env: Env): StorageProvider {
  return env.STORAGE_PROVIDER === 's3' ? new S3Storage(env) : new LocalStorage(path.resolve(env.UPLOAD_DIR));
}

/** Validates an uploaded screenshot by declared MIME type, file extension, size AND magic bytes. */
export function validateScreenshot(file: { buffer: Buffer; mimetype: string; originalname: string; size: number }): { ext: 'png' | 'jpg' | 'webp'; contentType: string } {
  if (file.size > MAX_SCREENSHOT_BYTES) throw badRequest('Screenshot exceeds 5 MB', { file: ['Maximum size is 5 MB'] });
  if (!(ALLOWED_SCREENSHOT_MIME as readonly string[]).includes(file.mimetype)) {
    throw badRequest('Unsupported image type', { file: ['Only PNG, JPEG and WebP are allowed'] });
  }
  const ext = path.extname(file.originalname || '').slice(1).toLowerCase();
  if (ext && !(ALLOWED_SCREENSHOT_EXT as readonly string[]).includes(ext)) {
    throw badRequest('Unsupported file extension', { file: ['Only .png, .jpg, .jpeg and .webp are allowed'] });
  }
  const b = file.buffer;
  const isPng = b.length > 8 && b.subarray(0, 8).equals(Buffer.from([0x89, 0x50, 0x4e, 0x47, 0x0d, 0x0a, 0x1a, 0x0a]));
  const isJpeg = b.length > 3 && b[0] === 0xff && b[1] === 0xd8 && b[2] === 0xff;
  const isWebp = b.length > 12 && b.subarray(0, 4).toString('ascii') === 'RIFF' && b.subarray(8, 12).toString('ascii') === 'WEBP';
  const detected = isPng ? 'image/png' : isJpeg ? 'image/jpeg' : isWebp ? 'image/webp' : null;
  if (!detected || detected !== file.mimetype) {
    throw badRequest('File content does not match an allowed image format', { file: ['File is not a valid PNG, JPEG or WebP image'] });
  }
  return { ext: isPng ? 'png' : isJpeg ? 'jpg' : 'webp', contentType: detected };
}

export const screenshotKey = (userId: string, ext: string) => `screenshots/${userId}/${randomUUID()}.${ext}`;
