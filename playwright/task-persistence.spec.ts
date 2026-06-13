// task-persistence.spec.ts
//
// Protects: test-plan.md Risk #2 — "Task silently fails to persist — gardener
//   saves a task but it never appears in the list or vanishes across sessions."
// Proves protection (per test-plan §2): a task saved via the create form appears
//   in the list, and SURVIVES a real logout → login session boundary.
// Modeled on: seed.spec.ts (role-based locators, unique timestamped data,
//   wait-for-state, self-contained setup/action/assertion/cleanup).
//
// seed.spec.ts already covers the "after page reload" half of this risk. This
// spec covers the distinct, browser-only half the seed does not: cross-session
// persistence through a genuine logout and re-login.
import { test, expect } from '@playwright/test';
import { E2E_USER } from './env';

test.describe('Task persistence (risk #2)', () => {
    // This test owns its FULL auth lifecycle (login → logout → login). Reusing the
    // shared storageState would let the real logout invalidate the session cookie
    // every other parallel test relies on. Start from a clean, unauthenticated
    // context so this test's session boundary is isolated.
    test.use({ storageState: { cookies: [], origins: [] } });

    test('task survives a logout / login session boundary', async ({ page }) => {
        const description = `Persist across sessions ${Date.now()}`;

        // --- Session 1: log in through the UI ---
        // (Logging in via the UI is intentional here — the login/logout cycle IS
        // the behavior under test, not auth boilerplate.)
        await page.goto('/login');
        await page.getByLabel('Email').fill(E2E_USER.email);
        await page.getByLabel('Password').fill(E2E_USER.password);
        await page.getByRole('button', { name: 'Log in' }).click();
        await page.waitForURL((url) => !url.pathname.includes('/login'));

        // --- Create a task ---
        await page.getByRole('button', { name: 'Add Task' }).click();
        await page.getByRole('textbox', { name: 'Description' }).fill(description);
        await page.getByRole('textbox', { name: 'Date' }).fill('2026-06-13');
        // Two buttons are named "Add Task": the modal trigger and the form submit.
        // The second (nth(1)) is the submit, as in seed.spec.ts.
        await page.getByRole('button', { name: 'Add Task' }).nth(1).click();

        // It appears in the list immediately (POST → redirect → list re-render).
        await expect(page.getByText(description, { exact: true })).toBeVisible();

        // --- Cross the session boundary: real logout ---
        await page.getByRole('button', { name: 'Test User' }).click();
        await page.getByRole('link', { name: 'Log Out' }).click();
        await page.waitForURL((url) => url.pathname === '/'); // logout → welcome
        // Confirm the session genuinely ended: the dashboard now bounces to login.
        await page.goto('/dashboard');
        await page.waitForURL(/\/login/);

        // --- Session 2: log back in through the UI ---
        await page.getByLabel('Email').fill(E2E_USER.email);
        await page.getByLabel('Password').fill(E2E_USER.password);
        await page.getByRole('button', { name: 'Log in' }).click();
        await page.waitForURL((url) => !url.pathname.includes('/login'));

        // --- Assertion: the task survived the session boundary ---
        // Fails if the risk materializes (task lost across sessions).
        await expect(page.getByText(description, { exact: true })).toBeVisible();

        // --- Cleanup: delete only THIS task's row (scoped by its unique text) ---
        const taskRow = page
            .locator('div')
            .filter({ has: page.getByText(description, { exact: true }) })
            .filter({ has: page.getByRole('button', { name: 'Delete task' }) })
            .last();
        await taskRow.getByRole('button', { name: 'Delete task' }).click();
        await page.getByRole('button', { name: 'Delete Task', exact: true }).click();
        await expect(page.getByText(description, { exact: true })).toBeHidden();
    });
});
