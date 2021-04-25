# T.A.R.A.L.L.O.
Tuttofare Assistente il Riuso di Aggeggi Logori e Localmente Opprimenti  
(aka L'inventario Opportuno)

An extremely granular inventory management software for computer hardware specifically, halfway between a CMDB (configuration management database) and a PIM (product information management).

## Installation

### Testing

If you just want to test the Tarallo, or interact with it, but not to develop it, the default configuration is fine.

First, install `docker` (usually `docker.io` in package managers) and `docker-compose` (same name).  
If you have macOS or Windows go [here](https://www.docker.com/products/docker-desktop).

Then:

```bash
git clone git@github.com:WEEE-Open/tarallo.git
cd tarallo
make build
make up
```

Now go to http://localhost:8080 and eat some taralli 🍩 (this is the most similar emoji, don't judge me, okay?)

However this does not include xdebug, you will need the Development configuration for that.


#### Features

- T.A.R.A.L.L.O. development instance accessible at [`127.0.0.1:8080`](http://127.0.0.1:8080).
- T.A.R.A.L.L.O. APIs at `127.0.0.1:8080/v2/`
- A default user (`dev.user`) generated on the fly with automatic login enabled (you can't test the SSO component, sorry).
- A default API Token: `yoLeCHmEhNNseN0BlG0s3A:ksfPYziGg7ebj0goT0Zc7pbmQEIYvZpRTIkwuscAM_k` (see the [documentation](https://github.com/WEEE-Open/tarallo/wiki/Managing-the-session-and-Authentication)).
- Adminer at [`127.0.0.1:8081`](http://127.0.0.1:8081).
- Database (MariaDB) acessible externally by `root` at `127.0.0.1:3307` (note the non-standard port)

### Development

```bash
git clone git@github.com:WEEE-Open/tarallo.git
cd tarallo
make devbuild
make up
```

this will also install Xdebug. Follow the [instructions](XDEBUG.md) to attach PHPStorm to it.

If you want to profile the application, use `make profilerbuild` instead. Xdebug profiler traces are generated directly within utils/xdebug in the git tree on the host machine.

Also connect PHPStorm to the database on `127.0.0.1:3307` for maximum 

#### Workflow
The directories of the git tree that contain the application's sources (`public`, `src` and `tests`) are directly mapped within the container, and changing any file in those directories will immediately reflect on the running instance inside the container.

### Other commands

Aside from the build commands, these may be useful:

- `make up` - start the development environment
- `make down` - stop the development environment
- `make destroy` - cleans up the development environment
- `make dbupdate` - update the database schema (when instructed to)
- `make examples` - resets database content with example items and products

### Production

Make a git clone of this repo, then:

```bash
cp config/config-example.php config/config.php
nano config/config.php # Set the actual values
bin/build-cache
composer install --no-dev --classmap-authoritative --optimize-autoloader
```

If this is the first deployment you'll need to import `sql/database.sql`, `sql/database-data.sql` and `sql/database-procedures.sql` manually in the production database, if it is an update you may need to run the update script: use `php bin/update.php` on the server.

## More details

### Examples

There's a set of default items and products that can be used for tests.

You get that by running `make examples`. It will reset the database, deleting any items or products that you added.

There's not much more to it.

### Database

Files are in the `sql` directory. The schema is located in `database.sql`.

`database-data.sql` contains some "static" data needed for the software to work. It can be edited, but you'll need to run `make features` afterwards.

`database-procedures.sql` contains some procedures and triggers, all of these are needed for the entire thing to work.

### Feature list generation

`generate-features /path/to/this/directory` reads the feature list from `sql/database-data.sql`, converts it to PHP data structures and places it into `src/Database/Feature.php` and some other files (`generate-features` tells you which ones when it's finished).

The prefererred way to run this script is to use `make features` instead of calling it directly. This is a CLI script, you shouldn't upload it to a server or access it from the browser.

Modified files should be manually reviewed and committed.

### Router cache

To enable FastRoute cache, set `CACHE_ENABLED` to true in `config.php`.

Caching probably won't work in developement, so you could enable it only in `config-production.php` (which `make` copies to `build/config/config.php`).

Cache files in `build` directory are generated automatically when running `make`.
