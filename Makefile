default: production

.PHONY:
production: clean copies compose build/db.php

.PHONY:
vm: SSR-router-cache
	composer install
	ansible-galaxy install goozbach.EPEL
	ansible-galaxy install geerlingguy.nginx
	vagrant plugin install vagrant-vbguest

# Maybe this should be an actual file target, maybe not
.PHONY:
features:
	utils/generate-features "$(CURDIR)"

.PHONY:
clean:
	mkdir -p build
	rm -rf build/*

.PHONY:
copies: SSR-router-cache
	cp "index.php" build/
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

# Cannot be non-phony, or it will never rebuild the cache...
.PHONY:
SSR-router-cache:
	test ! -f "SSRv1/router.cache" || rm SSRv1/router.cache
	php utils/build-cache SSRv1

build/db.php:
ifneq ("$(wildcard db-production.php)","")
	cp db-production.php build/db.php
else
	@echo "/!\\ No db-production.php found, add your own db.php in the 'build' directory /!\\"
endif
