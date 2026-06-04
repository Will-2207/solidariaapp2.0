FROM php:8.2-apache

# 1. Dependencias del sistema
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    pkg-config \
    libssl-dev \
    ca-certificates \
    libpq-dev \
    && update-ca-certificates \
    && rm -rf /var/lib/apt/lists/*

# 2. Habilitar mod_rewrite (para rutas limpias en PHP)
RUN a2enmod rewrite

# 3. Instalamos extensiones (MongoDB y PostgreSQL)
RUN pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && docker-php-ext-install pdo pdo_pgsql

# 4. Configuración de Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /var/www/html

# 5. Copiamos el código
COPY . .

# 6. Forzamos la regeneración del autoloader antes de instalar
RUN composer dump-autoload --optimize

# 7. Instalación de dependencias
RUN composer install --no-dev --optimize-autoloader

# 8. Permisos
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80
