<?php

namespace WEEEOpen\Tarallo\SSRv1;

use FastRoute;
use League\Plates\Engine;
use League\Plates\Extension\URI;
use WEEEOpen\Tarallo\Server\Database\Database;
use WEEEOpen\Tarallo\Server\HTTP\AdapterInterface;
use WEEEOpen\Tarallo\Server\HTTP\Request;
use WEEEOpen\Tarallo\Server\HTTP\Response;
use WEEEOpen\Tarallo\Server\Session;
use WEEEOpen\Tarallo\Server\User;


class Adapter implements AdapterInterface {
	public static function getItem(User $user = null, Database $db, Engine $engine, $parameters, $querystring): string {
		$template = $engine->make('viewItem');
		return $template->render(['code' => 'ASD']);
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
			return new Response(500, 'text/plain; charset=utf-8', 'SSR Error: unknown router result');
		}

		$callback = [Adapter::class, $route[1]];
		$parameters = $route[2];
		unset($route);

		if(!is_callable($callback)) {
			echo 'Server error: cannot call "' . implode('::', $callback) . '" (SSR)';
		}

		try {
			$db = new Database(DB_USERNAME, DB_PASSWORD, DB_DSN);
			$db->beginTransaction();
			$user = Session::restore($db);
			$db->commit();
		} catch(\Exception $e) {
			if(isset($db)) {
				$db->rollback();
			}
			return new Response(500, 'text/plain; charset=utf-8', 'Server error: ' . $e->getMessage());
		}

		$templates = new Engine(__DIR__ . DIRECTORY_SEPARATOR . 'templates', 'tpl');
		$templates->loadExtension(new URI($request->path));

		try {
			$db->beginTransaction();
			$result = call_user_func($callback, $user, $db, $templates, $parameters, $querystring);
			$db->commit();
		} catch(\Throwable $e) {
			$db->rollback();
			throw $e;
		}

		return new Response(500, $request->responseType, $result);
	}
}