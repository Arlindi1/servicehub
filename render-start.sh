#!/usr/bin/env sh
set -e

cd /var/www/html

export PORT="${PORT:-10000}"

if [ -f /etc/nginx/sites-enabled/default ]; then
  rm -f /etc/nginx/sites-enabled/default
fi

envsubst '${PORT}' < /etc/nginx/templates/default.conf.template > /etc/nginx/conf.d/default.conf

mkdir -p storage bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache

composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader

php artisan config:cache
php artisan route:cache

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

exec supervisord -c /etc/supervisor/conf.d/supervisord.conf

