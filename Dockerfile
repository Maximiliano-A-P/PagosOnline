# ==========================================================
# ETAPA 1
# Compilación de assets frontend
# ==========================================================

FROM node:22-bookworm-slim AS frontend


# ==========================================================
# Directorio de trabajo
# ==========================================================

WORKDIR /var/www/html


# ==========================================================
# Copiamos los archivos de npm
# ==========================================================

COPY package.json package-lock.json ./


# ==========================================================
# Instalamos dependencias frontend
# ==========================================================

RUN npm ci


# ==========================================================
# Copiamos los archivos necesarios para Vite
# ==========================================================

COPY resources ./resources
COPY public ./public
COPY vite.config.js ./
COPY postcss.config.js ./
COPY tailwind.config.js ./


# ==========================================================
# Compilamos los assets
# ==========================================================

RUN npm run build


# ==========================================================
# ETAPA 2
# Aplicación Laravel
# ==========================================================

FROM php:8.4-fpm-bookworm


# ==========================================================
# Dependencias del sistema
# ==========================================================

RUN apt-get update \
    && apt-get install -y \
        nginx \
        supervisor \
        git \
        unzip \
        libpq-dev \
        libzip-dev \
        libicu-dev \
        libonig-dev \
        libxml2-dev \
        libcurl4-openssl-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libssl-dev \
        curl \
    && rm -rf /var/lib/apt/lists/*


# ==========================================================
# Extensiones PHP necesarias para Laravel
# ==========================================================

RUN docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_pgsql \
        mbstring \
        bcmath \
        exif \
        pcntl \
        intl \
        zip \
        gd \
        opcache


# ==========================================================
# Composer
# ==========================================================

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer


# ==========================================================
# Directorio de la aplicación
# ==========================================================

WORKDIR /var/www/html


# ==========================================================
# Copiamos los archivos de Composer
# ==========================================================

COPY composer.json composer.lock ./


# ==========================================================
# Instalamos dependencias PHP sin ejecutar scripts
#
# Los scripts de Composer de Laravel necesitan que artisan
# ya exista. Por eso primero instalamos las dependencias
# y posteriormente copiamos la aplicación.
# ==========================================================

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts


# ==========================================================
# Copiamos el proyecto Laravel
# ==========================================================

COPY . .


# ==========================================================
# Ejecutamos los scripts de Composer ahora que artisan
# ya existe dentro del contenedor.
# ==========================================================

RUN composer dump-autoload \
    --optimize


# ==========================================================
# Copiamos los assets compilados por Vite
# ==========================================================

COPY --from=frontend \
    /var/www/html/public/build \
    /var/www/html/public/build


# ==========================================================
# Permisos de Laravel
# ==========================================================

RUN mkdir -p \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data \
        storage \
        bootstrap/cache \
    && chmod -R 775 \
        storage \
        bootstrap/cache


# ==========================================================
# Configuración de NGINX
# ==========================================================

COPY docker/nginx/default.conf \
    /etc/nginx/sites-available/default


# ==========================================================
# Configuración de Supervisor
# ==========================================================

COPY docker/supervisord.conf \
    /etc/supervisor/conf.d/supervisord.conf


# ==========================================================
# Script de inicio
# ==========================================================

COPY docker/start.sh \
    /usr/local/bin/start.sh

RUN chmod +x /usr/local/bin/start.sh


# ==========================================================
# Puerto de Render
# ==========================================================

EXPOSE 10000


# ==========================================================
# Inicio del contenedor
# ==========================================================

CMD ["/usr/local/bin/start.sh"]
