#!/bin/sh
set -e

mkdir -p \
  storage/app/public \
  storage/framework/cache \
  storage/framework/sessions \
  storage/framework/views \
  storage/logs \
  bootstrap/cache

# Keep Laravel public uploads reachable from every app container.
if [ -e public/storage ] && [ ! -L public/storage ]; then
  rm -rf public/storage
fi

if [ ! -L public/storage ]; then
  ln -s /var/www/html/storage/app/public public/storage
fi

chown -R www-data:www-data storage bootstrap/cache

exec "$@"
