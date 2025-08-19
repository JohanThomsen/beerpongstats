echo Recreating containers with latest code development mode...
docker compose -f docker-compose.yml -f docker-compose.dev.yml --profile dev up -d

echo Development deployment complete!
