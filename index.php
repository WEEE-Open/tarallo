<?php

namespace WEEEOpen\Tarallo;

// in case something goes wrong (reset to 200 when sending a JSON response)
http_response_code(500);

require 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

if(!isset($_GET['path']) || $_GET['path'] === null) {
	Response::sendFail('No query string');
}

try {
	$query = (new Query\Query())->fromString($_GET['path'], $_SERVER['REQUEST_METHOD']);
} catch(\Exception $e) {
	// TODO: better error messages
	Response::sendFail('Error: ' . $e->getMessage());
}

//var_dump($_GET['path']);
