#!/bin/bash
set -euo pipefail

APP_ROOT=/var/www
ENV_FILE="${APP_ROOT}/.env"
NGINX_TEMPLATE=/etc/nginx/templates/default.conf.template
NGINX_CONF=/etc/nginx/sites-available/default
LISTEN_PORT="${PORT:-80}"
SQLITE_DB="${APP_ROOT}/database/database.sqlite"

log() {
    printf '[startup] %s\n' "$1"
}

log "Starting Laravel application"

# Create storage directories if they don't exist
mkdir -p "${APP_ROOT}/storage/logs" \
         "${APP_ROOT}/storage/framework/cache/data" \
         "${APP_ROOT}/storage/framework/sessions" \
         "${APP_ROOT}/storage/framework/views" \
         "${APP_ROOT}/storage/app/public" \
         "${APP_ROOT}/bootstrap/cache" \
         "${APP_ROOT}/database" \
         /var/log/supervisor

# Create SQLite database file if it doesn't exist
if [ ! -f "${SQLITE_DB}" ]; then
    log "Creating SQLite database at ${SQLITE_DB}"
    touch "${SQLITE_DB}"
fi

# Set permissions
chown -R www-data:www-data "${APP_ROOT}/storage" "${APP_ROOT}/bootstrap/cache" "${APP_ROOT}/database"
chmod -R 775 "${APP_ROOT}/storage" "${APP_ROOT}/bootstrap/cache" "${APP_ROOT}/database"
chmod 664 "${SQLITE_DB}"

# Create .env file from .env.example if it doesn't exist
if [ ! -f "${ENV_FILE}" ]; then
    log "Creating .env from .env.example"
    cp "${APP_ROOT}/.env.example" "${ENV_FILE}"
fi

# Render assigns PORT dynamically, so render the final Nginx config at boot.
sed "s/__PORT__/${LISTEN_PORT}/g" "${NGINX_TEMPLATE}" > "${NGINX_CONF}"

if [ -n "${RENDER_EXTERNAL_URL:-}" ] && [ -z "${APP_URL:-}" ]; then
    export APP_URL="${RENDER_EXTERNAL_URL}"
fi

# Generate a local key automatically, but require a real APP_KEY on Render.
if [ -z "${APP_KEY:-}" ]; then
    if [ "${RENDER:-false}" = "true" ] || [ -n "${RENDER_EXTERNAL_HOSTNAME:-}" ]; then
        log "APP_KEY is not set. Add APP_KEY in Render before starting this service."
        exit 1
    fi

    log "Generating APP_KEY for local Docker use"
    php artisan key:generate --force --no-interaction
fi

log "Refreshing Laravel caches before boot"
php artisan optimize:clear
php artisan package:discover --ansi

php artisan storage:link --force >/dev/null 2>&1 || true

if [ "${SKIP_MIGRATIONS:-false}" != "true" ]; then
    log "Running database migrations"
    php artisan migrate --force --no-interaction
fi

if [ "${SKIP_OPTIMIZE:-false}" != "true" ]; then
    log "Caching Laravel configuration"
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

log "Laravel ready. Starting Supervisor"

# Start Supervisor (runs PHP-FPM + Nginx)
exec supervisord -c /etc/supervisor/conf.d/supervisord.conf
