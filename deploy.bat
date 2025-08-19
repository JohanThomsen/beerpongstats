@echo off
echo Building frontend assets...
docker compose -f docker-compose.yml -f docker-compose.prod.yml run --rm assets

echo.
echo Recreating containers with latest code...
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build

echo.
echo Deployment complete!
