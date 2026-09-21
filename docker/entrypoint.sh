#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

if [[ ! -f vendor/autoload.php ]]; then
  echo "[doccompiler] Installing Composer dependencies..."
  composer install --prefer-dist --no-interaction
fi

mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache || true

exec "$@"
