<?php

namespace WEEEOpen\Tarallo;

// in case something goes wrong (reset to 200 when sending a JSON response)
http_response_code(500);

require 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

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
