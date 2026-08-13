import { expect, test } from './fixtures';

test.describe('admin users CRUD', () => {
    test('creates, archives, and restores a user end to end', async ({ ownerPage: page }) => {
        await page.goto('/admin/users');

        const email = `e2e-${Date.now()}@rainwaves.test`;

        await page.getByRole('button', { name: 'New user' }).click();

        // Seeded starter accounts already exist, so openCreate() also raises a "use the
        // seeded accounts?" info dialog alongside the real create-user form — dismiss it
        // first, it stacks on top and would otherwise intercept clicks meant for the form.
        const seedDialogDismiss = page.getByRole('button', { name: 'Dismiss' });
        if (await seedDialogDismiss.isVisible().catch(() => false)) {
            await seedDialogDismiss.click();
        }

        await page.getByLabel('Name').fill('E2E Test User');
        await page.getByLabel('Email', { exact: true }).fill(email);
        await page.getByLabel('Password', { exact: true }).fill('password123');
        await page.getByLabel('Confirm password').fill('password123');
        await page.getByRole('button', { name: 'Create user' }).click();

        const row = page.locator('tbody tr', { hasText: email });
        await expect(row).toBeVisible();

        await row.getByTitle('Archive').click();
        await page.getByRole('dialog').getByRole('button', { name: 'Archive' }).click();
        await expect(page.locator('tbody tr', { hasText: email })).toHaveCount(0);

        await page.locator('.admin-users__status-filter').click();
        await page.getByRole('option', { name: 'Archived' }).click();

        const archivedRow = page.locator('tbody tr', { hasText: email });
        await expect(archivedRow).toBeVisible();

        await archivedRow.getByTitle('Restore').click();
        await expect(page.locator('tbody tr', { hasText: email })).toHaveCount(0);

        await page.locator('.admin-users__status-filter').click();
        await page.getByRole('option', { name: 'Active' }).click();
        await expect(page.locator('tbody tr', { hasText: email })).toBeVisible();
    });

    test('export button downloads a real xlsx file', async ({ ownerPage: page }) => {
        await page.goto('/admin/users');

        const [download] = await Promise.all([
            page.waitForEvent('download'),
            page.getByRole('link', { name: 'Export' }).click(),
        ]);

        expect(download.suggestedFilename()).toBe('users.xlsx');
    });

    test('non-admin cannot reach the users page', async ({ page }) => {
        await page.goto('/auth/login');
        await page.getByLabel('Email').fill('customer@rainwaves.test');
        await page.getByRole('textbox', { name: 'Password' }).fill('password');
        await page.getByRole('button', { name: 'Sign in' }).click();
        await page.waitForURL((url) => !url.pathname.includes('/auth/login'));

        await page.goto('/admin/users');
        await expect(page).not.toHaveURL(/\/admin\/users$/);
    });
});
