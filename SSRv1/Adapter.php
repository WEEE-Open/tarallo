<?php

namespace WEEEOpen\Tarallo\SSRv1;

use FastRoute;
use WEEEOpen\Tarallo\Server\Database\Database;
use WEEEOpen\Tarallo\Server\HTTP\AdapterInterface;
use WEEEOpen\Tarallo\Server\HTTP\Request;
use WEEEOpen\Tarallo\Server\HTTP\Response;
use WEEEOpen\Tarallo\Server\User;


class Adapter implements AdapterInterface {
	public static function getItem(User $user = null, Database $db, $parameters, $querystring, $payload) {

	}

	public static function go(Request $request): Response {
		$method = $request->method;
		$uri = $request->path;
		$querystring = $request->querystring;

		// TODO: use cachedDispatcher
		$dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) {
			$r->get('/', 'getHome');
			$r->get('/item/{code}', 'getItem');
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
			http_response_code(500);
			header('Content-Type: text/plain; charset=utf-8');
			echo 'Error: unknown router result';
			exit(1);
		}

		$callback = [Adapter::class, $route[1]];
		$parameters = $route[2];
		unset($route);

		if(!is_callable($callback)) {
			echo 'Server error: cannot call "' . implode('::', $callback) . '" (SSR)';

			return;
		}

		try {
			$db->beginTransaction();
			$result = call_user_func($callback, $user, $db, $parameters, $querystring);
			$db->commit();
		} catch(\Throwable $e) {
			$db->rollback();
			throw $e;
		}
	}
}