import { OWNER, expect, test } from './fixtures';

test.describe('guest homepage', () => {
    test('loads with brand text and working nav links, no console errors', async ({ page }) => {
        // A 401 from the session store's own "am I logged in?" probe (GET .../me) is
        // expected on every guest page load, by design — filtered out rather than
        // asserting zero console errors outright, so a genuine JS error still fails.
        const consoleErrors = [];
        page.on('console', (msg) => {
            if (msg.type() === 'error' && !msg.text().includes('401')) {
                consoleErrors.push(msg.text());
            }
        });

        await page.goto('/');

        await expect(page.getByText('Rainwaves').first()).toBeVisible();

        await page.getByRole('navigation').getByRole('link', { name: 'Sign in' }).click();
        await expect(page).toHaveURL(/\/auth\/login$/);

        await page.goto('/');
        await page.getByRole('navigation').getByRole('link', { name: 'Register' }).click();
        await expect(page).toHaveURL(/\/auth\/register$/);

        expect(consoleErrors).toEqual([]);
    });
});

test.describe('login', () => {
    test('rejects bad credentials with an inline error, not a navigation', async ({ page }) => {
        await page.goto('/auth/login');
        await page.getByLabel('Email').fill(OWNER.email);
        await page.getByRole('textbox', { name: 'Password' }).fill('wrong-password');
        await page.getByRole('button', { name: 'Sign in' }).click();

        await expect(page.getByText(/credentials are incorrect/i).first()).toBeVisible();
        await expect(page).toHaveURL(/\/auth\/login$/);
    });

    test('signs in with valid credentials and can sign out again', async ({ page }) => {
        await page.goto('/auth/login');
        await page.getByLabel('Email').fill(OWNER.email);
        await page.getByRole('textbox', { name: 'Password' }).fill(OWNER.password);
        await page.getByRole('button', { name: 'Sign in' }).click();

        await page.waitForURL('**/dashboard');
        await expect(page.getByRole('button', { name: 'Sign out' })).toBeVisible();

        await page.getByRole('button', { name: 'Sign out' }).click();
        await page.waitForURL('**/auth/login');
    });
});
