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

Advanced capabilities are optional modules rather than baseline coupling — Billing (PayFast) was the first; Mobile (the companion app's bootstrap/auth/device/sync API surface) is the second; Teams (multi-tenancy: teams, invites, roles, usage limits) is the third, the first module with a real `dependencies()` entry. Disabling a module removes its routes, migrations, and frontend nav/UI; nothing needs to be deleted from the codebase to ship without it.

**The on/off switch is one env var per module**, read directly by `bootstrap/providers.php` (this runs before the container/config exist, so it can't use `config()`):

```php
// bootstrap/providers.php
if (env('MODULE_TEAMS_ENABLED', true) && ! env('MODULE_BILLING_ENABLED', true)) {
    throw new RuntimeException('MODULE_TEAMS_ENABLED requires MODULE_BILLING_ENABLED — the Teams module depends on Billing.');
}

return array_filter([
    AppServiceProvider::class,
    HorizonServiceProvider::class,
    env('MODULE_BILLING_ENABLED', true) ? BillingServiceProvider::class : null,
    env('MODULE_MOBILE_ENABLED', true) ? MobileServiceProvider::class : null,
    env('MODULE_TEAMS_ENABLED', true) ? TeamsServiceProvider::class : null,
]);
```

A disabled module's `ServiceProvider` is simply never instantiated — no internal enabled-check needed inside it. Defaults to `true`, so every existing install/branch is unaffected unless the var is explicitly set.

**The explicit dependency check above is load-bearing, not defensive boilerplate** — a real bug caught by live-checking `route:list`, not assumed away: `ModuleRegistry`'s own dependency validation (below) only fires when something actually *resolves* it from the container. `route:list` never does, so before this check existed, Teams' routes/migrations registered and loaded fine with Billing disabled — the mismatch would only ever surface as an uncaught exception wherever `ModuleRegistry` first happened to get resolved (a real request to `WebConfigController`, say), inconsistently and well after the fact. `bootstrap/providers.php` is the one place that reliably runs on every single boot regardless of what a given request/command touches, so it's the right place to fail loudly and immediately for a module's *own* declared dependency, even though it duplicates that one fact already declared in `TeamsModule::dependencies()`.

**`App\Modules\ModuleManifest`** (interface) — `name()`, `permissions()` (owned permission strings, documentation/introspection only — see the billing note below), `dependencies()`, `conflicts()` (other module names). **`App\Modules\Billing\BillingModule`** is the reference implementation. **`App\Modules\ModuleRegistry`** (singleton) reads `config('modules.modules')`/`config('modules.enabled')` (the same env var, read the normal way for anything running after boot), exposes `isEnabled(string $name): bool`, and validates every *enabled* module's dependencies/conflicts at construction time — throws a clear `RuntimeException` if an enabled module needs another that isn't enabled, or conflicts with one that is.

**A module's `ServiceProvider` owns its own routes/migrations/rate-limiters** — `App\Providers\Modules\BillingServiceProvider` is the pattern: `loadRoutesFrom()` for `routes/modules/billing.php` (web) and `routes/modules/billing-api.php` (api — re-declares the `auth:sanctum`+`idempotency` group *and* wraps itself in `Route::prefix('api')->middleware('api')`, since `loadRoutesFrom()` from an arbitrary provider doesn't inherit the automatic `/api` prefix + `api` middleware group that `bootstrap/app.php`'s `withRouting(api: ...)` gives the one file passed there — verified empirically against `route:list`, not assumed), `loadMigrationsFrom(database_path('migrations/modules/billing'))` (the 3 subscriptions/payments/payment_events migrations physically live there, not in the flat `database/migrations/` — Laravel's default migrator always scans that flat directory regardless of any `loadMigrationsFrom()` call, so gating requires actually moving the files out; matches "already migrated" by filename not path, so this was safe for an already-migrated database), and the `payfast-initiate` rate limiter (moved here from `AppServiceProvider`).

**Frontend visibility**: `GET /api/v1/web-config` gains a `modules` key (`{ billing: true|false, mobile: true|false, teams: true|false }`), exposed via `appConfig.modules` (safe fallback: `{ billing: true }`, so a transient fetch failure never hides real functionality). A nav item declares the module it belongs to (`'module' => 'billing'` in `config/navigation.php`); `default.vue` and `customer.vue` each filter on it via a `hasModule()` predicate alongside their existing `hasPermission`/`inEnvironment` ones (both layouts filter nav independently — there's no single shared nav-filtering util). `dashboard.vue`/`customer/home.vue` wrap their billing widgets in `v-if="appConfig.modules.billing"` and skip calling `billing.fetch()` when disabled. Mobile has no SPA-facing nav/UI at all — it's a pure API surface for the companion mobile app — so it needs no frontend gating beyond appearing in the `modules` payload for consistency.

**RS-302 (Billing's full physical extraction) is done**: `PayFastController`/`BillingController`/`SubscriptionController`, the `Payment`/`PaymentEvent`/`Subscription` models, `PayFastCheckoutService`/`PayFastCheckoutServiceInterface`, the two `InitiateXPaymentRequest` form requests, and the three payment/subscription API resources all live under `App\Modules\Billing\...` with their own namespace (`app/Modules/Billing/{Models,Http/Controllers,Http/Requests,Http/Resources,Services,Contracts}`), not scattered across the normal `app/` locations. `PaymentStatus`/`SubscriptionStatus` deliberately stayed in `app/Enums` rather than moving into the module — they're shared vocabulary `config/mobile.php`'s `option_sets` also references, and a module manifest reaching into another module's internal namespace (even a disabled one) would be exactly the coupling this system exists to prevent. `PayFastCheckoutServiceInterface`'s binding moved from `AppServiceProvider` into `BillingServiceProvider::register()`, matching the Mobile module's already-established pattern of a module owning its own bindings. Mobile's controllers/models/services stayed in their normal `app/` locations — same shallow RS-301-only extraction Billing itself started with — since Mobile hasn't had its own RS-302-equivalent pass yet. `RolesAndPermissionsSeeder` still seeds `payments.*` unconditionally regardless of module state — harmless today since (separately, a real pre-existing gap) nothing in the codebase actually enforces those permissions anywhere yet.

**Mobile module boundary**: `App\Providers\Modules\MobileServiceProvider` owns `/v1/meta`, `/v1/auth/login`, `/v1/auth/two-factor`, `/v1/auth/logout`, `/v1/devices*`, `/v1/sync/*` (`routes/modules/mobile-api.php`), the `devices`/`sync_operations`/`sync_tombstones` migrations (`database/migrations/modules/mobile/`), the `MobileAuthServiceInterface`/`SyncProcessorInterface`/`SyncRegistry` bindings (moved here from `AppServiceProvider`), and the `mobile-auth` rate limiter. `GET /v1/ping` and `GET /v1/me` deliberately stayed in core `routes/api.php` — they're generic authenticated-envelope endpoints with no mobile-specific logic (used by `EnvelopeTest` as canonical examples of the response shape), not part of the mobile domain the way device registration and sync are. The web SPA's own "who am I" call is a completely different route (`/auth/session/me`, cookie-based, owned by `rainwaves/lara-auth-suite`) — confirmed by grepping the frontend before drawing the module boundary, since `/v1/notifications`, `/v1/sessions`, `/v1/profile`, `/v1/users` etc. all live in the SAME protected route group as the mobile-only endpoints but are genuinely shared core API the SPA depends on, not mobile-exclusive; gating those behind `MODULE_MOBILE_ENABLED` would have broken the web app the moment mobile was disabled.

**Teams module boundary**: `App\Providers\Modules\TeamsServiceProvider` owns `/v1/team`, `/v1/teams*`, `/v1/team-invites/{token}/accept`, `/v1/admin/teams*` (`routes/modules/teams-api.php`), the `teams`/`team_memberships`/`team_invites` migrations plus `users.current_team_id` and `payments`/`subscriptions`' `team_id`/`plan_key` columns (all under `database/migrations/modules/teams/` — owned by Teams, not Billing, even though two of them alter Billing's own tables, since those columns only exist when Teams is enabled), and the `TeamServiceInterface` binding. Fully namespaced under `App\Modules\Teams\...` from day one (models, controllers, requests, resources, services, enums) — the RS-302 depth Billing eventually reached, not RS-301's shallower routes/migrations-only shape Mobile still has.

**Team roles are deliberately not Spatie roles/permissions.** `config('permission.teams')` stays off — team-scoped authorization (`App\Modules\Teams\Enums\TeamRole`: Owner/Admin/Member, a plain backed enum on the `team_memberships` pivot) is a completely separate concept from the global Spatie roles (`super-admin`/`admin`/`staff`/`customer`) that drive the admin-vs-customer surface split (`session.isAdminSurface`). A team's Owner is fixed at creation (`Team.owner_id`) and can never be reassigned through a role change or removed through the members endpoint — only by deleting the team — which sidesteps "last owner" edge cases entirely rather than needing to guard against them. `teams.view`/`teams.manage` *are* real Spatie permissions (seeded to `super-admin`/`admin`), but they gate only the read-only admin overview (`AdminTeamController`) — self-service (`TeamController`/`TeamMemberController`/`TeamInviteController`) is authorized inline against the caller's `TeamMembership` role, matching this codebase's existing convention of inline `$request->user()->can(...)`/explicit-check authorization over Laravel Policies (there are none anywhere in this app).

**Self-removal from a team is intentional ("leave team") — self-role-change is not, and is blocked.** `TeamMemberController::destroy()` deliberately special-cases `$user->getKey() === $request->user()->getKey()`: it skips the normal `canManageMembers()` authorization and instead only requires *any* membership, since leaving your own team needs no elevated permission — that's existing, correct behavior from Phase 1, not something this pass changed. `update()` (role change) had no equivalent self-check, though, so an Admin could silently demote themselves via the same members-management endpoint the frontend uses for *other* people — a real gap found by exercising the page as a seeded Admin account, not from a report. Fixed with a controller-level guard (`update()` rejects `$user->getKey() === $request->user()->getKey()` with a 422, same pattern as the destroy-side check) — deliberately not a `TeamService` change, since (matching `removeMember()`'s existing shape) the service only owns the "can't touch the owner" domain invariant, while actor-vs-target identity is a controller/authorization concern. `pages/account/team-members.vue` hides the editable role `AppSelect` for the caller's own row (falls back to the same read-only `AppStatusBadge` the owner's row already used) and relabels the caller's own remove button "Leave" instead of "Remove" — the button itself was always correctly enabled for self-removal, it just read like a bug because nothing distinguished "leaving" from "removing someone else."

**Billing/core stays unaware of Teams** — the module dependency runs one way only. `Payment`/`Subscription` gained `team_id`/`plan_key` to their `$fillable` arrays (harmless column names, no class reference) but deliberately gained no `team()` relation, since that would require importing `App\Modules\Teams\Models\Team` and invert the dependency direction. Same reasoning kept `currentTeam()`/`teams()` relations off the core `User` model entirely (mirroring the earlier decision not to add `payments()`/`subscriptions()` to `User` for Billing) — anywhere Team-flavored data about a user is needed, it's resolved through `TeamService`/`Team`'s own relations, never the reverse. `PayFastCheckoutService` now persists `plan_key` (previously only the resolved `item_name`/`amount` snapshot was stored, not which catalog key produced it) — a real, small, teams-independent gap closed alongside this work, since `Team::maxMembers()` needs it to look up a team's current plan.

**Teams frontend**: `stores/team.js` (current team + `teams` list for the switcher + create/rename/switch/delete/accept-invite), `stores/team-members.js` (paginated members + invites, the `AppDataTable`/`AppFilterBar` crud-contract shape). `pages/account/team.vue` (settings + plan/usage stat cards, or a create-team empty state), `pages/account/team-members.vue` (member list, role changes, invite/revoke — `admin/users.vue`'s sibling), `pages/team-invites/[token].vue` (invite acceptance, public route). `AuthUserResource` deliberately gained no `current_team` field — same one-way-dependency reasoning as above, applied to the frontend bootstrap payload too; the team store fetches its own state from `GET /v1/team` independently, the same way `notifications`/`billing` stores already do.

**The sidebar team switcher lives in `customer.vue`, not `default.vue`.** `App.vue`'s `layout: "contextual"` resolution picks `default.vue` only for the admin surface and `customer.vue` for the customer surface — confirmed by grepping every page using a literal (non-contextual) `"layout": "default"` before touching either file: all of them are `admin/*`/`dashboard`/`adminOnly` showcase pages, none customer-reachable. Teams is a customer-facing concept, so a first attempt at adding the switcher to `default.vue`'s aside sidebar was genuinely unreachable dead code (`default.vue` never renders for a customer-surface session) — caught before shipping by tracing the actual layout-resolution logic, not assumed. The real switcher lives in `customer.vue`'s horizontal topbar (`.customer-actions`), gated on `appConfig.modules.teams`.

**Registering via a team invite bypasses `lara-auth-suite`'s registration gate entirely — a real, load-bearing design decision, not an oversight.** This starter ships with `authx.registration.enabled` defaulting `false` (registration closed), discovered live while testing the invite-accept flow end-to-end: a brand-new invitee hit "Registration is currently closed" and had no path to accept at all. `TeamService::registerAndAcceptInvite()` (called from the public `POST /v1/team-invites/{token}/register`, deliberately outside the `auth:sanctum` group and allow-listed in `ApiRouteAuthorizationTest`) creates the `User` directly, sets `email_verified_at` immediately (the invite link, delivered to a real inbox, *is* the verification), and logs the new account straight in (`Auth::guard('web')->login()` + a guarded `session()->regenerate()` — guarded because Sanctum's `EnsureFrontendRequestsAreStateful` only starts a session for a request whose Origin/Referer matches a configured stateful domain, which a plain PHPUnit `postJson()` call never does, so the guard keeps the same code correct in both a real browser and a test). This is intentional, not a workaround: a valid invite token is its own authorization to create an account, independent of whether public self-registration happens to be open.

**Admin teams overview (Phase 3)** — `stores/admin-teams.js` + `pages/admin/teams.vue`: `AppDataTable` list (search + sort, `GET /v1/admin/teams`, `can:teams.view`) with `AppDrawer` detail-on-click (`GET /v1/admin/teams/{team}`), the same list-then-drawer shape `admin/users.vue` uses for its dialog except genuinely read-only — no edit form, matching the earlier scoping decision that admins get visibility, not an admin-initiated write path into someone else's team. `AdminTeamController`/`teams.view`/`teams.manage` permission seeding were already built and seeded in Phase 1; this phase was purely the frontend catching up to backend that already existed. The `config/navigation.php` "Teams" admin entry (`module: teams`, `permission: teams.view`) — deliberately withheld in Phase 2 via a code comment so it wouldn't point at a page that didn't exist yet — is now wired in.

**`safeRedirectPath()`** (`stores/auth-shared.js`) is the small, shared mechanism this needed: `login.vue`/`register.vue`/`verify.vue` each read `route.query.redirect` and, if it's a same-origin relative path (`/...`, never `//...` — rules out an open protocol-relative redirect), land there instead of `session.homeRoute` after success — threaded through the whole register→login→2FA chain via query params so `pages/team-invites/[token].vue`'s "Sign in" / registration links return the user to the invite they started from. No such mechanism existed anywhere in the router before this; it's genuinely new, not an extension of something partial.

Proven with `tests/Unit/Modules/ModuleRegistryTest.php` (dependency/conflict validation, generic fake modules), `tests/Feature/ModuleDisableTest.php`'s `test_boot_fails_loudly_when_teams_is_enabled_but_billing_is_disabled` (the real Teams↔Billing case, not a synthetic one — proves the `bootstrap/providers.php` check above, not just `ModuleRegistry`'s), and `tests/Feature/Api/Team*Test.php`/`AdminTeamTest.php` (creation, ownership/role guards, invite lifecycle including the member-cap race close via a `DB::transaction` + fresh count at both invite-creation and accept time, admin-overview permission gating, and the registration-bypass path working "even when public registration is closed" as its own explicit test) — all live-verified via a real Sail session afterward, including the full multi-actor flow: create team → invite → real email in Mailpit → click the real link as a fresh browser context → create account → land back on the team page already logged in as a real member. Not just trusted from the automated suite.

Proven with `tests/Unit/Modules/ModuleRegistryTest.php` (dependency/conflict validation) and `tests/Feature/ModuleDisableTest.php` — a subprocess-boundary test, same pattern as `ProductionRouteHardeningTest` below (route/provider registration happens once at boot, so it can't be exercised by flipping config mid-process): spawns real `route:list`/`migrate:fresh` processes with `MODULE_BILLING_ENABLED`/`MODULE_MOBILE_ENABLED` set both ways, asserts routes and tables are actually present/absent accordingly (including that `/v1/ping`/`/v1/me` survive mobile being disabled), not just that the default doesn't regress.

`tests/Feature/PresetInstallMatrixTest.php` goes one step further: `ModuleDisableTest` only ever flips one module while the rest stay at their default, and never touches `config:cache`/`route:cache` — production's actual boot path, distinct from the always-uncached `route:list` every other subprocess test uses. This one is generic over the module env-var list (currently billing × mobile = 4 combinations; doubles automatically per module added later, nothing in the file itself needs to change) and for every combination genuinely installs — `config:cache` + `route:cache` + `migrate:fresh --seed` against a throwaway sqlite db — then asserts table presence matches each module's toggle. `config:cache`/`route:cache` write real files into `bootstrap/cache/` (unlike `route:list`/`migrate:fresh`, which never touch the working tree), so every combination unconditionally clears them again in a `finally` block — verified empirically that a full run leaves `bootstrap/cache/` exactly as it started, so a failed assertion can't leave a stale cached config/route file behind for whatever runs `php artisan` next, in CI or on a shared dev machine.

**Currently pinned to `MODULE_TEAMS_ENABLED=false`** in `PresetInstallMatrixTest`'s and `ModuleDisableTest`'s existing billing-focused cases — that matrix is billing × mobile only, and Teams (defaulting to enabled) would otherwise fail every `billing=false` combination in it for an unrelated reason now that a real cross-module dependency exists. A proper third `MODULE_TEAMS_ENABLED` axis (plus the one genuinely-expected-failure combination, `teams=true, billing=false`) is real, separate follow-up scope, not done here.

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

### Checkout: server-side plan authority (RS-002, scoped)

`/payments/payfast/initiate` and `/payments/payfast/subscriptions/initiate` **never accept amount/item_name from the client** — the request sends a `plan` key (`InitiateOneTimePaymentRequest`/`InitiateSubscriptionPaymentRequest` validate it against `config('billing-plans')`, filtered to that endpoint's mode via `Rule::in()`), and `PayFastCheckoutService::resolvePlan()` looks up the real `item_name`/`amount`/(`frequency`, subscriptions only) server-side. `config/billing-plans.php` is the catalog — placeholder starter plans a real project replaces with its actual product lineup. `GET /api/v1/billing/plans` (`BillingController::plans()`) exposes it to the frontend for the checkout form's Plan dropdown; `resources/js/app/stores/billing.js`'s `fetchPlans()` + `account/billing.vue`'s `planOptions` (filtered by the form's `mode`) are the reference consumer.

**Admin-on-behalf-of-a-customer**: both `InitiateXPaymentRequest`s accept an optional `user_id`, but `PayFastController::resolveCheckoutContext()` only honors it when the *authenticated* caller has the `payments.manage` permission (declared in `BillingModule::permissions()` since RS-301, never actually enforced anywhere until this) — otherwise it's silently ignored and the checkout is recorded under the caller's own id, no matter what the client sends. When honored, contact details (`name_first`/`name_last`/`email_address`) are resolved from the *target* user's own record, not the request body — the admin's browser session doesn't represent the customer being checked out for.

**Deliberately descoped**: anonymous/guest checkout (signed single-use links, no account required). RS-002's original plan speculatively scoped a "signed guest" checkout mode, but nothing in this starter's actual usage has needed it — descoped explicitly rather than building unused infrastructure; revisit if a real project needs "pay without an account."

Proven by `tests/Feature/CheckoutPlanAuthorityTest.php`: a spoofed client-supplied `amount`/`item_name` has zero effect (the plan's real price is what gets stored), an unknown or wrong-mode plan key is rejected with a 422, a regular customer's `user_id` override is ignored, and a `payments.manage` admin's override is honored with the target user's real contact details substituted in. `PayFastV2CompatibilityTest`'s direct-service-call tests were updated to the new `(string $plan, array $customerData, ?int $userId)` signature — before this change nothing exercised the FormRequest/controller layer for checkout initiation at all, only the service directly.

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

The reusable pattern this module established — response envelope, list/create/update/archive/restore/export request-response shapes, service-layer contract, `AppDataTable`/`AppFilterBar` frontend wiring — is written up prescriptively in `docs/crud-contract.md`. Read that when building a new CRUD module; this section stays as the historical "what shipped and why" for Users specifically.

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

### Fluid pages, single source of truth for spacing

Every page rendered through `default.vue` must be **fluid** — no page-level `max-width` + `margin: 0 auto` boxing/centering. `.app-main` in `default.vue` is the *only* place page-level padding is declared (`2rem 2rem 4rem`, stepping down at 959px/480px) — individual pages must not redeclare their own root padding, since that either duplicates it (wrong spacing) or, if a page adds padding but a *different* page doesn't, produces the exact bug this was written to prevent: some pages jam their header content against the topbar while others don't.

This was a real, comprehensive gap found and fixed in one pass across every `default.vue`/`contextual`-layout page (`dashboard.vue`, `admin/*`, `profile.vue`, `notifications.vue`, `account/*`, the showcase pages): several pages boxed themselves at `max-width: 1180px` (or narrower), wasting most of a wide screen, while others had no root padding at all, causing their header badges/buttons to visually collide with the topbar. Fixed at the layout level once rather than per-page, since per-page was exactly how the inconsistency happened in the first place.

**The one legitimate exception**: a genuine reading/scanning-width cap on specific *content* (a form, a single-column list), never the whole page. A width cap on a *whole page's root* just relocates the wasted-space problem one level in — this was tried on `notifications.vue`/`account/sessions.vue` and rejected: capping the root at 960px/1100px left a large empty void beside a narrow list on wide screens, the same underlying bug in a different spot. The fix there was to keep the page root fluid and instead add real content that uses the width (stat cards built from data already in the store, a genuine "Unread only" filter) plus cap only the innermost *text* element — `profile.vue`'s `.profile-grid { max-width: 1500px }` and `AppNotificationItem.vue`'s `.notification-item__copy { max-width: 720px }` (the same `justify-content: space-between` boxing bug recurring at component scale, found by checking the pattern's other users after the page-level version was caught) are the accurate examples. Never combined with `margin: 0 auto` — it just stops growing, doesn't re-center in the remaining space — and stat-card rows / tables / anything that genuinely benefits from width stay uncapped.

`AppFilterBar.vue` also had a real, structural bug behind the "inconsistent search/filter box sizing" symptom: `.filter-bar__primary` had no `flex-grow`, so it shrank to its own content's minimum size and forced its children to wrap onto separate lines even with plenty of room in the row. Fixed generically — `.filter-bar__primary :deep(.app-text-field)` gets `flex: 2 1 240px; max-width: 420px` and `.app-select`/`.app-autocomplete` get `flex: 1 1 180px; max-width: 280px` — so any page dropping an `AppTextField` + `AppSelect(s)` into `AppFilterBar` gets a consistent, proportional row for free, without a bespoke `min-width` class per page (several pages had one, several didn't, which is why the search box looked oversized on one page and was truncated to a couple of characters on another).

### Dialog/drawer scrims never dim the sidebar

Vuetify's `v-dialog`/temporary `v-navigation-drawer` scrim (`.v-overlay__scrim`) covers the full viewport by default, including the persistent sidebar — global chrome, not backdrop content the dialog is replacing. Left unfixed this reads as the sidebar going blank/unreadable every time any modal opens. Fixed with a global (unscoped, since Vuetify teleports the scrim outside any component's own DOM tree — scoped CSS and `:deep()` can't reach it) override in `default.vue`: `@media (min-width: 960px) { .v-overlay__scrim { left: var(--rw-sidebar) !important; } }`. Scoped to the desktop breakpoint only — below 960px the sidebar is a hidden slide-out drawer, so the scrim should still cover its full-width mobile footprint. Verified the sidebar stays fully legible and clickable (a nav link can be clicked straight through an open dialog, which correctly navigates and unmounts the dialog with the page) while dialog positioning/functionality is untouched, since only the decorative scrim layer is clipped — not the dialog content's z-index or centering.

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

## Backend architecture/security tests

Beyond the PayFast/permissions-specific tests already covered elsewhere: `tests/Feature/ApiRouteAuthorizationTest.php` sweeps the real route table and asserts every mutating (`POST`/`PUT`/`PATCH`/`DELETE`) `api/v1/*` route requires `auth:sanctum` unless explicitly allow-listed as genuinely public (currently just the login/2FA endpoints — the auth flow itself can't require auth) — catches "forgot to add `auth:sanctum` to a new route" as a failing test instead of a live gap. `tests/Feature/SecurityConfigurationTest.php` guards two cheap, easy-to-get-wrong things: CORS never combining a wildcard origin with `supports_credentials` (the classic real misconfiguration), and no `dd()`/`dump()`/`var_dump()`/`ray()`/`die()`/`exit()` left in `app/` (word-boundary-matched via regex — a naive substring check false-positives on `in_array(`/`array_map(` containing `ray(`).

## Deployment, backup/restore, incident response (RS-404 / RS-405)

`.github/workflows/deploy.yml.example` — a deployment template, not a working workflow (the `.example` suffix is deliberate: GitHub Actions only runs literal `*.yml`/`*.yaml` files, so this can never accidentally fire). Covers the parts that are genuinely the same regardless of hosting target — production build, `starter:doctor --production` as a real readiness gate before deploy, migration/cache-clear/Horizon-restart as the standard post-deploy sequence — and marks the one part that can't be templated generically (the actual deploy step: SSH/rsync, a PaaS's own deploy action, or a container registry push) with a clear TODO rather than a fake working example pointed at an imaginary host.

`docs/operations/` — three runbooks, each grounded in this app's actual architecture rather than generic boilerplate:
- `rollback.md` — code rollback (release-symlink pattern) vs. schema rollback (genuinely risky — additive migrations are safe to leave, destructive ones need a backup restore, not `migrate:rollback`, unless you've actually read what the `down()` method does), plus the module registry's own rollback story (an env var flip, not a schema change).
- `backup-restore.md` — what actually needs backing up (database, `APP_KEY` — separately, losing it makes encrypted data permanently unreadable) vs. what doesn't (Redis — cache/queue are meant to be ephemeral) vs. what needs its own story (S3-backed media — a DB restore alone doesn't restore the actual files if the bucket itself was also lost).
- `incident-response.md` — triage via `/health` (not just `/up`) and `starter:doctor`; where the 14 lara-auth-suite security events actually live (`activity_log`, `log_name = 'security'`, written by `LogSecurityActivity` — see "Auth flow" above) and which 4 are flagged `severity: 'high'` and worth an actual alert vs. just a database row; account-compromise session/token revocation; verifying a suspected PayFast ITN forgery attempt was actually rejected (it would have been, before touching any state — see "PayFast"); why a permission-fail-closed default can look alarming during an incident even though it's the correct behavior.

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
