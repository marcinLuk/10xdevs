// seed.spec.ts
import { test, expect } from '@playwright/test';

test('added task persists after page reload', async ({ page }) => {
    const taskData = `Task_${Date.now()}`;
    await page.goto('/dashboard');

    await page.getByRole('button', { name: 'Add Task' }).click();
    await page.getByRole('textbox', { name: 'Description' }).click();
    await page.getByRole('textbox', { name: 'Description' }).fill(taskData);
    await page.getByRole('textbox', { name: 'Date' }).fill('2026-06-10');
    await page.locator('#type_choice').selectOption('planting');
    await page.getByRole('button', { name: 'Add Task' }).nth(1).click();

    await expect(page.getByText(taskData, { exact: true })).toBeVisible();
    await page.reload()
    await expect(page.getByText(taskData, { exact: true })).toBeVisible();

    await page.getByRole('button', { name: 'Delete task' }).first().click();
    await page.getByRole('button', { name: 'Delete Task', exact: true }).click();
});
