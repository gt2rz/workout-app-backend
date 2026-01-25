# Etapa 1: Dependencias de Composer
FROM php:8.4-fpm-alpine AS vendor

# Instalar dependencias necesarias para compilar extensiones
RUN apk add --no-cache \
    freetype-dev libjpeg-turbo-dev libpng-dev libzip-dev \
    icu-dev postgresql-dev oniguruma-dev bash

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_pgsql mbstring zip exif pcntl bcmath gd intl opcache

# Instalar Composer en la primera etapa
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY composer.json composer.lock ./

# Usa un montaje de caché para la carpeta de Composer, esto hace que las instalaciones sean más rápidas al reutilizar dependencias ya descargadas
RUN --mount=type=cache,target=/root/.composer/cache \
    composer install --no-scripts --no-autoloader --prefer-dist --no-dev

# Etapa 2: Aplicación Final
FROM php:8.4-fpm-alpine

# 1. Instalar dependencias de ejecución (Añadimos Nginx y dependencias de GD/Intl)
RUN apk add --no-cache libpng libzip icu libpq nginx freetype libjpeg-turbo bash

# 2. Copiar Composer y extensiones compiladas (Desde Etapa 1)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
COPY --from=vendor /usr/local/lib/php/extensions /usr/local/lib/php/extensions
COPY --from=vendor /usr/local/etc/php/conf.d /usr/local/etc/php/conf.d

# 3. COPIAR CONFIGURACIONES PERSONALIZADAS (Aquí está lo que faltaba)
# Copia tu archivo de Nginx al directorio de configuración de Nginx en Alpine
COPY docker/nginx.conf /etc/nginx/http.d/default.conf

# Copia tu archivo de Opcache a la carpeta de configuración de PHP
COPY docker/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

WORKDIR /var/www/html

# 4. Copiar código y dependencias
COPY --from=vendor /app/vendor ./vendor
COPY . .

# 5. Optimizar Laravel 12
RUN composer dump-autoload --optimize && \
    php artisan optimize

# 6. Permisos y Entrypoint
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
EXPOSE 80

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
