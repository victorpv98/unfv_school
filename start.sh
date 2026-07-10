#!/usr/bin/env bash
set -euo pipefail

APP_DIR=/var/www/html
cd "${APP_DIR}"

rm -f bootstrap/cache/*.php || true

if [ -n "${DB_PORT:-}" ] && ! [[ "${DB_PORT}" =~ ^[0-9]+$ ]]; then
  echo "Invalid DB_PORT value: ${DB_PORT}" >&2
  echo "DB_PORT must be a number. For Railway Postgres private networking use 5432." >&2
  exit 1
fi

chown -R www-data:www-data storage bootstrap/cache || true
find storage -type d -exec chmod 0775 {} \; || true
find storage -type f -exec chmod 0664 {} \; || true
chmod -R ug+rwX bootstrap/cache || true

echo "Clearing Laravel caches..."
runuser -u www-data -- php artisan optimize:clear --no-ansi || true

if [ ! -L "${APP_DIR}/public/storage" ]; then
  echo "Creating storage symlink..."
  runuser -u www-data -- php artisan storage:link --no-ansi || true
fi

if [ "${DB_CONNECTION:-}" = "pgsql" ] && command -v pg_isready >/dev/null 2>&1; then
  echo "Waiting for PostgreSQL..."
  export PGPASSWORD="${DB_PASSWORD:-}"
  until pg_isready \
      -h "${DB_HOST:-127.0.0.1}" \
      -p "${DB_PORT:-5432}" \
      -d "${DB_DATABASE:-postgres}" \
      -U "${DB_USERNAME:-postgres}" >/dev/null 2>&1; do
    echo "Database not ready, retrying in 5 seconds..."
    sleep 5
  done
elif [ "${DB_CONNECTION:-}" = "mysql" ] && command -v mysqladmin >/dev/null 2>&1; then
  echo "Waiting for MySQL..."
  until mysqladmin ping -h "${DB_HOST:-127.0.0.1}" -P "${DB_PORT:-3306}" --silent; do
    echo "Database not ready, retrying in 5 seconds..."
    sleep 5
  done
fi

echo "Running database migrations..."
until runuser -u www-data -- php artisan migrate --force --no-interaction --no-ansi; do
  echo "Migrations failed or DB not ready yet. Retrying in 5 seconds..."
  sleep 5
done

if [ "${RUN_DEMO_USERS_ON_BOOT:-false}" = "true" ]; then
  echo "Running DemoUsersSeeder on boot..."
  runuser -u www-data -- php artisan db:seed --class=DemoUsersSeeder --force --no-interaction --no-ansi || true
fi

if [ "${RUN_SEEDERS_ON_BOOT:-false}" = "true" ]; then
  echo "Running DatabaseSeeder on boot..."
  runuser -u www-data -- php artisan db:seed --force --no-interaction --no-ansi || true
fi

if [ -n "${RUN_SEEDERS_CLASSES:-}" ]; then
  IFS=',' read -ra _classes <<< "${RUN_SEEDERS_CLASSES}"
  for _c in "${_classes[@]}"; do
    echo "Running seeder: ${_c}"
    runuser -u www-data -- php artisan db:seed --class="${_c}" --force --no-interaction --no-ansi || true
  done
fi

PORT="${PORT:-8080}"
APP_HOST="$(echo "${APP_URL:-localhost}" | sed -E 's#^https?://##' | sed 's#/.*$##')"

echo "Configuring Apache to listen on ${PORT} and ServerName ${APP_HOST} ..."

echo "ServerName ${APP_HOST}" > /etc/apache2/conf-available/servername.conf || true
a2enconf servername >/dev/null 2>&1 || true

a2dismod mpm_event mpm_worker >/dev/null 2>&1 || true
a2enmod mpm_prefork >/dev/null 2>&1 || true
a2enmod rewrite >/dev/null 2>&1 || true
a2enmod headers >/dev/null 2>&1 || true
a2enmod expires >/dev/null 2>&1 || true

if [ -f /etc/apache2/ports.conf ]; then
  if grep -qE '^[# ]*Listen +80\b' /etc/apache2/ports.conf; then
    sed -ri "s/^[# ]*Listen +80\b/Listen ${PORT}/" /etc/apache2/ports.conf
  fi
  if ! grep -qE "^[# ]*Listen +${PORT}\b" /etc/apache2/ports.conf; then
    echo "Listen ${PORT}" >> /etc/apache2/ports.conf
  fi
fi

cat >/etc/apache2/sites-available/railway.conf <<EOF
<VirtualHost *:${PORT}>
    ServerName ${APP_HOST}
    DocumentRoot ${APP_DIR}/public

    <Directory ${APP_DIR}/public>
        AllowOverride All
        Require all granted
        Options -Indexes +FollowSymLinks
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/error.log
    CustomLog \${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
EOF

a2dissite 000-default >/dev/null 2>&1 || true
a2ensite railway >/dev/null 2>&1

echo "Starting Apache..."
exec apache2-foreground
