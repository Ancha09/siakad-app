#!/usr/bin/env sh
set -eu
cd "$(dirname "$0")/.."

# Run in a clean build checkout, with PHP 8.3+ and Node 22.12+/24.
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
npm ci --include=dev
npm run build
php scripts/check-deploy.php
# Relative link works when the build checkout is packaged at /app on Wasmer.
# Preserve existing links/directories; no uploads are removed.
if [ ! -e public/storage ] && [ ! -L public/storage ]; then
    ln -s ../storage/app/public public/storage
fi

# Secrets belong to runtime. Do not migrate, seed, generate APP_KEY,
# or cache environment-dependent configuration during the build.
