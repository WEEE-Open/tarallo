<?php

namespace WEEEOpen\Tarallo;

use WEEEOpen\Tarallo\Server\Database\Database;

if (php_sapi_name() !== 'cli') {
	http_response_code(403);
	header('Content-Type', 'text/plain');
	echo 'Available only in PHP CLI';
	return;
}

require 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
require 'db.php';

try {
	$db = new Database(DB_USERNAME, DB_PASSWORD, DB_DSN);
	$updater = $db->updater();
	$updater->updateTo(1, 1);
} catch(\Exception $e) {
	echo get_class($e);
	echo PHP_EOL;
	echo $e->getMessage();
	echo PHP_EOL;
	echo $e->getTraceAsString();
	exit(1);
}

echo 'Update completed';
echo PHP_EOL;
exit(0);
