#!/usr/bin/env sh
set -e

cd /var/www/html

export PORT="${PORT:-10000}"

if [ -f /etc/nginx/sites-enabled/default ]; then
  rm -f /etc/nginx/sites-enabled/default
fi

envsubst '${PORT}' < /etc/nginx/templates/default.conf.template > /etc/nginx/conf.d/default.conf

# Ensure Laravel writable paths exist
mkdir -p \
  storage/framework/cache/data \
  storage/framework/sessions \
  storage/framework/views \
  storage/logs \
  bootstrap/cache

# Permissions (don’t fail if chown not allowed)
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R 775 storage bootstrap/cache || true

composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader

# Clear any stale cached config/routes/views from previous deploys
php artisan optimize:clear || true

# Wait for DB + migrate
attempts=0
until php artisan migrate --force; do
  attempts=$((attempts + 1))
  if [ "$attempts" -ge 10 ]; then
    echo "Migration failed after ${attempts} attempts."
    exit 1
  fi

  echo "Waiting for database... (${attempts}/10)"
  sleep 3
done

# Cache after migrations
php artisan config:cache
php artisan route:cache
php artisan view:cache || true

exec supervisord -c /etc/supervisor/conf.d/supervisord.conf
