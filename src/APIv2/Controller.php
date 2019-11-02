<?php

namespace WEEEOpen\Tarallo\APIv2;

use FastRoute;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Relay\RelayBuilder;
use WEEEOpen\Tarallo\BaseFeature;
use WEEEOpen\Tarallo\Database\Database;
use WEEEOpen\Tarallo\Database\DuplicateItemCodeException;
use WEEEOpen\Tarallo\Database\ItemDAO;
use WEEEOpen\Tarallo\Database\TreeDAO;
use WEEEOpen\Tarallo\ErrorHandler;
use WEEEOpen\Tarallo\Feature;
use WEEEOpen\Tarallo\HTTP\AuthManager;
use WEEEOpen\Tarallo\HTTP\AuthValidator;
use WEEEOpen\Tarallo\HTTP\DatabaseConnection;
use WEEEOpen\Tarallo\HTTP\InvalidPayloadParameterException;
use WEEEOpen\Tarallo\HTTP\TransactionWrapper;
use WEEEOpen\Tarallo\HTTP\Validation;
use WEEEOpen\Tarallo\Item;
use WEEEOpen\Tarallo\ItemCode;
use WEEEOpen\Tarallo\ItemNestingException;
use WEEEOpen\Tarallo\ItemPrefixerException;
use WEEEOpen\Tarallo\ItemValidator;
use WEEEOpen\Tarallo\NotFoundException;
use WEEEOpen\Tarallo\SearchException;
use WEEEOpen\Tarallo\ValidationException;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\JsonResponse;


class Controller implements RequestHandlerInterface {
	const cachefile = __DIR__ . '/router.cache';

	public static function sessionWhoami(ServerRequestInterface $request): ResponseInterface {
		$user = $request->getAttribute('User');

		return new JsonResponse(['username' => $user->getUsername()]);
	}

	public static function getItem(ServerRequestInterface $request): ResponseInterface {
		/** @var Database $db */
		$db = $request->getAttribute('Database');
		$parameters = $request->getAttribute('parameters', []);

		$id = Validation::validateOptionalString($parameters, 'id');
		$token = Validation::validateOptionalString($parameters, 'token');
		$depth = Validation::validateOptionalString($parameters, 'depth');

		if($id === null) {
			throw new \LogicException('Not implemented');
		} else {
			try {
				$item = new ItemCode($id);
			} catch(ValidationException $e) {
				throw new NotFoundException($id);
			}

			if(!$db->itemDAO()->itemVisible($item)) {
				throw new NotFoundException();
			}
			$data = $db->itemDAO()->getItem($item, $token, $depth);

			return new JsonResponse($data);
		}
	}

	public static function getDeletedItem(ServerRequestInterface $request): ResponseInterface {
		/** @var Database $db */
		$db = $request->getAttribute('Database');
		$parameters = $request->getAttribute('parameters', []);

		$id = Validation::validateHasString($parameters, 'id');

		try {
			$item = new ItemCode($id);
		} catch(ValidationException $e) {
			throw new NotFoundException($id);
		}

		$deleted = $db->itemDAO()->itemDeletedAt($item);

		if($deleted === null) {
			throw new NotFoundException();
		} else {
			$data = (new \DateTime($deleted, new \DateTimeZone('UTC')))->format('c');
			// TODO: wrap into something
			return new JsonResponse($data);
		}
	}

	public static function restoreItemParent(ServerRequestInterface $request): ResponseInterface {
		/** @var Database $db */
		$db = $request->getAttribute('Database');
		$query = $request->getQueryParams();
		$payload = json_decode($request->getBody()->getContents(), true);
		$parameters = $request->getAttribute('parameters', []);

		// Could allow restoring items as roots by not calling self::moveWithValidation at all, BTW...
		Validation::validateRequestBodyIsString($payload);
		$id = Validation::validateHasString($parameters, 'id');
		$fix = !isset($query['nofix']);
		$validate = !isset($query['novalidate']);

		try {
			$item = new ItemCode($id);
		} catch(ValidationException $e) {
			throw new NotFoundException($id);
		}
		try {
			$newParent = new ItemCode($payload);
		} catch(ValidationException $e) {
			throw new InvalidPayloadParameterException('*', $payload, 'Location does not exist');
		}

		$db->itemDAO()->undelete($item);
		$created = TreeDAO::moveWithValidation($db, $item, $newParent, $fix, $validate);

		if($created) {
			// TODO: return the item itself, maybe?
			return new EmptyResponse(201);
		} else {
			return new EmptyResponse(204);
		}
	}

	public static function getByFeature(ServerRequestInterface $request): ResponseInterface {
		/** @var Database $db */
		$db = $request->getAttribute('Database');
		$parameters = $request->getAttribute('parameters', []);

		$id = (string) $parameters['feature'];
		$value = (string) $parameters['value'];
		$limit = Validation::validateOptionalInt($parameters, 'limit', 5);

		// TODO: handle limits with a standardized method
		if($limit > 10) {
			throw new InvalidPayloadParameterException('limit', $limit, 'Maximum number of results is 10');
		} else {
			if($limit < 1) {
				throw new InvalidPayloadParameterException('limit', $limit, 'Limit < 1 doesn\'t make sense');
			}
		}

		try {
			if(BaseFeature::getType($id) !== BaseFeature::STRING) {
				throw new InvalidPayloadParameterException('*', $id, "Only text features are supported, $id isn't");
			}
		} catch(\InvalidArgumentException $e) {
			throw new InvalidPayloadParameterException('*', $value, $e->getMessage());
		}

		$feature = new Feature($id, $value);

		$data = $db->statsDAO()->getItemsByFeatures($feature, null, $limit);

		return new JsonResponse($data);
	}

	public static function createItem(ServerRequestInterface $request): ResponseInterface {
		/** @var Database $db */
		$db = $request->getAttribute('Database');
		$query = $request->getQueryParams();
		$payload = $request->getAttribute('ParsedBody', []);
		$parameters = $request->getAttribute('parameters', []);

		$id = Validation::validateOptionalString($parameters, 'id');
		$fix = !isset($query['nofix']);
		$validate = !isset($query['novalidate']);
		$loopback = isset($query['loopback']);

		$item = ItemBuilder::ofArray($payload, $id, $parent);

		// Validation and fixupLocation requires the full parent item, which may not exist.
		// Since this part is optional, its existence will be checked again later
		if($parent instanceof ItemCode && ($fix || $validate)) {
			try {
				$parent = $db->itemDAO()->getItem($parent, null, 1);
			} catch(NotFoundException $e) {
				throw new NotFoundException($parent->getCode(), 'Location doesn\'t exist', 0, $e);
			}
		}

		if($fix) {
			$parent = ItemValidator::fixupLocation($item, $parent);
			ItemValidator::fixupFeatures($item);
		}

		if($validate) {
			ItemValidator::validateLocation($item, $parent);
			ItemValidator::validateFeatures($item);
		}

		try {
			$db->itemDAO()->addItem($item, $parent);
		} catch(ItemPrefixerException $e) {
			// TODO: $e->setItemPath();
			throw $e;
		}

		if($loopback) {
			$response = new JsonResponse($db->itemDAO()->getItem($item), 201);
			$response = $response->withHeader('Location', '/v2/items/' . urlencode($item->getCode()));
		} else {
			// TODO: wrap into something
			$response = new JsonResponse($item->getCode(), 201);
			$response = $response->withHeader('Location', '/v2/items/' . urlencode($item->getCode()));
		}
		return $response;
	}

	public static function removeItem(ServerRequestInterface $request): ResponseInterface {
		/** @var Database $db */
		$db = $request->getAttribute('Database');
		$parameters = $request->getAttribute('parameters', []);

		$id = Validation::validateHasString($parameters, 'id');

		try {
			$db->itemDAO()->deleteItem(new ItemCode($id));
		} catch(ValidationException $e) {
			throw new NotFoundException($id);
		}

		return new EmptyResponse(204);
	}

	public static function setItemParent(ServerRequestInterface $request): ResponseInterface {
		/** @var Database $db */
		$db = $request->getAttribute('Database');
		$query = $request->getQueryParams();
		$payload = $request->getAttribute('ParsedBody', []);
		$parameters = $request->getAttribute('parameters', []);

		Validation::validateRequestBodyIsString($payload);
		$id = Validation::validateHasString($parameters, 'id');
		$fix = !isset($query['nofix']);
		$validate = !isset($query['novalidate']);

		try {
			$item = new ItemCode($id);
		} catch(ValidationException $e) {
			throw new NotFoundException($id);
		}
		try {
			$newParent = new ItemCode($payload);
		} catch(ValidationException $e) {
			throw new NotFoundException($payload);
		}

		$created = TreeDAO::moveWithValidation($db, $item, $newParent, $fix, $validate);

		if($created) {
			// TODO: return the item parent, maybe?
			return new EmptyResponse(201);
		} else {
			return new EmptyResponse(204);
		}
	}

	public static function deleteItemParent(ServerRequestInterface $request): ResponseInterface {
		/** @var Database $db */
		$db = $request->getAttribute('Database');
		$parameters = $request->getAttribute('parameters', []);

		$id = Validation::validateHasString($parameters, 'id');

		try {
			$code = new ItemCode($id);
		} catch(ValidationException $e) {
			throw new NotFoundException($id);
		}
		$db->itemDAO()->loseItem($code);

		return new EmptyResponse(204);
	}

	public static function setItemFeatures(ServerRequestInterface $request): ResponseInterface {
		/** @var Database $db */
		$db = $request->getAttribute('Database');
		$query = $request->getQueryParams();
		$payload = $request->getAttribute('ParsedBody', []);
		$parameters = $request->getAttribute('parameters', []);

		Validation::validateRequestBodyIsArray($payload);

		$id = Validation::validateOptionalString($parameters, 'id');
		$loopback = isset($query['loopback']);

		$item = new Item($id);
		// PUT => delete every feature, replace with new ones
		ItemBuilder::addFeatures($payload, $item);
		$db->featureDAO()->deleteFeaturesAll($item);
		$db->featureDAO()->setFeatures($item);

		// TODO: this should maybe return 201 sometimes
		if($loopback) {
			return new JsonResponse($db->itemDAO()->getItem($item));
		} else {
			return new EmptyResponse(204);
		}
	}

	public static function updateItemFeatures(ServerRequestInterface $request): ResponseInterface {
		/** @var Database $db */
		$db = $request->getAttribute('Database');
		$query = $request->getQueryParams();
		$payload = $request->getAttribute('ParsedBody', []);
		$parameters = $request->getAttribute('parameters', []);

		Validation::validateRequestBodyIsArray($payload);

		$id = Validation::validateOptionalString($parameters, 'id');
		$loopback = isset($query['loopback']);

		$item = new Item($id);
		// PATCH => specify features to update and to delete, other are left as they are
		$delete = ItemBuilder::addFeaturesDelta($payload, $item);

		$deleted = $db->featureDAO()->deleteFeature($item, $delete);
		$changed = $db->featureDAO()->setFeatures($item);

		// setFeatures generates an audit entry if anything changed, deleteFeature never does
		// so we may need to generate it manually
		if(!$changed && $deleted) {
			$db->featureDAO()->addAuditEntry($item);
		}

		if($loopback) {
			return new JsonResponse($db->itemDAO()->getItem($item));
		} else {
			return new EmptyResponse(204);
		}
	}

	public static function doSearch(ServerRequestInterface $request): ResponseInterface {
		/** @var Database $db */
		$db = $request->getAttribute('Database');
		$user = $request->getAttribute('User');
		$payload = $request->getAttribute('ParsedBody', []);
		$parameters = $request->getAttribute('parameters', []);

		Validation::validateRequestBodyIsArray($payload);

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

		// TODO: wrap into something
		return new JsonResponse($resultId);
	}

	public static function getLogs(ServerRequestInterface $request): ResponseInterface {
		/** @var Database $db */
		$db = $request->getAttribute('Database');
		$query = $request->getQueryParams();
		$parameters = $request->getAttribute('parameters', []);

		$page = Validation::validateOptionalInt($parameters, 'page', 1);
		$limit = Validation::validateOptionalInt($query, 'limit', 20);

		if($page < 1) {
			throw new InvalidPayloadParameterException('page', $page, 'Pages start from 1');
		}

		if($limit < 1) {
			throw new InvalidPayloadParameterException('limit', $limit, 'Length < 1 doesn\'t make sense');
		} else {
			if($limit > 50) {
				throw new InvalidPayloadParameterException('limit', $limit, 'Maximum number of entries per page is 50');
			}
		}

		$data = $db->auditDAO()->getRecentAudit($limit, $page);

		return new JsonResponse($data);
	}

	public static function getHistory(ServerRequestInterface $request): ResponseInterface {
		/** @var Database $db */
		$db = $request->getAttribute('Database');
		$query = $request->getQueryParams();
		$parameters = $request->getAttribute('parameters', []);

		$id = Validation::validateOptionalString($parameters, 'id');
		// TODO: rename to limit?
		$length = Validation::validateOptionalInt($query, 'length', 20);

		try {
			$item = new ItemCode($id);
		} catch(ValidationException $e) {
			throw new NotFoundException($id);
		}

		if(!$db->itemDAO()->itemExists($item)) {
			throw new NotFoundException();
		}

		if($length > 50) {
			throw new InvalidPayloadParameterException('limit', $length, 'Maximum number of entries is 50');
		} else {
			if($length < 1) {
				throw new InvalidPayloadParameterException('limit', $length, 'Length < 1 doesn\'t make sense');
			}
		}

		$data = $db->auditDAO()->getHistory($item, $length);

		return new JsonResponse($data);
	}

	public static function ItemsNotFeature(ServerRequestInterface $request): ResponseInterface {
		/** @var Database $db */
		$db = $request->getAttribute('Database');
		$parameters = $request->getAttribute('parameters', []);

		$feature = Validation::validateHasString($parameters, 'filter');
		$notFeature = Validation::validateHasString($parameters, 'notFeature');
		$location = Validation::validateOptionalString($parameters, 'location', null, null);
		$limit = Validation::validateOptionalInt($parameters, 'limit', 100);
		$creation = Validation::validateOptionalString($parameters, 'creation', null);
		$deleted = boolval(Validation::validateOptionalString($parameters, 'creation', false));
		//$deleted = isset($parameters['deleted']) ? $parameters['deleted'] : false;

		try {
			$explosion = Validation::explodeFeatureValuePair($feature);
		} catch(\InvalidArgumentException $e) {
			throw new InvalidPayloadParameterException('filter', $e->getMessage());
		}
		$data = $db->StatsDAO()->getItemByNotFeature(
			new Feature($explosion[0], $explosion[1]),
			$notFeature,
			$location === null ? null : new
			ItemCode(
				$location
			), $limit, $creation === null ? null : new \DateTime($creation), $deleted
		);

		return new JsonResponse($data);
	}

	public static function RecentAuditByType(ServerRequestInterface $request): ResponseInterface {
		/** @var Database $db */
		$db = $request->getAttribute('Database');
		$parameters = $request->getAttribute('parameters', []);

		$type = Validation::validateHasString($parameters, 'type');
		$limit = Validation::validateOptionalInt($parameters, 'howMany', 100);

		$data = $db->AuditDAO()->getRecentAuditByType($type, $limit);

		return new JsonResponse($data);
	}

	public static function CountByFeature(ServerRequestInterface $request): ResponseInterface {
		/** @var Database $db */
		$db = $request->getAttribute('Database');
		$parameters = $request->getAttribute('parameters', []);

		$feature = Validation::validateHasString($parameters, 'feature');
		$filter = Validation::validateOptionalString($parameters, 'filter', null, null);
		$location = Validation::validateOptionalString($parameters, 'location', null, null);
		$creation = Validation::validateOptionalString($parameters, 'creation');
		$deleted = isset($parameters['deleted']) ? $parameters['deleted'] : false;
		$cutoff = Validation::validateOptionalInt($parameters, 'cutoff', 1);

		if($filter !== null) {
			try {
				$explosion = Validation::explodeFeatureValuePair($feature);
			} catch(\InvalidArgumentException $e) {
				throw new SearchException(null, null, $e->getMessage(), 0, $e);
			}
			try {
				$filter = new Feature($explosion[0], $explosion[1]);
			} catch(\InvalidArgumentException $e) {
				throw new SearchException($explosion[0], $explosion[1], $e->getMessage(), 0, $e);
			}
		}

		try {
			$item = new ItemCode($location);
		} catch(ValidationException $e) {
			throw new NotFoundException($location);
		}

		$data = $db->statsDAO()->getCountByFeature(
			$feature,
			$filter,
			$location === null ? null : $item,
			$creation === null ? null : new \DateTime($creation),
			$deleted,
			$cutoff
		);

		return new JsonResponse($data);
	}

	public function handle(ServerRequestInterface $request): ResponseInterface {
		$route = $this->route($request);

		switch($route[0]) {
			case FastRoute\Dispatcher::FOUND:
				$level = $route[1][0];
				$function = $route[1][1];
				$request = $request->withAttribute('parameters', $route[2]);
				break;
			case FastRoute\Dispatcher::NOT_FOUND:
				return new JsonResponse(ErrorResponse::fromMessage('Not found'), 404);
			case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
				return new JsonResponse(ErrorResponse::fromMessage('Method Not Allowed'), 405, ['Allow' => implode(', ', $route[1])]);
			default:
				throw new \LogicException('Unknown result from router');
		}
		unset($route);

		$queue = [
			new ErrorHandler(),
			new EnsureJson(),
			new DatabaseConnection(),
			new ExceptionHandler(),
		];
		if($level !== null) {
			$queue[] = new AuthManager(false);
			$queue[] = new AuthValidator($level);
		}
		if($function !== null) {
			$queue[] = new TransactionWrapper();
			$queue[] = 'WEEEOpen\\Tarallo\\APIv2\\' . $function;
		}

		$relayBuilder = new RelayBuilder();
		$relay = $relayBuilder->newInstance($queue);

		return $relay->handle($request);
	}

	private function route(ServerRequestInterface $request): array {
		$dispatcher = FastRoute\cachedDispatcher(
			function(FastRoute\RouteCollector $r) {

				$r->addGroup(
					'/v2',
					function(FastRoute\RouteCollector $r) {
						$r->addGroup(
							'/items',
							function(FastRoute\RouteCollector $r) {
								$r->get('', [AuthValidator::AUTH_LEVEL_RO, 'Controller::getItem']);
								$r->post('', [AuthValidator::AUTH_LEVEL_RW, 'Controller::createItem']);

								$r->addGroup(
									'/{id}',
									function(FastRoute\RouteCollector $r) {
										$r->get('[/token/{token}]', [AuthValidator::AUTH_LEVEL_RO, 'Controller::getItem']);
										$r->get('/history', [AuthValidator::AUTH_LEVEL_RO, 'Controller::getHistory']);
										$r->put('', [AuthValidator::AUTH_LEVEL_RW, 'Controller::createItem']);
										$r->delete('', [AuthValidator::AUTH_LEVEL_RW, 'Controller::removeItem']);

										// Useless
										//$r->get('/parent',  [AuthValidator::AUTH_LEVEL_RW, 'Controller::getItemParent']);
										$r->put('/parent', [AuthValidator::AUTH_LEVEL_RW, 'Controller::setItemParent']);
										$r->delete('/parent', [AuthValidator::AUTH_LEVEL_RW, 'Controller::deleteItemParent']);

										//$r->get('/product', [AuthValidator::AUTH_LEVEL_RW, 'Controller::getItemProduct']);
										//$r->put('/product',  [AuthValidator::AUTH_LEVEL_RW, 'Controller::setItemProduct']);
										//$r->delete('/product',  [AuthValidator::AUTH_LEVEL_RW, 'Controller::deleteItemProduct']);

										// Also useless, just get the item
										// $r->get('/features',  [AuthValidator::AUTH_LEVEL_RW, 'Controller::getItemFeatures']);
										$r->put('/features', [AuthValidator::AUTH_LEVEL_RW, 'Controller::setItemFeatures']);
										$r->patch('/features', [AuthValidator::AUTH_LEVEL_RW, 'Controller::updateItemFeatures']);

										// TODO: implement this one
										//$r->get('/path',  [AuthValidator::AUTH_LEVEL_RW, 'Controller::getItemPath']);

										// $r->get('/contents',  [AuthValidator::AUTH_LEVEL_RW, 'Controller::getItemContents']);
									}
								);
							}
						);
						$r->addGroup('/deleted',
							function(FastRoute\RouteCollector $r) {
								$r->addGroup('/{id}',
									function(FastRoute\RouteCollector $r) {
										$r->get('', [AuthValidator::AUTH_LEVEL_RO, 'Controller::getDeletedItem']);
										$r->put('/parent', [AuthValidator::AUTH_LEVEL_RW, 'Controller::restoreItemParent']);
										// TODO: this $r->delete('', [AuthValidator::AUTH_LEVEL_RW, 'Controller::removeItemPermanently']);
									}
								);
							}
						);

						$r->post('/search', [AuthValidator::AUTH_LEVEL_RO, 'Controller::doSearch']);
						$r->patch('/search/{id}', [AuthValidator::AUTH_LEVEL_RO, 'Controller::doSearch']);
						$r->get('/search/{id}[/page/{page}]', [AuthValidator::AUTH_LEVEL_RO, 'Controller::getSearch']);

						$r->get('/features/{feature}/{value}', [AuthValidator::AUTH_LEVEL_RO, 'Controller::getByFeature']);

						$r->addGroup('/products',
							function(FastRoute\RouteCollector $r) {
								$r->get('', [AuthValidator::AUTH_LEVEL_RW, 'Controller::getProduct']); // TODO: implement
								$r->get('/{brand}[/{model}[/{variant}]]', [AuthValidator::AUTH_LEVEL_RW, 'Controller::getProduct']); // TODO: implement

								$r->post('/{brand}/{model}', [AuthValidator::AUTH_LEVEL_RW, 'Controller::createProduct']); // TODO: implement
								$r->put('/{brand}/{model}/{variant}', [AuthValidator::AUTH_LEVEL_RW, 'Controller::createProduct']); // TODO: implement

								$r->addGroup('/{brand}/{model}/{variant}',
									function(FastRoute\RouteCollector $r) {
										//$r->get('/features',  [AuthValidator::AUTH_LEVEL_RW, 'Controller::getProductFeatures']);
										$r->post('/features', [AuthValidator::AUTH_LEVEL_RW, 'Controller::setProductFeatures']); // TODO: implement
										$r->patch('/features', [AuthValidator::AUTH_LEVEL_RW, 'Controller::updateProductFeatures']); // TODO: implement
									}
								);
							}
						);

						$r->get('/logs[/page/{page}]', [AuthValidator::AUTH_LEVEL_RO, 'Controller::getLogs']);

						$r->get('/session', [AuthValidator::AUTH_LEVEL_RW, 'Controller::sessionWhoami']);

						$r->addGroup(
							'/stats',
							function(FastRoute\RouteCollector $r) {
								$r->get('/getItemByNotFeature/{filter}[/{notFeature}[/{location}[/{limit}[/{creation}[/{deleted}]]]]]', [AuthValidator::AUTH_LEVEL_RO, 'Controller::ItemsNotFeature']);
								$r->get('/getRecentAuditByType/{type}[/{howMany}]', [AuthValidator::AUTH_LEVEL_RO, 'Controller::RecentAuditByType']);
								$r->get('/getCountByFeature/{feature}[/{filter}[/{location}[/{creation[/{deleted[/{cutoff}]]]]]', [AuthValidator::AUTH_LEVEL_RO, 'Controller::CountByFeature']);
							}
						);
					}
				);
			},
			[
				'cacheFile' => self::cachefile,
				'cacheDisabled' => !TARALLO_CACHE_ENABLED,
			]
		);

		return $dispatcher->dispatch($request->getMethod(), $request->getUri()->getPath());
	}
}
