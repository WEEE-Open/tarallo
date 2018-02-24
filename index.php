<?php

namespace WEEEOpen\Tarallo;

use WEEEOpen\Tarallo\APIv1;
use WEEEOpen\Tarallo\SSRv1;
use WEEEOpen\Tarallo\Server\HTTP\Request;

// This is the entry point for the entire server.
// It normalizes HTTP requests (e.g. gets and sanitizes PATH_INFO, query string and the like),
// connects to database, gets current user from session if available, and then calls an Adapter
// (which does routing and response generation). It's the MVA pattern, almost?

// in case something goes wrong (gets changed when sending a response, usually)
http_response_code(500);

require 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
require 'db.php';

$request = Request::ofGlobals();

if(strpos($request->path, '/v1/') === 0) {
	// Some advanced URL rewriting techniques, right here.
	// This could be called a metarouter, or hyperrouter, or something like that.
	$request->path = substr($request->path, 3);
	APIv1\Adapter::go($request);
} else {
	SSRv1\Adapter::go($request);
}
