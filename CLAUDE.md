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
    Modules/          # BillingServiceProvider — one per module, conditionally
                       # registered in bootstrap/providers.php
  Modules/            # ModuleManifest, ModuleRegistry, Billing/BillingModule — see
                       # "Module registry" below

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
| DELETE | `/api/v1/users/{user}` | UserAdminController@destroy — archive (soft delete) |
| POST | `/api/v1/users/{user}/restore` | UserAdminController@restore |
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

## Module registry (RS-301)

Advanced capabilities are optional modules rather than baseline coupling — Billing (PayFast) is the first, and the reference implementation for Mobile/SaaS/Governance modules later. Disabling a module removes its routes, migrations, and frontend nav/UI; nothing needs to be deleted from the codebase to ship without it.

**The on/off switch is one env var per module**, read directly by `bootstrap/providers.php` (this runs before the container/config exist, so it can't use `config()`):

```php
// bootstrap/providers.php
return array_filter([
    AppServiceProvider::class,
    HorizonServiceProvider::class,
    env('MODULE_BILLING_ENABLED', true) ? BillingServiceProvider::class : null,
]);
```

A disabled module's `ServiceProvider` is simply never instantiated — no internal enabled-check needed inside it. Defaults to `true`, so every existing install/branch is unaffected unless the var is explicitly set.

**`App\Modules\ModuleManifest`** (interface) — `name()`, `permissions()` (owned permission strings, documentation/introspection only — see the billing note below), `dependencies()`, `conflicts()` (other module names). **`App\Modules\Billing\BillingModule`** is the reference implementation. **`App\Modules\ModuleRegistry`** (singleton) reads `config('modules.modules')`/`config('modules.enabled')` (the same env var, read the normal way for anything running after boot), exposes `isEnabled(string $name): bool`, and validates every *enabled* module's dependencies/conflicts at construction time — throws a clear `RuntimeException` if an enabled module needs another that isn't enabled, or conflicts with one that is.

**A module's `ServiceProvider` owns its own routes/migrations/rate-limiters** — `App\Providers\Modules\BillingServiceProvider` is the pattern: `loadRoutesFrom()` for `routes/modules/billing.php` (web) and `routes/modules/billing-api.php` (api — re-declares the `auth:sanctum`+`idempotency` group *and* wraps itself in `Route::prefix('api')->middleware('api')`, since `loadRoutesFrom()` from an arbitrary provider doesn't inherit the automatic `/api` prefix + `api` middleware group that `bootstrap/app.php`'s `withRouting(api: ...)` gives the one file passed there — verified empirically against `route:list`, not assumed), `loadMigrationsFrom(database_path('migrations/modules/billing'))` (the 3 subscriptions/payments/payment_events migrations physically live there, not in the flat `database/migrations/` — Laravel's default migrator always scans that flat directory regardless of any `loadMigrationsFrom()` call, so gating requires actually moving the files out; matches "already migrated" by filename not path, so this was safe for an already-migrated database), and the `payfast-initiate` rate limiter (moved here from `AppServiceProvider`).

**Frontend visibility**: `GET /api/v1/web-config` gains a `modules` key (`{ billing: true|false }`), exposed via `appConfig.modules` (safe fallback: `{ billing: true }`, so a transient fetch failure never hides real functionality). A nav item declares the module it belongs to (`'module' => 'billing'` in `config/navigation.php`); `default.vue` and `customer.vue` each filter on it via a `hasModule()` predicate alongside their existing `hasPermission`/`inEnvironment` ones (both layouts filter nav independently — there's no single shared nav-filtering util). `dashboard.vue`/`customer/home.vue` wrap their billing widgets in `v-if="appConfig.modules.billing"` and skip calling `billing.fetch()` when disabled.

**What this deliberately doesn't do yet** (real, separate, larger follow-up — RS-302): no physical relocation of `PayFastController`/`BillingController`/`SubscriptionController`/models/services/resources into a `modules/Billing/` directory with its own namespace — those stay in their normal `app/` locations, since only routes and migrations are things Laravel discovers by directory convention rather than PSR-4 autoloading, so only those two actually need to move for enable/disable to work. `RolesAndPermissionsSeeder` also still seeds `payments.*` unconditionally regardless of module state — harmless today since (separately, a real pre-existing gap) nothing in the codebase actually enforces those permissions anywhere yet.

Proven with `tests/Unit/Modules/ModuleRegistryTest.php` (dependency/conflict validation) and `tests/Feature/ModuleDisableTest.php` — a subprocess-boundary test, same pattern as `ProductionRouteHardeningTest` below (route/provider registration happens once at boot, so it can't be exercised by flipping config mid-process): spawns real `route:list`/`migrate:fresh` processes with `MODULE_BILLING_ENABLED` set both ways, asserts routes and tables are actually present/absent accordingly, not just that the default doesn't regress.

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

Local/testing-only (registered from `routes/payfast-local.php`, loaded by `App\Providers\Modules\BillingServiceProvider` only when `app()->environment(['local', 'testing'])` — never present in the production route table, proven by `tests/Feature/ProductionRouteHardeningTest.php`):

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

### User archive/restore + AppDataTable hardening (Gate 2 reference CRUD module)

`UserAdminController`/`UserAdminService` previously had no delete at all — Gate 2's own definition of a reference CRUD module is explicit ("create/read/update/**archive/restore/delete**"), so this was the concrete gap. `User` now uses Laravel's `SoftDeletes` (migration `2026_08_13_083710_add_soft_deletes_to_users_table`): archiving is a soft delete, which — for free, via Eloquent's default soft-delete query scope — also blocks the archived user from logging in, since the auth provider's user lookup excludes trashed rows.

- `DELETE /api/v1/users/{user}` (archive) / `POST /api/v1/users/{user}/restore` (route uses `->withTrashed()` for binding). Guards: can't archive yourself, can't archive the last remaining `super-admin` (`UserAdminService::isLastSuperAdmin()`).
- `UserAdminService::paginate()` gained `$status` (`active`/`archived`/`all`) and `$sortBy`/`$sortDirection` params.
- `AppDataTable.vue` gained real hardening, generically (any page can opt in):
  - **Sort**: `columns` items take `sortable: true` and optional `sortKey` (when the column's display doesn't match the backend's sort field name, e.g. the `users.vue` "User" column sorts by `name`). Parent-controlled via `:sort-by`/`:sort-direction` props + `@sort` event, same pattern as `@page-change`.
  - **Bulk actions**: `selectable` + `v-model:selected` (array of row ids) renders a checkbox column; while anything is selected, the toolbar is replaced by a bulk-action bar rendering the `#bulk-actions` slot (`{ selected, clear }`).
- `admin/users.vue` is the reference implementation of both: sortable columns, bulk-select + bulk-archive, a Status filter (Active/Archived/All), and per-row Archive/Restore actions. `stores/admin-users.js::bulkArchive()` fans out to the single-item endpoint via `Promise.allSettled` rather than a dedicated bulk endpoint — reasonable for a lightweight, roughly-idempotent action; a module with heavier bulk semantics (partial-failure reporting, transactions) would want a real endpoint instead.

### Exports (RS-107)

`app/Exports/CollectionExport.php` is the one generic export class every admin table export reuses — construct it with a `Collection`, a headings array, and a row-mapping closure rather than writing a dedicated Export class per resource (`rainwaves/laravel-excel` convention already established for this project — see the "Excel exports" rule). `Maatwebsite\Excel\Facades\Excel::download($export, $filename)` handles the actual `.xlsx` streaming.

Both `UserAdminService::filtered()` and `ActivityLogController`'s private `filteredQuery()` share the exact same filter logic as their paginated counterparts (`paginate()`/`index()`) — an export is "the same query, unpaginated, capped at 10,000 rows" rather than a separate hand-rolled query, so it can never silently drift out of sync with what the table is actually showing. The row cap exists because export is a synchronous request/response, not a queued job — it protects against a pathological unfiltered export on a huge table tying up a worker.

`AppDataTable`'s optional `export-href` prop renders a plain `<a download>` "Export" button in the toolbar — no client-side blob/fetch handling needed, the browser handles the file download from the response's `Content-Disposition` header, cookies included automatically (same-origin Sanctum session). The parent page builds the URL itself from its own current filter/search/sort state (`admin/users.vue`, `admin/audit-log.vue` are the two reference implementations) — `AppDataTable` only ever sees one page of rows, so it has no way to build a filtered export URL on its own.

Export endpoints (`GET /v1/users/export`, `GET /v1/activity-log/export`) sit behind the same permission as their index counterpart (`users.view`, `activity.view`) — exporting isn't a separate capability from viewing.

### Saved views

Scoped down to the useful 80%: `resources/js/app/composables/usePersistedFilters.js` remembers the last filter/search/sort state a user left a table in — per table, in localStorage, no backend model — so returning to `admin/users.vue` or `admin/audit-log.vue` doesn't silently reset to defaults. Call it once, right after the page's `filters = reactive({...})`, before the initial fetch:

```js
const filters = reactive({ search: '', status: '', page: 1, sortBy: '', sortDirection: 'asc' });
usePersistedFilters('admin-users', filters, { exclude: ['page'] });
```

`exclude` keeps fields like `page` out of both the restore and the write — a saved view should always land on page 1, not wherever you last paginated to. Restoring happens synchronously during `setup()`, before `onMounted` fires, so the page's initial fetch already sees the restored values.

What this deliberately isn't: named/multiple presets a user can save, switch between, or share with teammates. That's real scope — its own backing model and management UI — worth a proper design pass if a future module needs it, not a quick add-on to this pass.

While wiring this up, fixed a real (if minor) latent bug it would otherwise have made visible: `audit-log.vue`'s initial `onMounted(() => store.fetch())` never passed `filters.search`/`filters.logName` at all, so a pre-filled filter (now: a *restored* one) would show in the UI as selected but not actually be applied until the user touched a field again. Now passes them.

### Unsaved-change protection

`resources/js/app/composables/useUnsavedChanges.js` — `useUnsavedChanges(isDirty)` takes a `Ref`/`ComputedRef<boolean>` the page computes however makes sense for its own form(s), and wires it to the two places a user can actually lose unsaved input: an in-app route change (blocked with `ConfirmDialog`, via a `onBeforeRouteLeave` guard) and a tab close/refresh (blocked with the browser's native `beforeunload` prompt — the only kind browsers allow; the message text passed to `event.returnValue` is never actually shown, browsers render their own). The composable itself is generic and owns no form-specific logic — pages own what "dirty" means and reset it after a successful save.

Two reference implementations:
- `profile.vue` — `isDirty` compares the profile name/email fields plus avatar state against a baseline snapshot (`profileBaseline`, reset by `syncProfileForm()` on load and again after a successful save), OR'd with the password fields being non-empty (password fields don't need a baseline — clearing them to `''` after a successful save already makes them read as clean).
- `account/billing.vue` — `isDirty` is simply "any checkout field has content". The one real gotcha: a successful checkout calls `startPayFastCheckout()`, which navigates the browser away to PayFast — the exact same `beforeunload` event as an accidental tab close would fire. A `leavingForCheckout` ref is set the instant `billing.checkout()` resolves `{ ok: true }`, before the guard would otherwise see the still-populated form as dirty, so a user who genuinely finishes checkout never sees a spurious "leave site?" prompt on their way out. Verified live against the real PayFast sandbox: the guard blocks in-app nav while the form has unsaved input, and does not interfere with the real submit-and-redirect.

Dialog-based forms (the `admin/users.vue` create/edit dialog, `admin/roles.vue` permission editor) don't use this — it guards route/tab navigation, not a dialog's own Cancel/close button, and wiring the same pattern there is a separate, smaller piece of work if it's ever needed.

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

Frontend: `npm run lint` / `npm run lint:fix` (ESLint 9 flat config, `eslint.config.js`), `npm run format` / `npm run format:check` (Prettier, `.prettierrc.json`), `npm run test:unit` (Vitest + Vue Test Utils component tests, `resources/js/**/*.spec.js`), `npm run check:bundle-size` (`scripts/check-bundle-size.mjs`, run after `npm run build`).

The bundle-size budget checks *gzip* size (what's actually sent over the wire, not raw minified size, which moves around with sourcemap/whitespace noise) of every `public/build/assets/*.js` chunk. Two chunks get their own higher, still-real budget rather than a blanket exemption — `apexcharts` (a charting library) and `main` (the app entry) are legitimately larger than everything else; a regression in either should still fail, just against a realistic ceiling (170 KB / 220 KB gzip) instead of the tight 60 KB default every other chunk is held to. Verified the check actually catches a regression, not just that it runs clean on the current baseline — padded a chunk with random (incompressible) data past its budget and confirmed a non-zero exit before confirming the real build passes.

## E2E smoke tests (RS-403)

`npm run test:e2e` (Playwright, `playwright.config.js`, specs in `tests/e2e/`) — critical-path browser tests: guest homepage + nav, login (valid/invalid credentials, sign-out), admin user CRUD (create/archive/restore, non-admin denied), the users export button producing a real file download, and the unsaved-changes guard blocking/allowing navigation correctly. These are smoke tests, not exhaustive coverage — one or two representative flows per area, enough to catch "the app doesn't actually work" regressions that unit tests can't see (routing, real HTTP round-trips, Vuetify component interaction).

Two different targets depending on where you run it from, both handled by `playwright.config.js`:
- **Local dev**: defaults to `http://localhost` — your already-running Sail instance, with its real dev DB. Nothing extra to boot; run `npm run test:e2e` (or `npm run test:e2e:ui` for the interactive UI) while `sail up` is running and the frontend is built.
- **CI**: `scripts/e2e-server.sh` boots a throwaway, fully self-contained instance — a fresh sqlite file DB (migrated + seeded with the real `StarterUsersSeeder` accounts, never touching a real database), served by `php artisan serve` against the already-built frontend (no Vite dev server involved). Config comes from `.env.testing` (committed — the `APP_KEY` in it is a fixed, throwaway value for this always-rebuilt-from-scratch DB, safe to commit). Triggered automatically when `CI=true` — see the `e2e` job in `.github/workflows/ci.yml`.

A few Vuetify-specific selector gotchas worth knowing before adding more specs: `AppSelect`/`AppTextField`'s clear icon (`aria-label="Clear X"`) means `getByLabel(...)` on a clearable field often resolves to 2 elements — use `getByRole('combobox'/'textbox', { name: ... })` instead, or (for a Vuetify select specifically, where even the combobox role can have its click intercepted by an internal `v-field__input` overlay) click the wrapping element by its own class instead of the input. `admin/users.vue`'s `openCreate()` also opens a second "use seeded accounts?" info dialog stacked on top of the real create-user form whenever seeded users already exist — dismiss it first (see `tests/e2e/admin-users.spec.js`) or the real form's buttons are unreachable.

### Accessibility checks (RS-504)

`tests/e2e/accessibility.spec.js` runs `@axe-core/playwright`'s default ruleset (WCAG 2.0/2.1 A+AA plus a handful of best-practice rules) against one representative page per surface (guest homepage, login, dashboard, admin/users) as part of the same Playwright suite — no separate tool or CI job.

Writing this immediately found and fixed two real bugs, both introduced by the AppDataTable keyboard-accessibility work: sortable `<th>` and clickable `<tr>` both had `role="button"` layered on top of their native semantics (`columnheader`/`row`), which is invalid when the element also has focusable descendants (checkbox, action buttons) or a conflicting ARIA attribute (`aria-sort` isn't valid on a `role="button"` element). Fixed by dropping the `role` override in both cases — `tabindex="0"` + the existing Enter/Space keydown handling is enough to keep them keyboard-operable; removing the ARIA role restores their correct native semantics instead of fighting them. Also fixed: the notifications bell and two icon-only edit buttons (`admin/users.vue`, `admin/roles.vue`) had no accessible name at all (`title`/`aria-label`) — real `button-name` failures, not test artifacts.

`AppDataTable` columns can now set `srLabel` (e.g. `{ key: 'actions', label: '', srLabel: 'Actions' }`) so a visually-empty header (an icon/actions column) still gets a real accessible name via `aria-label` on the `<th>`.

**What's deliberately still failing and excluded, not hidden** — `KNOWN_PRE_EXISTING_RULE_IDS` in the spec file, each with a comment explaining what was found: `color-contrast` (several muted text/background pairs fall short of 4.5:1 across the app, as low as 1.85:1 on the dark guest-homepage hero — a real design-token pass, not a per-instance patch), `landmark-one-main`/`region` (`auth.vue`'s layout has no `<main>` landmark), `landmark-unique` (`default.vue` has two nav regions without distinguishing `aria-label`s), `heading-order` (`AppSectionCard` always renders an `h3` regardless of what precedes it, so several pages jump from h1 straight to h3), `empty-table-header` (axe's specific rule wants *visible* header text, not just `aria-label` — satisfying it literally would mean giving up the blank actions-column look, a deliberate design tradeoff). Same spirit as `phpstan-baseline.neon` — tracked and visible in a diff, not silently disabled — except these are genuinely real bugs, just ones spanning the whole layout/design system rather than anything a single session should patch piecemeal. A real remediation pass on any of these should also remove it from the exclusion list.

## starter:init

`php artisan starter:init` configures a project freshly copied from the starter: brand (name/short name/tagline/support email → `.env`'s `APP_NAME`/`APP_BRAND_*`/`APP_SUPPORT_EMAIL`), the `composer.json` `name` field, whether to keep the showcase pages (`--no-showcase`), and optionally runs migrations/seeding (`--migrate`/`--seed`, `--seed` implies `--migrate` — neither runs by default, on purpose: a pure config command shouldn't silently touch the database).

Every value can be passed as a CLI option (`--name=`, `--short-name=`, `--tagline=`, `--support-email=`, `--package=`); anything not passed is prompted for interactively (via Laravel Prompts) with a sensible default, or — combined with `--no-interaction` — resolved straight to that default with no I/O at all, so `starter:init --no-interaction --name="..." --package=vendor/app ...` is fully deterministic for CI/scripted setup (RS-103's requirement). Safe to re-run: it only ever rewrites the specific `.env` keys and the one `composer.json` line, never anything else — verified by a dedicated test that the `composer.json` edit is a single-line diff, not a reformat of the whole file (a naive `json_decode`/`json_encode` round-trip would silently reformat every array in the file).

**What it deliberately doesn't touch**: marketing prose (`pages/index.vue`, `pages/about.vue` still say "Rainwaves Starter" in body copy) — that's content a human should write, not something a config command should guess at. The command's own closing output says this explicitly.

## starter:doctor

`php artisan starter:doctor` reports on deployment readiness: APP_KEY/APP_ENV/APP_DEBUG, `authx` fail-closed permission config, whether dev-only PayFast routes are registered (checked against the live route table, not just config), DB connectivity + pending migrations, Redis, Horizon master supervisor, storage disk write access, mail driver, PayFast credentials (fails/warns if still the published sandbox defaults), and the frontend build manifest.

Plain `starter:doctor` is informational (always exits 0). `starter:doctor --production` turns every blocking finding into a non-zero exit — wire it into a deploy pipeline as a release gate. See `tests/Feature/Console/StarterDoctorCommandTest.php`.

## starter:version

`php artisan starter:version` reports what's actually installed: the starter template version (`config('starter.version')`, sourced from `STARTER_TEMPLATE_VERSION` — defaults to `'unreleased'` since this repo has no tagged releases yet, see the v2.0 gap-closure tracker's "Release Candidate" gate; bump it by hand when a real milestone ships rather than inventing a number), the current git commit + branch (gracefully reports "unavailable" outside a git checkout), PHP/Laravel versions, the pinned Node version (`.node-version`), and the installed `rainwaves/lara-auth-suite`/`rainwaves/payfast-payment` versions (via `Composer\InstalledVersions`, not a hand-parsed `composer.lock`). Useful for support: "what does `starter:version` say?" when someone reports an issue against a copied project.

## Health check

`GET /health` (public, unauthenticated, no auth required — infra probes this directly) is a **readiness** check: it actually exercises the database, cache, and queue connections the app depends on to serve real traffic. This is deliberately separate from Laravel's built-in `GET /up` (see `bootstrap/app.php`'s `health:` key), which is **liveness** only — it confirms the framework booted, nothing more. A deploy/orchestrator wired to `/health` instead of `/up` can tell "booted but can't reach its database" apart from "genuinely ready."

Returns `{ status: 'ok'|'degraded', checks: { database, cache, queue } }` — 200 when every check passes, 503 otherwise. Deliberately minimal in what it discloses (no version/config details), matching this project's fail-closed security posture elsewhere. See `App\Http\Controllers\HealthController` and `tests/Feature/HealthCheckTest.php` (including simulated dependency failures via `DB::shouldReceive`/`Cache::shouldReceive`, not just the happy path).

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
