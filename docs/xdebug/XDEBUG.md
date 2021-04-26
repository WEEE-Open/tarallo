# Using Xdebug with PHPStorm

## Setup
To use debug with the `docker-compose` development environment, some extra steps are required.

### Create a new configuration
If you have never added a configuration to PHPStorm, click on the `Add configuration...` button on the top-right of the main window:

![Step 1: Add a new configuration (no previous configurations)](docs/xdebug-docker/images/1.png)

If you have already added a configuration, click on the configurations drop-down and click on `Edit configurations...`.

![Step 1: Add a new configuration (with previous configurations)](docs/xdebug-docker/images/1b.png)

### Add a new PHP Web Page
In the window that opens, click on the `+` button at the top-left and then select `PHP Web Page` from the drop-down:

![Step 2: Add a new PHP Web Page configuration](docs/xdebug-docker/images/2.png)

### Configure the web page
In the right pane, choose a name for the configuration (the name is not important), then click on the `...` to the right of the `Server` field under the `Configuration` section:

![Step 3: Configure the Web Page in PHPStorm](docs/xdebug-docker/images/3.png)

### Create a new server configuration
In the window that opens, click on the `+` button at the top-left of the page:

![Step 4.a: Create a new configuration](docs/xdebug-docker/images/4.png)

Then on the right pane, give a name to the server (the name is no important) and configure the host as follows:
- Host: `127.0.0.1`
- Port: `8080`
- Debugger: `Xdebug`

![Step 4.b: Configure the server host](docs/xdebug-docker/images/5.png)

Then tick the `Use path mappings` box and add the following mappings:
- bin: `/var/www/html/bin`
- public: `/var/www/html/public`
- src: `/var/www/html/src`
- tests: `/var/www/html/tests`

![Step 4.c: Add path mappings](docs/xdebug-docker/images/6.png)

Mappings can be added by finding the correct folder in the folder tree and double-clicking on the right half of the bottom pane next to the entry for the desired folder:

![Step 4.d: Mappings have been added](docs/xdebug-docker/images/7.png)


### Finish configuring the Web Page
Click OK in the server configuration button. back to the Web Page configuration, select the browser you want to use to develop under the `Browser` dropdown in the Configuration section and then click OK:

![Step 5: Select the browser of choice and close the window](docs/xdebug-docker/images/8.png)

## Testing the setup

### Listen for incoming Xdebug connections
> If you haven't done so yet, start the development environment with `make up` (or `docker-compose up -d`) prior to executing this step.

In the top-right corner of the window, click on `Start Listening for PHP Debug Connections`:

![Step 6: Listen for Xdebug connections](docs/xdebug-docker/images/9.png)

### Place a test breakpoint
Put a breakpoint in a place you are sure it's going to hit (`public/index.php` is a perfect candidate) and put a breakpoint on a random line of code:

![Step 7: Place a breakpoint](docs/xdebug-docker/images/10.png)

### Start a debug session
Press on the `Debug` button:

![Step 8: Start a debug session](docs/xdebug-docker/images/11.png)

### Hope for the best
If everything went according to plan, a browser tab should open and the breakpoint trigger, presenting you with the debug view:

![Step 9: Fingers crossed...](docs/xdebug-docker/images/12.png)