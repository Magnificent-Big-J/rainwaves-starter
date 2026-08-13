import { defineConfig, devices } from '@playwright/test';

// Local dev: targets the developer's already-running Sail instance (real dev data,
// no separate server spun up) — same "verify against the running app, not a fake"
// pattern used throughout this project. CI: boots a throwaway self-contained instance
// via scripts/e2e-server.sh (fresh sqlite DB, migrated + seeded) since nothing else is
// running there. See CLAUDE.md "E2E smoke tests".
const baseURL = process.env.PLAYWRIGHT_BASE_URL || 'http://localhost';

export default defineConfig({
    testDir: './tests/e2e',
    fullyParallel: true,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 1 : 0,
    workers: process.env.CI ? 1 : undefined,
    reporter: 'list',
    use: {
        baseURL,
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
    },
    projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'] } }],
    webServer: process.env.CI
        ? {
              command: 'bash scripts/e2e-server.sh',
              url: `${baseURL}/up`,
              reuseExistingServer: false,
              timeout: 60000,
          }
        : undefined,
});
