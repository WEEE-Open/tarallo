<?php

namespace WEEEOpen\Tarallo\APIv1;

use FastRoute;
use WEEEOpen\Tarallo\Server\Database\Database;
use WEEEOpen\Tarallo\Server\Database\DatabaseException;
use WEEEOpen\Tarallo\Server\Database\TreeDAO;
use WEEEOpen\Tarallo\Server\ItemFeatures;
use WEEEOpen\Tarallo\Server\ItemIncomplete;
use WEEEOpen\Tarallo\Server\NotFoundException;
use WEEEOpen\Tarallo\Server\Session;
use WEEEOpen\Tarallo\Server\User;


class Adapter {
	public static function sessionWhoami(User $user = null, Database $db, $parameters, $querystring, $payload) {
		Validation::authorize($user);

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

		if($token === null) {
			Validation::authorize($user);
		}

		if($id === null) {
			throw new \LogicException('Not implemented');
		} else {
			return $db->itemDAO()->getItem(new ItemIncomplete($id), $token);
		}
	}

	public static function createItem(User $user = null, Database $db, $parameters, $querystring, $payload) {
		Validation::authorize($user);
		$id = isset($parameters['id']) ? (string) $parameters['id'] : null;

		$item = ItemBuilder::ofArray($payload, $id, $parent);
		$db->itemDAO()->addItem($item, $parent);

		return $db->itemDAO()->getItem($item);
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

		try {
			$db->treeDAO()->moveItem(new ItemIncomplete($id), new ItemIncomplete($payload));
		} catch(NotFoundException $e) {
			if($e->getCode() === TreeDAO::EXCEPTION_CODE_PARENT) {
				throw new InvalidPayloadParameterException('*', $payload, "Parent item doesn't exist");
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

		$item = new ItemFeatures($id);
		// PUT => delete every feature, replace with new ones
		ItemBuilder::addFeatures($payload, $item);
		$db->featureDAO()->deleteFeaturesAll($item);
		$db->featureDAO()->setFeatures($item);

		return $db->itemDAO()->getItem($item);
	}

	public static function updateItemFeatures(User $user = null, Database $db, $parameters, $querystring, $payload) {
		Validation::authorize($user);
		Validation::validateArray($payload);
		$id = isset($parameters['id']) ? (string) $parameters['id'] : null;

		$item = new ItemFeatures($id);
		// PATCH => specify features to update and to delete, other are left as they are
		$delete = ItemBuilder::addFeaturesDelta($payload, $item);
		foreach($delete as $feature) {
			$db->featureDAO()->deleteFeature($item, $feature);
		}
		$db->featureDAO()->setFeatures($item);

		return $db->itemDAO()->getItem($item);
	}

	/**
	 * @param string $method HTTP method (GET, POST, ...)
	 * @param string $uri URI, e.g. /items/PC42
	 * @param string[]|null $querystring Parsed query string (?foo=bar is ["foo" => "bar"]), null if none
	 * @param mixed|null $payload Request contents (decoded JSON), null if none
	 * @param Database $db
	 * @param User|null $user Current user, authenticated, authorized, or null if not logged in
	 *
	 * @return Response
	 * @throws \Throwable I have no idea but I'm forced to add this annotation
	 */
	public static function go($method, $uri, $querystring, $payload, Database $db, User $user = null) {
		// TODO: use cachedDispatcher
		$dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) {

			$r->addGroup('/items', function(FastRoute\RouteCollector $r) {
				$r->get('', 'getItem');
				$r->post('', 'createItem');

				$r->addGroup('/{id:[a-zA-Z0-9]+}', function(FastRoute\RouteCollector $r) {
					$r->get('[/token/{token}]', 'getItem');
					$r->put('', 'createItem');
					$r->delete('', 'removeItem');

					// Useless
					//$r->get('/parent', 'getItemParent');
					$r->put('/parent', 'setItemParent');

					$r->get('/product', 'getItemProduct');
					$r->put('/product', 'setItemProduct');
					$r->delete('/product', 'deleteItemProduct');

					// Also useless, just get the item
					// $r->get('/features', 'getItemFeatures');
					$r->put('/features', 'setItemFeatures');
					$r->patch('/features', 'updateItemFeatures');

					$r->get('/contents', 'getItemContents');
				});
			});

			$r->post('/search', 'createSearch');
			$r->patch('/search/{id}', 'refineSearch');
			$r->get('/search/{id}[/page/{page}]', 'getSearch');

			$r->addGroup('/products', function(FastRoute\RouteCollector $r) {
				$r->get('', 'getProduct');
				$r->get('/{brand}[/{model}[/{variant}]]', 'getProduct');

				$r->post('/{brand}/{model}', 'createProduct');
				$r->put('/{brand}/{model}/{variant}', 'createProduct');

				$r->addGroup('/{brand}/{model}/{variant}', function(FastRoute\RouteCollector $r) {
					$r->get('/features', 'getProductFeatures');
					$r->post('/features', 'setProductFeatures');
					$r->patch('/features', 'updateProductFeatures');
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
			return Response::ofError('Server error: unhandled router result');
		}

		$callback = [Adapter::class, $route[1]];
		$parameters = $route[2];
		unset($route);

		if(!is_callable($callback)) {
			return Response::ofError('Server error: cannot call "' . implode('::', $callback) . '"');
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
			return Response::ofError('Not authorized (insufficient permission)', 'AUTH403', null, 403);
		} catch(AuthenticationException $e) {
			// 401 requires a WWW authentication challenge in the response, so use 403 again
			return Response::ofError('Not authenticated or session expired', 'AUTH401',
				['notes' => 'Try POSTing to /session'], 403);
		} catch(InvalidPayloadParameterException $e) {
			return Response::ofFail($e->getParameter(), $e->getReason());
		} catch(DatabaseException $e) {
			return Response::ofError('Database error: ' . $e->getMessage());
		} catch(NotFoundException $e) {
			http_response_code(404);
			exit();
		} catch(\Exception $e) {
			return Response::ofError('Unhandled exception :(', null,
				['message' => $e->getMessage(), 'code' => $e->getCode()]);
		}

		try {
			return Response::ofSuccess($result);
		} catch(\Exception $e) {
			return Response::ofError('Unhandled exception', null,
				['message' => $e->getMessage(), 'code' => $e->getCode()]);
		}
	}
}