#!/bin/bash

echo "Building frontend assets..."
docker compose -f docker-compose.yml -f docker-compose.prod.yml run --rm assets

echo "Recreating containers with latest code..."
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build

docker compose -f docker-compose.yml -f docker-compose.prod.yml exec nginx nginx -s reload

echo "Deployment complete!"
