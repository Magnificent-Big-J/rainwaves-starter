# Rollback

## Before you roll back: can you roll forward instead?

A fix deployed in 5 minutes is almost always safer than a rollback, because a
rollback can put schema and code out of sync (see below). Only roll back if the
current deploy is actively broken and a forward fix isn't fast enough.

## Code rollback

This starter's deploy template (`.github/workflows/deploy.yml.example`) is
artifact-based — each deploy is a self-contained build tied to a commit SHA, deployed
as a new release directory with the previous one left in place (the standard
"releases/`{sha}`, symlink `current` → latest" pattern). Rolling back code is then just
re-pointing that symlink and reloading php-fpm/Horizon — no rebuild needed:

```bash
ln -sfn /var/www/releases/<previous-sha> /var/www/current
sudo systemctl reload php8.4-fpm   # or whatever your host uses
php artisan horizon:terminate      # supervisor restarts workers against the new symlink target
```

If your actual deploy mechanism differs (PaaS, container orchestrator), use its own
rollback/previous-revision command instead — the principle is the same: revert code
without touching data first, then decide separately whether data needs to change too.

## Schema rollback — be careful

`php artisan migrate:rollback` exists but is genuinely risky for this app's schema:
several migrations create tables with data that would already have real rows by the
time you'd roll back (`users`, `payments`, `subscriptions`, `activity_log`, ...) — a
rollback that drops a column or table **destroys that data**, it doesn't just undo the
migration.

Practical rule for this starter:
- **Additive migrations** (new nullable column, new table) — safe to leave in place
  even after a code rollback. Old code simply ignores the new column/table. Don't
  roll these back.
- **Destructive migrations** (dropped/renamed column, changed constraint) — these are
  the actual risk. If a deploy shipped one of these and needs rolling back, restore
  from the pre-deploy backup (see `backup-restore.md`) instead of `migrate:rollback`,
  unless you've specifically verified the down() migration doesn't lose data.

Check what a specific migration's rollback would actually do before running it:

```bash
php artisan migrate:status                        # what's actually been applied
cat database/migrations/<file>.php                 # read the down() method yourself
php artisan migrate:rollback --pretend --step=1     # see the SQL without running it
```

## Module registry rollback

If a deploy toggled `MODULE_BILLING_ENABLED` (or a future module's equivalent) and
that needs reverting: this is an env var change, not a schema change — flip it back
and restart (`bootstrap/providers.php` reads it at boot, see CLAUDE.md "Module
registry"). Disabling a module never drops its tables even on a fresh migration run
elsewhere; re-enabling an already-migrated module doesn't recreate anything, it just
resumes serving routes.

## Post-rollback checklist

- [ ] `php artisan config:clear && php artisan route:clear` (stale cached config/routes
      from the reverted code are a common source of confusing bugs post-rollback)
- [ ] `php artisan horizon:terminate` (workers restart against the rolled-back code)
- [ ] `php artisan starter:doctor --production` — confirms the rolled-back state is
      actually healthy, not just "the old commit"
- [ ] Check `payment_events` for any ITN webhooks received during the rollback window
      — PayFast's ITN handling is idempotent (unique constraint on
      `[provider, event_type, event_ref]`, see CLAUDE.md "PayFast"), so a replayed
      notification after rollback is safe, but confirm nothing was missed while the
      app was mid-rollback and briefly unavailable
- [ ] Announce the rollback and root cause in whatever channel your team uses for
      incidents (see `incident-response.md`)
