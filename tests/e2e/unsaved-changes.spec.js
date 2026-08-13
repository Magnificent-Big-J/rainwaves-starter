import { expect, test } from './fixtures';

test.describe('unsaved-change protection', () => {
    test('blocks leaving a dirty profile form until confirmed', async ({ ownerPage: page }) => {
        await page.goto('/profile');

        await page.locator('input[autocomplete="name"]').fill('Dirty Name E2E');

        await page.getByRole('link', { name: 'Dashboard' }).click();
        await expect(page.getByText('Discard unsaved changes?')).toBeVisible();
        await expect(page).toHaveURL(/\/profile$/);

        await page.getByRole('button', { name: 'Cancel' }).click();
        await expect(page.locator('input[autocomplete="name"]')).toHaveValue('Dirty Name E2E');

        await page.getByRole('link', { name: 'Dashboard' }).click();
        await page.getByRole('button', { name: 'Discard changes' }).click();
        await page.waitForURL('**/dashboard');
    });

    test('does not prompt when leaving a clean profile form', async ({ ownerPage: page }) => {
        await page.goto('/profile');

        await page.getByRole('link', { name: 'Dashboard' }).click();
        await page.waitForURL('**/dashboard');
        await expect(page.getByText('Discard unsaved changes?')).not.toBeVisible();
    });
});
