<?php

namespace WEEEOpen\Tarallo\SSRv1;

use FastRoute;


class Adapter {
	/**
	 * @param string $method HTTP method (GET, POST, ...)
	 * @param string $uri URI, e.g. /items/PC42
	 * @param string[]|null $querystring Parsed query string (?foo=bar is ["foo" => "bar"]), null if none
	 * @param mixed|null $payload Request contents (decoded JSON), null if none
	 *
	 */
	public static function go($method, $uri, $querystring, $payload) {
		// TODO: use cachedDispatcher
		$dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) {
			// TODO: implement
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

		}

		// TODO: call APIv1/Adapter
	}
}