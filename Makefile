default: production

.PHONY:
production: clean cache copies compose build/config/config.php

.PHONY:
vm:
	mkdir -p utils/provision/roles
	ansible-galaxy install -p utils/provision/roles goozbach.EPEL
	ansible-galaxy install -p utils/provision/roles geerlingguy.nginx
	ansible-galaxy install -p utils/provision/roles bertvv.mariadb
	#vagrant plugin install vagrant-vbguest

.PHONY:
features:
	utils/generate-features "$(CURDIR)"

.PHONY:
clean:
	mkdir -p build
	rm -rf build/*

.PHONY:
copies:
	cp -r "bin/" "build/"
	cp -r "public/" "build/"
	cp -r "src/" "build/"
	cp -r "resources/" "build/"
	cp composer.{json,lock} "build/"
	cp *.sql "build/"

.PHONY:
compose:
	test ! -f "build/vendor/autoload.php" || rm "build/vendor/autoload.php"
	pushd build/ >/dev/null && composer install --no-dev -n --no-suggest --classmap-authoritative --optimize-autoloader && popd
	rm -f "build/composer.json" "build/composer.lock"

.PHONY:
cache:
	test ! -f "build/resources/cache/SSRv1.cache" || rm "build/resources/cache/SSRv1.cache"
	test ! -f "build/resources/cache/APIv2.cache" || rm "build/resources/cache/APIv2.cache"
	php bin/build-cache

.PHONY:
dbupdate:
	vagrant provision --provision-with db_update,test_db_update

.PHONY:
examples:
	vagrant provision --provision-with example_data

build/config/config.php:
ifneq ("$(wildcard config/config-production.php)","")
	mkdir build/config
	cp config/config-production.php build/config/config.php
else
	@echo "/!\\ No config/config-production.php found, add your own config.php in the build/config directory /!\\"
endif
