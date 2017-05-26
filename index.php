<?php

namespace WEEEOpen\Tarallo;
use WEEEOpen\Tarallo\Query;
use WEEEOpen\Tarallo\Database\Database;

// in case something goes wrong (reset to 200 when sending a JSON response)
http_response_code(500);

// TODO: remove this. Maybe. Maybe not.
header("Access-Control-Allow-Origin: http://127.0.0.1:8080");
header("Access-Control-Allow-Credentials: true");
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// straight from the manual: https://secure.php.net/manual/en/class.errorexception.php
//set_error_handler(function($severity, $message, $file, $line) {
//	if (!(error_reporting() & $severity)) {
//		// This error code is not included in error_reporting
//		return;
//	}
//	throw new \ErrorException($message, 0, $severity, $file, $line);
//});

require 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
require 'db.php';

if(!isset($_REQUEST['path']) || $_REQUEST['path'] === null) {
	Response::sendFail('No query string');
}

if($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {

}

try {
	$query = Query\AbstractQuery::factory($_SERVER['REQUEST_METHOD'], $_REQUEST['path'], file_get_contents('php://input'));
} catch(InvalidParameterException $e) {
	Response::sendFail($e->getMessage());
} catch(\Exception $e) {
	Response::sendError($e->getMessage());
}

assert(isset($query));

try {
	$db   = new Database(DB_USERNAME, DB_PASSWORD, DB_DSN);
	$user = Session::restore($db);
} catch(\Exception $e) {
	Response::sendError('Error: ' . $e->getMessage());
	assert(isset($user)); // pointless, sendError exit()s, this just stops the IDE from throwing warnings at me
}

// WHY IS PHPSTORM COMPLAINING THAT $query MAY BE UNDEFINED?
// THERE'S AN ASSERTION 10 LINES ABOVE HERE!
// and asserting it's not null doesn't solve anything.
if($query instanceof Query\AbstractQuery) {
	// not really sold on this design, too much complexity hidden into a single function. But these objects ARE queries,
	// it doesn't make much sense to extract everything and "parse" it again to convert it to a SQL query somewhere else...
	try {
		$data = $query->run($user, $db);
		Response::sendSuccess((array) $data);
	} catch(\Exception $e) {
		Response::sendError('Error: ' . $e->getMessage());
	}
} else {
	Response::sendError("Failed to parse query (error that should never happen)");
}
