# Incident & Security Event Response

## Triage — is it up, and is it actually healthy?

```bash
curl https://your-domain/up       # liveness only: did the framework boot at all
curl https://your-domain/health   # readiness: can it actually reach DB/cache/queue (see HealthController)
php artisan starter:doctor        # deeper diagnosis: APP_KEY/permissions/PayFast config/build manifest/Horizon
```

`/up` returning 200 while `/health` returns 503 means the app booted but can't serve
real traffic — that distinction is the whole reason these are two separate endpoints
(see CLAUDE.md "Health check"). Check `/health`'s response body for which dependency
(`database`/`cache`/`queue`) is failing before guessing.

Queue/Horizon-specific: check the Horizon dashboard (`/horizon`, gated by
`HORIZON_ALLOWED_EMAILS`) for stuck/failed jobs before assuming the app itself is
broken — a queue backlog looks like "the app is slow" from outside.

## Where security events actually are

Every lara-auth-suite security event (14 of them — login, 2FA, password reset,
rate-limiting, recovery codes, etc.) is recorded into `spatie/laravel-activitylog`
under `log_name = 'security'` by `App\Listeners\LogSecurityActivity` — see CLAUDE.md
"Auth flow" for the exact event list. This is the first place to look, not raw
application logs:

```bash
php artisan tinker --execute="
    \Spatie\Activitylog\Models\Activity::where('log_name', 'security')
        ->where('created_at', '>=', now()->subHours(24))
        ->latest()->get(['description', 'causer_id', 'properties', 'created_at'])
        ->each(fn (\$a) => print_r(\$a->toArray()));
"
```

Or through the admin UI: `/admin/audit-log`, filtered to the `security` log.

**Four event types are flagged `severity: 'high'` and additionally logged via
`Log::warning()`** (so they also show up in whatever log aggregation you have wired to
your log channel, not just the database): `AuthenticationRateLimited`,
`AuthenticationStateRevoked`, `TwoFactorDisabled`, `RecoveryCodeUsed`. These are the
ones worth an actual alert, not just a database row — a burst of
`AuthenticationRateLimited` events against one account is a credential-stuffing
attempt in progress, right now, not history to review later.

`LoginFailed`/`AuthenticationRateLimited` deliberately don't bind a causer — the
package keeps failed-login attempts non-enumerating by design (see CLAUDE.md), so you
won't find "which account" directly on those rows; correlate by IP/timestamp instead.

## Suspected account compromise

```bash
# Revoke every other active session for the account (keeps the current one — use this
# from the account owner's own session, or drop that constraint if acting on their behalf)
php artisan tinker --execute="
    \$user = \App\Models\User::where('email', 'compromised@example.com')->first();
    \$user->tokens()->delete();                 // mobile PATs
"
```

Or via the API surface a real user/support agent would use: `DELETE /api/v1/sessions/others`
(browser sessions) and `DELETE /api/v1/devices/{uuid}` (mobile). For an admin acting on
someone else's behalf, there's no dedicated "force-logout this other user" admin
endpoint in the starter as shipped — that's a real gap worth building before you need
it if admin-initiated session revocation is part of your incident playbook, not
something to improvise via tinker under pressure.

## Suspected PayFast/payment forgery

The ITN endpoint validates the PayFast signature and merchant ID before touching any
state (`PayFastCheckoutService::validateItnSignature`, see `PayFastItnHardeningTest`
for the exact rejection cases) — a forged/tampered ITN is rejected before it can
mutate a `Payment`/`Subscription`. If you suspect an attempt: check `payment_events`
for rows that *don't* correspond to a real PayFast-initiated transaction, and check
web server access logs for POSTs to `/payments/payfast/itn` that returned a
non-2xx — a real attempt leaves a rejected-request trail, it doesn't succeed silently.

## Permission/authorization anomaly

`config/authx.php` hardcodes permission-checks to fail *closed* (denies access) if the
permission tables are missing or unreadable — not env-configurable, deliberately (see
CLAUDE.md and `PermissionsFailClosedTest`). If you're seeing unexpected 403s across
the board, check whether `roles`/`permissions`/`model_has_roles` etc. are actually
reachable (a botched migration, a DB failover mid-transaction) — the fail-closed
default means a database problem shows up as "nobody can do anything" rather than
"everyone can do everything," which is the correct failure mode but can look alarming
if you don't know it's by design.

## After the incident

- [ ] Write up what happened, when it was detected, what the fix was, and — the part
      that's easy to skip under pressure but matters most — what would have caught it
      *sooner* (a missing `/health` check? no alert on `AuthenticationRateLimited`
      bursts? a runbook gap this document should have covered?)
- [ ] If it involved a real security event (not just an outage), consider whether
      affected users need direct notification — that's a legal/product decision, not
      one this doc can make for you, but don't let it get lost in the postmortem.
