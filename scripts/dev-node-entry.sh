#!/usr/bin/env bash
set -euo pipefail

cd /var/www

# Ensure dependencies are installed
if [ ! -d "node_modules" ] || [ ! -f "node_modules/.bin/vite" ]; then
  echo "Installing node dependencies..."
  npm install
fi

# Patch lucide sourcemaps to avoid esbuild crashes in dev
if [ -f "scripts/patch-lucide-sourcemaps.js" ]; then
  echo "Patching lucide-vue-next sourcemaps..."
  node ./scripts/patch-lucide-sourcemaps.js || true
fi

# Clear Vite optimize cache to avoid stale prebundles
rm -rf node_modules/.vite || true

# Start Vite dev server
exec npm run dev

