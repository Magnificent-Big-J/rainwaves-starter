# Supported Versions

The baseline this release is built, tested, and shipped against. `composer.json`/
`package.json` constraints are the enforced source of truth — this table exists
so you don't have to reconstruct it from those files by hand.

| Component | Minimum | Tested against | Where it's pinned |
|---|---|---|---|
| PHP | 8.3 | 8.4 (Sail `sail-8.4/app` image, CI) | `composer.json` `"php": "^8.3"` |
| Laravel | 13.0 | 13.x latest | `composer.json` `"laravel/framework": "^13.0"` |
| Node.js | 20.19.0 | 20.19.0 | `.node-version` |
| MySQL | 8.4 | 8.4 (Sail `mysql:8.4` image) | `compose.yaml` |
| Redis | any (Alpine image) | `redis:alpine` | `compose.yaml` |
| `rainwaves/lara-auth-suite` | ^2.1.1 | ^2.1.1 | `composer.json` |
| `rainwaves/payfast-payment` | ^2.0.1 | ^2.0.1 | `composer.json` |

## Why these versions

- **PHP 8.3 minimum / 8.4 in Sail**: the codebase uses PHP 8.3+ constructs
  (enums, readonly properties, first-class callable syntax); Sail's local dev
  image tracks the newest stable PHP the framework supports, so day-to-day
  development happens one step ahead of the floor `composer.json` actually
  requires.
- **Laravel 13**: the starter was built against Laravel 13 from the start — no
  compatibility shims for earlier majors exist or are planned.
- **Node 20.19.0 pinned exactly** (not a floor): the frontend toolchain
  (Vite, ESLint 9 flat config, Vitest) is version-pinned rather than
  floor-constrained to keep local/CI builds reproducible; `.node-version` is the
  single source of truth `nvm`/`fnm`/CI all read from.
- **MySQL 8.4**: matches Sail's default. No SQLite-in-production path is
  supported — SQLite is used only for the isolated subprocess tests in
  `PresetInstallMatrixTest` and the CI-mode E2E server, never for a real
  deployment.

## What isn't version-matrixed

CI runs a single fixed version of each of the above — there's no
multi-version compatibility matrix (e.g. testing against both PHP 8.3 and 8.4,
or both Laravel 12 and 13). If your project needs to support a range of
environments beyond what's listed here, that's a deliberate scope decision to
revisit, not an oversight.
