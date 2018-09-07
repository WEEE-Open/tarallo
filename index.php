<?php

namespace WEEEOpen\Tarallo;

use Slim\Http\FactoryDefault;
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

// Code from this point onwards
// partially taken from https://github.com/http-interop/response-sender/
// Copyright (c) 2017 Woody Gilk, released under MIT license

$http_line = sprintf('HTTP/%s %s %s',
	$response->getProtocolVersion(),
	$response->getStatusCode(),
	$response->getReasonPhrase()
);

header($http_line, true, $response->getStatusCode());

foreach ($response->getHeaders() as $name => $values) {
	foreach ($values as $value) {
		header("$name: $value", false);
	}
}

$stream = $response->getBody();
if($stream !== null) {
	if($stream->isSeekable()) {
		$stream->rewind();
	}

	while(!$stream->eof()) {
		echo $stream->read(1024 * 8);
	}
}
