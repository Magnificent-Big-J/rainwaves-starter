import { expect, test } from './fixtures';

test.describe('governance', () => {
    test('a brand new user is redirected to accept legal documents, and accepting clears the gate', async ({
        ownerPage: page,
        browser,
    }) => {
        await page.goto('/admin/users');

        const email = `e2e-legal-${Date.now()}@rainwaves.test`;

        await page.getByRole('button', { name: 'New user' }).click();
        const seedDialogDismiss = page.getByRole('button', { name: 'Dismiss' });
        if (await seedDialogDismiss.isVisible().catch(() => false)) {
            await seedDialogDismiss.click();
        }

        await page.getByLabel('Name').fill('E2E Legal Gate User');
        await page.getByLabel('Email', { exact: true }).fill(email);
        await page.getByLabel('Password', { exact: true }).fill('password123');
        await page.getByLabel('Confirm password').fill('password123');
        await page.getByRole('button', { name: 'Create user' }).click();
        await expect(page.locator('tbody tr', { hasText: email })).toBeVisible();

        const newUserContext = await browser.newContext();
        const newUserPage = await newUserContext.newPage();
        await newUserPage.goto('/auth/login');
        await newUserPage.getByLabel('Email').fill(email);
        await newUserPage.getByRole('textbox', { name: 'Password' }).fill('password123');
        await newUserPage.getByRole('button', { name: 'Sign in' }).click();
        await newUserPage.waitForURL('**/legal/accept', { timeout: 15000 });

        await newUserPage.getByRole('button', { name: 'Accept and continue' }).click();
        await newUserPage.waitForURL((url) => !url.pathname.includes('/legal/accept'), { timeout: 15000 });

        // The gate really cleared, not just a one-time bypass — re-navigating anywhere
        // authenticated must not bounce back to it.
        await newUserPage.goto('/profile');
        await expect(newUserPage).not.toHaveURL(/\/legal\/accept$/);

        await newUserContext.close();
    });

    test('a customer can export their data and delete their own account', async ({ ownerPage: page, browser }) => {
        await page.goto('/admin/users');

        const email = `e2e-selfservice-${Date.now()}@rainwaves.test`;

        await page.getByRole('button', { name: 'New user' }).click();
        const seedDialogDismiss = page.getByRole('button', { name: 'Dismiss' });
        if (await seedDialogDismiss.isVisible().catch(() => false)) {
            await seedDialogDismiss.click();
        }

        await page.getByLabel('Name').fill('E2E Self Service User');
        await page.getByLabel('Email', { exact: true }).fill(email);
        await page.getByLabel('Password', { exact: true }).fill('password123');
        await page.getByLabel('Confirm password').fill('password123');
        await page.getByRole('button', { name: 'Create user' }).click();
        await expect(page.locator('tbody tr', { hasText: email })).toBeVisible();

        const userContext = await browser.newContext();
        const userPage = await userContext.newPage();
        await userPage.goto('/auth/login');
        await userPage.getByLabel('Email').fill(email);
        await userPage.getByRole('textbox', { name: 'Password' }).fill('password123');
        await userPage.getByRole('button', { name: 'Sign in' }).click();
        await userPage.waitForURL('**/legal/accept', { timeout: 15000 });
        await userPage.getByRole('button', { name: 'Accept and continue' }).click();
        await userPage.waitForURL((url) => !url.pathname.includes('/legal/accept'), { timeout: 15000 });

        await userPage.goto('/profile');

        const [download] = await Promise.all([
            userPage.waitForEvent('download'),
            userPage.getByRole('link', { name: 'Export my data' }).click(),
        ]);
        expect(download.suggestedFilename()).toBe('my-data.json');

        await userPage.getByRole('button', { name: 'Delete my account' }).click();
        await userPage.getByRole('dialog').getByRole('button', { name: 'Delete account' }).click();
        await userPage.waitForURL((url) => url.pathname === '/' || url.pathname.includes('/auth/login'), {
            timeout: 15000,
        });

        await userContext.close();
    });

    test('role elevation requires a second approver and blocks self-approval', async ({ ownerPage: page }) => {
        await page.goto('/admin/users');

        const email = `e2e-elevate-${Date.now()}@rainwaves.test`;

        await page.getByRole('button', { name: 'New user' }).click();
        const seedDialogDismiss = page.getByRole('button', { name: 'Dismiss' });
        if (await seedDialogDismiss.isVisible().catch(() => false)) {
            await seedDialogDismiss.click();
        }

        await page.getByLabel('Name').fill('E2E Elevate Target');
        await page.getByLabel('Email', { exact: true }).fill(email);
        await page.getByLabel('Password', { exact: true }).fill('password123');
        await page.getByLabel('Confirm password').fill('password123');
        await page.getByRole('combobox', { name: 'Roles' }).click();
        await page.getByRole('option', { name: 'admin', exact: true }).click();
        await page.keyboard.press('Escape');
        await page.getByRole('button', { name: 'Create user' }).click();

        const row = page.locator('tbody tr', { hasText: email });
        await expect(row).toBeVisible();
        // The elevated role was stripped, not applied immediately.
        await expect(row).not.toContainText('admin');

        await page.goto('/admin/governance');
        const pendingRow = page.locator('.request-row', { hasText: email });
        await expect(pendingRow).toBeVisible();

        // Self-approval is blocked — the requester (the seeded owner account, acting
        // through this same admin session) cannot approve their own request.
        await pendingRow.getByRole('button', { name: 'Approve' }).click();
        await page.getByRole('dialog').getByRole('button', { name: 'Approve' }).click();
        await expect(page.locator('.app-toast')).toContainText('cannot approve a role change you requested yourself');
    });
});
