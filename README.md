# T.A.R.A.L.L.O.
Trabiccolo Amministrazione Rottami e Assistenza, Legalmente-noto-come L'inventario Opportuno

Server in PHP e (My)SQL (anche se forse un NoSQL sarebbe stato più adatto) del programma che usiamo per gestire l'inventario dei vecchi computer accatastati in laboratorio, nonché di quelli donati ad associazioni e scuole a cui facciamo assistenza.

## Installation

### Developement

Install [Vagrant](http://vagrantup.com/) and [Ansible](http://ansible.com/) and run:

	make vm

this will install a Vagrant plugin and Ansible roles.

**If you're running Windows** you won't be able to run Ansible, see the "Developing on Windows" section below for instructions.
    
If you want to do that manually check the `Makefile` to see what's needed. The vagrant-vbguest plugin will be *installed system-wide*, as there's no way to install it locally for a single project.

Then do:
	
	vagrant up

and you will get:

* T.A.R.A.L.L.O. instance accessible at `127.0.0.1:8080`
* An example user (`dev.user`), that's generated on the fly for the development version and logs in automatically (you can't test the SSO component, sorry)
* APIs at `127.0.0.1:8080/v2/` (also used internally by the HTML interface)
* Xdebug enabled by default
* Xdebug profiler enabled, start it via trigger (`?XDEBUG_PROFILE=1` or browser extensions),
saves output to `xdebug` directory
* Adminer at `127.0.0.1:8081/adminer.php` for database inspection (user: root,
password: root, server: localhost:3306)
* Database accessible externally by root at `127.0.0.1:3307` (note the non-standard port), connect PHPStorm to it!
* phpinfo at `127.0.0.1:8081/phpinfo.php`

Ports may be changed from 8080/8081 to anything else by Vagrant, so pay attention to its output, especially at the beginning.

There are two databases: `tarallo`, which is the one used by the interface and the APIs, and `tarallo_test`, which is populated and used only when running PHPUnit tests.

If you're upgrading from a previous version, run `vagrant provision --provision-with db_update,test_db_update` (or `make dbupdate` which does the same thing) to run the schema update script, both on the development and test database.  
If the VM is not running, just start it, no other commands required: the update script runs automatically on each boot.

The `vagrant-vbguest` plugin is required because the default CentOS 7 image lacks VirtualBox additions, needed for folder sharing. CentOS was chosen since that's what we're using on the production server.

#### Developing on Windows

You'll need to install Vagrant and the vagrant-vbguest plugin:
`vagrant plugin install vagrant-vbguest`.

Then do `vagrant up`. Vagrant should recognize that you're on Windows and launch `utils/provision/windows-host.sh` on its own: it will install Ansible and its plugins *inside* the virtual machine and provision it.

### Production

The program doesn't really need to be built, but there's a Makefile that
collects in a single directory named `build` all the files you'll need to
upload, installs dependencies and builds an optimized autoloader. To do that,
run:

    make

At the end you may get this warning (complete with shoddy warning signs):

    /!\ No config/config-production.php found, add your own config.php in the build/config directory /!\

Copy `config/config-example.php` to `build/config/config.php` and edit it to suit your needs.

Or you could create a `config/db-production.php` file: when running `make` again, it will be copied to `build/config/config.php`.

If this is the first deployment you'll need to import `database.sql`, `database-data.sql` and `database-procedures.sql` manually in the production database, if it is an update you may need to run the update script: use `php bin/update.php` on the server.

## More details

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
