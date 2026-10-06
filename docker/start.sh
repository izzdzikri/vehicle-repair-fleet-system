#!/bin/sh
set -e

PORT="${PORT:-10000}"
echo ">>> Starting on port ${PORT}"

# Write the Apache config explicitly instead of patching it with sed
echo "Listen 0.0.0.0:${PORT}" > /etc/apache2/ports.conf

cat > /etc/apache2/sites-available/000-default.conf <<EOF
<VirtualHost *:${PORT}>
    ServerAdmin webmaster@localhost
    DocumentRoot /var/www/html/public
</VirtualHost>
EOF

echo "ServerName localhost" > /etc/apache2/conf-available/servername.conf
a2enconf servername > /dev/null

cd /var/www/html

php artisan storage:link --force || true
php artisan config:clear
php artisan migrate --force

# One-time demo data. Set RUN_SEED=true for ONE deploy only,
# because FreshSeeder TRUNCATES every table.
if [ "$RUN_SEED" = "true" ]; then
    php artisan db:seed --force
fi

# Don't run route:cache: your routes/web.php has closure routes.
php artisan config:cache
php artisan view:cache

# Print the config check so it shows up in the Render logs
apache2ctl -t || true
echo ">>> Apache config OK, launching"

exec apache2-foreground