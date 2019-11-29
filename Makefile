default: production

.PHONY:
production: clean cache copies compose build/config/config.php

.PHONY:
vm:
	mkdir -p utils/provision/roles
	ansible-galaxy install -p utils/provision/roles goozbach.EPEL
	ansible-galaxy install -p utils/provision/roles geerlingguy.nginx
	ansible-galaxy install -p utils/provision/roles bertvv.mariadb
	vagrant plugin install vagrant-vbguest

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

# Could be useful, could be not.
# chmod o-w build/src/SSRv1/router.cache

.PHONY:
compose:
	test ! -f "build/vendor/autoload.php" || rm "build/vendor/autoload.php"
	pushd build/ >/dev/null && composer install --no-dev -n --no-suggest --classmap-authoritative --optimize-autoloader && popd
	rm -f "build/composer.json" "build/composer.lock"

.PHONY:
cache:
	test ! -f "build/SSRv1/router.cache" || rm build/SSRv1/router.cache
	test ! -f "build/APIv1/router.cache" || rm build/APIv1/router.cache
	php bin/build-cache ../build/

.PHONY:
dbupdate:
	vagrant provision --provision-with db_update,test_db_update

build/config/config.php:
ifneq ("$(wildcard config/config-production.php)","")
	mkdir build/config
	cp config/config-production.php build/config/config.php
else
	@echo "/!\\ No config/config-production.php found, add your own config.php in the build/config directory /!\\"
endif
