# Release And Integration Checklist

This starter is meant to be ready to use, not a thin scaffold.
That means package release and package integration are part of implementation, not optional follow-up work.

## Current Reality

Validated local target versions:

- `rainwaves/lara-auth-suite` `v2.0.0`
- `rainwaves/payfast-payment` `v1.7.0`

Important distinction:

- both tags already exist locally in the package repos
- Packagist still reflects older published package state
- therefore the next task is release propagation and starter integration, not more package version planning

## Package Release Checklist

### `rainwaves/lara-auth-suite`

Repo:

- `/home/eclaims/package-development/rainwaves/lara-auth-suite`

Current observations:

- target tag exists locally: `v2.0.0`
- remote: `git@github.com:Magnificent-Big-J/lara-auth-suite.git`
- local cleanup needed before any new commit:
  - `src/build/`
  - `src/phpunit.xml.bak`

Release steps:

1. confirm `composer.json` version constraints and changelog are final for `v2.0.0`
2. remove local test artifacts that should not ship
3. verify tests pass
4. confirm local branch is committed
5. push branch
6. push tag `v2.0.0`
7. confirm GitHub shows the tag
8. confirm Packagist updates to `v2.0.0`
9. confirm published metadata shows Laravel 13 support

### `rainwaves/payfast-payment`

Repo:

- `/home/eclaims/package-development/rainwaves/payfast-payment`

Current observations:

- target tag exists locally: `v1.7.0`
- remote: `git@github.com:Magnificent-Big-J/payfast-payment.git`
- local cleanup needed before any new commit:
  - `.phpunit.cache/`
  - `.phpunit.result.cache`
  - `phpunit.xml.bak`

Release steps:

1. confirm `composer.json` constraints and changelog are final for `v1.7.0`
2. remove local test artifacts that should not ship
3. verify tests pass
4. confirm local branch is committed
5. push branch
6. push tag `v1.7.0`
7. confirm GitHub shows the tag
8. confirm Packagist updates to `v1.7.0`
9. confirm published metadata shows Laravel 13 support

## Starter Integration Checklist

Repo:

- `/home/eclaims/htdocs/rainwaves-starter`

Target result:

- starter installs both packages through normal Composer resolution
- no path repositories
- no temporary VCS shortcuts
- no manual copying of package code

Integration steps:

1. require `rainwaves/lara-auth-suite:^2.0`
2. require `rainwaves/payfast-payment:^1.7`
3. require supporting platform packages:
   - `laravel/sanctum`
   - `spatie/laravel-permission`
   - `spatie/laravel-medialibrary`
   - `spatie/laravel-activitylog`
   - `laravel/horizon`
   - `predis/predis` or approved Redis client
   - `league/flysystem-aws-s3-v3`
4. publish and review:
   - auth config
   - auth migrations
   - payment config
   - permission config and migrations
   - media config and migrations
   - activity log migrations
   - Sanctum config if needed
5. create host-side auth resource:
   - `app/Http/Resources/AuthUserResource.php`
6. create secure starter defaults in env/config:
   - registration disabled
   - permissions fail closed
   - session/cookie configuration reviewed
   - local MinIO and public asset access aligned
7. build starter baseline data model:
   - roles
   - permissions
   - seeders
   - factories
   - default admin bootstrap path
8. finish real auth integration:
   - login
   - logout
   - forgot password
   - reset password
   - 2FA challenge
   - 2FA verify
   - authenticated user payload
9. finish starter admin/profile baseline:
   - admin users
   - profile page
   - avatar/media path
10. finish payment baseline:
   - config
   - example checkout flow
   - ITN validation surface

## Reusable Starter Layer

Reusable components should be extracted as the real domains land.
Do not wait until the end to “componentize” the app.

Already in place:

- `AppHeader`
- `AppToastHost`
- `AuthCard`
- `BusyOverlay`
- `FormActions`
- `FormStatusAlert`

Next reusable components to add during platform build:

- `AppPageHeader`
- `AppSectionCard`
- `AppEmptyState`
- `AppStatusBadge`
- `AppDataTable`
- `AppPaginationBar`
- `ConfirmDialog`
- `MediaUploader`
- 2FA setup and recovery-code panels

## Definition Of Ready For The Starter

The starter can be called ready-to-use only when all of these are true:

1. fresh install works through normal Composer and npm flows
2. Sail local stack comes up cleanly
3. auth works end-to-end against the real package
4. roles and permissions are seeded and enforced
5. factories and seeders allow quick project start
6. profile/media baseline works with local MinIO
7. payment baseline is present and testable
8. reusable starter components cover the main platform flows
9. documentation is sufficient for another engineer to bootstrap a new app from this repo
