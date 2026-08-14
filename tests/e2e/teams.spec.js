import { expect, test } from './fixtures';

test.describe('teams', () => {
    test('customer creates a team, invites and revokes a member, admin sees it in the overview, then it is deleted', async ({
        customerPage: page,
        browser,
    }) => {
        await page.goto('/account/team');

        // Leftover from an interrupted previous run — clean up before asserting the
        // empty state, so this test is deterministically re-runnable against a real
        // (not throwaway) dev database too.
        const existingDeleteButton = page.getByRole('button', { name: 'Delete team' });
        if (await existingDeleteButton.isVisible().catch(() => false)) {
            await existingDeleteButton.click();
            await page.getByRole('dialog').getByRole('button', { name: 'Delete team' }).click();
            await expect(page.getByRole('heading', { name: 'Create your team' })).toBeVisible();
        }

        await expect(page.getByRole('heading', { name: 'Create your team' })).toBeVisible();

        const teamName = `E2E Team ${Date.now()}`;
        await page.getByLabel('Team name').fill(teamName);
        await page.getByRole('button', { name: 'Create team' }).click();

        await expect(page.locator('.stat-card', { hasText: 'Your role' })).toContainText('Owner');
        await expect(page.locator('.stat-card', { hasText: 'Members' })).toContainText('of 3 on your current plan');

        await page.goto('/account/team-members');
        await page.getByRole('button', { name: 'Invite member' }).click();

        const inviteEmail = `e2e-invitee-${Date.now()}@example.com`;
        await page.getByLabel('Email', { exact: true }).fill(inviteEmail);
        await page.getByRole('button', { name: 'Send invite' }).click();

        const inviteRow = page.locator('.invite-row', { hasText: inviteEmail });
        await expect(inviteRow).toBeVisible();

        // A second, admin-surface session views the platform-wide read-only overview —
        // real cross-actor visibility, not just the customer's own view of their team.
        const adminContext = await browser.newContext();
        const adminPage = await adminContext.newPage();
        await adminPage.goto('/auth/login');
        await adminPage.getByLabel('Email').fill('owner@rainwaves.test');
        await adminPage.getByRole('textbox', { name: 'Password' }).fill('password');
        await adminPage.getByRole('button', { name: 'Sign in' }).click();
        await adminPage.waitForURL('**/dashboard');

        await adminPage.goto('/admin/teams');
        const adminTeamRow = adminPage.locator('tbody tr', { hasText: teamName });
        await expect(adminTeamRow).toBeVisible();

        await adminTeamRow.click();
        await expect(adminPage.getByText('Members').first()).toBeVisible();
        await expect(adminPage.getByText('customer@rainwaves.test')).toBeVisible();
        await adminContext.close();

        await inviteRow.getByRole('button', { name: 'Revoke' }).click();
        await page.getByRole('dialog').getByRole('button', { name: 'Revoke' }).click();
        await expect(page.locator('.invite-row', { hasText: inviteEmail })).toHaveCount(0);

        await page.goto('/account/team');
        await page.getByRole('button', { name: 'Delete team' }).click();
        await page.getByRole('dialog').getByRole('button', { name: 'Delete team' }).click();
        await expect(page.getByRole('heading', { name: 'Create your team' })).toBeVisible();
    });

    test('a customer cannot reach the admin teams overview', async ({ customerPage: page }) => {
        await page.goto('/admin/teams');
        await expect(page).not.toHaveURL(/\/admin\/teams$/);
    });
});
