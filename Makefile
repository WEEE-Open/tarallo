.ONESHELL:
default: up


######################################### Utility #############################################
.PHONY:
features:
	bin/generate-features "$(CURDIR)"

.PHONY:
dbupdate:
	docker compose exec -T app php /var/www/html/bin/update-db

.PHONY:
examples:
	docker compose exec -T app php /var/www/html/bin/create-example-data

######################################## Production ###########################################
resources/cache/SSRv1.cache: src/SSRv1/Controller.php
	php bin/build-cache

resources/cache/APIv2.cache: src/APIv2/Controller.php
	php bin/build-cache

.PHONY:
cache: resources/cache/SSRv1.cache resources/cache/APIv2.cache


###################################### (Re)Build targets ######################################
.PHONY:
build: $(wildcard docker/**/*)
	docker compose down -v || true
	docker compose build

.PHONY:
rebuild: destroy build up

.PHONY:
refresh: $(wildcard docker/**/*)
	docker compose down -v || true
	docker compose build --no-cache

#################################### Continous Integration ####################################
.PHONY:
ci: build
	docker compose up -d

######################################### Environment #########################################
.PHONY:
up: up_internal dbupdate examples

.PHONY:
up_internal:
	docker compose up -d
	# database takes a while to really start, the next command fails immediately otherwise
	docker compose exec -T app php /var/www/html/bin/wait-for-db

.PHONY:
destroy: down
	docker volume rm "$(notdir $(PWD))_tarallo-db" || true

.PHONY:
down:
	docker compose down -v







