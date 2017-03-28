<?php

namespace WEEEOpen\Tarallo;
use WEEEOpen\Tarallo\Query;

// in case something goes wrong (reset to 200 when sending a JSON response)
http_response_code(500);

require 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
require 'db.php';

if(!isset($_REQUEST['path']) || $_REQUEST['path'] === null) {
	Response::sendFail('No query string');
}

try {
	$query = Query\AbstractQuery::factory();
} catch(InvalidParameterException $e) {
	Response::sendFail($e->getMessage());
} catch(\Exception $e) {
	Response::sendError($e->getMessage());
}

assert(isset($query));

try {
	$db   = new Database();
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
	Response::sendSuccess(["query" => (string) $query, "user" => (string) $user]);
}
