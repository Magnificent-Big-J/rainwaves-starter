# Upgrade Guide

## What "upgrading" means for this repo

Rainwaves Starter is a **template you copy once per project**, not a package you
`composer require` and later `composer update`. There is no dependency link back
to this repository from a project generated with `php artisan starter:init` — once
you've copied it and run `starter:init`, your project and this starter diverge
immediately and permanently. That's by design: you're meant to rename it, delete
what you don't need, and build your actual application on top.

Because of that, "upgrading" splits into two genuinely different situations:

### Starting a brand-new project

If you're copying the starter today, there's nothing to upgrade — just follow the
main `README.md` setup steps and `php artisan starter:init`. You're already on the
latest tagged version. This guide isn't for you.

### Backporting improvements into an existing project

If you (or someone else) copied an earlier baseline of this starter before it was
tagged — a pre-v2.0 snapshot — and want to pull specific v2.0 improvements into
that already-customized project, there is no automated path. A `git merge`/`git
pull` from this repository will not work cleanly against a project that has
already been renamed, restructured, and had application-specific code added on
top. Treat each improvement below as an independent, manually-ported feature, not
a single bulk upgrade.

## What changed in v2.0 that's worth backporting

Ordered roughly by how self-contained each piece is to lift out on its own — see
`CHANGELOG.md` for the full list and `CLAUDE.md` for how each one actually works.

1. **Module registry** (`App\Modules\ModuleManifest`/`ModuleRegistry`,
   `config/modules.php`, the conditional `bootstrap/providers.php` array). The
   most valuable and most self-contained piece — every other v2.0 module (Billing
   extraction, Mobile, Teams, Governance) is built on top of this pattern. Port
   this first if you're only taking one thing.
2. **Config-driven brand/navigation** (`config/app-brand.php`/`features.php`/
   `navigation.php`, `GET /api/v1/web-config`, `stores/app-config.js`). Requires
   your layouts to read from this config instead of hardcoded strings — a real,
   if mechanical, refactor if your project's layouts have since diverged
   significantly from the starter's originals.
3. **`docs/crud-contract.md`'s pattern** and `AppDataTable`'s server-side sort/
   filter/pagination/export contract, if your project's admin tables predate it.
4. **The module packs themselves** (Billing extraction, Mobile, Teams,
   Governance) — only relevant if you actually want that domain functionality;
   otherwise skip.
5. **Quality tooling** (CI workflow, Pint/PHPStan/ESLint/Prettier config, the E2E
   and accessibility test setup) — copy the config files and `.github/workflows/`
   directly; these don't depend on the module registry or config-driven UI.

## Things that will break if you copy code without reading it first

- **`payments.manage` permission enforcement** (RS-002): checkout now records
  under the *caller's own* user id unless they hold `payments.manage`, even if
  the request body includes a different `user_id`. If your project's checkout
  flow relies on setting `user_id` freely from the frontend, that will silently
  stop working — deliberately, since that was the actual security fix.
- **`rainwaves/payfast-payment` version constraint**: v2.0 requires `^2.0.1`, not
  `dev-main`. If your project pins an older/local version, resolve that
  separately before pulling in anything that assumes the newer package API.
- **Permission fail-open default**: `authx.permissions.fail_open_when_tables_missing`
  is `false` here. If your project set this `true` for some reason, changing it
  will start rejecting requests that previously passed.
- **`User` model gained `SoftDeletes`** as part of the Users CRUD reference
  module (Gate 2) — a straightforward additive migration, but confirm nothing in
  your project's own `User` queries assumes hard deletes.

## Version compatibility

See `docs/supported-versions.md` for the PHP/Laravel/Node/MySQL/Redis baseline
this release targets. Nothing in v2.0 requires a newer Laravel major version than
what the starter already ran on.
