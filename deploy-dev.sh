#!/usr/bin/env bash
set -euo pipefail

echo "Rebuilding Node image for dev..."
docker compose -f docker-compose.yml -f docker-compose.dev.yml --profile dev build node

echo "Starting dev stack (will build if needed)..."
docker compose -f docker-compose.yml -f docker-compose.dev.yml --profile dev up -d --build --remove-orphans

echo "Tail recent Node logs:"
docker compose -f docker-compose.yml -f docker-compose.dev.yml --profile dev logs -n 120 --no-log-prefix node || true

echo "Development deployment complete!"

