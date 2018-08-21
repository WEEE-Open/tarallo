# T.A.R.A.L.L.O.
Trabiccolo Amministrazione Rottami e Assistenza, Legalmente-noto-come L'inventario Opportuno

Server in PHP e (My)SQL (anche se forse un NoSQL sarebbe stato più adatto) del programma che usiamo per gestire l'inventario dei vecchi computer accatastati in laboratorio, nonché di quelli donati ad associazioni e scuole a cui facciamo assistenza.

## Installation

### Developement

Install Vagrant and Ansible (it's required to provision the VM), then run:

	make vm

Note that this will install a Vagrant plugin and Ansible roles outside of the project directory. If you want to do that manually check the `Makefile` to see what's needed, then you can just use `vagrant up` instead of `make vm`.

With the VM you will get:

* T.A.R.A.L.L.O. instance accessible at `127.0.0.1:8080`
* Some sample data, which right now is only 4 users and that's it
(user: `asd`, password: `asd`, all users have password `asd`)
* APIs at `127.0.0.1:8080/v1/` (also used internally by the HTML interface)
* Xdebug enabled by default
* Adminer at `127.0.0.1:8081/adminer.php` for database inspection (user: root, 
password: root, server: localhost:3306)
* Database accessible externally by root at `127.0.0.1:3307` (note the non-standard port)

If port gets changed from 8080 to anything else by Vagrant, no manual adjustments should be necessary but it hasn't been tested.

There are two databases: `tarallo`, which is the one used by the interface and the APIs, and `tarallo_test`, which is populated and used only when running PHPUnit tests.

The `vagrant-vbguest` plugin is required because the default CentOS 7 image lacks
VirtualBox additions, required for folder sharing. CentOS was chosen since that's
what we're using on the production server.

### Production

The program doesn't really need to be built, but there's a Makefile that collects in a single directory named `build` all the files you'll need to upload, installs dependencies and builds an optimized autoloader. To do that, run:

    make

At the end you may get this warning (complete with shoddy warning signs):

    /!\ No db-production.php found, add your own db.php in the 'build' directory /!\

Copy `db-example.php` to `build/db.php` and edit it to suit your needs.

Or you could name it `db-production.php` and place it in the main directory: when running `make` again, it will be copied to `build/db.php`.

Also remember to import `database.sql`, `database-data.sql` and `database-procesured.sql` in the production database, if needed.

## More details

### Database

The schema is located in `database.sql`.

`database-data.sql` contains some "static" data needed for the software to work. They can actually be edited, but you'll need to run `make features` or the `utils/generate-features` script afterwards.

`database-procedures.sql` contains some procedures and triggers, all of these are needed for the entire thing to work.

### Feature list generation

`generate-features /path/to/this/directory` reads the feature list from `database-data.sql`,
converts it to PHP data structures and places it into `src/Database/Feature.php`
and some other files (`generate-features` tells you which ones when it's finished).

Modified files should be manually reviewed and committed.

The prefererred way to run this script is to use `make features` instead of calling it directly.

### Gettext

There are a few references to that through the source code, but it's **currently unused**.

In theory, to generate .po and .mo these commands should be enough, but **there are no strings to translate yet** so they won't do anything useful:

    xgettext --from-code=UTF-8 -o SSRv1/locale/it_IT/LC_MESSAGES/tarallo.pot **/*.php
    msgfmt tarallo.pot -o tarallo.mo
