<?php

namespace WEEEOpen\Tarallo;

use WEEEOpen\Tarallo\APIv1;
use WEEEOpen\Tarallo\SSRv1;
use Zend\Diactoros\ServerRequestFactory;
use Zend\HttpHandlerRunner\Emitter\SapiEmitter;

// This is the entry point for the entire server.

// in case something goes wrong (should get changed when sending a response)
http_response_code(500);

require '..' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
require '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';

$request = ServerRequestFactory::fromGlobals();

if(substr($request->getUri()->getPath(), 0, 4) === '/v1/') {
	$response = APIv1\Controller::handle($request);
} else {
	$response = (new SSRv1\Controller())->handle($request);
}

(new SapiEmitter())->emit($response);
