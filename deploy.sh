#!/bin/bash

echo "Building frontend assets..."
docker compose -f docker-compose.yml -f docker-compose.prod.yml run --rm assets

echo "Recreating containers with latest code..."
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build

# Check if SSL certificates exist, if not, guide user to set them up
if [ ! -d "$(docker compose -f docker-compose.yml -f docker-compose.prod.yml exec -T nginx ls /etc/letsencrypt/live/ 2>/dev/null)" ]; then
    echo ""
    echo "⚠️  SSL certificates not found!"
    echo "Run './ssl-setup.sh' first to generate SSL certificates"
    echo "Make sure to update the domain and email in ssl-setup.sh before running it"
else
    echo "✅ SSL certificates found, reloading nginx..."
    docker compose -f docker-compose.yml -f docker-compose.prod.yml exec nginx nginx -s reload
fi

echo "Deployment complete!"
