import { expect, test, type Page } from '@playwright/test';

const unique = () => `e2e.${Date.now()}.${Math.random().toString(36).slice(2, 7)}@example.com`;
const shots = process.env.E2E_SCREENSHOT_DIR;
const snap = async (page: Page, name: string) => {
  if (shots) await page.screenshot({ path: `${shots}/${name}.png`, fullPage: true });
};

async function devSignIn(page: Page, email: string) {
  await page.goto('/login');
  await expect(page.getByRole('link', { name: /Continue with Google/ })).toBeVisible();
  await page.getByLabel('Email').fill(email);
  await page.getByLabel('Name').fill('E2E Trader');
  await page.getByRole('button', { name: /Sign in as local developer/ }).click();
}

async function completeOnboarding(page: Page, demo: boolean) {
  await expect(page).toHaveURL(/\/onboarding/);
  await expect(page.getByRole('heading', { name: /Welcome to journzey\.ai/ })).toBeVisible();
  await page.getByRole('button', { name: 'Continue' }).click(); // welcome
  await page.getByRole('button', { name: 'Forex' }).click();
  await page.getByRole('button', { name: 'Continue' }).click(); // markets
  await page.getByLabel('Account name').fill('E2E Live');
  await page.getByLabel('Starting capital').fill('10000');
  await page.getByRole('button', { name: 'Continue' }).click(); // account
  await page.getByRole('button', { name: 'Continue' }).click(); // risk
  await page.getByRole('button', { name: 'Continue' }).click(); // timezone
  await page.getByRole('button', { name: demo ? /Demo Mode/ : /Start With Empty Account/ }).click();
  await page.getByRole('button', { name: 'Enter the terminal' }).click();
  await expect(page).toHaveURL(/\/app$/);
}

test('unauthenticated terminal access redirects to login', async ({ page }) => {
  await page.goto('/app/trades');
  await expect(page).toHaveURL(/\/login/);
});

test('full trader journey: sign in → onboard → quick trade → analytics → journal → sign out', async ({ page }) => {
  const errors: string[] = [];
  page.on('pageerror', (e) => errors.push(e.message));
  page.on('console', (m) => m.type() === 'error' && !/Failed to load resource/.test(m.text()) && errors.push(m.text()));

  await page.goto('/');
  await snap(page, '01-landing');
  await devSignIn(page, unique());
  await snap(page, '02-onboarding');
  await completeOnboarding(page, true);

  // Home Hub
  await expect(page.getByRole('heading', { name: 'Home Hub' })).toBeVisible();
  await expect(page.getByText('DEMO DATA').first()).toBeVisible(); // market feed without API key + demo scope
  await snap(page, '03-home');

  // Switch to real accounts and log a quick trade
  await page.getByLabel('Account mode').selectOption('real');
  const cmd = page.getByLabel('Quick trade command');
  await cmd.fill('buy gold 2862 sl 2858 3r 0.5 lot val bounce');
  await expect(page.getByText('XAUUSD', { exact: true }).first()).toBeVisible();
  await expect(page.getByText('+3R')).toBeVisible();
  await cmd.press('Enter');
  await expect(page.getByText(/XAUUSD LONG logged · \+\$600\.00/)).toBeVisible();

  // Checklist persists
  await page.getByRole('checkbox', { name: 'Economic news checked' }).click();
  await expect(page.getByRole('checkbox', { name: 'Economic news checked' })).toHaveAttribute('aria-checked', 'true');

  // Trade log
  await page.getByRole('link', { name: 'Trade Log' }).click();
  await expect(page.getByRole('heading', { name: 'Trade Log' })).toBeVisible();
  await expect(page.getByRole('cell', { name: 'XAUUSD', exact: true })).toBeVisible();
  await snap(page, '04-trades');

  // Edit via modal
  await page.getByRole('button', { name: 'Edit XAUUSD trade' }).click();
  await page.getByLabel('Notes').fill('E2E edited note');
  await page.getByRole('button', { name: 'Save changes' }).click();
  await expect(page.getByText(/XAUUSD LONG updated/)).toBeVisible();

  // Dashboard uses stored trades
  await page.getByRole('link', { name: 'Dashboard' }).click();
  await expect(page.getByText('Net P&L')).toBeVisible();
  await expect(page.getByText('+$600.00').first()).toBeVisible();
  await snap(page, '05-dashboard');

  // Demo scope analytics
  await page.getByLabel('Account mode').selectOption('demo');
  await page.getByRole('link', { name: 'Edge Matrix' }).click();
  await expect(page.getByText('Best trading window (historical)')).toBeVisible();
  await page.getByRole('tab', { name: 'Risk of ruin' }).click();
  await expect(page.getByText(/Statistical estimate based on historical assumptions/)).toBeVisible();
  await snap(page, '06-edge-risk');
  await page.getByRole('tab', { name: 'Discipline leak' }).click();
  await expect(page.getByText('Your execution mistakes cost approximately')).toBeVisible();

  // Calendar
  await page.getByRole('link', { name: 'Calendar' }).click();
  await expect(page.getByRole('grid')).toBeVisible();
  await snap(page, '07-calendar');

  // Journal
  await page.getByLabel('Account mode').selectOption('real');
  await page.getByRole('link', { name: 'Daily Notepad' }).click();
  await page.getByLabel('Reflection').fill('Followed the plan; waited for acceptance.');
  await page.getByLabel('Key lesson').fill('Patience pays');
  await page.getByRole('button', { name: 'Save entry' }).click();
  await expect(page.getByText(/Journal saved for/)).toBeVisible();
  await snap(page, '08-notepad');

  // AI coach shows graceful not-configured state
  await page.getByRole('link', { name: 'AI Coach' }).click();
  await expect(page.getByText('AI Coach requires server configuration.').first()).toBeVisible();

  // Settings: theme persists
  await page.getByRole('link', { name: 'Account Settings' }).first().click();
  await page.getByRole('tab', { name: 'Preferences' }).click();
  await page.getByLabel('Theme').selectOption('clean-light');
  await page.getByRole('button', { name: 'Save preferences' }).click();
  await expect(page.getByText('Preferences saved')).toBeVisible();
  await page.reload();
  await expect(page.locator('html')).toHaveAttribute('data-theme', 'clean-light');
  await page.getByRole('tab', { name: 'Broker sync' }).click();
  await expect(page.getByText('Universal Broker Sync Hub')).toBeVisible();
  await snap(page, '09-settings-brokers');

  // Delete trade with confirmation
  await page.getByRole('link', { name: 'Trade Log' }).click();
  await page.getByRole('button', { name: 'Delete XAUUSD trade' }).click();
  await page.getByRole('dialog').getByRole('button', { name: 'Delete' }).click();
  await expect(page.getByText('Trade deleted')).toBeVisible();

  // Sign out destroys the session
  await page.getByRole('button', { name: /E2E Trader/ }).click();
  await page.getByRole('menuitem', { name: 'Sign Out' }).click();
  await expect(page).toHaveURL(/\/login/);
  await page.goto('/app');
  await expect(page).toHaveURL(/\/login/);

  expect(errors, errors.join('\n')).toEqual([]);
});

test('mobile navigation and quick logging @mobile', async ({ page }) => {
  await devSignIn(page, unique());
  await completeOnboarding(page, false);
  await expect(page.getByRole('navigation', { name: 'Quick tabs' })).toBeVisible();
  await page.getByRole('button', { name: 'Menu' }).click();
  await expect(page.getByRole('dialog', { name: 'Navigation' })).toBeVisible();
  await page.getByRole('dialog', { name: 'Navigation' }).getByRole('link', { name: 'Calendar' }).click();
  await expect(page.getByRole('heading', { name: 'Calendar' })).toBeVisible();
  await page.getByRole('button', { name: 'Log trade' }).click();
  await expect(page.getByRole('dialog', { name: 'Log trade' })).toBeVisible();
  await page.getByLabel('Entry').fill('1.1650');
  await page.getByLabel('Symbol').selectOption('EURUSD');
  await page.getByLabel('Stop loss').fill('1.1630');
  await page.getByLabel('Exit (blank = open)').fill('1.1690');
  await page.getByRole('dialog').getByRole('button', { name: 'Log trade' }).click();
  await expect(page.getByText(/EURUSD LONG logged/)).toBeVisible();
  await snap(page, '10-mobile');
});
