#!/bin/bash

echo "Building frontend assets..."
docker compose -f docker-compose.yml -f docker-compose.prod.yml run --rm assets

echo "Recreating containers with latest code..."
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build

echo "Deployment complete!"
