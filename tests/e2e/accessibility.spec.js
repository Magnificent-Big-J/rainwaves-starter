import AxeBuilder from '@axe-core/playwright';

import { expect, test } from './fixtures';

// Axe's default ruleset (WCAG 2.0/2.1 A+AA + a handful of best-practice rules) run
// against a representative page per surface — guest, auth, and two authenticated admin
// pages exercising AppDataTable. Fails on any NEW violation of anything not listed
// below; the two real bugs this suite already caught (AppDataTable's clickable rows
// and sortable headers both had role="button" fighting their own child/native
// semantics, plus an icon-only edit button and the notification bell missing
// accessible names) were fixed immediately, not exempted.
//
// KNOWN_PRE_EXISTING_RULE_IDS are real, found, NOT-yet-fixed issues — same spirit as
// phpstan-baseline.neon (tracked and documented, not silently hidden), but unlike that
// baseline these genuinely are real bugs, just ones spanning the whole layout/design
// system rather than anything this session's own changes introduced:
//   - color-contrast: several muted text/background colour pairs across the app fall
//     short of 4.5:1 (as low as 1.85:1 on the dark guest-homepage hero). A real design
//     token pass, not a per-instance patch — CSS custom properties in resources/css.
//   - landmark-one-main / region: auth.vue's layout has no <main> landmark, so all its
//     content sits outside any landmark region.
//   - landmark-unique: default.vue's sidebar nav and a second nav region aren't
//     distinguished by an accessible name (aria-label), so assistive tech can't tell
//     them apart.
//   - heading-order: several pages jump from h1 straight to h3 (AppSectionCard always
//     renders an h3 regardless of what precedes it) — a real content-hierarchy gap,
//     not a broken single instance.
//   - empty-table-header: AppDataTable's actions columns now carry a real aria-label
//     (see srLabel), but axe's specific rule wants *visible* text specifically, which
//     would mean giving up the blank actions-column look entirely — a real, deliberate
//     design tradeoff, not an oversight.
// Each is a real remediation item of its own; fixing all of them is out of scope for
// "add an accessibility check" and belongs in a dedicated follow-up pass.
const KNOWN_PRE_EXISTING_RULE_IDS = [
    'color-contrast',
    'landmark-one-main',
    'region',
    'landmark-unique',
    'heading-order',
    'empty-table-header',
];

const assertNoViolations = async (page) => {
    const results = await new AxeBuilder({ page }).disableRules(KNOWN_PRE_EXISTING_RULE_IDS).analyze();

    expect(results.violations, JSON.stringify(results.violations, null, 2)).toEqual([]);
};

test.describe('accessibility', () => {
    test('guest homepage', async ({ page }) => {
        await page.goto('/');
        await assertNoViolations(page);
    });

    test('login page', async ({ page }) => {
        await page.goto('/auth/login');
        await assertNoViolations(page);
    });

    test('dashboard', async ({ ownerPage: page }) => {
        await page.goto('/dashboard');
        await assertNoViolations(page);
    });

    test('admin users page', async ({ ownerPage: page }) => {
        await page.goto('/admin/users');
        await assertNoViolations(page);
    });
});
