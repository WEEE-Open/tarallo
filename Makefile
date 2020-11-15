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
dbupdate:
	vagrant provision --provision-with db_update,test_db_update

.PHONY:
examples:
	vagrant provision --provision-with example_data
