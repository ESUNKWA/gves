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
php artisan migrate --force

# Every seeder DatabaseSeeder calls is idempotent (firstOrCreate-based), so
# this is safe to run on every boot, not just the first — it's what creates
# (or repairs the role on) the instance's super-admin from ADMIN_EMAIL /
# ADMIN_PASSWORD, plus starter departments/positions/leave types/etc.
php artisan db:seed --force

exec "$@"
