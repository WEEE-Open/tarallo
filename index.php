<?php

namespace WEEEOpen\Tarallo;

use Slim\Http\FactoryDefault;
use Slim\Http\Response;
use WEEEOpen\Tarallo\APIv1;
use WEEEOpen\Tarallo\SSRv1;

// This is the entry point for the entire server.

// in case something goes wrong (should get changed when sending a response)
http_response_code(500);

require 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
require 'db.php';

$request = (new FactoryDefault)->makeRequest($_SERVER);

if(substr($request->getUri()->getPath(), 0, 5) === '/v1/') {
	$response = APIv1\Controller::handle($request);
} else {
	$response = SSRv1\Controller::handle($request);
}

// TODO: send response (in a better way, I mean)
var_dump($response);
