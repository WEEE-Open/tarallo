<?php

namespace WEEEOpen\Tarallo\SSRv1;

use FastRoute;
use League\Plates\Engine;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Relay\RelayBuilder;
use Slim\Http\Body;
use WEEEOpen\Tarallo\Database\Database;
use WEEEOpen\Tarallo\Database\TreeDAO;
use WEEEOpen\Tarallo\BaseFeature;
use WEEEOpen\Tarallo\Feature;
use WEEEOpen\Tarallo\HTTP\AbstractController;
use WEEEOpen\Tarallo\HTTP\AuthenticationException;
use WEEEOpen\Tarallo\HTTP\AuthorizationException;
use WEEEOpen\Tarallo\HTTP\DatabaseConnection;
use WEEEOpen\Tarallo\HTTP\InvalidPayloadParameterException;
use WEEEOpen\Tarallo\HTTP\Validation;
use WEEEOpen\Tarallo\Item;
use WEEEOpen\Tarallo\ItemCode;
use WEEEOpen\Tarallo\ItemIncomplete;
use WEEEOpen\Tarallo\ItemNestingException;
use WEEEOpen\Tarallo\ItemValidator;
use WEEEOpen\Tarallo\NotFoundException;
use WEEEOpen\Tarallo\Session;
use WEEEOpen\Tarallo\User;
use WEEEOpen\Tarallo\ValidationException;


class Controller extends AbstractController {
	const cachefile = __DIR__ . '/router.cache';

	public static function getItem(Request $request, Response $response, ?callable $next = null): Response {
		/** @var Database $db */
		$db = $request->getAttribute('Database');
		$query = $request->getQueryParams();
		$user = $request->getAttribute('User');
		$parameters = $request->getAttribute('parameters', []);

		Validation::authorize($user, 3);

		// So things aren't url-decoded automatically...
		$id = urldecode(Validation::validateOptionalString($parameters, 'id', null));
		$edit = Validation::validateOptionalString($parameters, 'edit', null);
		$add = Validation::validateOptionalString($parameters, 'add', null);
		$depth = Validation::validateOptionalInt($query, 'depth', 20);

		try {
			$ii = new ItemCode($id);
		} catch(ValidationException $e) {
			if($e->getCode() === 3) {
				$response = $response
					->withStatus(404);
				$request = $request
					->withAttribute('Template', 'error')
					->withAttribute('TemplateParameters', ['reason' => "Code '$id' contains invalid characters"]);
				return $next ? $next($request, $response) : $response;
			}
			throw $e;
		}

		$item = $db->itemDAO()->getItem($ii, null, $depth);
		$renderParameters = ['item' => $item];
		// These should be mutually exclusive
		if($edit !== null) {
			$renderParameters['add'] = null;
			$renderParameters['edit'] = $edit;
		} else if($add !== null) {
			$renderParameters['add'] = $add;
			$renderParameters['edit'] = null;
		} else {
			$renderParameters['add'] = null;
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
		$limit = Validation::validateOptionalInt($query, 'limit', 20);
		if($limit > 100) {
			$limit = 100;
		} else if($limit <= 0) {
			$limit = 20;
		}
		$limit++;

		// Full item needed to show breadcrumbs
		$item = new ItemCode($id);
		$item = $db->itemDAO()->getItem($item, null, 0);

		$history = $db->auditDAO()->getHistory($item, $limit);
		if(count($history) === $limit) {
			array_pop($history);
			$tooLong = true;
		} else {
			$tooLong = false;
		}

		$request = $request
			->withAttribute('Template', 'history')
			->withAttribute(
				'TemplateParameters',
				['item' => $item, 'history' => $history, 'tooLong' => $tooLong]
			);

		return $next ? $next($request, $response) : $response;
	}

	public static function addItem(Request $request, Response $response, ?callable $next = null): Response {
		$query = $request->getQueryParams();
		$user = $request->getAttribute('User');

		Validation::authorize($user);

		$from = Validation::validateOptionalString($query, 'copy', null);

		if($from === null) {
			$from = new ItemIncomplete(null);
			$from->addFeature(new BaseFeature('type'));
			$from->addFeature(new BaseFeature('working'));
		} else {
			/** @var Database $db */
			$db = $request->getAttribute('Database');
			$from = $db->itemDAO()->getItem(new ItemCode($from));
		}

		$request = $request
			->withAttribute('Template', 'newItemPage')
			->withAttribute('TemplateParameters', ['add' => true, 'base' => $from]);

		return $next ? $next($request, $response) : $response;
	}

	public static function login(Request $request, Response $response, ?callable $next = null): Response {
		$db = $request->getAttribute('Database');

		if($request->getMethod() === 'POST') {
			$body = $request->getParsedBody();
			$username = Validation::validateHasString($body, 'username');
			$password = Validation::validateHasString($body, 'password');
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
			->withAttribute(
				'TemplateParameters',
				[
					'locations' => $locations = $db->statsDAO()->getLocationsByItems(),
					'recentlyAdded' => $db->auditDAO()->getRecentAuditByType('C', max(20, count($locations))),
				]
			);

		return $next ? $next($request, $response) : $response;
	}

	public static function getStats(Request $request, Response $response, ?callable $next = null): Response {
		/** @var Database $db */
		$db = $request->getAttribute('Database');
		$user = $request->getAttribute('User');
		$query = $request->getQueryParams();
		$parameters = $request->getAttribute('parameters', ['which' => null]);

		Validation::authorize($user, 3);

		// a nice default value: 'now - 1 year'
		$startDateDefault = '2016-01-01';
		$startDate = Validation::validateOptionalString($query, 'from', $startDateDefault, null);
		$startDateSet = $startDate !== $startDateDefault;
		/** @noinspection PhpUnhandledExceptionInspection */
		$startDate = new \DateTime($startDate, new \DateTimeZone('Europe/Rome'));

		switch($parameters['which']) {
			case '':
				$request = $request
					->withAttribute('Template', 'stats::main')
					->withAttribute(
						'TemplateParameters',
						[
							'locations' => $db->statsDAO()->getLocationsByItems(),
							'recentlyAdded' => $db->auditDAO()->getRecentAuditByType('C', 40),
							'recentlyModified' => $db->auditDAO()->getRecentAuditByType('M', 40),
						]
					);
				break;

			case 'todo':
				$todos = [];
				$possibileTodos = array_keys(BaseFeature::features['todo']);
				foreach($possibileTodos as $possibileTodo) {
					$todos[$possibileTodo] = $db->statsDAO()->getItemsByFeatures(new Feature('todo', $possibileTodo), null, 100);
				}

				$request = $request
					->withAttribute('Template', 'stats::todo')
					->withAttribute('TemplateParameters', ['todos' => $todos]);
				break;

			case 'attention':
				$request = $request
					->withAttribute('Template', 'stats::needAttention')
					->withAttribute(
						'TemplateParameters',
						[
							'serials' => $db->statsDAO()->getCountByFeature('sn', null, null, null, false, 2),
							'missingData' => $db->statsDAO()->getItemsByFeatures(
								new Feature('check', 'missing-data'),
								null,
								500
							),
							'lost' => $db->statsDAO()->getLostItems([], 100),
						]
					);
				break;

			case 'cases':
				$locationDefault = 'Chernobyl';
				$location = Validation::validateOptionalString($query, 'where', $locationDefault, null);
				$locationSet = $location !== $locationDefault;
				$location = $location === null ? null : new ItemCode($location);

				$request = $request
					->withAttribute('Template', 'stats::cases')
					->withAttribute(
						'TemplateParameters',
						[
							'location' => $location === null ? null : $location->getCode(),
							'locationSet' => $locationSet,
							'startDate' => $startDate,
							'startDateSet' => $startDateSet,
							'leastRecent' => $db->statsDAO()->getModifiedItems($location, false, 30),
							'mostRecent' => $db->statsDAO()->getModifiedItems($location, true, 30),
							'byOwner' => $db->statsDAO()
								->getCountByFeature('owner', new Feature('type', 'case'), $location, $startDate),
							'byMobo' => $db->statsDAO()
								->getCountByFeature(
									'motherboard-form-factor',
									new Feature('type', 'case'),
									$location,
									$startDate
								),
							'ready' => $db->statsDAO()->getItemsByFeatures(
								new Feature('restrictions', 'ready'),
								$location,
								100
							),
						]
					);
				break;

			case 'rams':
				$locationDefault = 'Rambox';
				$location = Validation::validateOptionalString($query, 'where', $locationDefault, null);
				$locationSet = $location !== $locationDefault;
				$location = $location === null ? null : new ItemCode($location);

				$request = $request
					->withAttribute('Template', 'stats::rams')
					->withAttribute(
						'TemplateParameters',
						[
							'location' => $location === null ? null : $location->getCode(),
							'locationSet' => $locationSet,
							'startDate' => $startDate,
							'startDateSet' => $startDateSet,
							'byType' => $db->statsDAO()
								->getCountByFeature('ram-type', new Feature('type', 'ram'), $location),
							'byFormFactor' => $db->statsDAO()
								->getCountByFeature('ram-form-factor', new Feature('type', 'ram'), $location),
							'bySize' => $db->statsDAO()
								->getCountByFeature('capacity-byte', new Feature('type', 'ram'), $location),
							'byTypeFrequency' => $db->statsDAO()->getRollupCountByFeature(
								new Feature('type', 'ram'),
								['ram-type', 'ram-form-factor', 'frequency-hertz'],
								$location
							),
							'byTypeSize' => $db->statsDAO()->getRollupCountByFeature(
								new Feature('type', 'ram'),
								['ram-type', 'ram-form-factor', 'capacity-byte'],
								$location
							),
							'noWorking' => $db->statsDAO()->getItemByNotFeature(
								new Feature('type', 'ram'),
								'working',
								$location,
								200
							),
							'noFrequency' => $db->statsDAO()->getItemByNotFeature(
								new Feature('type', 'ram'),
								'frequency-hertz',
								$location,
								200
							),
							'noSize' => $db->statsDAO()->getItemByNotFeature(
								new Feature('type', 'ram'),
								'capacity-byte',
								$location,
								200
							),
						]
					);
				break;

			default:
				// TODO: if this gets used only for items (and the page suggesting items), change to something else
				throw new NotFoundException();
		}

		return $next ? $next($request, $response) : $response;
	}

	public static function search(Request $request, Response $response, ?callable $next = null): Response {
		$db = $request->getAttribute('Database');
		$user = $request->getAttribute('User');
		$parameters = $request->getAttribute('parameters', []);
		$query = $request->getQueryParams();

		Validation::authorize($user, 3);

		$id = Validation::validateOptionalInt($parameters, 'id', null);
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
				'searchId' => $id,
				'page' => $page,
				'pages' => $pages,
				'total' => $total,
				'resultsPerPage' => $perPage,
				'results' => $results,
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
		$body = $request->getParsedBody();

		Validation::authorize($user, 3);

		if(empty($body)) {
			$result = null;
		} else {
			$result = 'success';
			$password = Validation::validateHasString($body, 'password');
			$confirm = Validation::validateHasString($body, 'confirm');
			$username = isset($body['username']) ? (string) trim($body['username']) : null;

			$target = null;
			if($username === null || $username === '') {
				$target = $user;
			} else {
				Validation::authorize($user, 0);
			}

			try {
				if($target === null) {
					$target = new User($username, $password, null, 2);
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

	public static function bulk(Request $request, Response $response, ?callable $next = null): Response {
		$user = $request->getAttribute('User');

		Validation::authorize($user);

		$response = $response
			->withStatus(303)
			->withoutHeader('Content-type')
			->withHeader('Location', '/bulk/move');
		return $next ? $next($request, $response) : $response;
	}

	public static function bulkMove(Request $request, Response $response, ?callable $next = null): Response {
		$db = $request->getAttribute('Database');
		$user = $request->getAttribute('User');
		$body = $request->getParsedBody();

		Validation::authorize($user);

		if($body === null) {
			// Opened page, didn't submit anything yet
			$items = null;
		} else {
			if(empty($_FILES['Fitems']['tmp_name'])) {
				$items = (string) $body['items'];
			} else {
				$items = file_get_contents($_FILES['Fitems']['tmp_name']);
				if($items === false) {
					throw new \LogicException('Cannot open temporary file');
				}
			}
		}

		$error = null;
		$moved = null;
		$code = 200;
		if($items != null) {
			// Null if there's no value or an empty string
			$where = Validation::validateOptionalString($body, 'where', null, null);
			if($where !== null) {
				$where = new ItemCode($where);
			}
			try {
				$moved = self::doBulkMove($items, $where, $db);
			} catch(\Exception $e) {
				$error = $e->getMessage();
				if($e instanceof \InvalidArgumentException || $e instanceof InvalidPayloadParameterException) {
					$code = 400;
				} else {
					$code = 500;
				}
			}
		}
		$request = $request
			->withAttribute('Template', 'bulk::move')
			->withAttribute('TemplateParameters', ['error' => $error, 'moved' => $moved]);
		$response = $response
			->withStatus($code);
		return $next ? $next($request, $response) : $response;
	}


	public static function bulkAdd(Request $request, Response $response, ?callable $next = null): Response {
		///* @var Database $db */
		//$db = $request->getAttribute('Database');
		$user = $request->getAttribute('User');
		$body = $request->getParsedBody();

		Validation::authorize($user);

		if($body === null) {
			// Opened page, didn't submit anything yet
			$request = $request
				->withAttribute('Template', 'bulk::add');
			$response = $response
				->withStatus(200);
		} else {
			$add = json_decode((string) $body['add'], true);
			if($add === null || json_last_error() !== JSON_ERROR_NONE) {
				$request = $request
					->withAttribute('Template', 'bulk::add')
					->withAttribute('TemplateParameters', ['error' => json_last_error_msg()]);
				$response = $response
					->withStatus(400);
			} else {
				// TODO: move to an ItemBuilder class?
				$items = [];
				foreach($add as $stuff) {
					$item = new ItemIncomplete(null);
					foreach($stuff as $k => $v) {
						$item->addFeature(new Feature($k, $v));
					}
					$items[] = $item;
				}

				foreach($items as $k => $item) {
					$items[$k] = ItemValidator::fillWithDefaults($item);
				}
				unset($item);
				$case = ItemValidator::treeify($items);
				ItemValidator::fixupFromPeracotta($case);

				$request = $request
					->withAttribute('Template', 'bulk::add')
					->withAttribute('TemplateParameters', ['item' => $case]);
				$response = $response
					->withStatus(200);
			}
		}
		return $next ? $next($request, $response) : $response;
	}

	public static function getFeaturesJson(Request $request, Response $response, ?callable $next = null): Response {
		// They aren't changing >1 time per second, so this should be stable enough for the ETag header...
		$lastmod1 = ItemValidator::defaultFeaturesLastModified();
		$lastmod2 = BaseFeature::featuresLastModified();
		$language = 'en';
		$etag = "$lastmod1$lastmod2$language";

		$response = $response
			->withHeader('Etag', $etag)
			->withHeader('Cache-Control', 'public, max-age=36000');

		$cachedEtags = $request->getHeader('If-None-Match');
		foreach($cachedEtags as $cachedEtag) {
			if($cachedEtag === $etag) {
				$response = $response
					->withStatus(304);
				return $next ? $next($request, $response) : $response;
			}
		}

		$defaults = [];
		foreach(Feature::features['type'] as $type => $useless) {
			$defaults[$type] = ItemValidator::getDefaultFeatures($type);
		}
		//$defaults[null] = ItemValidator::getDefaultFeatures('');

		$response->getBody()->write(json_encode([
			'features' => FeaturePrinter::getAllFeatures(),
			'defaults' => $defaults,
		]));

		$response = $response
			->withHeader('Content-Type', 'text/json');

		return $next ? $next($request, $response) : $response;
	}

	public static function getDispatcher(string $cachefile): FastRoute\Dispatcher {
		return FastRoute\cachedDispatcher(
			function(FastRoute\RouteCollector $r) {
				$r->addRoute(['GET', 'POST'], '/login', [[Controller::class, 'login']]);
				$r->addRoute(['GET', 'POST'], '/options', [[Controller::class, 'options']]);
				$r->get('/logout', [[Controller::class, 'logout']]);

				$r->get('/', [[Controller::class, 'getHome']]);
				$r->get('', [[Controller::class, 'getHome']]);
				$r->get('/features.json', [[Controller::class, 'getFeaturesJson']]);
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
				$r->get('/bulk', [[Controller::class, 'bulk']]);
				$r->addRoute(['GET', 'POST'], '/bulk/move', [[Controller::class, 'bulkMove']]);
				$r->addRoute(['GET', 'POST'], '/bulk/add', [[Controller::class, 'bulkAdd']]);
				$r->addGroup(
					'/stats',
					function(FastRoute\RouteCollector $r) {
						$r->get('', [[Controller::class, 'getStats']]);
						$r->get('/{which}', [[Controller::class, 'getStats']]);
					}
				);
			},
			[
				'cacheFile' => $cachefile,
				'cacheDisabled' => !TARALLO_CACHE_ENABLED,
			]
		);
	}

	public static function handle(Request $request): Response {
		$queue = [
			new DatabaseConnection(),
			//new LanguageNegotiatior(),
			new TemplateEngine(),
			[self::class, 'handleExceptions'],
		];

		$response = new \Slim\Http\Response();
		$response = $response
			->withHeader('Content-Type', 'text/html')
			->withBody(new Body(fopen('php://memory', 'r+')));

		$route = self::route($request);

		switch($route[0]) {
			case FastRoute\Dispatcher::FOUND:
				$queue = array_merge($queue, [[static::class, 'doTransaction']], $route[1]);
				$request = $request
					->withAttribute('parameters', $route[2]);
				$response = $response
					->withStatus(200);
				break;
			case FastRoute\Dispatcher::NOT_FOUND:
				$request = $request
					->withAttribute('Template', 'error')
					->withAttribute('TemplateParameters', ['reason' => 'Invalid URL (no route in router)']);
				$response = $response->withStatus(404);
				break;
			case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
				$request = $request
					->withAttribute('Template', 'error');
				$response = $response->withStatus(405)
					->withHeader('Allow', implode(', ', $route[1]));
				break;
			default:
				$request = $request
					->withAttribute('Template', 'error')
					->withAttribute('TemplateParameters', ['reason' => 'SSR Error: unknown router result']);
				$response = $response->withStatus(500);
				break;
		}

		unset($route);

		$queue = array_merge($queue, [[static::class, 'renderResponse']]);

		$relayBuilder = new RelayBuilder();
		$relay = $relayBuilder->newInstance($queue);

		return $relay($request, $response);
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

			$engine->addData(
				[
					'user' => $request->getAttribute('User'),
					'self' => $request->getUri()->getPath(),
					'request' => $request,
					'response' => $response,
				]
			);

			$response->getBody()->rewind();
			$response->getBody()->write($engine->render($template, $request->getAttribute('TemplateParameters', [])));
		}

		if($next) {
			return $next($request, $response);
		} else {
			return $response;
		}
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @param callable|null $next
	 *
	 * @return Response
	 * @deprecated see https://github.com/WEEE-Open/tarallo/issues/50
	 */
	public static function handleExceptions(
		Request $request,
		Response $response,
		?callable $next = null
	): Response {
		if($next) {
			try {
				return $next($request, $response);
			} catch(AuthenticationException $e) {
				$request = $request
					->withAttribute('Template', 'error')
					->withAttribute('TemplateParameters', ['reasonNoEscape' => '<a href="/login">Please authenticate</a>']);
				$response = $response
					->withStatus(401)
					->withHeader('WWW-Authenticate', 'login');
			} catch(AuthorizationException $e) {
				$request = $request
					->withAttribute('Template', 'error')
					->withAttribute('TemplateParameters', []);
				$response = $response
					->withStatus(403);
			} catch(NotFoundException $e) {
				$request = $request
					->withAttribute('Template', 'error')
					->withAttribute('TemplateParameters', ['reason' => 'Whatever you\'re looking for, it doesn\'t exist.']);
				$response = $response
					->withStatus(404);
			} catch(\Throwable $e) {
				$request = $request
					->withAttribute('Template', 'error')
					->withAttribute('TemplateParameters', ['reason' => $e->getMessage()]);
				$response = $response
					->withStatus(500);
			}

			// This thing. It's horrible. It shouldn't be here.
			return self::renderResponse($request, $response);
		} else {
			return $response;
		}
	}

	/**
	 * Parse the file/input format for bulk move operations and do whatever's needed
	 *
	 * @param string $itemsList Items list, in string format
	 * @param ItemCode|null $defaultLocation Default location for items without a location in the list
	 * @param Database $db Our dear database
	 * @param bool $fix Perform fixup
	 * @param bool $validate Perform validation
	 *
	 * @return array [ string item => (string) its new location ]
	 * @throws \InvalidArgumentException if syntax or logic of the inputs doesn't make sense
	 * @throws \Exception whatever may surface from TreeDAO::moveWithValidation
	 */
	public static function doBulkMove(
		string $itemsList,
		?ItemCode $defaultLocation,
		Database $db,
		bool $fix = true,
		bool $validate = true
	): array {
		$moved = [];
		if(strpos($itemsList, ',') === false) {
			$array = explode("\n", $itemsList);
		} else {
			$array = explode(',', $itemsList);
		}

		foreach($array as $line) {
			$line = trim($line);
			if($line === '') {
				// Skip empty lines (trailing commas, two consecutive commas, etc...)
				continue;
			}
			$lineExploded = explode(':', $line);
			if(count($lineExploded) == 1) {
				$item = new ItemCode(trim($lineExploded[0]));
				if($defaultLocation === null) {
					throw new \InvalidArgumentException("No location provided for $line and no default location", 1);
				} else {
					$location = $defaultLocation;
				}
			} else if(count($lineExploded) == 2) {
				$item = new ItemCode(trim($lineExploded[0]));
				$location = new ItemCode(trim($lineExploded[1]));
			} else {
				throw new \InvalidArgumentException("Invalid format for \"$line\", too many separators (:)", 2);
			}
			// This may throw and leave the function
			TreeDAO::moveWithValidation($db, $item, $location, $fix, $validate);
			$moved[$item->getCode()] = $location->getCode();
		}
		return $moved;
	}

}
