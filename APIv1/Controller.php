<?php

namespace WEEEOpen\Tarallo\APIv1;

use FastRoute;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Relay\RelayBuilder;
use Slim\Http\Body;
use WEEEOpen\Tarallo\Server\Database\Database;
use WEEEOpen\Tarallo\Server\Database\DatabaseException;
use WEEEOpen\Tarallo\Server\Database\ItemDAO;
use WEEEOpen\Tarallo\Server\Database\TreeDAO;
use WEEEOpen\Tarallo\Server\Feature;
use WEEEOpen\Tarallo\Server\HTTP\AbstractController;
use WEEEOpen\Tarallo\Server\HTTP\AuthenticationException;
use WEEEOpen\Tarallo\Server\HTTP\AuthorizationException;
use WEEEOpen\Tarallo\Server\HTTP\DatabaseConnection;
use WEEEOpen\Tarallo\Server\HTTP\InvalidPayloadParameterException;
use WEEEOpen\Tarallo\Server\HTTP\Validation;
use WEEEOpen\Tarallo\Server\ItemFeatures;
use WEEEOpen\Tarallo\Server\ItemIncomplete;
use WEEEOpen\Tarallo\Server\ItemLocationValidator;
use WEEEOpen\Tarallo\Server\ItemNestingException;
use WEEEOpen\Tarallo\Server\NotFoundException;
use WEEEOpen\Tarallo\Server\Session;


class Controller extends AbstractController {
	const cachefile = __DIR__ . '/router.cache';

	public static function sessionWhoami(Request $request, Response $response, ?callable $next = null): Response {
		$user = $request->getAttribute('User');

		Validation::authorize($user, 3);

		$request = $request
			->withAttribute('Status', JSend::SUCCESS)
			->withAttribute('Data', ['username' => $user->getUsername()]);

		return $next ? $next($request, $response) : $response;
	}

	public static function sessionStart(Request $request, Response $response, ?callable $next = null): Response {
		/** @var Database $db */
		$db = $request->getAttribute('Database');
		$payload = $request->getParsedBody();

		$username = Validation::validateHasString($payload, 'username');
		$password = Validation::validateHasString($payload, 'password');

		$user = $db->userDAO()->getUserFromLogin($username, $password);
		if($user === null) {
			throw new InvalidPayloadParameterException('*', '', 'Wrong username or password');
		}
		Session::start($user, $db);

		$response = $response
			->withStatus(204);

		return $next ? $next($request, $response) : $response;
	}

	public static function sessionClose(Request $request, Response $response, ?callable $next = null): Response {
		/** @var Database $db */
		$db = $request->getAttribute('Database');
		$user = $request->getAttribute('User');

		// If we ever add another level for e.g. banned users, this at least allows them to log out
		Validation::authenticate($user);
		Session::close($user, $db);

		$response = $response
			->withStatus(204);

		return $next ? $next($request, $response) : $response;
	}

	public static function sessionRefresh(Request $request, Response $response, ?callable $next = null): Response {
		$user = $request->getAttribute('User');

		// The refresh itself has already been done by Session::restore, sooooo...
		Validation::authenticate($user);

		$response = $response
			->withStatus(204);

		return $next ? $next($request, $response) : $response;
	}

	public static function getItem(Request $request, Response $response, ?callable $next = null): Response {
		/** @var Database $db */
		$db = $request->getAttribute('Database');
		$user = $request->getAttribute('User');
		$parameters = $request->getAttribute('parameters', []);

		$id = Validation::validateOptionalString($parameters, 'id');
		$token = Validation::validateOptionalString($parameters, 'token');
		$depth = Validation::validateOptionalString($parameters, 'depth');

		if($token === null) {
			Validation::authorize($user, 3);
		}

		if($id === null) {
			throw new \LogicException('Not implemented');
		} else {
			$data = $db->itemDAO()->getItem(new ItemIncomplete($id), $token, $depth);

			$request = $request
				->withAttribute('Data', $data);
			$response = $response
				->withStatus(200);
		}

		return $next ? $next($request, $response) : $response;
	}

	public static function getByFeature(Request $request, Response $response, ?callable $next = null): Response {
		/** @var Database $db */
		$db = $request->getAttribute('Database');
		$user = $request->getAttribute('User');
		$parameters = $request->getAttribute('parameters', []);

		Validation::authorize($user, 3);

		$id = (string) $parameters['feature'];
		$value = (string) $parameters['value'];
		$limit = Validation::validateOptionalInt($parameters, 'limit', 5);

		if($limit > 10) {
			throw new InvalidPayloadParameterException('limit', $limit, 'Maximum number of results is 10');
		} else if($limit < 1) {
			throw new InvalidPayloadParameterException('limit', $limit, 'Limit < 1 doesn\'t make sense');
		}

		try {
			if(Feature::getType($id) !== Feature::STRING) {
				throw new InvalidPayloadParameterException('*', $id, "Only text features are supported, $id isn't");
			}
		} catch(\InvalidArgumentException $e) {
			throw new InvalidPayloadParameterException('*', $value, $e->getMessage());
		}

		$feature = new Feature($id, $value);

		$data = $db->featureDAO()->getItemsByFeatures($feature, $limit);

		$request = $request
			->withAttribute('Data', $data);
		$response = $response
			->withStatus(200);

		return $next ? $next($request, $response) : $response;
	}

	public static function createItem(Request $request, Response $response, ?callable $next = null): Response {
		/** @var Database $db */
		$db = $request->getAttribute('Database');
		$user = $request->getAttribute('User');
		$query = $request->getQueryParams();
		$payload = $request->getParsedBody();
		$parameters = $request->getAttribute('parameters', []);

		Validation::authorize($user);

		$id = Validation::validateOptionalString($parameters, 'id');
		$fix = isset($query['fix']);
		$loopback = isset($query['loopback']);

		$item = ItemBuilder::ofArray($payload, $id, $parent);

		// Fixing nesting issues need the full parent item, which may not exist (but will be checked again later)
		if($fix && $parent instanceof ItemIncomplete) {
			try {
				$parent = $db->itemDAO()->getItem($parent, null, 1);
			} catch(NotFoundException $e) {
				throw new InvalidPayloadParameterException('parent', $parent->getCode(), 'Location doesn\'t exist');
			}
			ItemLocationValidator::reparentAll($item, $parent);
		}

		try {
			$db->itemDAO()->addItem($item, $parent);
		} catch(NotFoundException $e) {
			if($e->getCode() === TreeDAO::EXCEPTION_CODE_PARENT) {
				throw new InvalidPayloadParameterException('parent', $parent->getCode(),
					'Requested location doesn\'t exist');
			}
		} catch(\InvalidArgumentException $e) {
			if($e->getCode() === ItemDAO::EXCEPTION_CODE_GENERATE_ID) {
				throw new InvalidPayloadParameterException('code', null,
					'Cannot generate code for an item (missing "type"?)');
			} else {
				throw $e;
			}
		}

		// TODO: this should probably return 201 sometimes
		if($loopback) {
			$request = $request
				->withAttribute('Data', $db->itemDAO()->getItem($item));
			$response = $response
				->withStatus(200);
		} else {
			$request = $request
				->withAttribute('Data', $item->getCode());
			$response = $response
				->withStatus(200);
		}

		return $next ? $next($request, $response) : $response;
	}

	public static function removeItem(Request $request, Response $response, ?callable $next = null): Response {
		/** @var Database $db */
		$db = $request->getAttribute('Database');
		$user = $request->getAttribute('User');
		$parameters = $request->getAttribute('parameters', []);

		Validation::authorize($user);

		$id = Validation::validateOptionalString($parameters, 'id');

		$db->itemDAO()->deleteItem(new ItemIncomplete($id));

		$response = $response
			->withStatus(204);

		return $next ? $next($request, $response) : $response;
	}

	public static function setItemParent(Request $request, Response $response, ?callable $next = null): Response {
		/** @var Database $db */
		$db = $request->getAttribute('Database');
		$user = $request->getAttribute('User');
		$query = $request->getQueryParams();
		$payload = $request->getParsedBody();
		$parameters = $request->getAttribute('parameters', []);

		Validation::authorize($user);
		Validation::validateIsString($payload);

		$id = Validation::validateOptionalString($parameters, 'id');
		$fix = isset($query['fix']);
		$validate = !isset($query['novalidate']);

		// We'll need the full item in any case, not just an ItemIncomplete
		$item = $db->itemDAO()->getItem(new ItemIncomplete($id), null, 0);
		$hadParent = count($item->getPath()) > 0;
		$parent = new ItemIncomplete($payload);

		// Also the parent, in these cases
		if($fix || $validate) {
			try {
				$parent = $db->itemDAO()->getItem($parent, null, 1);
			} catch(NotFoundException $e) {
				throw new InvalidPayloadParameterException('*', $payload, "Parent item doesn't exist");
			}
		}

		if($fix) {
			$parent = ItemLocationValidator::reparent($item, $parent);
		}

		if($validate) {
			try {
				ItemLocationValidator::checkNesting($item, $parent);
			} catch(ItemNestingException $e) {
				throw new InvalidPayloadParameterException('*', $e->parentCode, $e->getMessage());
			}
		}

		$path = $item->getPath();

		// If item was nowhere (now it's going somewhere) or it's not already in its final location (don't think too hard about this, really)
		if(count($path) === 0 || $path[count($path) - 1]->getCode() !== $parent->getCode()) {
			try {
				// TODO: if fix&novalidate, parent may be in a DiFfErEnT CaSe than what it is in getPath...
				$db->treeDAO()->moveItem($item, $parent);
			} catch(NotFoundException $e) {
				if($e->getCode() === TreeDAO::EXCEPTION_CODE_PARENT) {
					throw new InvalidPayloadParameterException('*', $parent->getCode(), "Parent item doesn't exist");
				} else {
					throw $e;
				}
			}
		}

		if($hadParent) {
			// Changed
			$response = $response->withStatus(204);
		} else {
			// Created
			$response = $response->withStatus(201);
		}

		return $next ? $next($request, $response) : $response;
	}

	public static function setItemFeatures(Request $request, Response $response, ?callable $next = null): Response {
		/** @var Database $db */
		$db = $request->getAttribute('Database');
		$user = $request->getAttribute('User');
		$query = $request->getQueryParams();
		$payload = $request->getParsedBody();
		$parameters = $request->getAttribute('parameters', []);

		Validation::authorize($user);
		Validation::validateArray($payload);

		$id = Validation::validateOptionalString($parameters, 'id');
		$loopback = isset($query['loopback']);

		$item = new ItemFeatures($id);
		// PUT => delete every feature, replace with new ones
		ItemBuilder::addFeatures($payload, $item);
		$db->featureDAO()->deleteFeaturesAll($item);
		$db->featureDAO()->setFeatures($item);

		// TODO: this should maybe return 201 sometimes
		if($loopback) {
			$request = $request->withAttribute('Data', $db->itemDAO()->getItem($item));
			$response = $response->withStatus(200);
		} else {
			$response = $response->withStatus(204);
		}

		return $next ? $next($request, $response) : $response;
	}

	public static function updateItemFeatures(Request $request, Response $response, ?callable $next = null): Response {
		/** @var Database $db */
		$db = $request->getAttribute('Database');
		$user = $request->getAttribute('User');
		$query = $request->getQueryParams();
		$payload = $request->getParsedBody();
		$parameters = $request->getAttribute('parameters', []);

		Validation::authorize($user);
		Validation::validateArray($payload);

		$id = Validation::validateOptionalString($parameters, 'id');
		$loopback = isset($query['loopback']);

		$item = new ItemFeatures($id);
		// PATCH => specify features to update and to delete, other are left as they are
		$delete = ItemBuilder::addFeaturesDelta($payload, $item);
		foreach($delete as $feature) {
			// TODO: if no features are added, this will never add an audit entry...
			$db->featureDAO()->deleteFeature($item, $feature);
		}
		$db->featureDAO()->setFeatures($item);

		// TODO: this could meybe return 201 sometimes
		if($loopback) {
			$request = $request->withAttribute('Data', $db->itemDAO()->getItem($item));
			$response = $response->withStatus(200);
		} else {
			$response = $response->withStatus(204);
		}

		return $next ? $next($request, $response) : $response;
	}

	public static function doSearch(Request $request, Response $response, ?callable $next = null): Response {
		/** @var Database $db */
		$db = $request->getAttribute('Database');
		$user = $request->getAttribute('User');
		$payload = $request->getParsedBody();
		$parameters = $request->getAttribute('parameters', []);

		Validation::authorize($user, 3);
		Validation::validateArray($payload);

		$id = Validation::validateOptionalString($parameters, 'id');

		if($id) {
			// Refining a search: must be owner or admin
			$username = $db->searchDAO()->getOwnerUsername($id);
			if($username !== $user->getUsername()) {
				Validation::authorize($user, 0);
			}
		}

		$search = SearchBuilder::ofArray($payload);
		$resultId = $db->searchDAO()->search($search, $user, $id);

		$request = $request
			->withAttribute('Data', $resultId);
		$response = $response
			->withStatus(200);

		return $next ? $next($request, $response) : $response;
	}

	public static function getLogs(Request $request, Response $response, ?callable $next = null): Response {
		/** @var Database $db */
		$db = $request->getAttribute('Database');
		$user = $request->getAttribute('User');
		$query = $request->getQueryParams();
		$payload = $request->getParsedBody();
		$parameters = $request->getAttribute('parameters', []);

		Validation::authorize($user, 3);
		Validation::validateArray($payload);

		$page = Validation::validateOptionalInt($parameters, 'page', 1);
		$limit = Validation::validateOptionalInt($query, 'limit', 20);

		if($page < 1) {
			throw new InvalidPayloadParameterException('page', $page, 'Pages start from 1');
		}

		if($limit > 50) {
			throw new InvalidPayloadParameterException('limit', $limit, 'Maximum number of entries per page is 50');
		} else if($limit < 1) {
			throw new InvalidPayloadParameterException('limit', $limit, 'Length < 1 doesn\'t make sense');
		}

		$data = $db->auditDAO()->getRecentAudit($limit, $page);

		$request = $request
			->withAttribute('Data', $data);
		$response = $response
			->withStatus(200);

		return $next ? $next($request, $response) : $response;
	}

	public static function getHistory(Request $request, Response $response, ?callable $next = null): Response {
		/** @var Database $db */
		$db = $request->getAttribute('Database');
		$user = $request->getAttribute('User');
		$query = $request->getQueryParams();
		$parameters = $request->getAttribute('parameters', []);

		Validation::authorize($user, 3);

		$id = Validation::validateOptionalString($parameters, 'id');
		// TODO: rename to limit?
		$length = Validation::validateOptionalInt($query, 'length', 20);

		$item = new ItemIncomplete($id);

		if(!$db->itemDAO()->itemExists($item)) {
			throw new NotFoundException();
		}

		if($length > 50) {
			throw new InvalidPayloadParameterException('limit', $length, 'Maximum number of entries is 50');
		} else if($length < 1) {
			throw new InvalidPayloadParameterException('limit', $length, 'Length < 1 doesn\'t make sense');
		}

		$data = $db->auditDAO()->getHistory($item, $length);

		$request = $request
			->withAttribute('Data', $data);
		$response = $response
			->withStatus(200);

		return $next ? $next($request, $response) : $response;
	}

	public static function getDispatcher(string $cachefile): FastRoute\Dispatcher {
		return FastRoute\cachedDispatcher(function(FastRoute\RouteCollector $r) {

			$r->addGroup('/items', function(FastRoute\RouteCollector $r) {
				$r->get('', 'getItem');
				$r->post('', 'createItem');

				$r->addGroup('/{id:[a-zA-Z0-9]+}', function(FastRoute\RouteCollector $r) {
					$r->get('[/token/{token}]', 'getItem');
					$r->get('/history', 'getHistory');
					$r->put('', 'createItem');
					$r->delete('', 'removeItem');

					// Useless
					//$r->get('/parent', 'getItemParent');
					$r->put('/parent', 'setItemParent');

					$r->get('/product', 'getItemProduct'); // TODO: implement
					//$r->put('/product', 'setItemProduct');
					//$r->delete('/product', 'deleteItemProduct');

					// Also useless, just get the item
					// $r->get('/features', 'getItemFeatures');
					$r->put('/features', 'setItemFeatures');
					$r->patch('/features', 'updateItemFeatures');

					// $r->get('/contents', 'getItemContents');
				});
			});

			$r->post('/search', 'doSearch');
			$r->patch('/search/{id}', 'doSearch');
			$r->get('/search/{id}[/page/{page}]', 'getSearch'); // TODO: implement

			$r->get('/features/{feature}/{value}', 'getByFeature');

			$r->addGroup('/products', function(FastRoute\RouteCollector $r) {
				$r->get('', 'getProduct'); // TODO: implement
				$r->get('/{brand}[/{model}[/{variant}]]', 'getProduct'); // TODO: implement

				$r->post('/{brand}/{model}', 'createProduct'); // TODO: implement
				$r->put('/{brand}/{model}/{variant}', 'createProduct'); // TODO: implement

				$r->addGroup('/{brand}/{model}/{variant}', function(FastRoute\RouteCollector $r) {
					//$r->get('/features', 'getProductFeatures');
					$r->post('/features', 'setProductFeatures'); // TODO: implement
					$r->patch('/features', 'updateProductFeatures'); // TODO: implement
				});
			});

			$r->get('/logs[/page/{page}]', 'getLogs');

			$r->get('/session', 'sessionWhoami');
			$r->post('/session', 'sessionStart');
			$r->delete('/session', 'sessionClose');
			$r->head('/session', 'sessionRefresh');
		}, [
			'cacheFile'     => $cachefile,
			'cacheDisabled' => !CACHE_ENABLED,
		]);
	}

	public static function handle(Request $request): Response {
		$queue = [
			new DatabaseConnection(),
			[self::class, 'handleExceptions']
		];

		$response = new \Slim\Http\Response();
		$response = $response
			->withHeader('Content-Type', 'application/json')
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
					->withAttribute('Status', Jsend::FAIL)
					->withAttribute('ErrorMessage', "API endpoint not found");
				$response = $response->withStatus(404);
				break;
			case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
				$allowed = implode(', ', $route[1]);
				$request = $request
					->withAttribute('Status', Jsend::FAIL)
					->withAttribute('ErrorMessage', "Method not allowed for this endpoint, use one of: $allowed");
				$response = $response->withStatus(405)
					->withHeader('Allow', $allowed);
				break;
			default:
				$request = $request
					->withAttribute('Status', Jsend::ERROR)
					->withAttribute('ErrorMessage', 'Unhandled router result');
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

		$body = null;
		if($request->getMethod() !== 'HEAD') {
			$data = $request->getAttribute('Data');
			switch($request->getAttribute('Status', null)) {

				case JSend::SUCCESS:
					$body = JSend::success($data);
					break;
				case JSend::ERROR:
					$body = JSend::error($request->getAttribute('ErrorMessage'),
						$request->getAttribute('ErrorCode'),
						$data);
					break;
				case JSend::FAIL:
					$body = JSend::fail($data);
					break;
				case null:
					// Do nothing
					break;
				default:
					$response = $response
						->withStatus(500);
					$body = JSend::error('Unhandled JSend response type, most probably a typo somewhere in the code');
			}
		}

		if($body === null) {
			$response->getBody()->close();
		} else {
			$response->getBody()->rewind();
			$response->getBody()->write($body);
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
			} catch(AuthorizationException $e) {
				$request = $request
					->withAttribute('Status', Jsend::ERROR)
					->withAttribute('ErrorMessage', 'Not authorized (insufficient permission)')
					->withAttribute('ErrorCode', 'AUTH403');
				$response = $response
					->withStatus(403);
			} catch(AuthenticationException $e) {
				$request = $request
					->withAttribute('Status', Jsend::ERROR)
					->withAttribute('ErrorMessage', 'Not authenticated or session expired')
					->withAttribute('ErrorCode', 'AUTH401')
					->withAttribute('Data', ['notes' => 'Try POSTing to /session']);
				// 401 requires a WWW authentication challenge in the response, so use 403 again
				$response = $response
					->withStatus(403);
			} catch(InvalidPayloadParameterException $e) {
				$request = $request
					->withAttribute('Status', Jsend::FAIL)
					->withAttribute('Data', [$e->getParameter() => $e->getReason()]);
				$response = $response
					->withStatus(400);
			} catch(DatabaseException $e) {
				$request = $request
					->withAttribute('Status', Jsend::ERROR)
					->withAttribute('ErrorMessage', 'Database error: ' . $e->getMessage());
				$response = $response
					->withStatus(500);
			} catch(NotFoundException $e) {
				$response = $response
					->withStatus(404);
			} catch(\Throwable $e) {
				$request = $request
					->withAttribute('Status', Jsend::ERROR)
					->withAttribute('ErrorMessage', 'Unhandled exception :(')
					->withAttribute('Data', ['message' => $e->getMessage(), 'code' => $e->getCode()]);
				$response = $response
					->withStatus(403);
			}

			return self::renderResponse($request, $response);
		} else {
			return $response;
		}
	}
}
