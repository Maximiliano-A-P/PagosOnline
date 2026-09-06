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
