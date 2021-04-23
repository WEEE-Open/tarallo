.ONESHELL:
default: vm

.PHONY:
vm:
	mkdir -p utils/provision/roles
	ansible-galaxy install -p utils/provision/roles goozbach.EPEL
	ansible-galaxy install -p utils/provision/roles geerlingguy.nginx
	ansible-galaxy install -p utils/provision/roles bertvv.mariadb
	ansible-galaxy install -p utils/provision/roles geerlingguy.repo-remi
	ansible-galaxy install -p utils/provision/roles geerlingguy.php

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
vmdbupdate:
	vagrant provision --provision-with db_update

.PHONY:
vmexamples:
	vagrant provision --provision-with example_data

.PHONY:
build: $(wildcard docker/**/*)
	docker-compose down || true
	docker volume rm "$(notdir $(PWD))_tarallo-web" || true
	docker-compose build --no-cache

.PHONY:
buildcached: $(wildcard docker/**/*)
	docker-compose down || true
	docker volume rm "$(notdir $(PWD))_tarallo-web" || true
	docker-compose build

.PHONY:
up: up_internal dbupdate examples

.PHONY:
up_internal:
	docker-compose up -d
	sleep 10 # database takes a while to really start, the next command fails immediately otherwise

.PHONY:
down:
	docker-compose down

.PHONY:
destroy: down
	docker volume rm "$(notdir $(PWD))_tarallo-web" || true

.PHONY:
rebuild: down destroy build up

.PHONY:
dbupdate:
	docker-compose exec -T app php /var/www/html/bin/update.php

.PHONY:
examples:
	docker-compose exec -T app php /var/www/html/bin/create_example_data.php
