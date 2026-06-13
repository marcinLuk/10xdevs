// auth.setup.ts — logs in once and saves the authenticated storage state.
// Runs as a dependency before the test projects (see playwright.config.ts).
import { test as setup, expect } from '@playwright/test';
import { E2E_USER } from './env';

const authFile = './playwright/.auth/user.json';

setup('authenticate', async ({ page }) => {
    await page.goto('/login');

    await page.getByLabel('Email').fill(E2E_USER.email);
    await page.getByLabel('Password').fill(E2E_USER.password);
    // Check "Remember me" so the session is more durable.
    await page.getByRole('checkbox', { name: 'Remember me' }).check();
    await page.getByRole('button', { name: 'Log in' }).click();

    // Wait for the post-login redirect away from /login before saving state.
    await page.waitForURL(url => !url.pathname.includes('/login'));
    // Sanity check: we are actually authenticated.
    await expect(page.getByRole('button', { name: 'Add Task' })).toBeVisible();

    await page.context().storageState({ path: authFile });
});
