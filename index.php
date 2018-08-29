<?php

namespace WEEEOpen\Tarallo;

use Relay\RelayBuilder;
use Slim\Http\FactoryDefault;
use Slim\Http\Response;
use WEEEOpen\Tarallo\APIv1;
use WEEEOpen\Tarallo\Server\HTTP\LanguageNegotiatior;
use WEEEOpen\Tarallo\SSRv1;

// This is the entry point for the entire server.

// in case something goes wrong (should get changed when sending a response)
http_response_code(500);

require 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
require 'db.php';

$request = (new FactoryDefault)->makeRequest($_SERVER);
$response = new Response();
$relayBuilder = new RelayBuilder();

if(substr($request->getUri()->getPath(), 0, 5) === '/v1/') {
	$queue = [new APIv1\Controller];
} else {
	$queue = [new LanguageNegotiatior, new SSRv1\Controller];
}

$relay = $relayBuilder->newInstance($queue);
$response = $relay($request, $response);

// TODO: send response (in a better way, I mean)
var_dump($response);
