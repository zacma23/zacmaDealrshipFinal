#!/bin/sh
set -e

echo "Starting Zacma Dealership Platform container..."

# Create storage directory links if needed
php artisan storage:link || true

# Check if migrations should run
if [ "$RUN_MIGRATIONS" = "true" ]; then
    echo "Running database migrations..."
    php artisan migrate --force
fi

# Cache configuration in production
if [ "$APP_ENV" = "production" ]; then
    echo "Caching configurations..."
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

# Execute supervisor
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf

