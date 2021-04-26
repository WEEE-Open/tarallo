# Running tests with PHPStorm

## Setup
To run tests with the `docker-compose` development environment, some extra steps are required.

## Create a new configuration from the `Run/Debug configurations menu`
![alt](images/1.png)

## Add a new PHPUnit configuration

In the window that opens, click on the `+` in the top-left corner and click on `PHPUnit` in the dropdown.

![alt](images/2.png)

## Setup PHPUnit with `docker-compose`

Choose a name for your configuration, choose `Defined in the configuration file` as `Test scope` under `Test Runner`, then click on the gear to the right of the first text field as indicated in the picture.

> **NOTE:** Do **not** select the `Use alternative configuration file` box.

![alt](images/3.png)

## Create a CLI interpreter

In the window that opens click on the `+` in the top-left corner and click on `PHPUnit by Remote Interpreter`.

![alt](images/4.png)

In the window that opens click on the `...` to the right of the drop-down menu.

![alt](images/5.png)

In the window that opens click on the `+` in  the top-left corner and click on `From Docker, Vagrant, VM, WSL, Remote...`.

![alt](images/6.png)

In the window that opens select `Docker Compose` as a runner and click on `New...` to the right of the server field.

![alt](images/7.png)

Select a name for the test server. If your docker daemon is running (it should!) PHPStorm should be able to detect it automatically, otherwise you have to configure it yourself. How to connect to the docker daemon varies from OS and installation. In this case, [Duck Duck Go is your friend](https://duckduckgo.com/).

When you see "Connection successful" in the bottom half of the window, the connection is successfull and you can click OK.

![alt](images/8.png)

Make sure the value of the `Configuration files` is set to `./docker-compose.yml` (it should by default) and select `app` from the `Service` drop-down menu.

![alt](images/9.png)

Under `Lifecycle` select `Connect to existing container`. Under `General` make sure that the system correctly identifies that PHPStorm detects correctly PHP and XDebug installations in the container and click OK.

![alt](images/10.png)

Select the interpreter you just created in the previous step from the drop-down and click OK.

![alt](images/11.png)

Click on the folder icon inside the `Path mappings` field, to the right.

![alt](images/12.png)

Make sure that the path mappings have been correctly identified and added. If not, add them (using the `+` button in the top-left) or edit them to match the ones shown below and then press OK.

![alt](images/13.png)

Inside the `PHPUnit library` section select `Use Composer autoloader` and write `/var/www/html/vendor/autoloader.php` in the `Path to script` field, then click the refresh button to the right of that same field and make sure that PHPStorm correctly identifies the container's PHPUnit installation.

![alt](images/14.png)

Under `Test Runner` tick the `Default configuration file` box and write `phpunit.xml` in the text field to the right and press OK.

![alt](images/15.png)

Under the `Command Line` section select the newly created interpreter under the `Interpreter` drop-down.

![alt](images/16.png)

## Run tests

Make sure to select the test configuration from the `Run/Debug configurations` drop-down.

You can use these three buttons to run your tests. In order from left to right:

- `Run tests`: run the tests normally and report on their results
- `Run tests in debug mode`: like the above, but also enable debugging during the run
- `Coverage`: run coverage tests on top of the tests

![alt](images/17.png)