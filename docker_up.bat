docker-compose up -d
docker-compose exec -T app php /var/www/html/bin/wait-for-db
docker-compose exec -T app php /var/www/html/bin/update-db
docker-compose exec -T app php /var/www/html/bin/create-example-data