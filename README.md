# T.A.R.A.L.L.O.
Trabiccolo Amministrazione Rottami e Assistenza, Legalmente-noto-come L'inventario Opportuno

Server in PHP e (My)SQL (anche se forse un NoSQL sarebbe stato più adatto) del programma che usiamo per gestire l'inventario dei vecchi computer accatastati in laboratorio, nonché di quelli donati ad associazioni e scuole a cui facciamo assistenza.

## Installation

### Developement

Install [Vagrant](http://vagrantup.com/) and [Ansible](http://ansible.com/) and run:

	make vm

this will install the required Ansible roles.

**If you're running Windows** you won't be able to run Ansible or `make`, see the "Developing on Windows" section below for instructions.
    
If you want to do that manually check the `Makefile` to see what's needed.

Then do:
	
	vagrant up

and you will get:

* T.A.R.A.L.L.O. instance accessible at `127.0.0.1:8080`
* An example user (`dev.user`), that's generated on the fly for the development version and logs in automatically (you can't test the SSO component, sorry)
* APIs at `127.0.0.1:8080/v2/` (also used internally by the HTML interface)
* Xdebug enabled by default
* Xdebug profiler enabled, start it via trigger (`?XDEBUG_PROFILE=1` or browser extensions), saves output to `xdebug` directory
* Adminer at `127.0.0.1:8081/adminer.php` for database inspection (user: root, password: root, server: localhost:3306)
* Database (MySQL/MariaDB) accessible externally by root at `127.0.0.1:3307` (note the non-standard port), connect PHPStorm to it!
* phpinfo at `127.0.0.1:8081/phpinfo.php`

Ports may be changed from 8080/8081 to anything else by Vagrant, so pay attention to its output, especially at the beginning.

There are two databases: `tarallo`, which is the one used by the interface and the APIs, and `tarallo_test`, which is populated and used only when running PHPUnit tests.

If you're upgrading from a previous version, run `vagrant provision --provision-with db_update,test_db_update` (or `make dbupdate` which does the same thing) to run the schema update script, both on the development and test database.  
If the VM is not running, just start it, no other commands required: the update script runs automatically on each boot.

To quickly reset the database content (the example items and products), use `make examples` - or `vagrant provision --provision-with example_data` on Windows. This will delete all items and products and insert the example ones again.

#### Useful commands

If you need T.A.R.A.L.L.O. just for its APIs, not to develop it, here's a short list of some useful commands:

* `vagrant halt` shuts down the VM
* `vagrant up` starts it again
* `vagrant destroy` deletes the VM (when you're instructed to or you want to save space), `vagrant up` will create it again from scratch
* `vagrant provision` runs all provisioners again (when instructed to)
* `make dbupdate` or `vagrant provision --provision-with db_update,test_db_update` updates the database schema (when instructed to)
* `make examples` or `vagrant provision --provision-with example_data` resets database content with example items and products

Getting everything up and running the first time will require some time (we're still setting up an entire CentOS VM with a LEMP stack, albeit in a fully automated manner), but every effort has been made to favor **incremental upgrades**: after a `git pull` you will sometimes need to run `make dbupdate` or, rarely, `vagrant provision`, as instructed for every specific version, but you don't need to delete the VM and re-install everything.  
Even if provisioning fails, it can be restarted with `vagrant provision` usually and it will pick up from where it left.

#### Developing on Windows

Vagrant should recognize that you're on Windows and launch `utils/provision/windows-host.sh` on its own: it will install Ansible and its plugins *inside* the virtual machine and provision it.

During `vagrant up` setup process, system will ask you for user and password. These are your Windows account credentials and they're used by Vagrant to create the SMB shared folder.

If you log in Windows with your Microsoft account, you have to insert your local user (_type `echo %username%` in your windows terminal_)  and your Microsoft account password. If you use a local user, just type your local credentials.

On Windows only, the last task in the Ansible playbook probably will fail:

```
default: TASK [Create configuration file] *******************************************
    default: fatal: [localhost]: FAILED! => {"changed": false, "checksum": "979c69d8f640b15717b866b2b03cd1c6f25ac9df", "cur_context": ["system_u", "object_r", "cifs_t", "s0"], "gid": 1000, "group": "vagrant", "
input_was": ["system_u", "object_r", "httpd_sys_content_t", "s0"], "mode": "0755", "msg": "invalid selinux context: [Errno 95] Operation not supported", "new_context": ["system_u", "object_r", "httpd_sys_conten
t_t", "s0"], "owner": "vagrant", "path": "/var/www/html/server/config/.ansible_tmpiyaWwdconfig.php", "secontext": "system_u:object_r:cifs_t:s0", "size": 813, "state": "file", "uid": 1000}
    default:
    default: RUNNING HANDLER [geerlingguy.nginx : reload nginx] *************************
```

the text may be different, but the point is that the task named `Create configuration file` fails.

This happens because of the combination of VirtualBox, SMB and Ansible trying to set SELinux permissions to the config file, despite being istructed *not* to do so in the `ansible.cfg` file. Which Ansible reads and parses correctly. And then ignores the instructions contained within.

Anyway, there is a manual workaround which needs to be done *just once*:

1. Go to the `config` directory on your machine (host)
2. Create `config.php`
3. Add this exact content:

```php
<?php
// Ansible managed

define('TARALLO_DB_USERNAME', 'tarallo');
define('TARALLO_DB_PASSWORD', 'thisisnottheproductionpassword');
define('TARALLO_DB_DSN', 'mysql:dbname=tarallo;host=localhost;charset=utf8mb4');
define('TARALLO_CACHE_ENABLED', false); // Set to true to enable FastRoute cache (use in production only, leave false in developement)
define('TARALLO_DEVELOPMENT_ENVIRONMENT', true); // Set to false or delete in production
define('TARALLO_POST_GRACE_TIME', 1800);
define('TARALLO_OIDC_ISSUER', 'https://sso.example.com/auth/realms/master');
define('TARALLO_OIDC_CLIENT_KEY', 'tarallo');
define('TARALLO_OIDC_CLIENT_SECRET', '');
define('TARALLO_OIDC_REFRESH_TOKEN_EXPIRY', 60 * 60 * 24);
define('TARALLO_OIDC_READ_ONLY_GROUPS', ['TaralloReadOnly']);
define('TARALLO_OIDC_ADMIN_GROUPS', ['Admin']);
```

Now run `vagrant provision` again: it should notice that the file exists and is correct and just go on with other provisioners.

### Production

The program doesn't really need to be built, but there's a Makefile that collects in a single directory named `build` all the files you'll need to upload, installs dependencies and builds an optimized autoloader. To do that, run:

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
