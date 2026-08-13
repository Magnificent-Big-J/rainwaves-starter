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
  layouts/            # default.vue (admin sidebar + guest bar), auth.vue (split auth shell), guest.vue (marketing pages), customer.vue (lighter customer-surface topbar)
  pages/
    auth/             # login, forgot-password, reset-password, verify
    admin/            # users, roles, audit-log, settings
    account/          # sessions (active devices)
    index.vue         # marketing home (layout: guest)
    dashboard.vue      # admin surface home (layout: default)
    customer/home.vue  # customer surface home (layout: customer)
    notifications.vue  # full notification history (layout: contextual)
    profile.vue       # account/security page — not under auth/, it's not an auth flow
    legal/            # privacy, terms (static content pages)
    support.vue       # contact/support page
  stores/             # session, app-config (brand/nav bootstrap), profile, admin-users, two-factor, app-errors, notifications, auth-shared (utils)
  utils/api.js        # ofetch instance with credentials + headers
  plugins/vuetify.js  # rainwavesStarter theme
  router/index.js     # vue-router/auto-routes + auth guard + showcase/environment gating
```

Which layout a page uses is declared per-page via a `<route lang="json">{ "meta": { "layout": "..." } }</route>` block (unplugin-vue-router convention), not inferred from folder location. `layout: "contextual"` (used by profile.vue, notifications.vue) picks `default.vue` on the admin surface and `customer.vue` on the customer surface at render time (see `App.vue`).

## Auth flow

- Sanctum cookie-based session auth via `rainwaves/lara-auth-suite`
- `session` store — login/logout/ensureLoaded/fetchUser
- Router guard in `router/index.js`:
  - `meta.requiresAuth` → redirect to `/auth/login` if unauthenticated
  - `meta.guestOnly` → redirect to `/` if already authenticated
  - Pending 2FA → force `/auth/verify`
- Auth package routes are registered automatically under the configured prefix (see `config/authx.php`)
- `GET /api/v1/me` returns `AuthUserResource` (id, name, email, avatar_url, roles, permissions)
- `App\Listeners\LogSecurityActivity` (registered in `AppServiceProvider::registerSecurityAuditListeners()`) records all 14 lara-auth-suite security events into `spatie/laravel-activitylog` under `log_name = 'security'` — the package's own listener only writes a debug-level log line with the event class name, nothing queryable. Each entry carries the causing user (when the event has one), IP, truncated user-agent, and event-specific safe fields (channel, rate-limit policy/dimension, etc.). `AuthenticationRateLimited`, `AuthenticationStateRevoked`, `TwoFactorDisabled`, and `RecoveryCodeUsed` additionally get `severity: 'high'` and a `Log::warning()` line. `LoginFailed`/`AuthenticationRateLimited` never bind a causer/subject — the package deliberately keeps those non-enumerating, and looking the email up to attach a user would undo that.

## API response envelope

Every `/api/*` response uses `App\Http\Responses\Envelope`:

```json
{ "success": true, "message": "", "data": ..., "meta": {}, "errors": {} }
```

- Controllers return `Envelope::success($data, $message, $meta, $status)` / `Envelope::error(...)` (no response macro — call the helper directly).
- Paginators (or resource collections wrapping them) are unwrapped automatically; pagination lands in `meta.pagination` (`current_page`, `per_page`, `last_page`, `total`).
- The exception handler (bootstrap/app.php) renders 401/403/404/409/422/429/500 envelopes for `api/*`; validation errors land in `errors` as a field map.
- The SPA catch-all in `routes/web.php` excludes `api/*` so unknown API paths 404 with an envelope.

## API routes

Public:

| Method | Endpoint | Notes |
|---|---|---|
| GET | `/api/v1/meta` | Mobile bootstrap: min app version, feature flags, sync resources, enum option sets (config/mobile.php) |
| POST | `/api/v1/auth/login` | Mobile token login (throttle `mobile-auth`) |
| POST | `/api/v1/auth/two-factor` | Complete pending 2FA challenge (throttle `mobile-auth`) |

Under `auth:sanctum` + `idempotency` middleware:

| Method | Endpoint | Controller |
|---|---|---|
| POST | `/api/v1/auth/logout` | MobileAuthController@logout (revokes current PAT) |
| GET | `/api/v1/ping` | inline |
| GET | `/api/v1/me` | inline (AuthUserResource) |
| GET/POST | `/api/v1/devices` | DeviceController (list / upsert by uuid) |
| DELETE | `/api/v1/devices/{uuid}` | DeviceController@destroy (revokes linked token) |
| POST | `/api/v1/sync/operations` | SyncController@operations (batch ingest) |
| GET | `/api/v1/sync/delta` | SyncController@delta (`?since=<iso8601>&resources=a,b`) |
| GET | `/api/v1/notifications` | NotificationController@index (`meta.unread_count`, `?unread=1`) |
| POST | `/api/v1/notifications/read-all` | NotificationController@markAllRead |
| POST | `/api/v1/notifications/{id}/read` | NotificationController@markRead |
| GET | `/api/v1/profile` | ProfileController@show |
| PATCH | `/api/v1/profile` | ProfileController@update |
| PUT | `/api/v1/profile/password` | ProfileController@updatePassword |
| GET | `/api/v1/billing` | BillingController@show — own latest payment/subscription/recent events |
| GET | `/api/v1/subscriptions` | SubscriptionController@index — own subscriptions, paginated |
| POST | `/api/v1/subscriptions/{subscription}/cancel` | SubscriptionController@cancel — own subscription only |
| GET | `/api/v1/sessions` | SessionController@index — active browser sessions |
| DELETE | `/api/v1/sessions/others` | SessionController@destroyOthers |
| DELETE | `/api/v1/sessions/{id}` | SessionController@destroy |
| GET | `/api/v1/users` | UserAdminController@index |
| POST | `/api/v1/users` | UserAdminController@store |
| PATCH | `/api/v1/users/{user}` | UserAdminController@update |
| GET | `/api/v1/roles` | RoleAdminController@index |
| PUT | `/api/v1/roles/{role}/permissions` | RoleAdminController@updatePermissions |
| GET | `/api/v1/activity-log` | ActivityLogController@index |

Public also includes `GET /api/v1/web-config` (brand/navigation/features bootstrap, see below).

Admin routes are gated by permissions: `users.view` / `users.create` / `users.update`, `roles.view` / `roles.manage`, `activity.view`.

## Mobile auth (token flow)

Web SPA keeps cookie/session auth (untouched). Mobile uses Sanctum PATs:

- `POST /api/v1/auth/login` with `email`, `password`, `device {uuid, platform, model?, os_version?, app_version?}` → PAT named by device uuid with the `mobile` ability, plus user + device in `data`. One live token per device — re-login replaces the previous token.
- If the user has 2FA, login returns `{two_factor_required, pending_auth_id, channel}`; complete with `POST /api/v1/auth/two-factor` (`pending_auth_id`, `code`, optional `channel`: `email` | `totp` | `recovery`). Device payload is stashed server-side against the pending id.
- `MobileAuthService` (bound to `MobileAuthServiceInterface`) reuses lara-auth-suite services (`AuthService`, `ITwoFactorAuth`, `PendingAuthManager`) — token issue/device link is the only app-owned logic.
- Devices table links `personal_access_token_id`; deleting a device revokes its token.

## Idempotency

`idempotency` middleware (alias in bootstrap/app.php) engages on mutating requests carrying an `Idempotency-Key` header: first non-5xx response is cached 24h and replayed verbatim (`Idempotency-Replay: true`); same key with a different payload → 409; concurrent same-key requests are serialised by an atomic lock. Keys are scoped per user.

## Sync framework

Offline-first sync ships as a framework; apps register resources in `config/sync.php` (`resource => ['handler' => SyncResourceHandler, 'delta' => DeltaProvider]`). Devices are the reference implementation (`DeviceSyncHandler`, `DeviceDeltaProvider`).

- `POST /api/v1/sync/operations` — batch of `{id (uuidv7), type, resource, client_id?, resource_id?, payload, occurred_at, depends_on[]}`. Per-op transactional apply; results are `{id, status: applied|conflict|failed, server_id?, errors}`. Idempotent via the `sync_operations` ledger (replayed op ids return their stored result). Unknown resources / bad payloads fail per-op, never the batch.
- Conflict pattern: client echoes the `updated_at` it last saw as `payload.version`; handler throws `SyncConflictException` when the server row is newer. Deletes are idempotent (already-gone → applied).
- `GET /api/v1/sync/delta?since=<iso8601>&resources=a,b` — per resource: `records` (serialized), `tombstones`, `has_more`, `cursor`; `meta.server_time` is the client's next cursor once all `has_more` are false.
- Deletions propagate via the `RecordsTombstones` model trait (define `syncResource()` / `syncResourceId()`), written to `sync_tombstones`.

## Notifications

Database notifications with a mobile payload contract. Extend `App\Notifications\AppNotification` and implement `type()`, `title()`, `body()`, `deepLink()` (`{route, params}` the app opens on tap). Channels come from `config/mobile.php` `notification_channels` — add an FCM/APNs channel there later without touching subclasses. `SystemAnnouncementNotification` is the reference implementation.

## Web routes (PayFast)

Production route table (always registered):

| Method | Endpoint | Notes |
|---|---|---|
| POST | `/payments/payfast/initiate` | Returns PayFast HTML form for one-time payment; throttled `payfast-initiate` |
| POST | `/payments/payfast/subscriptions/initiate` | Returns PayFast HTML form for subscription; throttled `payfast-initiate` |
| POST | `/payments/payfast/itn` | ITN webhook — CSRF excluded. **The only path allowed to mutate payment/subscription state.** |
| GET | `/payments/payfast/return` | Cosmetic redirect only — reads query params for display, never writes to the DB |
| GET | `/payments/payfast/cancel` | Cosmetic redirect only — same as above |

`handleReturn`/`handleCancel` redirect to `/payfast-browser-test` in local/testing and `/dashboard` everywhere else. A buyer fully controls this URL (wrong id, forged token, replay) so it must never be trusted — see `tests/Feature/PayFastV2CompatibilityTest.php`.

Local/testing-only (registered from `routes/payfast-local.php`, included from `routes/web.php` only when `app()->environment(['local', 'testing'])` — never present in the production route table, proven by `tests/Feature/ProductionRouteHardeningTest.php`):

| Method | Endpoint | Notes |
|---|---|---|
| GET | `/payments/payfast/records` | Dumps recent payments/subscriptions/events as JSON |
| POST | `/payments/payfast/simulate-itn` | Builds and replays a signed ITN payload for a given record |
| POST | `/payments/payfast/subscriptions/action` | Native subscription actions (fetch/pause/unpause/cancel/card-update-link/update/adhoc) |

## Enums

`App\Enums\PaymentStatus`, `SubscriptionStatus`, `DevicePlatform`, `SyncOperationStatus` and `SyncOperationType` are PHP backed string enums.

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
- `processItn` — validates payment, updates record, idempotent via `PaymentEvent` event_ref unique constraint. This is the **only** method that ever changes `status` on a `Payment`/`Subscription` — there is deliberately no `markReturn`/`markCancelled`; the browser return/cancel routes are cosmetic redirects only.

Config: `config/payfast.php` — reads merchant ID, key, pass phrase, env, URLs from `.env`.

### Gotcha: spatie/laravel-permission's dynamic-guard relations crash inside `auth:sanctum` routes

`Role->users()` / `Permission->roles()` (and anything built on them, e.g. `Role::withCount('users')`) resolve their target model via `config('auth.defaults.guard')` when called on a fresh (attribute-less) model instance — which is exactly how Eloquent builds a relation for `withCount`/`loadCount`. `Illuminate\Auth\Middleware\Authenticate::authenticate()` calls `AuthManager::shouldUse('sanctum')` for every request that authenticates via `auth:sanctum`, which **mutates that same `auth.defaults.guard` config value for the rest of the request**. `'sanctum'` has no `config('auth.guards.sanctum')` entry (Sanctum registers its guard programmatically), so `Guard::getModelForGuard('sanctum')` returns `null` and the relation build fatals with `Error: Class name must be a valid object or a string` — a controller-only failure that never reproduces calling the same query directly in a test body or tinker (see `RoleAdminController::withUserCounts()` for the workaround: count the `model_has_roles`/`model_has_permissions` pivot table directly instead of using the relation).

## Standard page catalogue (RS-105)

Beyond auth/profile/admin-users (pre-existing), the starter now ships:

| Page | Route | Backend |
|---|---|---|
| Billing | `/account/billing` | `GET /api/v1/billing` + a real PayFast checkout (see "PayFast checkout UI" below) |
| Notifications (full history) | `/notifications` | `GET/POST /api/v1/notifications*` (pre-existing) |
| Active sessions | `/account/sessions` | `GET/DELETE /api/v1/sessions*` — real browser sessions from `sessions` table, distinct from mobile devices |
| Roles & Permissions | `/admin/roles` | `GET /api/v1/roles`, `PUT /api/v1/roles/{role}/permissions` — `super-admin` is immutable through this endpoint |
| Audit Log | `/admin/audit-log` | `GET /api/v1/activity-log` — `spatie/laravel-activitylog` entries, includes the 14 security events from `LogSecurityActivity` |
| Settings | `/admin/settings` | Read-only view of `config/app-brand.php` / `features.php` / `navigation.php` via the existing `/api/v1/web-config` |
| Privacy / Terms | `/legal/privacy`, `/legal/terms` | Static placeholder content — replace before launch |
| Support | `/support` | Static contact page reading `brand.support_email` |
| Not found | any unmatched path, or `/showcase-disabled` | `pages/[...notFound].vue` — SPA-side catch-all |

Laravel-level error pages (`resources/views/errors/{404,403,419,429,500,503}.blade.php`) are separate from the SPA catch-all above — they render for genuine backend HTTP errors that occur before Vue mounts (see "Brand & navigation config" section for detail).

## PayFast checkout UI

Until this was added, the *only* place in the SPA that ever called `/payments/payfast/initiate` or `/subscriptions/initiate` was `payfast-browser-test.vue` — a local/testing-only, admin-only dev tool. There was no production-usable way for a real user to actually pay or subscribe, and `dashboard.vue`/`customer/home.vue`'s billing widgets were 100% hardcoded literal props (`amount="R 1,499.00"`, `customer="Starter Owner"`, etc.) — decorative, not real data.

- `resources/js/app/utils/payfast-checkout.js` — `startPayFastCheckout(mode, payload)`. POSTs to the web-layer initiate route (returns raw HTML — a `PayFastClient`-generated auto-submit form, not JSON), parses out the action + hidden fields, builds a real `<form>`, and submits it so the browser navigates to PayFast's hosted checkout. Same mechanism the dev tool uses, extracted so there's one implementation.
- `stores/billing.js` / `GET /api/v1/billing` (`BillingController`) — the authenticated user's own latest payment, latest subscription (active preferred over a more recently-touched terminal one), and last 5 payment events. Scoped to `user_id`; nothing cross-user.
- `pages/account/billing.vue` — real payment/subscription cards + event timeline + a genuine checkout form (item name, amount, one-time vs subscription) that calls `startPayFastCheckout`. Verified end-to-end with a real headless-browser submission landing on PayFast's actual sandbox checkout page.
- `dashboard.vue` and `customer/home.vue` now pull the same `billing` store instead of hardcoding `PaymentStatusCard`/`SubscriptionStatusCard` props; `dashboard.vue`'s role/permission stat counts also now come from `admin-roles.js` instead of hardcoded `'4'`/`'21'` literals that would silently drift from the real seeded data.

`PaymentResource`/`SubscriptionResource`/`PaymentEventResource` (new, `app/Http/Resources/`) are the shared serialization for `Payment`/`Subscription`/`PaymentEvent` — reach for these rather than hand-rolling arrays if another endpoint needs to expose the same models.

### Subscription management

`SubscriptionController` (`GET /api/v1/subscriptions`, `POST /api/v1/subscriptions/{subscription}/cancel`) is the production-safe counterpart to `PayFastController::subscriptionAction` (routes/payfast-local.php) — that dev tool has zero ownership checks by design (admin-only, local/testing-only); this controller enforces `$subscription->user_id === $request->user()->id` before allowing a cancel. `PayFastCheckoutService::cancelSubscription()` calls PayFast's native subscription API (a real network round-trip, unlike checkout initiation which just builds a form) — wrap calls to it in `try/catch (Throwable)`, since a transport failure or non-JSON response throws rather than returning a `successful: false` result. Found this the hard way: cancelling a subscription whose token was fabricated by local dev tooling (never actually issued by PayFast) throws `PayFast API returned unsupported content type: text/html`, which without the catch surfaced as an uncaught 500 instead of a normal 422.

Same `isTerminal()` landmine as elsewhere: `SubscriptionStatus::isTerminal()` reports `Active` as terminal too, so the "already cancelled, can't cancel again" guard checks `in_array($status, [Cancelled, Failed])` explicitly rather than calling `isTerminal()`.

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

## Brand & navigation config (RS-101 / RS-102)

`config/app-brand.php`, `config/features.php`, `config/navigation.php` are the single source of truth for product name/logo mark/footer, the `show_showcase_pages` toggle, and every nav item (main/admin/showcase/guest/legal groups), plus which roles count as the admin surface (`navigation.admin_roles`) and each surface's home route (`navigation.home_routes`). Nothing in the frontend hardcodes a brand name, role name, or nav item.

`GET /api/v1/web-config` (public, unauthenticated) serves all three as one payload. `resources/js/app/stores/app-config.js` fetches it once (`ensureLoaded()`, called from the router guard alongside `session.ensureLoaded()` — both must resolve before `session.isAdminSurface`/`homeRoute` can be trusted, since those getters read `navigation.admin_roles`/`home_routes` from this store) with safe built-in fallbacks if the request fails. All four layouts (`default.vue`, `auth.vue`, `guest.vue`, `customer.vue`) render brand text from `appConfig.brand`; `default.vue`/`customer.vue`/`guest.vue` render nav items from `appConfig.navigation`, filtered client-side by surface, `item.permission` (checked against the session user's `permissions` array), and `item.environments`.

To rebrand a copied project: edit the three config files (or their `.env` overrides) — no Vue changes needed.

### Showcase pages (RS-106)

Component catalogue, foundation, about, and the PayFast browser test are starter-authoring aids, not product surface. Their route meta carries `"showcase": true` (`payfast-browser-test.vue` additionally carries `"environments": ["local", "testing"]`). The router guard (`router/index.js`) redirects to the catch-all not-found page (`pages/[...notFound].vue`, path `/showcase-disabled`) when `features.show_showcase_pages` is false or the current environment isn't in `meta.environments`. `default.vue`'s sidebar only renders the "Showcase" nav group when `show_showcase_pages` is true. Set `SHOW_SHOWCASE_PAGES=false` in any deployed environment.

## Design system

Vuetify theme: `rw` in `plugins/vuetify.js`. Primary green `#006a4a`, background `#f2efe8`.

CSS custom properties (in `resources/css/app.css`) — prefix `--rw-*`:
- `--rw-ink` / `--rw-ink-2` — primary/secondary body text
- `--rw-muted` / `--rw-dim` — muted and dimmer text
- `--rw-surface` / `--rw-surface-2` — card and elevated backgrounds
- `--rw-border` — default border colour
- `--rw-600` / `--rw-700` etc. — green scale
- `--rw-amber` — amber accent (#b45309)
- `--rw-shadow-xs` / `--rw-shadow` — shadow levels
- `--rw-sidebar-width` / `--rw-sidebar-collapsed` — layout dimensions

Legacy aliases `--starter-*` remain for backward compat but point to `--rw-*` values.

Font: **Plus Jakarta Sans** (Google Fonts) with system-ui fallback.

Cards default to `elevation: 0`, `rounded: xl`. Buttons `rounded: lg`, `fontWeight: 600`.

## Layouts

- `default.vue` — authenticated shell with **custom CSS sidebar** (no `v-navigation-drawer`). CSS `transform` for mobile slide-in with backdrop overlay. Left-border green active indicator on nav items. Guest users (unauthenticated) see a slim top bar with About / Register / Sign in links instead of the sidebar.
- `auth.vue` — dark `#011d12` background with three CSS geometric rings (`border-radius: 50%`) and a dot-grid overlay. Centered floating card, brand logo above, footer below. No split panel.

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
10. devices (links personal_access_tokens)
11. sync_operations / sync_tombstones
12. notifications

## Quality tooling (RS-401 / RS-402)

Backend: `composer lint` (Pint, check-only), `composer lint:fix` (Pint, writes), `composer stan` (Larastan level 5, `phpstan.neon`). `composer test` runs the PHPUnit suite.

`phpstan-baseline.neon` (committed, generated the day Larastan was added) suppresses 81 pre-existing findings — all one root cause: Eloquent model properties/casts (`Subscription::$status`, `Device::$uuid`, `PersonalAccessToken::$personal_access_token_id`, etc.) aren't visible to static analysis without generated model docblocks, so Larastan sees `string`/`Model` where the real runtime type is a backed enum or a specific model. None of them are real bugs — confirmed by cross-referencing against the passing test suite. **Don't just re-run `--generate-baseline` to silence a new finding** — that hides real regressions behind the same file. The actual fix is adding `barryvdh/laravel-ide-helper` (`php artisan ide-helper:models`) to generate accurate model docblocks, then regenerating a much smaller (ideally empty) baseline; not done yet, left as follow-up.

Frontend: `npm run lint` / `npm run lint:fix` (ESLint 9 flat config, `eslint.config.js`), `npm run format` / `npm run format:check` (Prettier, `.prettierrc.json`).

## starter:doctor

`php artisan starter:doctor` reports on deployment readiness: APP_KEY/APP_ENV/APP_DEBUG, `authx` fail-closed permission config, whether dev-only PayFast routes are registered (checked against the live route table, not just config), DB connectivity + pending migrations, Redis, Horizon master supervisor, storage disk write access, mail driver, PayFast credentials (fails/warns if still the published sandbox defaults), and the frontend build manifest.

Plain `starter:doctor` is informational (always exits 0). `starter:doctor --production` turns every blocking finding into a non-zero exit — wire it into a deploy pipeline as a release gate. See `tests/Feature/Console/StarterDoctorCommandTest.php`.

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
- Two `ofetch` instances in `utils/api.js`: `api` (no baseURL, used by auth/session stores) and `v1` (baseURL `/api/v1`, used by profile and admin stores with short paths like `profile`, `users/1`)
- `ProfileResource` wraps the authenticated user for the profile endpoint (includes avatar_url from media)
- `AuthUserResource` wraps the session user for `/api/v1/me` (lighter, same avatar logic)
- `UserAdminService` is dependency-injected; bind to `UserAdminServiceInterface` in AppServiceProvider
- ITN endpoint is CSRF-excluded via `withoutMiddleware(['web'])`
- ITN signature + merchant ID validation lives in `PayFastCheckoutService::validateItnSignature` — controller is a pure HTTP adapter
- `PAYFAST_ENV=sandbox` for local/staging; switch to `live` in production
- `PAYFAST_NOTIFY_URL` must point to `/payments/payfast/itn` (publicly reachable)
- Schema::hasTable checks protect permission/media calls before migrations run
