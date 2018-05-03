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

		$item = $db->itemDAO()->getItem(new ItemIncomplete($id));
		$renderParameters = ['item' => $item, 'deleted' => !$db->itemDAO()->itemVisible($item)];
		// These should be mutually exclusive
		if($edit !== null) {
			$renderParameters['edit'] = $edit;
		} else if($add !== null) {
			$renderParameters['add'] = $add;
		}

		return new Response(200, 'text/html', $engine->render('viewItem', $renderParameters));
	}

	private static function getHistory(
		User $user = null,
		Database $db,
		Engine $engine,
		$parameters,
		$querystring
	): Response {
		Validation::authorize($user);
		$id = isset($parameters['id']) ? (string) $parameters['id'] : null;
		$count = isset($querystring['count']) ? (int) $querystring['count'] : 20;

		$item = $db->itemDAO()->getItem(new ItemIncomplete($id), null, 0);
		$history = $db->statsDAO()->getHistory($item, $count);

		return new Response(200, 'text/html', $engine->render('history',
			['item' => $item, 'deleted' => !$db->itemDAO()->itemVisible($item), 'history' => $history]));
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

	public static function logout(User $user = null, Database $db, Engine $engine, $parameters, $querystring) {
		Validation::authenticate($user);
		Session::close($user, $db);

		return new Response(200, 'text/html', $engine->render('logout'));
	}

	private static function getHome(
		User $user = null,
		Database $db,
		Engine $engine,
		$parameters,
		$querystring
	): Response {
		try {
			Validation::authorize($user);
		} catch(AuthenticationException $e) {
			return new RedirectResponse(303, '/login');
		}

		$locations = $db->statsDAO()->getLocationsByItems();
		$recentlyAdded = $db->statsDAO()->getRecentAuditByType('C', max(20, count($locations)));

		return new Response(200, 'text/html',
			$engine->render('home', ['locations' => $locations, 'recentlyAdded' => $recentlyAdded]));
	}

	private static function getStats(
		User $user = null,
		Database $db,
		Engine $engine,
		$parameters,
		$querystring
	): Response {
		Validation::authorize($user);

		$locations = $db->statsDAO()->getLocationsByItems();
		$serials = $db->statsDAO()->getDuplicateSerialsCount();
		$recentlyAdded = $db->statsDAO()->getRecentAuditByType('C', 40);

		return new Response(200, 'text/html', $engine->render('stats',
			['locations' => $locations, 'serials' => $serials, 'recentlyAdded' => $recentlyAdded]));
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
		$add = isset($parameters['add']) ? (string) $parameters['add'] : null;
		$edit = isset($parameters['edit']) ? (string) $parameters['edit'] : null;

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
			if($add !== null) {
				$parameters['add'] = $add;
			} else if($edit !== null) {
				$parameters['edit'] = $edit;
			}
		}

		return new Response(200, 'text/html', $engine->render('search', $parameters));
	}

	private static function options(
		User $user = null,
		Database $db,
		Engine $engine,
		$parameters,
		$querystring
	): Response {
		Validation::authorize($user);
		if($querystring === null) {
			$result = null;
		} else {
			$result = 'success';
			$password = Validation::validateHasString($querystring, 'password');
			$confirm = Validation::validateHasString($querystring, 'confirm');
			$username = isset($querystring['username']) ? (string) trim($querystring['username']) : null;

			$target = null;
			if($username === null || $username === '') {
				$target = $user;
			} else {
				Validation::authorize($user, 0);
			}

			try {
				if($target === null) {
					$target = new User($username, $password);
				}
				$target->setPassword($password, $confirm);
				$db->userDAO()->setPasswordFromUser($target->getUsername(), $target->getHash());
			} catch(\InvalidArgumentException $e) {
				switch($e->getCode()) {
					case 5:
						$result = 'empty';
						break;
					case 6:
						$result = 'nomatch';
						break;
					case 7:
						$result = 'short';
						break;
					default:
						throw $e;
				}
			} catch(NotFoundException $e) {
				if($e->getCode() === 8) {
					$db->userDAO()->createUser($target->getUsername(), $target->getHash());
					$result = 'successnew';
				} else {
					throw $e;
				}
			}
		}
		if($result === null || $result === 'success' || $result === 'successnew') {
			$status = 200;
		} else {
			$status = 400;
		}

		return new Response($status, 'text/html', $engine->render('options', ['result' => $result]));
	}

	private static function getFeaturesJson(
		User $user = null,
		Database $db,
		Engine $engine,
		$parameters,
		$querystring
	) {
		session_cache_limiter('');
		header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 36000) . ' GMT');
		header('Cache-Control	: max-age=36000');

		return new Response(200, 'text/json', json_encode(FeaturePrinter::getAllFeatures()));
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
			$r->addRoute(['GET', 'POST'], '/login', 'login');
			$r->addRoute(['GET', 'POST'], '/options', 'options');
			$r->get('/logout', 'logout');

			$r->get('/', 'getHome');
			$r->get('/features.json', 'getFeaturesJson');
			$r->get('/home', 'getHome');
			$r->get('/item/{id}', 'getItem');
			$r->get('/history/{id}', 'getHistory');
			$r->get('/item/{id}/add/{add}', 'getItem');
			$r->get('/item/{id}/edit/{edit}', 'getItem');
			$r->get('/add', 'addItem');
			$r->get('/stats', 'getStats');
			$r->get('/search[/{id:[0-9]+}[/page/{page:[0-9]+}]]', 'search');
			$r->get('/search/{id:[0-9]+}/add/{add}', 'search');
			$r->get('/search/{id:[0-9]+}/page/{page:[0-9]+}/add/{add}', 'search');
			$r->get('/search/{id:[0-9]+}/edit/{edit}', 'search');
			$r->get('/search/{id:[0-9]+}/page/{page:[0-9]+}/edit/{edit}', 'search');
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
