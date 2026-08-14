# Changelog

All notable changes to the Rainwaves Starter template are documented here. This
project doesn't follow strict [Semantic Versioning](https://semver.org/) in the
library sense — it's a template you copy once per project, not a dependency you
`composer update` — but version numbers still mark real, coherent milestones. See
`docs/upgrade-guide.md` for what a version number actually means for this repo,
and `docs/supported-versions.md` for the PHP/Laravel/Node/MySQL baseline each
release targets.

## [2.0.0] - 2026-08-14

The v1.0 baseline was a solid internal project (auth, permissions, media, activity
logging, mobile auth/sync, PayFast billing) but wasn't yet safe or reusable as a
starting point for new projects: secrets and machine-specific paths in
`composer.json`, hardcoded brand/navigation strings, no module boundaries, no CI,
no accessibility or E2E coverage. v2.0 closes that gap — see the tracker at
`Documents/projects/Rainwaves_Starter/Improvement/Planning/2026-07-29-rainwaves-starter-v2-tracker.md`
for the full session-by-session log this summary is drawn from.

### Security & portability (Gate 0)

- Removed PayFast return/cancel browser-route state mutation — those redirects are
  now cosmetic only; ITN is the sole source of truth for payment/subscription state.
- Server-side plan authority for checkout: `config/billing-plans.php` is the pricing
  catalog, checkout requests reference a `plan` key and never send amount/item name
  directly. Admin-on-behalf-of-a-customer checkout gated on `payments.manage`.
- Local-only PayFast dev/demo tooling (records, ITN simulation, subscription
  actions, the browser test page) structurally absent outside local/testing,
  proven by a dedicated production-route-hardening test, not just hidden UI.
- Replaced the `dev-main`/local-path `rainwaves/payfast-payment` dependency with an
  immutable released constraint; removed local Composer path repositories.
- Permission checks fail closed by default; a dedicated test proves a missing
  permission table doesn't silently fail open in production.
- `php artisan starter:doctor --production` — a real pre-deploy readiness gate.

### Configurable project factory (Gate 1)

- Brand, feature flags, and navigation moved out of hardcoded Vue strings into
  `config/app-brand.php` / `config/features.php` / `config/navigation.php`, served
  to the frontend via `GET /api/v1/web-config` and `stores/app-config.js`.
- `php artisan starter:init` — configures a freshly-copied project's brand,
  `composer.json` name, and (optionally) runs migrations/seeding. Fully
  non-interactive under `--no-interaction` for scripted/CI setup.
- Standard page catalogue completed (notifications, sessions, roles & permissions,
  audit log, settings, privacy, terms, support, the full 4xx/5xx error page set).
- Showcase/demo pages (component catalogue, foundation, PayFast browser test) live
  behind `features.show_showcase_pages` and environment gating, not mixed into the
  production route table.

### UI & CRUD foundation (Gate 2)

- `AppDataTable` reached a complete server-side contract: sort, filter, pagination,
  selectable rows + bulk actions, per-column visibility, generic `.xlsx` export
  (`App\Exports\CollectionExport`, reused across every export endpoint), persisted
  filter/sort state per table, and a responsive mobile card layout.
- Standardised server-validation-error handling (`normalizeErrorMessage`/
  `validationErrors`) across every admin/auth/profile form.
- Unsaved-change protection (in-app navigation + tab-close) on the two forms where
  losing work actually matters (profile, checkout).
- `docs/crud-contract.md` — the backend/frontend request-response contract every
  later module (Teams, Governance) was built against, extracted from the Users
  reference module (create/read/update/archive/restore).

### Modular capability packs (Gate 3)

- A real module registry (`App\Modules\ModuleManifest`/`ModuleRegistry`,
  `config/modules.php`, a conditional `bootstrap/providers.php` array) — one env
  var per module, genuinely removes routes/migrations/nav/UI when disabled, not
  just hidden behind a flag.
- **Billing** extracted into `App\Modules\Billing\...` with its own namespace
  (PayFast checkout/subscriptions/payments).
- **Mobile** formalised as a module (`/v1/meta`, mobile auth, devices, sync).
- **Teams** (SaaS module) added: multi-tenancy, plan-based member-cap usage limits,
  invites with a registration-gate bypass for brand-new invitees, admin read-only
  overview.
- **Governance** module added: versioned legal-document consent tracking with a
  forced accept gate, fully automated self-service data export/account deletion,
  and a maker-checker approval workflow for role elevation (granting admin/
  super-admin requires a second approver; demotions apply immediately).
- Module dependency/conflict validation, enforced both by `ModuleRegistry` at
  runtime and redundantly at boot in `bootstrap/providers.php` (the real Teams→
  Billing case is proven, not just a synthetic unit test).
- `PresetInstallMatrixTest` — every combination of the four module toggles
  (16 total) genuinely installs via `config:cache` + `route:cache` +
  `migrate:fresh --seed`, including the one combination that's expected to fail
  fast at boot (Teams enabled without Billing).

### Quality, delivery & operations (Gate 4)

- GitHub Actions CI: Composer/npm install, backend tests, Pint, PHPStan (Larastan,
  level 5), frontend lint/format/unit tests, Playwright E2E, accessibility checks,
  bundle-size budget, dependency audits.
- `tests/e2e/` (Playwright) — guest homepage, auth, admin user CRUD, Teams, and
  Governance flows, run against a real Sail instance locally or a throwaway
  self-contained instance in CI.
- `@axe-core/playwright` accessibility checks against representative pages per
  surface — found and fixed 4 real bugs (ARIA role conflicts, missing accessible
  names) immediately; genuine pre-existing gaps (colour contrast, landmark
  structure) tracked explicitly rather than hidden.
- `GET /health` (real DB/cache/queue connectivity, distinct from Laravel's
  liveness-only `/up`), a deployment workflow template
  (`.github/workflows/deploy.yml.example`), and three operational runbooks
  (`docs/operations/{rollback,backup-restore,incident-response}.md`).
- `php artisan starter:version` — starter version, git commit/branch, PHP/Laravel/
  Node versions, installed `rainwaves/*` package versions.

### Known limitations

- Frontend is plain JavaScript, not TypeScript — no static type-checking gate yet.
- `php artisan starter:upgrade-check` doesn't exist yet — there was nothing to
  check upgrades against until this release existed to tag against.
- Onboarding and print layouts are not built (Gate 1's layout matrix is only
  partially closed — guest/auth/admin/customer layouts exist and are complete).
