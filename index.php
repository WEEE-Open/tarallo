<?php

namespace WEEEOpen\Tarallo;

use WEEEOpen\Tarallo\APIv1;

// in case something goes wrong (gets changed when sending a response, usually)
http_response_code(500);

require 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
require 'db.php';

if(isset($_SERVER['PATH_INFO'])) {
	$uri = urldecode($_SERVER['PATH_INFO']);
} else if(!isset($_SERVER['REQUEST_URI'])) {
	$uri = '';
} else {
	header('Content-Type: text/plain');
	echo 'No PATH_INFO';
	exit(2);
}

if(strpos($uri, '/v1/') === 0) {
	$uri = substr($uri, 3);
	$api = APIv1\Adapter::class;
} else {
	$api = APIv1\Adapter::class;
}

$method = $_SERVER['REQUEST_METHOD'];
// TODO: crash and burn if encoding is anything other than utf-8?
$contentType = isset($_SERVER['CONTENT_TYPE']) ? trim(explode(';', $_SERVER['CONTENT_TYPE'])[0]) : '';

// TODO: enable this someday
//$mediaType = (new Negotiator())->getBest(isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : '', ['application/json']);
//if($mediaType !== null) {
//	$mediaType->getValue();
//}

switch($contentType) {
	case '': // GET request
		$rawquerystring = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : null;
		$rawcontents = null;
		break;
	case 'text/plain':
	case 'application/json':
	case '*/*':
		$rawquerystring = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : null;
		$rawcontents = file_get_contents("php://input");
		break;
	case 'application/x-www-form-urlencoded':
		$rawquerystring = file_get_contents("php://input");
		$rawcontents = null;
		break;
	default:
		http_response_code(415);
		header('Content-Type: text/plain');
		echo 'Error: unknown content type: ' . $contentType;
		exit();
}

if($rawquerystring === null) {
	$querystring = null;
} else {
	parse_str($_SERVER['QUERY_STRING'], $querystring);
	if(empty($querystring)) {
		$querystring = null;
	}
}

if(trim($rawcontents) === '') {
	$payload = null;
} else {
	$payload = json_decode($rawcontents, true);
	if(json_last_error() !== JSON_ERROR_NONE) {
		http_response_code(400);
		header('Content-Type: text/plain');
		echo 'Error: malformed JSON, ' . json_last_error_msg();
		exit();
	}
}

if($api === APIv1\Adapter::class) {
	$response = APIv1\Adapter::go($method, $uri, $querystring, $payload);
	$response->send();
	return;
} else {
	SSRv1\Adapter::go($method, $uri, $querystring, $payload);
}
