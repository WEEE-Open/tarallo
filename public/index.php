<?php

namespace WEEEOpen\Tarallo;

use WEEEOpen\Tarallo\APIv2\ErrorResponse;
use WEEEOpen\Tarallo\SSRv1;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;

// This is the entry point for the entire server.

// in case something goes wrong (should get changed when sending a response)
http_response_code(500);

require '..' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
require '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';

$request = ServerRequestFactory::fromGlobals();
$area = substr($request->getUri()->getPath(), 0, 4);

if($area === '/v2/') {
	$response = (new APIv2\Controller())->handle($request);
} elseif($area === '/v1/') {
	$response = new JsonResponse(ErrorResponse::fromMessage('The v1 API is gone. Use v2.'), 410);
} else {
	$response = (new SSRv1\Controller())->handle($request);
}

(new SapiEmitter())->emit($response);
