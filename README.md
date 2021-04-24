# T.A.R.A.L.L.O.
Tuttofare Assistente il Riuso di Aggeggi Logori e Localmente Opprimenti  
(aka L'inventario Opportuno)

An extremely granular inventory management software for computer hardware specifically, halfway between a CMDB (configuration management database) and a PIM (product information management).

## Installation

### Development (with docker-compose)
> NOTE: This method is still work in progress. Please use the [tested method](#development-with-vagrant) for development purposes

Install `docker` (usually `docker.io` in package managers) and `docker-compose` (same name).  
If you have macOS or Windows go [here](https://www.docker.com/products/docker-desktop).

- `git clone git@github.com:WEEE-Open/tarallo.git`
- `cd tarallo`
- `docker-compose up -d`
- go to http://localhost:8080 and eat some taralli 🍩 (this is the most similar emoji, don't judge me, okay?)

When you're finished: `docker-compose down`.

If you have screwed up:

```bash
# Stop the containers and network, remove permanent volumes.
$ docker-compose down
$ docker volume rm tarallo_tarallo-web

# Rebuild the images (ignoring cache) and start the containers.
$ docker-compose build --no-cache
$ docker-compose up -d
```

If you have make on your machine:
- `make build` - (re)builds the development environment
- `make rebuild` - shortcut for `make build && make up`
- `make destroy` - cleans up the development environment
- `make up` - start the development environment
- `make down` - stop the development environment
- `make dbupdate` - update the database schema (when instructed to)
- `make examples` - resets database content with example items and products

#### Features
- T.A.R.A.L.L.O. development instance accessible at [`127.0.0.1:8080`](http://127.0.0.1:8080).
- T.A.R.A.L.L.O. APIs at `127.0.0.1:8080/v2/`
- A default user (`dev.user`) generated on the fly with automatic login enabled (you can't test the SSO component, sorry).
- A default API Token: `yoLeCHmEhNNseN0BlG0s3A:ksfPYziGg7ebj0goT0Zc7pbmQEIYvZpRTIkwuscAM_k` (see the [documentation](https://github.com/WEEE-Open/tarallo/wiki/Managing-the-session-and-Authentication)).
- Xdebug enabled by default with `debug` and `profile` modes (can be disabled in [docker-compose.yml](docker-compose.yml)).
- Xdebug profiler traces are generated directly within utils/xdebug in the git tree on the host machine.
- Adminer at [`127.0.0.1:8081`](http://127.0.0.1:8081).
- PHP Info at [`127.0.0.1:8081/phpinfo.php`](http://127.0.0.1:8081/phpinfo.php)
- Database (MySQL/MariaDB) acessible externally by `root` at `127.0.0.1:3307` (note the non-standard port), connect PHPStorm to it!

#### Workflow
The directories of the git tree that contain the application's sources (`public`, `src` and `tests`) are directly mapped within the container, and changing any file in those directories will immediately reflect on the running instance inside the container.

Xdebug is supported, but requires some extra steps use: please read the relative [instructions](XDEBUG.md).

#### Configuration
Both Xdebug and its profiler can be enabled/disabled by tweaking the value of the `XDEBUG` and `PROFILER` ARGs for the `app` container inside the `docker-compose.yml` file.

#### Issues
We are working on loading some default data into the database at the moment (see [this issue](https://github.com/WEEE-Open/tarallo/issues/181)), only the interface works as of now.


### Development (with vagrant)
> NOTE: This method is about to be discontinued (when the docker-compose setup is fully functional)

Use Vagrant. You know what to do.  
This is what you get, though:

* T.A.R.A.L.L.O. instance accessible at `127.0.0.1:8080`
* An example user (`dev.user`), that's generated on the fly for the development version and logs in automatically (you can't test the SSO component, sorry)
* APIs at `127.0.0.1:8080/v2/`
* A default token for APIs: `yoLeCHmEhNNseN0BlG0s3A:ksfPYziGg7ebj0goT0Zc7pbmQEIYvZpRTIkwuscAM_k` (see [documentation](https://github.com/WEEE-Open/tarallo/wiki/Managing-the-session-and-Authentication))
* Xdebug enabled by default
* Xdebug profiler enabled, start it via trigger (`?XDEBUG_PROFILE=1` or browser extensions), saves output to `xdebug` directory
* Adminer at `127.0.0.1:8081/adminer.php` for database inspection (user: root, password: root, server: localhost:3306)
* Database (MySQL/MariaDB) accessible externally by root at `127.0.0.1:3307` (note the non-standard port), connect PHPStorm to it!
* phpinfo at `127.0.0.1:8081/phpinfo.php`

### API Development

If you need T.A.R.A.L.L.O. just for its APIs, not to develop it, here's a short list of some useful commands:

- `make build` - (re)builds the development environment
- `make rebuild` - shortcut for `make build && make up`
- `make destroy` - cleans up the development environment
- `make up` - start the development environment
- `make down` - stop the development environment
- `make dbupdate` - update the database schema (when instructed to)
- `make examples` - resets database content with example items and products

If you're on Windows and you don't have make, open the Makefile and copy the raw commands, it's 1 or 2 commands for each one of these.

### Production

Make a git clone of this repo, then:

```bash
cp config/config-example.php config/config.php
nano config/config.php # Set the actual values
bin/build-cache
composer install --no-dev --no-suggest --classmap-authoritative --optimize-autoloader
```

And configure MariaDB and nginx, too. Look at `utils/provision`, there's an Ansible playbook just for that. It's really similar to our production playbook.

If this is the first deployment you'll need to import `database.sql`, `database-data.sql` and `database-procedures.sql` manually in the production database, if it is an update you may need to run the update script: use `php bin/update.php` on the server.

## More details

### Examples

There's a set of default items and products that can be used for tests.

You get that by running `make examples`. It will reset the database, deleting any items or products that you added.

There's not much more to it.

### Database

The schema is located in `database.sql`.

`database-data.sql` contains some "static" data needed for the software to work. It can be edited, but you'll need to run `make features` afterwards.

`database-procedures.sql` contains some procedures and triggers, all of these are needed for the entire thing to work.

### Feature list generation

`generate-features /path/to/this/directory` reads the feature list from `database-data.sql`, converts it to PHP data structures and places it into `src/Database/Feature.php` and some other files (`generate-features` tells you which ones when it's finished).

The prefererred way to run this script is to use `make features` instead of calling it directly. This is a CLI script, you shouldn't upload it to a server or access it from the browser.

Modified files should be manually reviewed and committed.

### Router cache

To enable FastRoute cache, set `CACHE_ENABLED` to true in `config.php`.

Caching probably won't work in developement, so you could enable it only in `config-production.php` (which `make` copies to `build/config/config.php`).

Cache files in `build` directory are generated automatically when running `make`.
