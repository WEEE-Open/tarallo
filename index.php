<?php

namespace WEEEOpen\Tarallo;

// in case something goes wrong (reset to 200 when sending a JSON response)
http_response_code(500);

require 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

if(!isset($_GET['path']) || $_GET['path'] === null) {
	Response::sendFail('No query string');
}

//var_dump($_GET['path']);
