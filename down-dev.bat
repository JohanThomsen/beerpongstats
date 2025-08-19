echo Downing...
docker compose -f docker-compose.yml -f docker-compose.dev.yml --profile dev down --remove-orphans

echo Down complete!
