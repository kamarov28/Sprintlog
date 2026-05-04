#!/bin/sh
set -e

mkdir -p \
  storage/app/public \
  storage/framework/cache \
  storage/framework/sessions \
  storage/framework/views \
  storage/logs \
  bootstrap/cache

# Keep Laravel public file uploads reachable from Apache in every Swarm replica.
# The storage directory is mounted as a shared NFS-backed Docker volume, while
# public/storage lives inside each container, so the symlink must be recreated
# whenever a new app container starts.
if [ -e public/storage ] && [ ! -L public/storage ]; then
  rm -rf public/storage
fi

if [ ! -L public/storage ]; then
  ln -s /var/www/html/storage/app/public public/storage
fi

chown -R www-data:www-data storage bootstrap/cache

exec "$@"
