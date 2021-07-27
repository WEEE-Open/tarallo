start "" "C:\Program Files\Docker\Docker\Docker Desktop.exe"
timeout /t 20 /nobreak
docker-compose up -d
timeout /t 10 /nobreak
docker-compose exec -T app php /var/www/html/bin/update.php
docker-compose exec -T app php /var/www/html/bin/create_example_data.php