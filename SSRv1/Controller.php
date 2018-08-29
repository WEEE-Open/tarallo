<?php

namespace WEEEOpen\Tarallo\SSRv1;

use FastRoute;
use League\Plates\Engine;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Relay\RelayBuilder;
use Slim\Http\Body;
use WEEEOpen\Tarallo\Server\Database\Database;
use WEEEOpen\Tarallo\Server\Feature;
use WEEEOpen\Tarallo\Server\HTTP\AuthenticationException;
use WEEEOpen\Tarallo\Server\HTTP\AuthorizationException;
use WEEEOpen\Tarallo\Server\HTTP\DatabaseConnection;
use WEEEOpen\Tarallo\Server\HTTP\LanguageNegotiatior;
use WEEEOpen\Tarallo\Server\HTTP\Validation;
use WEEEOpen\Tarallo\Server\ItemIncomplete;
use WEEEOpen\Tarallo\Server\NotFoundException;
use WEEEOpen\Tarallo\Server\Session;
use WEEEOpen\Tarallo\Server\User;


class Controller {
	public static function getItem(Request $request, Response $response, ?callable $next = null): Response {
		/** @var Database $db */
		$db = $request->getAttribute('Database');
		$query = $request->getQueryParams();
		$user = $request->getAttribute('User');
		$parameters = $request->getAttribute('parameters', []);

		Validation::authorize($user, 3);

		$id = Validation::validateOptionalString($parameters, 'id', null);
		$edit = Validation::validateOptionalString($parameters, 'edit', null);
		$add = Validation::validateOptionalString($parameters, 'add', null);
		$depth = Validation::validateOptionalInt($query, 'depth', 20);

		$item = $db->itemDAO()->getItem(new ItemIncomplete($id), null, $depth);
		$renderParameters = ['item' => $item, 'deleted' => !$db->itemDAO()->itemVisible($item)];
		// These should be mutually exclusive
		if($edit !== null) {
			$renderParameters['add'] = null;
			$renderParameters['edit'] = $edit;
		} else if($add !== null) {
			$renderParameters['add'] = $add;
			$renderParameters['edit'] = null;
		}

		$request = $request
			->withAttribute('Template', 'viewItem')
			->withAttribute('TemplateParameters', $renderParameters);

		return $next ? $next($request, $response) : $response;
	}

	public static function getHistory(Request $request, Response $response, ?callable $next = null): Response {
		/** @var Database $db */
		$db = $request->getAttribute('Database');
		$query = $request->getQueryParams();
		$user = $request->getAttribute('User');
		$parameters = $request->getAttribute('parameters', []);

		Validation::authorize($user, 3);

		$id = Validation::validateOptionalString($parameters, 'id', null);
		$count = Validation::validateOptionalInt($query, 'count', 20);

		// Full item needed to show breadcrumbs
		$item = $db->itemDAO()->getItem(new ItemIncomplete($id), null, 0);
		if(!$db->itemDAO()->itemExists($item)) {
			throw new NotFoundException();
		}

		// TODO: place a limit on $count
		$history = $db->auditDAO()->getHistory($item, $count);

		$request = $request
			->withAttribute('Template', 'history')
			->withAttribute('TemplateParameters',
				['item' => $item, 'deleted' => !$db->itemDAO()->itemVisible($item), 'history' => $history]);

		return $next ? $next($request, $response) : $response;
	}

	public static function addItem(Request $request, Response $response, ?callable $next = null): Response {
		// TODO: a ?template=some_other_id, to clone (with minor modifications) other items
		//$db = $request->getAttribute('Database');
		//$query = $request->getQueryParams();
		$user = $request->getAttribute('User');

		Validation::authorize($user);

		$request = $request
			->withAttribute('Template', 'newItem')
			->withAttribute('TemplateParameters', ['add' => true]);

		return $next ? $next($request, $response) : $response;
	}

	public static function login(Request $request, Response $response, ?callable $next = null): Response {
		$db = $request->getAttribute('Database');

		if($request->getMethod() === 'POST') {
			$query = $request->getParsedBody();
			$username = Validation::validateHasString($query, 'username');
			$password = Validation::validateHasString($query, 'password');
			$user = $db->userDAO()->getUserFromLogin($username, $password);

			if($user === null) {
				$request = $request
					->withAttribute('Template', 'login')
					->withAttribute('TemplateParameters', ['failed' => true]);
				$response = $response
					->withStatus(400);
			} else {
				Session::start($user, $db);
				$request = $request
					->withAttribute('User', $user);
				$response = $response
					->withStatus(303)
					->withoutHeader('Content-type')
					->withHeader('Location', '/home');
			}
		} else {
			$request = $request
				->withAttribute('Template', 'login');
		}

		return $next ? $next($request, $response) : $response;
	}

	public static function logout(Request $request, Response $response, ?callable $next = null): Response {
		$db = $request->getAttribute('Database');
		$user = $request->getAttribute('User');

		Validation::authenticate($user);
		Session::close($user, $db);

		$request = $request
			->withAttribute('Template', 'logout');

		return $next ? $next($request, $response) : $response;
	}

	public static function getHome(Request $request, Response $response, ?callable $next = null): Response {
		$db = $request->getAttribute('Database');
		$user = $request->getAttribute('User');

		try {
			Validation::authorize($user, 3);
		} catch(AuthenticationException $e) {
			$response = $response
				->withStatus(303)
				->withoutHeader('Content-type')
				->withHeader('Location', '/login');
			return $next ? $next($request, $response) : $response;
		}

		$request = $request
			->withAttribute('Template', 'home')
			->withAttribute('TemplateParameters',
				[
					'locations'     => $locations = $db->statsDAO()->getLocationsByItems(),
					'recentlyAdded' => $db->auditDAO()->getRecentAuditByType('C', max(20, count($locations)))
				]);

		return $next ? $next($request, $response) : $response;
	}

	public static function getStats(Request $request, Response $response, ?callable $next = null): Response {
		$db = $request->getAttribute('Database');
		$user = $request->getAttribute('User');
		$parameters = $request->getAttribute('parameters', ['which' => null]);

		Validation::authorize($user, 3);

		switch($parameters['which']) {
			case '':
			default:
				$request = $request
					->withAttribute('Template', 'stats::main')
					->withAttribute('TemplateParameters',
						[
							'locations'     => $db->statsDAO()->getLocationsByItems(),
							'recentlyAdded' => $db->auditDAO()->getRecentAuditByType('C', 40)
						]);
				break;
			case 'attention':
				$request = $request
					->withAttribute('Template', 'stats::main')
					->withAttribute('TemplateParameters',
						[
							'serials'     => $db->statsDAO()->getDuplicateSerialsCount(),
							'missingData' => $db->featureDAO()->getItemsByFeatures(new Feature('check', 'missing-data'),
								500),
							'lost'        => $db->featureDAO()->getItemsByFeatures(new Feature('check', 'lost'), 100)
						]);
		}

		return $next ? $next($request, $response) : $response;
	}

	public static function search(Request $request, Response $response, ?callable $next = null): Response {
		$db = $request->getAttribute('Database');
		$user = $request->getAttribute('User');
		$parameters = $request->getAttribute('parameters', []);
		$query = $request->getQueryParams();

		Validation::authorize($user, 3);

		$id = Validation::validateOptionalString($parameters, 'id', null);
		$page = Validation::validateOptionalInt($parameters, 'page', 1);
		$edit = Validation::validateOptionalString($parameters, 'edit', null);
		$add = Validation::validateOptionalString($parameters, 'add', null);
		$depth = Validation::validateOptionalInt($query, 'depth', 20);

		if($id === null) {
			$templateParameters = ['searchId' => null];
		} else {
			$perPage = 10;
			$results = $db->searchDAO()->getResults($id, $page, $perPage, $depth);
			$total = $db->searchDAO()->getResultsCount($id);
			$pages = (int) ceil($total / $perPage);
			$templateParameters = [
				'searchId'       => $id,
				'page'           => $page,
				'pages'          => $pages,
				'total'          => $total,
				'resultsPerPage' => $perPage,
				'results'        => $results,
			];
			if($add !== null) {
				$templateParameters['add'] = $add;
			} else if($edit !== null) {
				$templateParameters['edit'] = $edit;
			}
		}

		$request = $request
			->withAttribute('Template', 'search')
			->withAttribute('TemplateParameters', $templateParameters);

		return $next ? $next($request, $response) : $response;
	}

	public static function options(Request $request, Response $response, ?callable $next = null): Response {
		$db = $request->getAttribute('Database');
		$user = $request->getAttribute('User');
		$query = $request->getQueryParams();

		Validation::authorize($user, 3);

		if(empty($query)) {
			$result = null;
		} else {
			$result = 'success';
			$password = Validation::validateHasString($query, 'password');
			$confirm = Validation::validateHasString($query, 'confirm');
			$username = isset($query['username']) ? (string) trim($query['username']) : null;

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

		$request = $request
			->withAttribute('Template', 'options')
			->withAttribute('TemplateParameters', ['result' => $result]);

		if($result === null || $result === 'success' || $result === 'successnew') {
			$response = $response->withStatus(200);
		} else {
			$response = $response->withStatus(400);
		}

		return $next ? $next($request, $response) : $response;
	}

	public static function getFeaturesJson(Request $request, Response $response, ?callable $next = null): Response {
		$response = $response
			->withHeader('Content-Type', 'text/json')
			->withHeader('Expires', gmdate('D, d M Y H:i:s', time() + 36000) . ' GMT')
			->withHeader('Cache-Control', 'max-age=36000');
		//->withHeader('Last-Modified', '...');

		$response->getBody()->write(json_encode(FeaturePrinter::getAllFeatures()));

		return $next ? $next($request, $response) : $response;
	}

	public static function handle(Request $request): Response {
		$queue = [
			new DatabaseConnection(),
			new LanguageNegotiatior(),
			new TemplateEngine(),
			[Controller::class, 'handleExceptions']
		];
		$response = new \Slim\Http\Response();
		$response = $response
			->withHeader('Content-Type', 'text/html')
			->withBody(new Body(fopen('php://memory', 'r+')));

		// TODO: use cachedDispatcher
		$dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) {
			// TODO: [new RateLimit(), [Controller::class, 'login']] or something like that
			$r->addRoute(['GET', 'POST'], '/login', [[Controller::class, 'login']]);
			$r->addRoute(['GET', 'POST'], '/options', [[Controller::class, 'options']]);
			$r->get('/logout', [[Controller::class, 'logout']]);

			$r->get('/', [[Controller::class, 'getHome']]);
			$r->get('', [[Controller::class, 'getHome']]);
			$r->get('/features.json', [Controller::class, 'getFeaturesJson']);
			$r->get('/home', [[Controller::class, 'getHome']]);
			$r->get('/item/{id}', [[Controller::class, 'getItem']]);
			$r->get('/history/{id}', [[Controller::class, 'getHistory']]);
			$r->get('/item/{id}/add/{add}', [[Controller::class, 'getItem']]);
			$r->get('/item/{id}/edit/{edit}', [[Controller::class, 'getItem']]);
			$r->get('/add', [[Controller::class, 'addItem']]);
			$r->get('/search[/{id:[0-9]+}[/page/{page:[0-9]+}]]', [[Controller::class, 'search']]);
			$r->get('/search/{id:[0-9]+}/add/{add}', [[Controller::class, 'search']]);
			$r->get('/search/{id:[0-9]+}/page/{page:[0-9]+}/add/{add}', [[Controller::class, 'search']]);
			$r->get('/search/{id:[0-9]+}/edit/{edit}', [[Controller::class, 'search']]);
			$r->get('/search/{id:[0-9]+}/page/{page:[0-9]+}/edit/{edit}', [[Controller::class, 'search']]);

			$r->addGroup('/stats', function(FastRoute\RouteCollector $r) {
				$r->get('', [[Controller::class, 'getStats']]);
				$r->get('/{which}', [[Controller::class, 'getStats']]);
			});
		});

		$route = $dispatcher->dispatch($request->getMethod(), $request->getUri()->getPath());

		switch($route[0]) {
			case FastRoute\Dispatcher::FOUND:
				$queue = array_merge($queue, [[Controller::class, 'doTransaction']], $route[1]);
				$request = $request
					->withAttribute('parameters', $route[2]);
				$response = $response
					->withStatus(200);
				break;
			case FastRoute\Dispatcher::NOT_FOUND:
				$request = $request
					->withAttribute('Template', 'notFound');
				$response = $response->withStatus(404);
				break;
			case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
				$request = $request
					->withAttribute('Template', 'genericError');
				$response = $response->withStatus(405)
					->withHeader('Allow', implode(', ', $route[1]));
				break;
			default:
				$request = $request
					->withAttribute('Template', 'genericError')
					->withAttribute('TemplateParameters', ['reason' => 'SSR Error: unknown router result']);
				$response = $response->withStatus(500);
				break;
		}

		unset($route);

		$queue = array_merge($queue, [[Controller::class, 'renderResponse']]);

		//		if(!is_callable($callback)) {
		//			echo 'Server error: cannot call "' . implode('::', $callback) . '" (SSR)';
		//		}

		$relayBuilder = new RelayBuilder();
		$relay = $relayBuilder->newInstance($queue);

		return $relay($request, $response);
	}

	public static function doTransaction(
		Request $request,
		Response $response,
		?callable $next = null
	): Response {
		$db = $request->getAttribute('Database');
		$db->beginTransaction();

		try {
			if($next) {
				$response = $next($request, $response);
			}
			$db->commit();
		} catch(\Throwable $e) {
			$db->rollback();
			/** @noinspection PhpUnhandledExceptionInspection */
			throw $e;
		}

		return $response;
	}

	public static function renderResponse(
		Request $request,
		Response $response,
		?callable $next = null
	): Response {
		$template = $request->getAttribute('Template');

		if($request->getMethod() !== 'HEAD' && $template !== null) {
			/** @var Engine $engine */
			$engine = $request->getAttribute('TemplateEngine');

			// TODO: remove addData, read attrbitues in templates directly
			$engine->addData([
				'user'     => $request->getAttribute('User'),
				'self'     => $request->getUri()->getPath(),
				'request'  => $request,
				'response' => $response
			]);

			$response->getBody()->rewind();
			$response->getBody()->write($engine->render($template, $request->getAttribute('TemplateParameters', [])));
		}

		if($next) {
			return $next($request, $response);
		} else {
			return $response;
		}
	}

	public static function handleExceptions(
		Request $request,
		Response $response,
		?callable $next = null
	): Response {
		if($next) {
			try {
				return $next($request, $response);
			} catch(AuthenticationException $e) {
				// One of these should be 401, but that requires a challenge header in the response...
				$request = $request
					->withAttribute('Template', 'genericError')
					->withAttribute('TemplateParameters', []);
				$response = $response
					->withStatus(403);
			} catch(AuthorizationException $e) {
				$request = $request
					->withAttribute('Template', 'genericError')
					->withAttribute('TemplateParameters', []);
				$response = $response
					->withStatus(403);
			} catch(NotFoundException $e) {
				$request = $request
					->withAttribute('Template', 'notFound')
					->withAttribute('TemplateParameters', []);
				$response = $response
					->withStatus(404);
			} catch(\Throwable $e) {
				$request = $request
					->withAttribute('Template', 'genericError')
					->withAttribute('TemplateParameters', ['reason' => $e->getMessage()]);
				$response = $response
					->withStatus(500);
			}

			return self::renderResponse($request, $response);
		} else {
			return $response;
		}
	}

}
