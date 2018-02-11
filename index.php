<?php

namespace WEEEOpen\Tarallo\Server;

use FastRoute;
use WEEEOpen\Tarallo\Server\Database\Database;
use WEEEOpen\Tarallo\Server\v1\InvalidPayloadParameterException;

// in case something goes wrong (reset to 200 when sending a JSON response)
http_response_code(500);

require 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
require 'db.php';

// TODO: enable this someday
//$mediaType = (new Negotiator())->getBest(isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : '', ['application/json']);
//if($mediaType !== null) {
//	$mediaType->getValue();
//}

$method = $_SERVER['REQUEST_METHOD'];
$uri = isset($_SERVER['PATH_INFO']) ? urldecode($_SERVER['PATH_INFO']) : '';
// TODO: crash and burn if encoding is anything other than utf-8?
$contentType = isset($_SERVER['CONTENT_TYPE']) ? trim(explode(';', $_SERVER['CONTENT_TYPE'])[0]) : '';

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
		Response::sendError('Error: unknown content type: ' . $contentType, 'CONTENT', null, 415);
}

assert(isset($rawquerystring));
assert(isset($rawcontents));

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
	$payload = json_decode($rawcontents);
	if(json_last_error() !== JSON_ERROR_NONE) {
		Response::sendError('Error: malformed JSON, ' . json_last_error_msg(), 'JSON', null, 400);
	}
}

// TODO: use cachedDispatcher
$dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) {
	$r->addGroup('/v1', function(FastRoute\RouteCollector $r) {
		$r->addGroup('/items', function(FastRoute\RouteCollector $r) {
			$r->get('', 'getItem');
			$r->post('', 'createItem');

			$r->addGroup('/{id:[a-zA-Z0-9]+}', function(FastRoute\RouteCollector $r) {
				$r->get('[/token/{token}]', 'getItem');
				$r->put('', 'createItem');
				$r->delete('', 'removeItem');

				$r->get('/parent', 'getItemParent');
				$r->put('/parent', 'setItemParent');

				$r->get('/movable', 'getItemMovable');
				$r->put('/movable', 'setItemMovable');

				$r->get('/product', 'getItemProduct');
				$r->put('/product', 'setItemProduct');
				$r->delete('/product', 'deleteItemProduct');

				$r->get('/features', 'getItemFeatures');
				$r->put('/features', 'setItemFeatures');
				$r->patch('/features', 'updateItemFeatures');

				$r->get('/contents', 'getItemContents');
			});
		});

		/*
		$r->post('/search', 'createSearch');
		$r->patch('/search/{id}', 'refineSearch');
		$r->get('/search/{id}[/page/{page}]', 'getSearch');

		$r->addGroup('/products', function(FastRoute\RouteCollector $r) {
			$r->get('', 'getProduct');
			$r->get('/{brand}[/{model}[/{variant}]]', 'getProduct');

			$r->post('/{brand}/{model}', 'createProduct');
			$r->put('/{brand}/{model}/{variant}', 'createProduct');

			$r->addGroup('/{brand}/{model}/{variant}', function(FastRoute\RouteCollector $r) {
				$r->get('/features', 'getProductFeatures');
				$r->post('/features', 'setProductFeatures');
				$r->patch('/features', 'updateProductFeatures');
			});
		});

		$r->get('/logs[/page/{page}]', 'getLogs');
		*/

		$r->get('/session', 'sessionWhoami');
		$r->post('/session', 'sessionStart');
		$r->delete('/session', 'sessionClose');
		$r->head('/session', 'sessionRefresh');
	});
});

$route = $dispatcher->dispatch($method, $uri);

if($route[0] === FastRoute\Dispatcher::NOT_FOUND) {
	http_response_code(404);
	exit();
}

if($route[0] === FastRoute\Dispatcher::METHOD_NOT_ALLOWED) {
	http_response_code(405);
	header('Allow: ' . implode(', ', $route[1]));
	exit();
}

if($route[0] !== FastRoute\Dispatcher::FOUND) {
	Response::SendError('Server error: unhandled router result');
}

$callback = [v1\Adapter::class, $route[1]];
$parameters = $route[2];
unset($route);

if(!is_callable($callback)) {
	Response::SendError('Server error: cannot call "' . implode('::', $callback) . '"');
}

try {
	$db = new Database(DB_USERNAME, DB_PASSWORD, DB_DSN);
	$user = Session::restore($db);
} catch(\Exception $e) {
	Response::sendError('Server error: ' . $e->getMessage());
	assert(isset($user)); // pointless, sendError exit()s, this just stops the IDE from throwing warnings at me
}

try {
	call_user_func($callback, $user, $db, $parameters, $querystring, $payload);
} catch(AuthorizationException $e) {
	Response::sendError('Not authorized (insufficient permission)', 'AUTH403', null, 403);
} catch(AuthenticationException $e) {
	// 401 requires a WWW authentication challenge in the response, so use 403 again
	Response::sendError('Not authenticated or session expired', 'AUTH401', ['notes' => 'Try POSTing to /session'], 403);
} catch(InvalidPayloadParameterException $e) {
	Response::sendFail($e->getParameter(), $e->getReason());
} catch(DatabaseException $e) {
	Response::sendError('Database error: ' . $e->getMessage());
} catch(\Exception $e) {
	Response::sendError('Unhandled exception :(', null, ['message' => $e->getMessage(), 'code' => $e->getCode()]);
}

http_response_code(200);
