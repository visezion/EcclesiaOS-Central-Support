#!/bin/sh
set -eu

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown www-data:www-data storage storage/framework storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache

if [ ! -L public/storage ]; then
    su www-data -s /bin/sh -c 'php artisan storage:link' >/dev/null 2>&1 || true
fi

exec "$@"
