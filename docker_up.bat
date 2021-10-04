docker-compose up -d
docker-compose exec -T app php /var/www/html/bin/wait-for-db
docker-compose exec -T app php /var/www/html/bin/update.php
docker-compose exec -T app php /var/www/html/bin/create_example_data.php