#!/usr/bin/env bash
# Boots a throwaway, self-contained instance of the app for the Playwright E2E suite:
# fresh sqlite file DB (migrated + seeded), Laravel's built-in server, real starter
# accounts (see database/seeders/StarterUsersSeeder.php). Requires `npm run build` to
# have already run, so `php artisan serve` has a real manifest to serve — it doesn't
# start (or need) the Vite dev server. Not used by PHPUnit, which runs entirely
# in-memory with its own env (see .env.testing's header comment).
set -euo pipefail

cd "$(dirname "$0")/.."

export APP_ENV=testing

mkdir -p database
rm -f database/e2e.sqlite
touch database/e2e.sqlite

php artisan config:clear
php artisan migrate:fresh --seed --force

exec php artisan serve --host=127.0.0.1 --port=8080
