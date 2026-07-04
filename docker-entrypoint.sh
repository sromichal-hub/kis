#!/usr/bin/env bash
set -euo pipefail

# Remove all cache
if [ -d var/cache ]; then
  echo "Removing cache directory..."
  rm -rf var/cache
fi

# Wait for Postgres to be ready
DB_HOST=$(echo "${DATABASE_URL:-}" | sed -n 's/.*@\([^:]*\):\([0-9]*\)\/.*$/\1/p' || echo db)
DB_PORT=$(echo "${DATABASE_URL:-}" | sed -n 's/.*@[^:]*:\([0-9]*\)\/.*$/\1/p' || echo 5432)
if [ -z "$DB_HOST" ]; then DB_HOST=db; fi
if [ -z "$DB_PORT" ]; then DB_PORT=5432; fi

echo "Waiting for database $DB_HOST:$DB_PORT..."
while ! nc -z "$DB_HOST" "$DB_PORT"; do
  sleep 1
done

# Ensure vendor exists (in case build didn't install)
if [ ! -d vendor ]; then
  composer install --no-interaction --prefer-dist
fi

# Run migrations
if [ -f bin/console ]; then
  echo "Running migrations..."
  php bin/console doctrine:migrations:migrate --no-interaction || true
fi

# Run built-in web server
echo "Starting PHP built-in server on 0.0.0.0:8000"
php -S 0.0.0.0:8000 -t public

