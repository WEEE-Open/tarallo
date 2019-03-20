default: production

.PHONY:
production: clean copies cache compose build/db.php

.PHONY:
vm:
	ansible-galaxy install goozbach.EPEL
	ansible-galaxy install geerlingguy.nginx
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
	cp "index.php" build/
	cp "update.php" build/
	cp composer.{json,lock} "build/"
	cp -r "src/" "build/"
	cp -r "APIv1/" "build/"
	cp -r "SSRv1/" "build/"

# Could be useful, could be not.
# chmod o-w build/SSRv1/router.cache

.PHONY:
compose:
	test ! -f "build/vendor/autoload.php" || rm "build/vendor/autoload.php"
	pushd build/ >/dev/null && composer install --no-dev -n --no-suggest --classmap-authoritative --optimize-autoloader && popd
	rm -f "build/composer.json" "build/composer.lock"

.PHONY:
cache:
	test ! -f "build/SSRv1/router.cache" || rm build/SSRv1/router.cache
	test ! -f "build/APIv1/router.cache" || rm build/APIv1/router.cache
	php utils/build-cache build/

.PHONY:
dbupdate:
	vagrant provision --provision-with db_update,test_db_update

build/db.php:
ifneq ("$(wildcard db-production.php)","")
	cp db-production.php build/db.php
else
	@echo "/!\\ No db-production.php found, add your own db.php in the 'build' directory /!\\"
endif
