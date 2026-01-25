#!/bin/bash

set -e

echo "Starting Laravel application..."

# Esperar a que la base de datos esté lista
echo "Waiting for database..."
until php artisan migrate:status 2>&1 | grep -q "Migration table created successfully\|Migration name"; do
  echo "Database is unavailable - sleeping"
  sleep 2
done

echo "Database is ready!"

# Ejecutar migraciones solo si es necesario
if [ "$APP_ENV" != "local" ]; then
  echo "Running migrations..."
  php artisan migrate --force --no-interaction
fi

# Limpiar y cachear configuraciones en producción
if [ "$APP_ENV" = "production" ]; then
  echo "Caching configurations..."
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
fi

# Iniciar supervisor (que maneja nginx y php-fpm)
echo "Starting services..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
