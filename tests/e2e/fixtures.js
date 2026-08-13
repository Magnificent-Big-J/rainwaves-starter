import { test as base, expect } from '@playwright/test';

// Seeded by database/seeders/StarterUsersSeeder.php — real accounts, not fixtures this
// suite creates itself, so a failure here also means the seeder or auth flow broke.
export const OWNER = { email: 'owner@rainwaves.test', password: 'password' };
export const CUSTOMER = { email: 'customer@rainwaves.test', password: 'password' };

export const test = base.extend({
    ownerPage: async ({ page }, use) => {
        await page.goto('/auth/login');
        await page.getByLabel('Email').fill(OWNER.email);
        await page.getByRole('textbox', { name: 'Password' }).fill(OWNER.password);
        await page.getByRole('button', { name: 'Sign in' }).click();
        await page.waitForURL('**/dashboard');
        await use(page);
    },
});

export { expect };
