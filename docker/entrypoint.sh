#!/bin/sh
set -e

cd /var/www/html

if [ ! -f .env ] && [ -f .env.example ]; then
    cp .env.example .env
fi

if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
    php artisan key:generate --force
fi

if [ ! -d public/build ]; then
    npm install
    npm run build
fi

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

php artisan storage:link --force || true

# Additive only — this project never runs migrate:fresh outside an explicit,
# separate operator decision (see CLAUDE.md).
#
# Central migrations only (tenants/domains/cache/jobs/sessions) — business
# schema now lives in per-tenant databases (database/migrations/tenant),
# migrated individually when a tenant is provisioned (see
# App\Http\Controllers\Platform\TenantController). DatabaseSeeder's
# unconditional `db:seed --force` doesn't run here anymore: it assumes the
# old single-tenant schema (users/roles/etc, now tenant-scoped) and would
# fail against the central-only connection.
php artisan migrate --force

# Propagates any new tenant migration to every existing tenant database on
# every boot/redeploy, not just at provisioning time. Safe no-op with zero
# tenants (runForMultiple() over an empty list). --force is already baked
# into config('tenancy.migration_parameters').
php artisan tenants:migrate

exec "$@"
