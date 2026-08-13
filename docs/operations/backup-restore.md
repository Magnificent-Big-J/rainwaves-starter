# Backup & Restore

## What actually needs backing up

| What | Where | Back up? |
|---|---|---|
| Database (`users`, `payments`, `subscriptions`, `activity_log`, everything else) | MySQL | **Yes — the primary thing that matters** |
| Media/avatars (`spatie/laravel-medialibrary`) | S3-compatible object storage (`FILESYSTEM_DISK`/`MEDIA_DISK`) | Yes, but via the storage provider's own mechanism (bucket versioning/replication), not this app |
| `APP_KEY` | `.env`, not in the database | **Yes — separately, and treat it like a secret.** Losing it makes every encrypted session and any encrypted model cast permanently unreadable. A database restore without the matching `APP_KEY` is a partial restore. |
| Redis (cache, queue, Horizon metrics) | Redis | **No.** Cache is regenerable by definition. Queued jobs are transient work-in-flight, not durable records — anything that must survive a Redis loss should already be persisted to the database by the time it's queued (see `sync_operations`'s ledger pattern in CLAUDE.md for the general idea). Don't spend backup effort here.
| `storage/framework/sessions` (if `SESSION_DRIVER=file`) | Local disk | No — sessions are meant to be ephemeral; losing them just logs everyone out. |

## Database backup

```bash
# Full dump, compressed
mysqldump --single-transaction --routines --triggers \
  -h "$DB_HOST" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" \
  | gzip > "backup-$(date +%Y%m%d-%H%M%S).sql.gz"
```

`--single-transaction` gives a consistent snapshot without locking tables — important
since this app has live traffic hitting `payments`/`subscriptions` continuously via
PayFast ITN callbacks.

Automate this on a schedule appropriate to your data's change rate and your recovery
point objective — this starter doesn't ship a cron job for it, since that decision
(frequency, retention, where backups live) is a real product/ops decision for whoever
deploys this, not something to guess at generically here.

## Database restore

```bash
gunzip < backup-20260813-120000.sql.gz | mysql -h "$DB_HOST" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE"

php artisan migrate:status   # confirm the restored schema matches what current code expects
php artisan starter:doctor --production
```

If the restored dump predates the currently-deployed code, `migrate:status` will show
pending migrations — run `php artisan migrate --force` to bring the schema forward
from the restore point. If the dump is *newer* than the deployed code (restoring after
a bad deploy that also shipped a destructive migration), see `rollback.md`'s schema
rollback section first.

## Verifying a restore actually worked

Don't just check that the restore command exited 0 — confirm the data is real:

```bash
php artisan tinker --execute="echo \App\Models\User::count().' users, '.\App\Models\Payment::count().' payments';"
```

And functionally: log in as a real seeded/restored account, load `/dashboard`, confirm
billing widgets and audit log entries show real historical data, not empty states.

## Media/avatar restore

A database restore alone does **not** restore the actual files `spatie/laravel-medialibrary`
manages — the `media` table's rows reference files, but the files themselves live on
whatever `MEDIA_DISK` points at (S3-compatible object storage). If the object storage
itself is intact (its own versioning/replication, untouched by whatever caused the DB
loss), a DB restore alone is sufficient — the `media` rows will correctly re-point at
the still-existing files. If the object storage was *also* lost, there is no
application-level fallback; it needs restoring from the storage provider's own backup
mechanism before the `media` table rows mean anything.
