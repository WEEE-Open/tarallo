<?php

namespace WEEEOpen\Tarallo\APIv1;

use FastRoute;
use WEEEOpen\Tarallo\Server\Database\Database;
use WEEEOpen\Tarallo\Server\Database\DatabaseException;
use WEEEOpen\Tarallo\Server\Database\ItemDAO;
use WEEEOpen\Tarallo\Server\Database\TreeDAO;
use WEEEOpen\Tarallo\Server\Feature;
use WEEEOpen\Tarallo\Server\HTTP\AdapterInterface;
use WEEEOpen\Tarallo\Server\HTTP\AuthenticationException;
use WEEEOpen\Tarallo\Server\HTTP\AuthorizationException;
use WEEEOpen\Tarallo\Server\HTTP\InvalidPayloadParameterException;
use WEEEOpen\Tarallo\Server\HTTP\Request;
use WEEEOpen\Tarallo\Server\HTTP\Response;
use WEEEOpen\Tarallo\Server\HTTP\Validation;
use WEEEOpen\Tarallo\Server\ItemFeatures;
use WEEEOpen\Tarallo\Server\ItemIncomplete;
use WEEEOpen\Tarallo\Server\ItemLocationValidator;
use WEEEOpen\Tarallo\Server\ItemNestingException;
use WEEEOpen\Tarallo\Server\NotFoundException;
use WEEEOpen\Tarallo\Server\Session;
use WEEEOpen\Tarallo\Server\User;


class Controller implements AdapterInterface {
	public static function sessionWhoami(User $user = null, Database $db, $parameters, $querystring, $payload) {
		Validation::authorize($user, 3);

		return ['username' => $user->getUsername()];
	}

	public static function sessionStart(User $user = null, Database $db, $parameters, $querystring, $payload) {
		Validation::validateArray($payload);
		$username = Validation::validateHasString($payload, 'username');
		$password = Validation::validateHasString($payload, 'password');
		$user = $db->userDAO()->getUserFromLogin($username, $password);
		if($user === null) {
			throw new InvalidPayloadParameterException('*', '', 'Wrong username or password');
		}
		Session::start($user, $db);

		return null;
	}

	public static function sessionClose(User $user = null, Database $db, $parameters, $querystring, $payload) {
		// If we ever add another level for e.g. banned users, this at least allows them to log out
		Validation::authenticate($user);
		Session::close($user, $db);

		return null;
	}

	public static function sessionRefresh(User $user = null, Database $db, $parameters, $querystring, $payload) {
		// The refresh itself has already been done by Session::restore, sooooo...
		Validation::authenticate($user);

		return null;
	}

	public static function getItem(User $user = null, Database $db, $parameters, $querystring, $payload) {
		$id = isset($parameters['id']) ? (string) $parameters['id'] : null;
		$token = isset($parameters['token']) ? (string) $parameters['token'] : null;
		$depth = isset($querystring['depth']) ? (int) $querystring['depth'] : null;

		if($token === null) {
			Validation::authorize($user, 3);
		}

		if($id === null) {
			throw new \LogicException('Not implemented');
		} else {
			return $db->itemDAO()->getItem(new ItemIncomplete($id), $token, $depth);
		}
	}

	public static function getByFeature(User $user = null, Database $db, $parameters, $querystring, $payload) {
		Validation::authorize($user, 3);

		$id = (string) $parameters['feature'];
		$value = (string) $parameters['value'];
		$limit = isset($querystring['limit']) ? (int) $querystring['limit'] : 5;

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

		return $db->featureDAO()->getItemsByFeatures($feature, $limit);
	}

	public static function createItem(User $user = null, Database $db, $parameters, $querystring, $payload) {
		Validation::authorize($user);
		$id = isset($parameters['id']) ? (string) $parameters['id'] : null;
		$fix = isset($querystring['fix']) ? true : false;
		$loopback = isset($querystring['loopback']) ? true : false;

		$item = ItemBuilder::ofArray($payload, $id, $parent);

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
				throw new InvalidPayloadParameterException('parent', $parent->getCode(), 'Requested location doesn\'t exist');
			}
		} catch(\InvalidArgumentException $e) {
			if($e->getCode() === ItemDAO::EXCEPTION_CODE_GENERATE_ID) {
				throw new InvalidPayloadParameterException('code', null,
					'Cannot generate code for an item (missing "type"?)');
			} else {
				throw $e;
			}
		}

		if($loopback) {
			return $db->itemDAO()->getItem($item);
		} else {
			return $item->getCode();
		}
	}

	public static function removeItem(User $user = null, Database $db, $parameters, $querystring, $payload) {
		Validation::authorize($user);
		$id = isset($parameters['id']) ? (string) $parameters['id'] : null;

		$db->itemDAO()->deleteItem(new ItemIncomplete($id));

		return null;
	}

	public static function setItemParent(User $user = null, Database $db, $parameters, $querystring, $payload) {
		Validation::authorize($user);
		Validation::validateIsString($payload);
		$id = isset($parameters['id']) ? (string) $parameters['id'] : null;
		$fix = isset($querystring['fix']) ? true : false;
		$validate = isset($querystring['novalidate']) ? false : true;

		// We'll need the full item in any case, not just an ItemIncomplete
		$item = $db->itemDAO()->getItem(new ItemIncomplete($id), null, 0);
		$parent = new ItemIncomplete($payload);

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
		if(count($path) > 0 && $path[count($path) - 1]->getCode() === $parent->getCode()) {
			return null;
		}

		try {
			$db->treeDAO()->moveItem($item, $parent);
		} catch(NotFoundException $e) {
			if($e->getCode() === TreeDAO::EXCEPTION_CODE_PARENT) {
				throw new InvalidPayloadParameterException('*', $parent->getCode(), "Parent item doesn't exist");
			} else {
				throw $e;
			}
		}

		return null;
	}

	public static function setItemFeatures(User $user = null, Database $db, $parameters, $querystring, $payload) {
		Validation::authorize($user);
		Validation::validateArray($payload);
		$id = isset($parameters['id']) ? (string) $parameters['id'] : null;
		$loopback = isset($querystring['loopback']) ? true : false;

		$item = new ItemFeatures($id);
		// PUT => delete every feature, replace with new ones
		ItemBuilder::addFeatures($payload, $item);
		$db->featureDAO()->deleteFeaturesAll($item);
		$db->featureDAO()->setFeatures($item);

		if($loopback) {
			return $db->itemDAO()->getItem($item);
		} else {
			return null;
		}
	}

	public static function updateItemFeatures(User $user = null, Database $db, $parameters, $querystring, $payload) {
		Validation::authorize($user);
		Validation::validateArray($payload);
		$id = isset($parameters['id']) ? (string) $parameters['id'] : null;
		$loopback = isset($querystring['loopback']) ? true : false;

		$item = new ItemFeatures($id);
		// PATCH => specify features to update and to delete, other are left as they are
		$delete = ItemBuilder::addFeaturesDelta($payload, $item);
		foreach($delete as $feature) {
			$db->featureDAO()->deleteFeature($item, $feature);
		}
		$db->featureDAO()->setFeatures($item);

		if($loopback) {
			return $db->itemDAO()->getItem($item);
		} else {
			return null;
		}
	}

	public static function doSearch(User $user = null, Database $db, $parameters, $querystring, $payload) {
		Validation::authorize($user, 3);
		Validation::validateArray($payload);
		$id = isset($parameters['id']) ? (int) $parameters['id'] : null;

		if($id) {
			// Refreshing a search: must be owner or admin
			$username = $db->searchDAO()->getOwnerUsername($id);
			if($username !== $user->getUsername()) {
				Validation::authorize($user, 0);
			}
		}

		$search = SearchBuilder::ofArray($payload);
		$resultId = $db->searchDAO()->search($search, $user, $id);

		return $resultId;
	}

	public static function getLogs(User $user = null, Database $db, $parameters, $querystring, $payload) {
		Validation::authorize($user, 3);
		$page = isset($parameters['page']) ? (int) $parameters['page'] : 1;
		$length = isset($querystring['length']) ? (int) $querystring['length'] : 20;

		if($page < 1) {
			throw new InvalidPayloadParameterException('page', $page, 'Pages start from 1');
		}

		if($length > 50) {
			throw new InvalidPayloadParameterException('limit', $length, 'Maximum number of entries per page is 50');
		} else if($length < 1) {
			throw new InvalidPayloadParameterException('limit', $length, 'Length < 1 doesn\'t make sense');
		}

		return $db->auditDAO()->getRecentAudit($length, $page);
	}

	public static function getHistory(User $user = null, Database $db, $parameters, $querystring, $payload) {
		Validation::authorize($user, 3);
		$id = isset($parameters['id']) ? (string) $parameters['id'] : null;
		$length = isset($querystring['length']) ? (int) $querystring['length'] : 20;
		$item = new ItemIncomplete($id);

		if(!$db->itemDAO()->itemExists($item)) {
			throw new NotFoundException();
		}

		if($length > 50) {
			throw new InvalidPayloadParameterException('limit', $length, 'Maximum number of entries is 50');
		} else if($length < 1) {
			throw new InvalidPayloadParameterException('limit', $length, 'Length < 1 doesn\'t make sense');
		}

		return $db->auditDAO()->getHistory($item, $length);
	}

	public static function route(Request $request): Response {
		return self::goInternal($request->method, $request->path, $request->querystring,
			$request->payload)->asResponseInterface();
	}

	/**
	 * @param string $method HTTP method (GET, POST, ...)
	 * @param string $uri URI, e.g. /items/PC42
	 * @param string[]|null $querystring Parsed query string (?foo=bar is ["foo" => "bar"]), null if none
	 * @param mixed|null $payload Request contents to be decoded, null if none
	 *
	 * @return JSend The JSend wrapper thinghy
	 */
	public static function goInternal($method, $uri, $querystring, $payload): JSend {
		// TODO: use cachedDispatcher
		$dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) {

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
			return JSend::ofError('Server error: unhandled router result');
		}

		$callback = [Controller::class, $route[1]];
		$parameters = $route[2];
		unset($route);

		if(!is_callable($callback)) {
			return JSend::ofError('Server error: cannot call "' . implode('::', $callback) . '"');
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
			http_response_code(500);

			return JSend::ofError('Server error: ' . $e->getMessage());
		}

		if($payload !== null) {
			$payload = json_decode($payload, true);
			if(json_last_error() !== JSON_ERROR_NONE) {
				return JSend::ofError('Cannot decode JSON request body');
			}
		}

		try {
			try {
				$db->beginTransaction();
				$result = call_user_func($callback, $user, $db, $parameters, $querystring, $payload);
				$db->commit();
			} catch(\Throwable $e) {
				$db->rollback();
				throw $e;
			}
		} catch(AuthorizationException $e) {
			return JSend::ofError('Not authorized (insufficient permission)', 'AUTH403', null, 403);
		} catch(AuthenticationException $e) {
			// 401 requires a WWW authentication challenge in the response, so use 403 again
			return JSend::ofError('Not authenticated or session expired', 'AUTH401',
				['notes' => 'Try POSTing to /session'], 403);
		} catch(InvalidPayloadParameterException $e) {
			return JSend::ofFail($e->getParameter(), $e->getReason());
		} catch(DatabaseException $e) {
			return JSend::ofError('Database error: ' . $e->getMessage());
		} catch(NotFoundException $e) {
			http_response_code(404);
			exit();
		} catch(\Throwable $e) {
			return JSend::ofError('Unhandled exception :(', null,
				['message' => $e->getMessage(), 'code' => $e->getCode()]);
		}

		try {
			return JSend::ofSuccess($result);
		} catch(\Exception $e) {
			return JSend::ofError('Unhandled exception', null,
				['message' => $e->getMessage(), 'code' => $e->getCode()]);
		}
	}
}
