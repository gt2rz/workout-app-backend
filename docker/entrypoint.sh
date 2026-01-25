#!/bin/bash

set -e

echo "🚀 Starting Laravel 12 Deployment..."

# 1. Esperar a Postgres (Usando artisan para verificar conexión)
echo "⏳ Waiting for Postgres..."
MAX_RETRIES=30
COUNT=0
# Intentamos conectar a la DB sin importar si hay migraciones o no
until php artisan db:monitor --databases=pgsql > /dev/null 2>&1 || [ $COUNT -eq $MAX_RETRIES ]; do
  echo "🟡 Database not ready yet ($COUNT/$MAX_RETRIES)..."
  sleep 2
  ((COUNT++))
done

if [ $COUNT -eq $MAX_RETRIES ]; then
  echo "❌ Error: Database connection timeout."
  exit 1
fi

echo "✅ Database is up!"

# 2. Migraciones (Solo en producción o staging)
if [ "$APP_ENV" != "local" ]; then
  echo "📦 Running migrations..."
  # --force es clave para no pedir confirmación
  php artisan migrate --force --no-interaction
fi

# 3. Optimización de Laravel 12
if [ "$APP_ENV" = "production" ]; then
  echo "⚡ Optimizing for Production..."
  # 'optimize' en Laravel 12 ya gestiona config, routes y files
  php artisan optimize
  # Asegurar que las carpetas de storage tengan permisos correctos cada vez
  chmod -R 775 storage bootstrap/cache
  chown -R www-data:www-data storage bootstrap/cache
fi

# 4. Iniciar Supervisor
echo "🏁 Starting Supervisor..."
exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/supervisord.conf
