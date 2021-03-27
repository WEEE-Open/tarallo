#!/usr/bin/php
<?php

namespace WEEEOpen\Tarallo;

use WEEEOpen\Tarallo\Database\Database;

if (php_sapi_name() !== 'cli') {
	http_response_code(403);
	header('Content-Type', 'text/plain');
	echo 'Available only in PHP CLI';
	return;
}

require __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
require __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';

$database = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'database.sql');
//$databasedata = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'database-data.sql');

preg_match("#\(.SchemaVersion., (\d+)\)#", $database, $matches);
$schema = (int) $matches[1];

preg_match("#\(.DataVersion., (\d+)\)#", $database, $matches);
$data = (int) $matches[1];

echo "Last versions found in sql files: schema $schema, data $data";
echo PHP_EOL;

try {
	$db = new Database(TARALLO_DB_USERNAME, TARALLO_DB_PASSWORD, TARALLO_DB_DSN);
	$updater = $db->updater();
	$updater->updateTo($schema, $data);
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
