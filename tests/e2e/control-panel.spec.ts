import { expect, test, type Page } from '@playwright/test';

/**
 * Control-panel journey. Requires an owner account; the default matches the README's dev example:
 *   ADMIN_PASSWORD='Control-Panel-Pass-2026' npm run admin:create -- --email owner@journzey.test --name "Site Owner"
 */
const EMAIL = process.env.E2E_ADMIN_EMAIL ?? 'owner@journzey.test';
const PASSWORD = process.env.E2E_ADMIN_PASSWORD ?? 'Control-Panel-Pass-2026';
const shots = process.env.E2E_SCREENSHOT_DIR;
const snap = async (page: Page, name: string) => {
  if (shots) await page.screenshot({ path: `${shots}/${name}.png`, fullPage: true });
};

async function adminSignIn(page: Page) {
  await page.goto('/control-panel/');
  await expect(page).toHaveURL(/\/control-panel\/login/);
  await page.getByLabel('Email').fill(EMAIL);
  await page.getByLabel('Password').fill(PASSWORD);
  await page.getByRole('button', { name: 'Sign in' }).click();
  await expect(page.getByRole('heading', { name: 'Dashboard' })).toBeVisible();
}

test('control panel: clean URLs, API keys, live website controls', async ({ page, browser }) => {
  const errors: string[] = [];
  page.on('pageerror', (e) => errors.push(e.message));

  // Unauthenticated access to any panel route lands on the panel's own login (not the trader login).
  await page.goto('/control-panel/integrations');
  await expect(page).toHaveURL(/\/control-panel\/login\?next=%2Fintegrations/);
  await expect(page.getByRole('heading', { name: /Administrator sign-in/ })).toBeVisible();
  await snap(page, 'cp-01-login');

  // Wrong password is rejected.
  await page.getByLabel('Email').fill(EMAIL);
  await page.getByLabel('Password').fill('wrong-password-123');
  await page.getByRole('button', { name: 'Sign in' }).click();
  await expect(page.getByRole('alert').filter({ hasText: 'Invalid email' })).toBeVisible();

  await adminSignIn(page);
  await snap(page, 'cp-02-dashboard');

  // Enter Google OAuth keys in the panel.
  await page.getByRole('link', { name: 'Integrations & API keys' }).click();
  await expect(page).toHaveURL(/\/control-panel\/integrations$/);
  await page.getByLabel('OAuth client ID').fill('1234567890-e2etest.apps.googleusercontent.com');
  await page.getByLabel('OAuth client secret').fill('GOCSPX-e2e-test-secret-wxyz');
  await page.getByRole('button', { name: 'Save changes' }).first().click();
  await expect(page.getByText('Saved — changes are live on the website now').last()).toBeVisible();
  await expect(page.getByLabel('OAuth client secret')).toHaveAttribute('placeholder', /Configured \(…wxyz\)/);
  await expect(page.getByLabel('OAuth client secret')).toHaveValue('');
  await snap(page, 'cp-03-integrations');

  // The public login page now offers Google sign-in with those credentials — no restart.
  const visitor = await browser.newPage();
  await visitor.goto('/login');
  const google = visitor.getByRole('link', { name: /Continue with Google/ });
  await expect(google).not.toHaveAttribute('aria-disabled', 'true');
  const resp = await visitor.request.get('/api/v1/auth/google', { maxRedirects: 0 });
  expect(resp.status()).toBe(302);
  expect(resp.headers().location).toContain('client_id=1234567890-e2etest.apps.googleusercontent.com');

  // Website controls: announcement + maintenance mode.
  await page.getByRole('link', { name: 'Website controls' }).click();
  await page.getByRole('switch', { name: 'Show announcement banner' }).click();
  await page.getByLabel('Announcement text').fill('E2E: new AI reviews are live');
  await page.getByRole('button', { name: 'Save changes' }).nth(1).click();
  await expect(page.getByText('Saved — changes are live on the website now').last()).toBeVisible();
  await page.getByRole('switch', { name: 'Maintenance mode' }).click();
  await page.getByRole('button', { name: 'Save changes' }).first().click();
  await expect(page.getByText(/Maintenance mode is ON/)).toBeVisible();
  await snap(page, 'cp-04-website');

  await visitor.goto('/');
  await expect(visitor.getByRole('heading', { name: 'Scheduled maintenance' })).toBeVisible();
  await snap(visitor, 'cp-05-public-maintenance');

  // Turn maintenance off again; the public site recovers.
  await page.getByRole('switch', { name: 'Maintenance mode' }).click();
  await page.getByRole('button', { name: 'Save changes' }).first().click();
  await expect(page.getByText(/Maintenance mode is ON/)).toBeHidden();
  await visitor.reload();
  await expect(visitor.getByRole('heading', { name: 'Scheduled maintenance' })).toBeHidden();

  // Clean the Google keys up again so other e2e runs start from env defaults.
  await page.getByRole('link', { name: 'Integrations & API keys' }).click();
  for (const label of ['OAuth client ID', 'OAuth client secret']) {
    const revert = page.locator('div').filter({ has: page.getByLabel(label) }).getByRole('button', { name: 'Revert' }).first();
    await revert.click();
    await expect(page.getByText('Reverted to environment / default value').first()).toBeVisible();
  }

  // Other sections render.
  for (const [link, heading] of [
    ['Users', 'Users'],
    ['Audit log', 'Audit log'],
    ['System health', 'System health'],
    ['Administrators', 'Administrators'],
  ] as const) {
    await page.getByRole('link', { name: link }).click();
    await expect(page.getByRole('heading', { name: heading, exact: true })).toBeVisible();
  }
  await expect(page.getByText('settings.updated').first()).toBeHidden(); // admins page, not audit
  await page.getByRole('link', { name: 'Audit log' }).click();
  await expect(page.getByText('settings.updated').first()).toBeVisible();
  await snap(page, 'cp-06-audit');

  // Sign out.
  await page.getByRole('button', { name: /Site Owner/ }).click();
  await page.getByRole('menuitem', { name: 'Sign out' }).click();
  await expect(page).toHaveURL(/\/control-panel\/login/);
  await page.goto('/control-panel/users');
  await expect(page).toHaveURL(/\/control-panel\/login/);

  expect(errors, errors.join('\n')).toEqual([]);
});
