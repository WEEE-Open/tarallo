.ONESHELL:
default: up


.PHONY:
features:
	utils/generate-features "$(CURDIR)"


resources/cache/SSRv1.cache: src/SSRv1/Controller.php
	php bin/build-cache

resources/cache/APIv2.cache: src/APIv2/Controller.php
	php bin/build-cache

.PHONY:
cache: resources/cache/SSRv1.cache resources/cache/APIv2.cache


.PHONY:
build: $(wildcard docker/**/*)
	docker-compose down --volume || true
	docker-compose build --no-cache

.PHONY:
buildcached: $(wildcard docker/**/*)
	docker-compose down || true
	docker-compose build


.PHONY:
profilerbuild: $(wildcard docker/**/*)
	docker-compose down || true
	docker-compose build --no-cache --build-arg XDEBUG=true --build-arg PROFILER=true


.PHONY:
up: up_internal dbupdate examples

.PHONY:
ci: up_internal dbupdate

.PHONY:
up_internal:
	docker-compose up -d
	sleep 10 # database takes a while to really start, the next command fails immediately otherwise


.PHONY:
down:
	docker-compose down --volume


.PHONY:
destroy: down
	docker volume rm "$(notdir $(PWD))_tarallo-db" || true


.PHONY:
rebuild: down destroy build up


.PHONY:
dbupdate:
	docker-compose exec -T app php /var/www/html/bin/update.php


.PHONY:
examples:
	docker-compose exec -T app php /var/www/html/bin/create_example_data.php
