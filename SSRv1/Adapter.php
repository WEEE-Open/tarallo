<?php

namespace WEEEOpen\Tarallo\SSRv1;

use FastRoute;
use League\Plates\Engine;
use WEEEOpen\Tarallo\Server\Database\Database;
use WEEEOpen\Tarallo\Server\HTTP\AdapterInterface;
use WEEEOpen\Tarallo\Server\HTTP\AuthenticationException;
use WEEEOpen\Tarallo\Server\HTTP\AuthorizationException;
use WEEEOpen\Tarallo\Server\HTTP\RedirectResponse;
use WEEEOpen\Tarallo\Server\HTTP\Request;
use WEEEOpen\Tarallo\Server\HTTP\Response;
use WEEEOpen\Tarallo\Server\HTTP\Validation;
use WEEEOpen\Tarallo\Server\ItemIncomplete;
use WEEEOpen\Tarallo\Server\NotFoundException;
use WEEEOpen\Tarallo\Server\Session;
use WEEEOpen\Tarallo\Server\User;


class Adapter implements AdapterInterface {
	private static function getItem(
		User $user = null,
		Database $db,
		Engine $engine,
		$parameters,
		$querystring
	): Response {
		Validation::authorize($user);
		$id = isset($parameters['id']) ? (string) $parameters['id'] : null;
		$edit = isset($parameters['edit']) ? (string) $parameters['edit'] : null;
		$add = isset($parameters['add']) ? (string) $parameters['add'] : null;

		$renderParameters = ['item' => $db->itemDAO()->getItem(new ItemIncomplete($id))];
		// These should be mutually exclusive
		if($edit !== null) {
			$renderParameters['edit'] = $edit;
		} else if($add !== null) {
			$renderParameters['add'] = $add;
		}

		return new Response(200, 'text/html', $engine->render('viewItem', $renderParameters));
	}

	private static function addItem(
		User $user = null,
		Database $db,
		Engine $engine,
		$parameters,
		$querystring
	): Response {
		Validation::authorize($user);

		return new Response(200, 'text/html', $engine->render('newItem', ['add' => true]));
	}

	private static function login(
		User $user = null,
		Database $db,
		Engine $engine,
		$parameters,
		$querystring
	): Response {
		if($querystring !== null) {
			$username = Validation::validateHasString($querystring, 'username');
			$password = Validation::validateHasString($querystring, 'password');
			$user = $db->userDAO()->getUserFromLogin($username, $password);
			if($user === null) {
				$response = new Response(400, 'text/html', $engine->render('login', ['failed' => true]));
			} else {
				Session::start($user, $db);
				$response = new RedirectResponse(303, '/home');
			}
		} else {
			$response = new Response(200, 'text/html', $engine->render('login'));
		}

		return $response;
	}

	private static function getHome(
		User $user = null,
		Database $db,
		Engine $engine,
		$parameters,
		$querystring
	): Response {
		Validation::authorize($user);

		return new Response(200, 'text/html', $engine->render('home'));
	}

	private static function search(
		User $user = null,
		Database $db,
		Engine $engine,
		$parameters,
		$querystring
	): Response {
		Validation::authorize($user);
		$id = isset($parameters['id']) ? (int) $parameters['id'] : null;
		$page = isset($parameters['page']) ? (int) $parameters['page'] : 1;
		// TODO: the add/edit thing

		if($id === null) {
			$parameters = ['searchId' => null];
		} else {
			$perPage = 10;
			$results = $db->searchDAO()->getResults($id, $page, $perPage);
			$total = $db->searchDAO()->getResultsCount($id);
			$pages = (int) ceil($total / $perPage);
			$parameters = [
				'searchId'       => $id,
				'page'           => $page,
				'pages'          => $pages,
				'total'          => $total,
				'resultsPerPage' => $perPage,
				'results'        => $results,
			];
		}

		// Example search:
		//var_dump($db->searchDAO()->search(new Search('R%'), $user));

		return new Response(200, 'text/html', $engine->render('search', $parameters));
	}

	private static function getFeaturesJson(
		User $user = null,
		Database $db,
		Engine $engine,
		$parameters,
		$querystring
	) {
		// TODO: cache ("expires" header + last-modified if HEAD request, maybe?)
		return new Response(200, 'text/json', json_encode(FeaturePrinter::getAll()));
	}

	public static function go(Request $request): Response {
		$method = $request->method;
		$uri = $request->path;
		$querystring = $request->querystring;

		//Localizer::localize($request->language);

		$engine = new Engine(__DIR__ . DIRECTORY_SEPARATOR . 'templates');
		$engine->addData(['lang' => $request->language]);
		//$engine->loadExtension(new URI($request->path));
		$engine->loadExtension(new TemplateUtilities());

		// TODO: use cachedDispatcher
		$dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) {
			$r->get('/', 'getHome');
			$r->get('/features.json', 'getFeaturesJson');
			$r->get('/home', 'getHome');
			$r->get('/item/{id}', 'getItem');
			$r->get('/item/{id}/add/{add}', 'getItem');
			$r->get('/item/{id}/edit/{edit}', 'getItem');
			$r->get('/add', 'addItem');
			$r->get('/search[/{id:[0-9]+}[/page/{page:[0-9]+}]]', 'search');
			$r->addRoute(['GET', 'POST'], '/login', 'login');
		});

		$route = $dispatcher->dispatch($method, $uri);

		if($route[0] === FastRoute\Dispatcher::NOT_FOUND) {
			return new Response(404, $request->responseType, $engine->render('notFound'));
		}

		if($route[0] === FastRoute\Dispatcher::METHOD_NOT_ALLOWED) {
			http_response_code(405);
			header('Allow: ' . implode(', ', $route[1]));
			exit(); // TODO: return new Response instead of exiting
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

		// self is the routed path thingamajig
		$engine->addData(['user' => $user, 'self' => $uri]);

		try {
			try {
				$db->beginTransaction();
				$result = call_user_func($callback, $user, $db, $engine, $parameters, $querystring);
				$db->commit();
			} catch(\Throwable $e) {
				$db->rollback();
				throw $e;
			}
		} catch(AuthenticationException $e) {
			// One of these should be 401, but that requires a challenge header in the response...
			return new Response(403, $request->responseType, $engine->render('notAuthenticated'));
		} catch(AuthorizationException $e) {
			return new Response(403, $request->responseType, $engine->render('notAuthorized'));
		} catch(NotFoundException $e) {
			return new Response(404, $request->responseType, $engine->render('notFound'));
		} catch(\Throwable $e) {
			return new Response(500, 'text/plain', 'Unhandled exception: ' . $e->getMessage());
		}

		return $result;
	}
}
