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

if($_SERVER['REQUEST_METHOD'] === 'GET') {
	try {
		$query = (new Query\GetQuery())->fromString($_REQUEST['path']);
	} catch(\Exception $e) {
		// TODO: better error messages
		Response::sendFail('Error: ' . $e->getMessage());
	}
} else if($_SERVER['REQUEST_METHOD'] === 'POST') {
	try {
		$query = (new Query\PostQuery())->fromString($_REQUEST['path'], file_get_contents('php://input'));
		// TODO: if Login, start session
	} catch(\Exception $e) {
		// TODO: better error messages
		Response::sendFail('Error: ' . $e->getMessage());
	}
} else {
	Response::sendFail('Unsupported HTTP method: ' . $_SERVER['REQUEST_METHOD']);
}

assert(isset($query));

$db = new Database();
$user = Session::restore($db);

if($query instanceof Query\PostQuery) {
	// not really sold on this design, too much complexity hidden into a single function. But these objects ARE queries,
	// it doesn't make much sense to extract everything and "parse" it again to convert it to a SQL query somewhere else...
	$data = $query->run($user, $db);
	Response::sendSuccess((array) $data);
} else {
	Response::sendSuccess([(string) $query]);
}
