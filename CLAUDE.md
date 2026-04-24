# rainwaves-starter — CLAUDE.md

## What this is

Internal Laravel 13 + Vue SPA starter template for Rainwaves apps. Not a renamed vendor template — owned codebase with explicit structure.

Stack: Laravel 13 · Laravel Sanctum · Vue 3 · Vuetify 3 · Pinia · Vite · Sail

## Package baseline

| Package | Purpose |
|---|---|
| `rainwaves/lara-auth-suite` | Session auth, 2FA (TOTP + email OTP), password reset |
| `rainwaves/payfast-payment` | PayFast one-time + subscription checkout |
| `spatie/laravel-permission` | Roles and permissions via `HasRoles` |
| `spatie/laravel-medialibrary` | Avatar and file management |
| `spatie/laravel-activitylog` | Audit trail |
| `laravel/horizon` | Queue dashboard |
| `laravel/sanctum` | Cookie-based API auth |

## Directory layout

```
app/
  Contracts/          # Service interfaces
  Http/
    Controllers/
      Api/            # Authenticated API controllers
        Admin/        # Admin-scoped controllers
      PayFastController.php
    Requests/
      Admin/
      Payments/
    Resources/
      Admin/
  Models/             # User, Payment, Subscription, PaymentEvent
  Services/           # UserAdminService, PayFastCheckoutService
  Providers/          # AppServiceProvider — binds service interfaces

resources/js/app/
  components/         # AppDataTable, AppSectionCard, MediaUploader, FormActions, FormStatusAlert, BusyOverlay, AppToastHost, AuthCard
  layouts/            # default.vue (sidebar + guest bar), auth.vue (split auth shell)
  pages/
    auth/             # login, forgot-password, reset-password, verify, profile
    admin/            # users
    index.vue         # dashboard/home
  stores/             # session, profile, admin-users, two-factor, app-errors, auth-shared (utils)
  utils/api.js        # ofetch instance with credentials + headers
  plugins/vuetify.js  # rainwavesStarter theme
  router/index.js     # vue-router/auto-routes + auth guard
```

## Auth flow

- Sanctum cookie-based session auth via `rainwaves/lara-auth-suite`
- `session` store — login/logout/ensureLoaded/fetchUser
- Router guard in `router/index.js`:
  - `meta.requiresAuth` → redirect to `/auth/login` if unauthenticated
  - `meta.guestOnly` → redirect to `/` if already authenticated
  - Pending 2FA → force `/auth/verify`
- Auth package routes are registered automatically under the configured prefix (see `config/authx.php`)
- `GET /api/v1/me` returns `AuthUserResource` (id, name, email, avatar_url, roles, permissions)

## API routes

All under `auth:sanctum` middleware:

| Method | Endpoint | Controller |
|---|---|---|
| GET | `/api/v1/ping` | inline |
| GET | `/api/v1/me` | inline (AuthUserResource) |
| GET | `/api/v1/profile` | ProfileController@show |
| PATCH | `/api/v1/profile` | ProfileController@update |
| PUT | `/api/v1/profile/password` | ProfileController@updatePassword |
| GET | `/api/v1/users` | UserAdminController@index |
| POST | `/api/v1/users` | UserAdminController@store |
| PATCH | `/api/v1/users/{user}` | UserAdminController@update |

Admin routes are gated by `users.view` / `users.create` / `users.update` permissions.

## Web routes (PayFast)

| Method | Endpoint | Notes |
|---|---|---|
| POST | `/payments/payfast/initiate` | Returns PayFast HTML form for one-time payment |
| POST | `/payments/payfast/subscriptions/initiate` | Returns PayFast HTML form for subscription |
| POST | `/payments/payfast/itn` | ITN webhook — CSRF excluded |
| GET | `/payments/payfast/return` | PayFast return redirect |
| GET | `/payments/payfast/cancel` | PayFast cancel redirect |

## Enums

`App\Enums\PaymentStatus` and `App\Enums\SubscriptionStatus` are PHP backed string enums.

Both expose:
- `->label()` — human-readable display string
- `->color()` — Vuetify colour token (`success`, `error`, `warning`, `info`, `default`)

`PaymentStatus` also exposes `->isTerminal()`.
`SubscriptionStatus` also exposes `->isActive()` and `->isTerminal()`.

Models cast the `status` column to the relevant enum. When you need the raw string (e.g. bulk `update()`), pass `EnumCase->value`.

## PayFast

Service: `PayFastCheckoutService` (bound via `PayFastCheckoutServiceInterface`)

- `initiateOneTimePayment` — creates `Payment` record, returns HTML checkout form
- `initiateSubscriptionPayment` — creates `Subscription` record, returns HTML checkout form
- `processItn` — validates payment, updates record, idempotent via `PaymentEvent` event_ref unique constraint
- `markReturn` / `markCancelled` — updates status from redirect

Config: `config/payfast.php` — reads merchant ID, key, pass phrase, env, URLs from `.env`.

## Roles & permissions

Roles: `super-admin`, `admin`, `staff`, `customer`

Seeded in `RolesAndPermissionsSeeder`. Run with `artisan db:seed --class=RolesAndPermissionsSeeder`.

Permission naming convention: `resource.action` (e.g. `users.view`, `payments.create`).

User model uses `HasRoles` from `spatie/laravel-permission`. Guard: `web`.

## Frontend components

| Component | Purpose |
|---|---|
| `AppDataTable` | Paginated table with search, toolbar slot, row slot |
| `AppSectionCard` | Titled card wrapper consistent with design system |
| `MediaUploader` | Avatar picker with preview, remove, emit |
| `FormStatusAlert` | Inline success/error alert |
| `FormActions` | Submit + loading state footer row |
| `BusyOverlay` | Full-area loading overlay |
| `AppToastHost` | Toast notifications via app-errors store |
| `AuthCard` | Centered auth card wrapper |
| `AppHeader` | Unused in authenticated shell; kept for guest/public views |

## Design system

Theme: `rainwavesStarter` in `plugins/vuetify.js`

CSS custom properties (in `resources/css/app.css`):
- `--starter-ink` — body text
- `--starter-muted` — secondary text
- `--starter-surface` / `--starter-surface-strong` — backgrounds
- `--starter-accent` — primary green (#006a52)
- `--starter-accent-soft` — muted green tint
- `--starter-highlight` — amber (#d38b00)

Font: Instrument Sans with Segoe UI fallback.

Cards default to `elevation: 0`, `rounded: xl`. Buttons default to `rounded: xl`.

## Layouts

- `default.vue` — authenticated shell with collapsible sidebar nav, breadcrumb, user avatar/logout footer. Guest users see a top nav bar instead of sidebar.
- `auth.vue` — split-panel shell: marketing copy left, form right. Responsive (panel-only on mobile).

## Migrations

Run order matters. Key tables:
1. users (core Laravel)
2. personal_access_tokens
3. activity_log
4. permission_tables (roles, permissions, model_has_roles, model_has_permissions, role_has_permissions)
5. media
6. two_factor_* (from lara-auth-suite)
7. subscriptions
8. payments
9. payment_events

## Local development

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan db:seed
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
```

Vite proxies in `vite.config.js`. VITE_AUTH_BASE defaults to `/auth`.

## Key conventions

- No `@core` / `@layouts` — all components are owned and explicitly placed
- API utility is `ofetch` from `resources/js/app/utils/api.js` — credentials included by default
- Stores use absolute API paths (`/api/v1/...`), not relative path helpers
- `ProfileResource` wraps the authenticated user for the profile endpoint (includes avatar_url from media)
- `AuthUserResource` wraps the session user for `/api/v1/me` (lighter, same avatar logic)
- `UserAdminService` is dependency-injected; bind to `UserAdminServiceInterface` in AppServiceProvider
- ITN endpoint is CSRF-excluded via `withoutMiddleware(['web'])`
- ITN signature + merchant ID validation lives in `PayFastCheckoutService::validateItnSignature` — controller is a pure HTTP adapter
- `PAYFAST_ENV=sandbox` for local/staging; switch to `live` in production
- `PAYFAST_NOTIFY_URL` must point to `/payments/payfast/itn` (publicly reachable)
- Schema::hasTable checks protect permission/media calls before migrations run
