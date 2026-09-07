#!/bin/sh

set -e

echo "Iniciando Laravel..."

# ==========================================================
# Preparar directorios necesarios de Laravel
# ==========================================================

mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache


# ==========================================================
# Permisos
# ==========================================================

chown -R www-data:www-data \
    storage \
    bootstrap/cache

chmod -R 775 \
    storage \
    bootstrap/cache


# ==========================================================
# Preparar almacenamiento público
# ==========================================================

if [ ! -L public/storage ]; then
    php artisan storage:link || true
fi


# ==========================================================
# Migrar base de datos ANTES de tocar cachés
#
# optimize:clear intenta limpiar la tabla "cache" (si el
# driver es "database"), así que las tablas deben existir
# antes de correr cualquier comando de caché.
# --force evita el prompt interactivo de producción.
# ==========================================================

php artisan migrate --force


# ==========================================================
# Seedear datos iniciales (idempotente: usa updateOrCreate,
# seguro de correr en cada deploy)
# ==========================================================

php artisan db:seed --force


# ==========================================================
# Limpiar cachés anteriores
# ==========================================================

php artisan optimize:clear


# ==========================================================
# Cachear configuración y rutas
#
# APP_KEY, APP_URL, DB y demás variables serán proporcionadas
# por Render mediante variables de entorno.
# ==========================================================

php artisan config:cache
php artisan route:cache
php artisan view:cache


# ==========================================================
# Iniciar Supervisor
#
# Supervisor mantendrá funcionando:
# - PHP-FPM
# - NGINX
# ==========================================================

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf