import { defineConfig, devices } from '@playwright/test';

/**
 * End-to-end tests run against the real stack: Vite web app on :3000 proxying to the API on :4000,
 * backed by PostgreSQL. They use the clearly-labelled developer sign-in (DEV_AUTH_BYPASS=true),
 * because automated tests cannot complete a real Google consent screen.
 *
 * Start the stack first (see README → Running tests), or set E2E_START_SERVERS=true.
 */
const executablePath = process.env.PLAYWRIGHT_CHROMIUM_EXECUTABLE || undefined;

export default defineConfig({
  testDir: '.',
  timeout: 60_000,
  retries: 0,
  workers: 1,
  reporter: [['list']],
  outputDir: '../../test-results',
  use: {
    baseURL: process.env.E2E_BASE_URL ?? 'http://localhost:3000',
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
    launchOptions: { executablePath },
  },
  projects: [
    { name: 'desktop', use: { ...devices['Desktop Chrome'], launchOptions: { executablePath } }, grepInvert: /@mobile/ },
    { name: 'mobile', use: { ...devices['Pixel 7'], launchOptions: { executablePath } }, grep: /@mobile/ },
  ],
  webServer: process.env.E2E_START_SERVERS
    ? [
        { command: 'npm run dev:api', url: 'http://localhost:4000/api/v1/health', reuseExistingServer: true, cwd: '../..', timeout: 120_000 },
        { command: 'npm run dev -w @journzey/web', url: 'http://localhost:3000', reuseExistingServer: true, cwd: '../..', timeout: 120_000 },
      ]
    : undefined,
});
