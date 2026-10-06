#!/bin/sh
set -e

# Render tells us which port to listen on
PORT="${PORT:-10000}"
sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

cd /var/www/html

php artisan storage:link --force || true
php artisan config:clear
php artisan migrate --force

# One-time demo data. Set RUN_SEED=true for the first deploy only,
# because FreshSeeder TRUNCATES every table.
if [ "$RUN_SEED" = "true" ]; then
    php artisan db:seed --force
fi

# Don't run route:cache: your routes/web.php has closure routes.
php artisan config:cache
php artisan view:cache

exec apache2-foreground