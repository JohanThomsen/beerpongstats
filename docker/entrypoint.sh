#!/usr/bin/env sh
set -e

cd /var/www

echo "[entrypoint] Preparing application..."

# Ensure required dirs exist and are writable
mkdir -p storage/framework/{cache,sessions,views} 2>/dev/null || true
mkdir -p bootstrap/cache 2>/dev/null || true

need_install=false
# Determine if we need to (re)install composer deps
if [ ! -f vendor/autoload.php ]; then
  need_install=true
elif [ ! -f vendor/composer/installed.php ]; then
  need_install=true
elif [ composer.lock -nt vendor/composer/installed.php ] || [ composer.json -nt vendor/composer/installed.php ]; then
  need_install=true
elif [ ! -f vendor/psr/http-factory/src/RequestFactoryInterface.php ]; then
  # Missing a commonly required package file -> install
  need_install=true
fi

# Install Composer dependencies if needed (with simple lock to avoid races)
if [ "$need_install" = true ]; then
  lockdir="/tmp/vendor-install.lock"
  until mkdir "$lockdir" 2>/dev/null; do
    echo "[entrypoint] Waiting for composer lock..."
    sleep 1
  done
  trap 'rmdir "$lockdir" 2>/dev/null || true' EXIT INT TERM

  echo "[entrypoint] Installing Composer dependencies..."
  COMPOSER_ARGS="--no-ansi --no-interaction --prefer-dist --no-progress"
  if [ "$APP_ENV" = "production" ] || [ "$APP_DEBUG" = "false" ]; then
    COMPOSER_ARGS="$COMPOSER_ARGS --no-dev --classmap-authoritative --optimize-autoloader"
  fi
  COMPOSER_ALLOW_SUPERUSER=1 composer install $COMPOSER_ARGS || true

  # Ensure autoload is generated
  COMPOSER_ALLOW_SUPERUSER=1 composer dump-autoload -o --no-ansi --no-interaction --no-progress >/dev/null 2>&1 || true

  rmdir "$lockdir" 2>/dev/null || true
  trap - EXIT INT TERM
fi

# Ensure .env exists (Laravel reads env vars too, but this helps artisan commands)
if [ ! -f .env ] && [ -f .env.example ]; then
  echo "[entrypoint] Creating .env from .env.example"
  cp .env.example .env
fi

# Generate APP_KEY if missing
if [ -z "${APP_KEY}" ] || [ "${APP_KEY}" = "base64:" ]; then
  echo "[entrypoint] Generating APP_KEY..."
  php artisan key:generate --force || true
fi

# Clear stale caches (ok if none exist)
php artisan config:clear >/dev/null 2>&1 || true
php artisan route:clear >/dev/null 2>&1 || true
php artisan view:clear >/dev/null 2>&1 || true

# Fix permissions for runtime dirs
chown -R www-data:www-data storage bootstrap/cache vendor 2>/dev/null || true
chmod -R ug+rwX storage bootstrap/cache 2>/dev/null || true

# Optionally wait for DB and run migrations
if [ "${MIGRATE_ON_START}" = "true" ]; then
  echo "[entrypoint] Waiting for database to be ready before running migrations..."
  i=0
  until php artisan migrate:status --no-interaction >/dev/null 2>&1; do
    i=$((i+1))
    if [ "$i" -ge 60 ]; then
      echo "[entrypoint] Database not ready after waiting; skipping migrations."
      break
    fi
    sleep 2
  done

  if php artisan migrate:status --no-interaction >/dev/null 2>&1; then
    echo "[entrypoint] Running database migrations..."
    php artisan migrate --force --no-interaction || true
  fi
fi

# In production, optionally cache config/routes for speed
if [ "$APP_ENV" = "production" ] || [ "$APP_DEBUG" = "false" ]; then
  php artisan config:cache >/dev/null 2>&1 || true
  php artisan route:cache >/dev/null 2>&1 || true
  php artisan view:cache >/dev/null 2>&1 || true
fi

echo "[entrypoint] Starting: $@"
exec "$@"
