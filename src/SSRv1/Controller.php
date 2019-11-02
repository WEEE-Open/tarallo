<?php

namespace WEEEOpen\Tarallo\SSRv1;

use FastRoute;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Relay\RelayBuilder;
use WEEEOpen\Tarallo\BaseFeature;
use WEEEOpen\Tarallo\Database\Database;
use WEEEOpen\Tarallo\Database\TreeDAO;
use WEEEOpen\Tarallo\ErrorHandler;
use WEEEOpen\Tarallo\Feature;
use WEEEOpen\Tarallo\HTTP\AuthManager;
use WEEEOpen\Tarallo\HTTP\AuthValidator;
use WEEEOpen\Tarallo\HTTP\DatabaseConnection;
use WEEEOpen\Tarallo\HTTP\TransactionWrapper;
use WEEEOpen\Tarallo\HTTP\Validation;
use WEEEOpen\Tarallo\ItemCode;
use WEEEOpen\Tarallo\ItemIncomplete;
use WEEEOpen\Tarallo\ItemValidator;
use WEEEOpen\Tarallo\NotFoundException;
use WEEEOpen\Tarallo\ValidationException;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\Response\RedirectResponse;
use Zend\Diactoros\UploadedFile;


class Controller implements RequestHandlerInterface {
	const cachefile = __DIR__ . '/router.cache';

	public static function getItem(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
		/** @var Database $db */
		$db = $request->getAttribute('Database');
		$query = $request->getQueryParams();

		$parameters = $request->getAttribute('parameters', []);
		// So things aren't url-decoded automatically...
		$id = urldecode(Validation::validateOptionalString($parameters, 'id', null));
		$edit = Validation::validateOptionalString($parameters, 'edit', null);
		$add = Validation::validateOptionalString($parameters, 'add', null);
		$depth = Validation::validateOptionalInt($query, 'depth', 20);

		try {
			$ii = new ItemCode($id);
		} catch(ValidationException $e) {
			if($e->getCode() === 3) {
				$request = $request->withAttribute('Template', 'error')->withAttribute('ResponseCode', 404)->withAttribute('TemplateParameters', ['reason' => "Code '$id' contains invalid characters"]);
				return $handler->handle($request);
			}
			throw $e;
		}

		$item = $db->itemDAO()->getItem($ii, null, $depth);
		$renderParameters = ['item' => $item];
		// These should be mutually exclusive
		if($edit !== null) {
			$renderParameters['add'] = null;
			$renderParameters['edit'] = $edit;
		} else {
			if($add !== null) {
				$renderParameters['add'] = $add;
				$renderParameters['edit'] = null;
			} else {
				$renderParameters['add'] = null;
				$renderParameters['edit'] = null;
			}
		}

		$request = $request->withAttribute('Template', 'viewItem')->withAttribute(
			'TemplateParameters', $renderParameters
		);

		return $handler->handle($request);
	}

	public static function getHistory(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
		/** @var Database $db */
		$db = $request->getAttribute('Database');
		$query = $request->getQueryParams();
		$parameters = $request->getAttribute('parameters', []);
		$id = Validation::validateOptionalString($parameters, 'id', null);
		$limit = Validation::validateOptionalInt($query, 'limit', 20);
		if($limit > 100) {
			$limit = 100;
		} else {
			if($limit <= 0) {
				$limit = 20;
			}
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

		$request = $request->withAttribute('Template', 'history')->withAttribute(
			'TemplateParameters', ['item' => $item, 'history' => $history, 'tooLong' => $tooLong]
		);

		return $handler->handle($request);
	}

	public static function addItem(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
		$query = $request->getQueryParams();
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

		$request = $request->withAttribute('Template', 'newItemPage')->withAttribute(
			'TemplateParameters', ['add' => true, 'base' => $from]
		);

		return $handler->handle($request);
	}

	public static function authError(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
		$request = $request
			->withAttribute('Template', 'error')
			->withAttribute('ResponseCode', 400)
			->withAttribute('TemplateParameters', ['reason' => 'Login failed']);

		return $handler->handle($request);
	}

	public static function logout(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
		$request = $request->withAttribute('Template', 'logout');

		return $handler->handle($request);
	}

	public static function getHome(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
		$db = $request->getAttribute('Database');

		$request = $request->withAttribute('Template', 'home')->withAttribute(
			'TemplateParameters', [
				'locations' => $locations = $db->statsDAO()->getLocationsByItems(),
				'recentlyAdded' => $db->auditDAO()->getRecentAuditByType('C', max(20, count($locations))),
			]
		);

		return $handler->handle($request);
	}

	public static function getStats(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
		/** @var Database $db */
		$db = $request->getAttribute('Database');
		$query = $request->getQueryParams();
		$parameters = $request->getAttribute('parameters', ['which' => null]);
		// a nice default value: 'now - 1 year'
		$startDateDefault = '2016-01-01';
		$startDate = Validation::validateOptionalString($query, 'from', $startDateDefault, null);
		$startDateSet = $startDate !== $startDateDefault;
		/** @noinspection PhpUnhandledExceptionInspection */
		$startDate = new \DateTime($startDate, new \DateTimeZone('Europe/Rome'));

		switch($parameters['which']) {
			case '':
				$request = $request->withAttribute('Template', 'stats::main')->withAttribute(
					'TemplateParameters', [
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
					$todos[$possibileTodo] = $db->statsDAO()->getItemsByFeatures(
						new Feature('todo', $possibileTodo), null, 100
					);
				}

				$request = $request->withAttribute('Template', 'stats::todo')->withAttribute(
					'TemplateParameters', ['todos' => $todos]
				);
				break;

			case 'attention':
				$request = $request->withAttribute('Template', 'stats::needAttention')->withAttribute(
					'TemplateParameters', [
						'serials' => $db->statsDAO()->getCountByFeature('sn', null, null, null, false, 2),
						'missingData' => $db->statsDAO()->getItemsByFeatures(
							new Feature('check', 'missing-data'), null, 500
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

				$request = $request->withAttribute('Template', 'stats::cases')->withAttribute(
					'TemplateParameters', [
						'location' => $location === null ? null : $location->getCode(),
						'locationSet' => $locationSet,
						'startDate' => $startDate,
						'startDateSet' => $startDateSet,
						'leastRecent' => $db->statsDAO()->getModifiedItems($location, false, 30),
						'mostRecent' => $db->statsDAO()->getModifiedItems($location, true, 30),
						'byOwner' => $db->statsDAO()->getCountByFeature(
							'owner', new Feature('type', 'case'), $location, $startDate
						),
						'byMobo' => $db->statsDAO()->getCountByFeature(
							'motherboard-form-factor', new Feature('type', 'case'), $location, $startDate
						),
						'ready' => $db->statsDAO()->getItemsByFeatures(
							new Feature('restrictions', 'ready'), $location, 100
						),
					]
				);
				break;

			case 'rams':
				$locationDefault = 'Rambox';
				$location = Validation::validateOptionalString($query, 'where', $locationDefault, null);
				$locationSet = $location !== $locationDefault;
				$location = $location === null ? null : new ItemCode($location);

				$request = $request->withAttribute('Template', 'stats::rams')->withAttribute(
					'TemplateParameters', [
						'location' => $location === null ? null : $location->getCode(),
						'locationSet' => $locationSet,
						'startDate' => $startDate,
						'startDateSet' => $startDateSet,
						'byType' => $db->statsDAO()->getCountByFeature(
							'ram-type', new Feature('type', 'ram'), $location
						),
						'byFormFactor' => $db->statsDAO()->getCountByFeature(
							'ram-form-factor', new Feature('type', 'ram'), $location
						),
						'bySize' => $db->statsDAO()->getCountByFeature(
							'capacity-byte', new Feature('type', 'ram'), $location
						),
						'byTypeFrequency' => $db->statsDAO()->getRollupCountByFeature(
							new Feature('type', 'ram'), ['ram-type', 'ram-form-factor', 'frequency-hertz'], $location
						),
						'byTypeSize' => $db->statsDAO()->getRollupCountByFeature(
							new Feature('type', 'ram'), ['ram-type', 'ram-form-factor', 'capacity-byte'], $location
						),
						'noWorking' => $db->statsDAO()->getItemByNotFeature(
							new Feature('type', 'ram'), 'working', $location, 200
						),
						'noFrequency' => $db->statsDAO()->getItemByNotFeature(
							new Feature('type', 'ram'), 'frequency-hertz', $location, 200
						),
						'noSize' => $db->statsDAO()->getItemByNotFeature(
							new Feature('type', 'ram'), 'capacity-byte', $location, 200
						),
					]
				);
				break;

			default:
				// TODO: if this gets used only for items (and the page suggesting items), change to something else
				throw new NotFoundException();
		}

		return $handler->handle($request);
	}

	public static function search(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
		$db = $request->getAttribute('Database');
		$parameters = $request->getAttribute('parameters', []);
		$query = $request->getQueryParams();
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
			} else {
				if($edit !== null) {
					$templateParameters['edit'] = $edit;
				}
			}
		}

		$request = $request->withAttribute('Template', 'search')->withAttribute(
			'TemplateParameters', $templateParameters
		);

		return $handler->handle($request);
	}

	public static function bulk(
		/** @noinspection PhpUnusedParameterInspection */ ServerRequestInterface $request, RequestHandlerInterface $handler
	): ResponseInterface {
		$response = new RedirectResponse('/bulk/move', 303);
		$response->withoutHeader('Content-type');

		return $response;
	}

	public static function bulkMove(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
		$db = $request->getAttribute('Database');
		$body = $request->getParsedBody();

		if($body === null) {
			// Opened page, didn't submit anything yet
			$items = null;
		} else {
			/** @var UploadedFile[] $uploaded */
			$uploaded = $request->getUploadedFiles();
			if(count($uploaded) === 0 || !isset($uploaded['Fitems'])) {
				$items = (string) $body['items'];
			} else {
				if($uploaded['Fitems']->getError() !== UploadedFile::ERROR_MESSAGES['UPLOAD_ERR_OK']) {
					$items = $uploaded['Fitems']->getStream()->getContents();
					if($items === false) {
						// TODO: throw some other exception
						throw new \LogicException('Cannot open temporary file');
					}
				} else {
					// TODO: throw some other exception
					throw new \LogicException(UploadedFile::ERROR_MESSAGES[$uploaded['Fitems']->getError()]);
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
			} catch(\Exception $e) { // TODO: catch specific exceptions (when an item is not found, it's too generic)
				$error = $e->getMessage();
				if($e instanceof \InvalidArgumentException || $e instanceof ValidationException) {
					$code = 400;
				} else {
					$code = 500;
				}
			}
		}
		$request = $request
			->withAttribute('Template', 'bulk::move')
			->withAttribute('StatusCode', $code)
			->withAttribute('TemplateParameters', ['error' => $error, 'moved' => $moved]);

		return $handler->handle($request);
	}


	public static function bulkAdd(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
		///* @var Database $db */
		//$db = $request->getAttribute('Database');
		$body = $request->getParsedBody();

		if($body === null) {
			// Opened page, didn't submit anything yet
			$request = $request->withAttribute('Template', 'bulk::add');
		} else {
			$add = json_decode((string) $body['add'], true);
			if($add === null || json_last_error() !== JSON_ERROR_NONE) {
				$request = $request->withAttribute('Template', 'bulk::add')->withAttribute('StatusCode', 400)->withAttribute('TemplateParameters', ['error' => json_last_error_msg()]);
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

				$request = $request->withAttribute('Template', 'bulk::add')->withAttribute(
					'TemplateParameters', ['item' => $case]
				);
			}
		}

		return $handler->handle($request);
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
		string $itemsList, ?ItemCode $defaultLocation, Database $db, bool $fix = true, bool $validate = true
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
			} else {
				if(count($lineExploded) == 2) {
					$item = new ItemCode(trim($lineExploded[0]));
					$location = new ItemCode(trim($lineExploded[1]));
				} else {
					throw new \InvalidArgumentException("Invalid format for \"$line\", too many separators (:)", 2);
				}
			}
			// This may throw and leave the function
			TreeDAO::moveWithValidation($db, $item, $location, $fix, $validate);
			$moved[$item->getCode()] = $location->getCode();
		}
		return $moved;
	}

	public static function getFeaturesJson(
		/** @noinspection PhpUnusedParameterInspection */ ServerRequestInterface $request, RequestHandlerInterface $handler
	): ResponseInterface {
		// They aren't changing >1 time per second, so this should be stable enough for the ETag header...
		$lastmod1 = ItemValidator::defaultFeaturesLastModified();
		$lastmod2 = BaseFeature::featuresLastModified();
		$language = 'en';
		$etag = "$lastmod1$lastmod2$language";

		$responseHeaders = ['Etag' => $etag, 'Cache-Control' => 'public, max-age=36000'];

		$cachedEtags = $request->getHeader('If-None-Match');
		foreach($cachedEtags as $cachedEtag) {
			if($cachedEtag === $etag) {
				return new EmptyResponse(304, $responseHeaders);
			}
		}

		$defaults = [];
		foreach(Feature::features['type'] as $type => $useless) {
			$defaults[$type] = ItemValidator::getDefaultFeatures($type);
		}

		$json = [
			'features' => FeaturePrinter::getAllFeatures(),
			'defaults' => $defaults,
		];

		return new JsonResponse($json, 200, $responseHeaders);
	}

	public function handle(ServerRequestInterface $request): ResponseInterface {
		$route = $this->route($request);

		switch($route[0]) {
			case FastRoute\Dispatcher::FOUND:
				$level = $route[1][0];
				$function = $route[1][1];
				$request = $request
					->withAttribute('parameters', $route[2]);
				break;
			case FastRoute\Dispatcher::NOT_FOUND:
				$level = null;
				$function = null;
				$request = $request
					->withAttribute('Template', 'error')
					->withAttribute('TemplateParameters', ['reason' => 'Invalid URL (no route in router)'])
					->withAttribute('ResponseCode', 404);
				break;
			case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
				$level = null;
				$function = null;
				$request = $request
					->withAttribute('Template', 'error')
					->withAttribute('ReasponseHeaders', ['Allow' => implode(', ', $route[1])])
					->withAttribute('ResponseCode', 405);
				break;
			default:
				$level = null;
				$function = null;
				$request = $request
					->withAttribute('Template', 'error')
					->withAttribute('TemplateParameters', ['reason' => 'SSR Error: unknown router result'])
					->withAttribute('ResponseCode', 500);
				break;
		}
		unset($route);

		$queue = [
			new ErrorHandler(),
			new DatabaseConnection(),
			//LanguageNegotiatior::class,
			new AuthManager(),
			new TemplateEngine(),
			new GracefulExceptionHandler(),
		];
		if($level !== null) {
			$queue[] = new AuthValidator($level);
		}
		if($function !== null) {
			$queue[] = new TransactionWrapper();
			$queue[] = 'WEEEOpen\\Tarallo\\SSRv1\\' . $function;
		}
		$queue[] = new TemplateRender();

		$relayBuilder = new RelayBuilder();
		$relay = $relayBuilder->newInstance($queue);

		return $relay->handle($request);
	}

	private function route(ServerRequestInterface $request): array {
		$dispatcher = FastRoute\cachedDispatcher(
			function(FastRoute\RouteCollector $r) {
				$r->get('/auth', [null, 'Controller::authError']);
				$r->get('/logout', [null, 'Controller::logout']);

				$r->get('/', [AuthValidator::AUTH_LEVEL_RO, 'Controller::getHome']);
				$r->get('', [AuthValidator::AUTH_LEVEL_RO, 'Controller::getHome']);
				$r->get('/features.json', [AuthValidator::AUTH_LEVEL_RO, 'Controller::getFeaturesJson']);
				$r->get('/item/{id}', [AuthValidator::AUTH_LEVEL_RO, 'Controller::getItem']);
				$r->get('/history/{id}', [AuthValidator::AUTH_LEVEL_RO, 'Controller::getHistory']);
				$r->get('/item/{id}/add/{add}', [AuthValidator::AUTH_LEVEL_RO, 'Controller::getItem']);
				$r->get('/item/{id}/edit/{edit}', [AuthValidator::AUTH_LEVEL_RO, 'Controller::getItem']);
				$r->get('/add', [AuthValidator::AUTH_LEVEL_RO, 'Controller::addItem']);
				$r->get('/search[/{id:[0-9]+}[/page/{page:[0-9]+}]]', [AuthValidator::AUTH_LEVEL_RO, 'Controller::search']);
				$r->get('/search/{id:[0-9]+}/add/{add}', [AuthValidator::AUTH_LEVEL_RO, 'Controller::search']);
				$r->get('/search/{id:[0-9]+}/page/{page:[0-9]+}/add/{add}', [AuthValidator::AUTH_LEVEL_RO, 'Controller::search']);
				$r->get('/search/{id:[0-9]+}/edit/{edit}', [AuthValidator::AUTH_LEVEL_RO, 'Controller::search']);
				$r->get('/search/{id:[0-9]+}/page/{page:[0-9]+}/edit/{edit}', [AuthValidator::AUTH_LEVEL_RO, 'Controller::search']);
				$r->get('/bulk', [AuthValidator::AUTH_LEVEL_RO, 'Controller::bulk']);
				$r->get('/bulk/move', [AuthValidator::AUTH_LEVEL_RO, 'Controller::bulkMove']);
				$r->post('/bulk/move', [AuthValidator::AUTH_LEVEL_RW, 'Controller::bulkMove']);
				$r->get('/bulk/add', [AuthValidator::AUTH_LEVEL_RO, 'Controller::bulkAdd']);
				$r->post('/bulk/add', [AuthValidator::AUTH_LEVEL_RW, 'Controller::bulkAdd']);
				$r->addGroup(
					'/stats', function(FastRoute\RouteCollector $r) {
					$r->get('', [AuthValidator::AUTH_LEVEL_RO, 'Controller::getStats']);
					$r->get('/{which}', [AuthValidator::AUTH_LEVEL_RO, 'Controller::getStats']);
				}
				);
			}, [
				'cacheFile' => self::cachefile,
				'cacheDisabled' => !TARALLO_CACHE_ENABLED,
			]
		);

		return $dispatcher->dispatch($request->getMethod(), $request->getUri()->getPath());
	}

}
