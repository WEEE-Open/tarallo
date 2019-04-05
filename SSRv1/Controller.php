<?php

namespace WEEEOpen\Tarallo\SSRv1;

use FastRoute;
use League\Plates\Engine;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Relay\RelayBuilder;
use Slim\Http\Body;
use WEEEOpen\Tarallo\Server\Database\Database;
use WEEEOpen\Tarallo\Server\Database\TreeDAO;
use WEEEOpen\Tarallo\Server\Feature;
use WEEEOpen\Tarallo\Server\HTTP\AbstractController;
use WEEEOpen\Tarallo\Server\HTTP\AuthenticationException;
use WEEEOpen\Tarallo\Server\HTTP\AuthorizationException;
use WEEEOpen\Tarallo\Server\HTTP\DatabaseConnection;
use WEEEOpen\Tarallo\Server\HTTP\LanguageNegotiatior;
use WEEEOpen\Tarallo\Server\HTTP\Validation;
use WEEEOpen\Tarallo\Server\ItemIncomplete;
use WEEEOpen\Tarallo\Server\NotFoundException;
use WEEEOpen\Tarallo\Server\Session;
use WEEEOpen\Tarallo\Server\User;


class Controller extends AbstractController {
	const cachefile = __DIR__ . '/router.cache';

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
		$query = $request->getQueryParams();
		$user = $request->getAttribute('User');

		Validation::authorize($user);

		$from = Validation::validateOptionalString($query, 'copy', null);

		if($from !== null) {
			/** @var Database $db */
			$db = $request->getAttribute('Database');
			$from = $db->itemDAO()->getItem(new ItemIncomplete($from));
		}

		$request = $request
			->withAttribute('Template', 'newItem')
			->withAttribute('TemplateParameters', ['add' => true, 'copy' => $from]);

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
			->withAttribute('TemplateParameters',
				[
					'locations' => $locations = $db->statsDAO()->getLocationsByItems(),
					'recentlyAdded' => $db->auditDAO()->getRecentAuditByType('C', max(20, count($locations)))
				]);

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
					->withAttribute('TemplateParameters',
						[
							'locations' => $db->statsDAO()->getLocationsByItems(),
							'recentlyAdded'	=> $db->auditDAO()->getRecentAuditByType('C', 40),
							'recentlyModified' => $db->auditDAO()->getRecentAuditByType('M', 40),
						]);
				break;

			case 'attention':
				$request = $request
					->withAttribute('Template', 'stats::needAttention')
					->withAttribute('TemplateParameters',
						[
							'serials' => $db->statsDAO()->getCountByFeature('sn', null, null, null, false, 2),
							'missingData' => $db->statsDAO()->getItemsByFeatures(new Feature('check', 'missing-data'), null, 500),
							'lost' => $db->statsDAO()->getItemsByFeatures(new Feature('check', 'lost'), null, 100)
						]);
				break;

			case 'cases':
				$locationDefault = 'LabFis4';
				$location = Validation::validateOptionalString($query, 'where', $locationDefault, null);
				$locationSet = $location !== $locationDefault;
				$location = $location === null ? null : new ItemIncomplete($location);

				$request = $request
					->withAttribute('Template', 'stats::cases')
					->withAttribute('TemplateParameters',
						[
							'location' => $location === null ? null : $location->getCode(),
							'locationSet' => $locationSet,
							'startDate' => $startDate,
							'startDateSet'=> $startDateSet,
							'leastRecent' => $db->statsDAO()->getModifiedItems($location, false, 30),
							'mostRecent' => $db->statsDAO()->getModifiedItems($location, true, 30),
							'byOwner' => $db->statsDAO()->getCountByFeature('owner', new Feature('type', 'case'), $location, $startDate),
							'byMobo' => $db->statsDAO()->getCountByFeature('motherboard-form-factor', new Feature('type', 'case'), $location, $startDate),
							'ready' => $db->statsDAO()->getItemsByFeatures(new Feature('restrictions', 'ready'), $location, 100),
						]);
				break;

			case 'rams':
				$locationDefault = 'Rambox';
				$location = Validation::validateOptionalString($query, 'where', $locationDefault, null);
				$locationSet = $location !== $locationDefault;
				$location = $location === null ? null : new ItemIncomplete($location);

				$request = $request
					->withAttribute('Template', 'stats::rams')
					->withAttribute('TemplateParameters',
						[
							'location' => $location === null ? null : $location->getCode(),
							'locationSet' => $locationSet,
							'startDate' => $startDate,
							'startDateSet'=> $startDateSet,
							'byType' => $db->statsDAO()->getCountByFeature('ram-type', new Feature('type', 'ram'), $location),
							'byFormFactor'=> $db->statsDAO()->getCountByFeature('ram-form-factor', new Feature('type', 'ram'), $location),
							'bySize'=> $db->statsDAO()->getCountByFeature('capacity-byte', new Feature('type', 'ram'), $location),
							'byTypeFrequency' => $db->statsDAO()->getRollupCountByFeature(new Feature('type', 'ram'), ['ram-type', 'ram-form-factor', 'frequency-hertz'], $location),
							'byTypeSize' => $db->statsDAO()->getRollupCountByFeature(new Feature('type', 'ram'), ['ram-type', 'ram-form-factor', 'capacity-byte'], $location),
							'noWorking' => $db->statsDAO()->getItemByNotFeature(new Feature('type', 'ram'), 'working', $location, 200),
							'noFrequency' => $db->statsDAO()->getItemByNotFeature(new Feature('type', 'ram'), 'frequency-hertz', $location, 200),
							'noSize' => $db->statsDAO()->getItemByNotFeature(new Feature('type', 'ram'), 'capacity-byte', $location, 200),
						]);
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


    public static function moveAll(Request $request, Response $response, ?callable $next = null): Response {
        $db = $request->getAttribute('Database');
        $body = $request->getParsedBody();
        if(!empty($_FILES['Fitems']['tmp_name'])) {
	        $file = $_FILES['Fitems'];
            $items = file_get_contents($file['tmp_name']);
            if($items === false)
	        	throw new \LogicException("Errore nell' apertura del file");
            $items = trim($items);
        } else
            $items = $body['items'] !== "Lista degli oggetti" ? (string)trim($body['items']) : null;
        if(!empty($items)) {
            $where = Validation::validateOptionalString($body, 'where');
            $array = explode(",", $items);
            foreach($array as $oggetto) {
                if($where === '') {
                    $param = explode(":", $oggetto);
                    if(count($param) != 2)
                        throw new \LogicException("Formato non valido");
                    $oggetto = $param[0];
                    $location = $param[1];
                } else
                    $location = $where;
                try {
                    TreeDAO::moveWithValidation($db, new ItemIncomplete($oggetto), new ItemIncomplete($location), true, true);
                } catch(Exception $e) {
                    throw new Exception($e->getMessage());
                }
            }
        }
        $request = $request->withAttribute('Template', 'moveAll');
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

	public static function getDispatcher(string $cachefile): FastRoute\Dispatcher {
		return FastRoute\cachedDispatcher(function(FastRoute\RouteCollector $r) {
			// TODO: [new RateLimit(), [Controller::class, 'login']] or something like that
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
			$r->addRoute(['GET', 'POST'], '/moveAll', [[Controller::class, 'moveAll']]);

			$r->addGroup('/stats', function(FastRoute\RouteCollector $r) {
				$r->get('', [[Controller::class, 'getStats']]);
				$r->get('/{which}', [[Controller::class, 'getStats']]);
			});
		}, [
			'cacheFile' => $cachefile,
			'cacheDisabled' => !CACHE_ENABLED,
		]);
	}

	public static function handle(Request $request): Response {
		$queue = [
			new DatabaseConnection(),
			new LanguageNegotiatior(),
			new TemplateEngine(),
			[self::class, 'handleExceptions']
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

			// TODO: remove addData, read attrbitues in templates directly
			$engine->addData([
				'user' => $request->getAttribute('User'),
				'self' => $request->getUri()->getPath(),
				'request' => $request,
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
				$request = $request
					->withAttribute('Template', 'notAuthenticated')
					->withAttribute('TemplateParameters', []);
				$response = $response
					->withStatus(401)
					->withHeader('WWW-Authenticate', 'login');
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
