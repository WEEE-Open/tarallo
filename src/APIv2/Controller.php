<?php

namespace WEEEOpen\Tarallo\APIv2;

use FastRoute;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Relay\RelayBuilder;
use WEEEOpen\Tarallo\BaseFeature;
use WEEEOpen\Tarallo\Database\Database;
use WEEEOpen\Tarallo\Database\TreeDAO;
use WEEEOpen\Tarallo\DuplicateBulkIdentifierException;
use WEEEOpen\Tarallo\ErrorHandler;
use WEEEOpen\Tarallo\Feature;
use WEEEOpen\Tarallo\HTTP\AuthManager;
use WEEEOpen\Tarallo\HTTP\AuthTokenManager;
use WEEEOpen\Tarallo\HTTP\AuthValidator;
use WEEEOpen\Tarallo\HTTP\DatabaseConnection;
use WEEEOpen\Tarallo\HTTP\InvalidParameterException;
use WEEEOpen\Tarallo\HTTP\TransactionWrapper;
use WEEEOpen\Tarallo\HTTP\Validation;
use WEEEOpen\Tarallo\Item;
use WEEEOpen\Tarallo\ItemCode;
use WEEEOpen\Tarallo\ItemPrefixerException;
use WEEEOpen\Tarallo\ItemValidator;
use WEEEOpen\Tarallo\ItemWithCode;
use WEEEOpen\Tarallo\NotFoundException;
use WEEEOpen\Tarallo\Product;
use WEEEOpen\Tarallo\ProductCode;
use WEEEOpen\Tarallo\ProductException;
use WEEEOpen\Tarallo\SearchException;
use WEEEOpen\Tarallo\StateChangedException;
use WEEEOpen\Tarallo\User;
use WEEEOpen\Tarallo\ValidationException;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;


class Controller implements RequestHandlerInterface {
	const cachefile = __DIR__ . '/../../resources/cache/APIv2.cache';

	public static function sessionWhoami(ServerRequestInterface $request): ResponseInterface {
		/** @var User $user */
		$user = $request->getAttribute('User');

		return new JsonResponse(['username' => $user->uid, 'cn' => $user->cn, 'level' => $user->getLevel()]);
	}

	public static function getItem(ServerRequestInterface $request): ResponseInterface {
		/** @var Database $db */
		$db = $request->getAttribute('Database');
		$parameters = $request->getAttribute('parameters', []);
		$query = $request->getQueryParams();

		$id = Validation::validateOptionalString($parameters, 'id');
		$token = Validation::validateOptionalString($parameters, 'token');
		$depth = Validation::validateOptionalString($query, 'depth');

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
			if(isset($query['separate'])) {
				$data->setSeparate();
			}
			return new JsonResponse($data);
		}
	}

	public static function getProduct(ServerRequestInterface $request): ResponseInterface {
		/** @var Database $db */
		$db = $request->getAttribute('Database');
		$parameters = $request->getAttribute('parameters', []);

		$brand = Validation::validateOptionalString($parameters, 'brand');
		$model = Validation::validateOptionalString($parameters, 'model');
		$variant = Validation::validateOptionalString($parameters, 'variant');

		try {
			$product = new ProductCode($brand, $model, $variant);
		} catch(ValidationException $e) {
			throw new NotFoundException();
		}

		$data = $db->productDAO()->getProduct($product);

		return new JsonResponse($data);
	}

	public static function getProducts(ServerRequestInterface $request): ResponseInterface {
		/** @var Database $db */
		$db = $request->getAttribute('Database');
		$parameters = $request->getAttribute('parameters', []);

		$brand = Validation::validateOptionalString($parameters, 'brand');
		$model = Validation::validateOptionalString($parameters, 'model');

		$data = $db->productDAO()->getProducts($brand, $model);

		return new JsonResponse($data);
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
			throw new NotFoundException($payload, 'Location doesn\'t exist', 0, $e);
		}

		$db->itemDAO()->undelete($item);
		[$oldParent, $newParentActual, $moved] = TreeDAO::moveWithValidation($db, $item, $newParent, $fix, $validate);
		$created = $oldParent === null && $moved;

		$response = self::generateMoveResponse($item, $moved, $newParent, $newParentActual, $oldParent);
		if($created) {
			return new JsonResponse($response,201);
		} else {
			return new JsonResponse($response);
		}
	}

	public static function getByFeature(ServerRequestInterface $request): ResponseInterface {
		/** @var Database $db */
		$db = $request->getAttribute('Database');
		$parameters = $request->getAttribute('parameters', []);

		$id = (string) $parameters['feature'];
		$value = (string) $parameters['value'];
		$limit = Validation::validateOptionalInt($parameters, 'limit', 5);

		self::range('limit', $limit, 1, 10);

		try {
			if(BaseFeature::getType($id) !== BaseFeature::STRING) {
				// TODO: throw notImplementedException or something
				throw new InvalidParameterException('feature', $id, "Only text features are supported, $id isn't");
			}
		} catch(\InvalidArgumentException $e) {
			throw new InvalidParameterException('feature', $id, $e->getMessage(), 0, $e);
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
		$importId = Validation::validateOptionalInt($query, 'import');

		$item = ItemBuilder::ofArray($payload, $id, $parent);

		if($importId) {
			$exists = $db->bulkDAO()->identifierExistsAndLocked($importId);
			if(!$exists) {
				throw new StateChangedException('The imported product does not exist anymore, are you trying to create it twice or using an old version of the data?');
			}
		}

		// Validation and fixupLocation requires the full parent item, which may not exist.
		// Since this part is optional, its existence will be checked again later
		if($parent instanceof ItemCode && ($fix || $validate)) {
			try {
				$parent = $db->itemDAO()->getItem($parent, null, 1);
			} catch(NotFoundException $e) {
				throw new NotFoundException($parent->getCode(), 'Location doesn\'t exist', 0, $e);
			}
		}

		$flat = $item->getFlatContent();
		ItemValidator::addAllVariants($flat);

		// We need product features for fixup and validation
		if($fix || $validate) {
			$db->productDAO()->getProductsAll($flat);
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

		// Remove import once we are sure that product is added
		if($importId) {
			$db->bulkDAO()->deleteImport($importId);
		}

		if($loopback) {
			$response = new JsonResponse($db->itemDAO()->getItem($item), 201);
			$response = $response->withHeader('Location', '/v2/items/' . rawurlencode($item->getCode()));
		} else {
			// TODO: wrap into something
			$response = new JsonResponse($item->getCode(), 201);
			$response = $response->withHeader('Location', '/v2/items/' . rawurlencode($item->getCode()));
		}
		return $response;
	}

	public static function createProduct(ServerRequestInterface $request): ResponseInterface {
		/** @var Database $db */
		$db = $request->getAttribute('Database');
		$query = $request->getQueryParams();
		$payload = $request->getAttribute('ParsedBody', []);
		$parameters = $request->getAttribute('parameters', []);

		$brand = Validation::validateOptionalString($parameters, 'brand');
		$model = Validation::validateOptionalString($parameters, 'model');
		$variant = Validation::validateOptionalString($parameters, 'variant');
		$importId = Validation::validateOptionalInt($query, 'import');;
		$loopback = isset($query['loopback']);
		$validate = !isset($query['novalidate']);

		$product = ProductBuilder::ofArray($payload, $brand, $model, $variant);

		if($importId) {
			$exists = $db->bulkDAO()->identifierExistsAndLocked($importId);
			if(!$exists) {
				throw new StateChangedException('The imported product does not exist anymore, are you trying to create it twice or using an old version of the data?');
			}
		}

		if($validate) {
			ItemValidator::validateFeatures($product);
		}

		$db->productDAO()->addProduct($product);

		// Remove import once we are sure that product is added
		if($importId) {
			$db->bulkDAO()->deleteImport($importId);
		}

		if($loopback) {
			$response = new JsonResponse($db->productDAO()->getProduct($product), 201);
			$response = $response->withHeader('Location', '/v2/products/' . rawurlencode($product->getBrand()) . '/' . rawurlencode($product->getModel()) . '/' . rawurlencode($product->getVariant()));
		} else {
			$response = new JsonResponse(['brand' => $product->getBrand(), 'model' => $product->getModel(), 'variant' => $product->getVariant()], 201);
			$response = $response->withHeader('Location', '/v2/products/' . rawurlencode($product->getBrand()) . '/' . rawurlencode($product->getModel()) . '/' . rawurlencode($product->getVariant()));
		}
		return $response;
	}

	public static function renameProduct(ServerRequestInterface $request): ResponseInterface {
		/** @var Database $db */
		$db = $request->getAttribute('Database');
		$query = $request->getQueryParams();
		$payload = $request->getAttribute('ParsedBody', []);
		$parameters = $request->getAttribute('parameters', []);

		$brand = Validation::validateOptionalString($parameters, 'brand');
		$model = Validation::validateOptionalString($parameters, 'model');
		$variant = Validation::validateOptionalString($parameters, 'variant');
		$loopback = isset($query['loopback']);

		$brandNew = Validation::validateOptionalString($payload, 'brand', null, null);
		$modelNew = Validation::validateOptionalString($payload, 'model', null, null);
		$variantNew = Validation::validateOptionalString($payload, 'variant', null, null);

		$product = new ProductCode($brand, $model, $variant);
		$product = $db->productDAO()->renameProduct($product, $brandNew, $modelNew, $variantNew);

		// TODO: why do these headers break everything? Why do they break everything *ONLY* for this method?
		if($loopback) {
			$response = new JsonResponse($db->productDAO()->getProduct($product), 200);
			//$response = $response->withHeader('Location', '/v2/products/' . rawurlencode($product->getBrand()) . '/' . rawurlencode($product->getModel()) . '/' . rawurlencode($product->getVariant()));
		} else {
			$response = new JsonResponse(['brand' => $product->getBrand(), 'model' => $product->getModel(), 'variant' => $product->getVariant()], 200);
			//$response = $response->withHeader('Location', '/v2/products/' . rawurlencode($product->getBrand()) . '/' . rawurlencode($product->getModel()) . '/' . rawurlencode($product->getVariant()));
		}
		return $response;
	}

	public static function removeItem(ServerRequestInterface $request): ResponseInterface {
		/** @var Database $db */
		$db = $request->getAttribute('Database');
		$parameters = $request->getAttribute('parameters', []);

		$id = Validation::validateHasString($parameters, 'id');

		try {
			$id = new ItemCode($id);
		} catch(ValidationException $e) {
			throw new NotFoundException($id);
		}

		$db->itemDAO()->deleteItem($id);

		return new EmptyResponse(204);
	}

	public static function deleteProduct(ServerRequestInterface $request): ResponseInterface {
		/** @var Database $db */
		$db = $request->getAttribute('Database');
		$query = $request->getQueryParams();
		$parameters = $request->getAttribute('parameters', []);

		$brand = Validation::validateOptionalString($parameters, 'brand');
		$model = Validation::validateOptionalString($parameters, 'model');
		$variant = Validation::validateOptionalString($parameters, 'variant');

		$product = new ProductCode($brand, $model, $variant);

		$db->productDAO()->productMustExist($product);

		if(!isset($query['force'])) {
			$count = $db->productDAO()->countItemsAndLock($product);
			if($count > 0) {
				if($count === 1) {
					$message = "There is 1 item of $product, you can only delete products that are not referenced by any item";
				} else {
					$message = "There are $count items of $product, you can only delete products that are not referenced by any item";
				}
				throw new ProductException($product, $message);
			}
		}

		$found = $db->productDAO()->deleteProduct($product);

		if($found) {
			return new EmptyResponse();
		} else {
			throw new NotFoundException();
		}
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

		[$oldParent, $newParentActual, $moved] = TreeDAO::moveWithValidation($db, $item, $newParent, $fix, $validate);
		$created = $oldParent === null && $moved;

		$response = self::generateMoveResponse($item, $moved, $newParent, $newParentActual, $oldParent);
		if($created) {
			return new JsonResponse($response,201);
		} else {
			return new JsonResponse($response);
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

	public static function setFeatures(ServerRequestInterface $request): ResponseInterface {
		/** @var Database $db */
		$db = $request->getAttribute('Database');
		$query = $request->getQueryParams();
		$payload = $request->getAttribute('ParsedBody', []);
		$parameters = $request->getAttribute('parameters', []);
		$validate = !isset($query['novalidate']);
		$loopback = isset($query['loopback']);

		$id = Validation::validateOptionalString($parameters, 'id', null);
		Validation::validateRequestBodyIsArray($payload);

		$thing = self::getProductOrItemForUpdate($db, $id, $parameters);

		// PUT => delete every feature, replace with new ones
		ItemBuilder::addFeatures($payload, $thing);

		if($validate) {
			ItemValidator::validateFeatures($thing);
		}

		$deleted = $db->featureDAO()->deleteFeaturesAll($thing);
		$added = $db->featureDAO()->setFeatures($thing);

		if($deleted && !$added) {
			// Delete everything and replace with an empty array => we need to generate an audit entry
			$db->featureDAO()->addAuditEntry($thing);
		}

		if($loopback) {
			if($id === null) {
				return new JsonResponse($db->itemDAO()->getItem($thing));
			} else {
				return new JsonResponse($db->productDAO()->getProduct($thing));
			}
		} else {
			return new EmptyResponse(204);
		}
	}

	public static function updateFeatures(ServerRequestInterface $request): ResponseInterface {
		/** @var Database $db */
		$db = $request->getAttribute('Database');
		$query = $request->getQueryParams();
		$payload = $request->getAttribute('ParsedBody', []);
		$parameters = $request->getAttribute('parameters', []);
		$validate = !isset($query['novalidate']);
		$loopback = isset($query['loopback']);

		$id = Validation::validateOptionalString($parameters, 'id', null);
		Validation::validateRequestBodyIsArray($payload);

		$thing = self::getProductOrItemForUpdate($db, $id, $parameters);

		// PATCH => specify features to update and to delete, other are left as they are
		$delete = ItemBuilder::addFeaturesDelta($payload, $thing);

		if($validate) {
			if($id === null) {
				$thingWithFullFeatures = $db->productDAO()->getProduct($thing);
			} else {
				$thingWithFullFeatures = $db->itemDAO()->getItem($thing);
			}

			foreach($delete as $deleteThis) {
				$thingWithFullFeatures->removeFeatureByName($deleteThis);
			}
			foreach($thing->getFeatures() as $addThis) {
				$thingWithFullFeatures->addFeature($addThis);
			}
			ItemValidator::validateFeatures($thingWithFullFeatures);
		}

		$deleted = $db->featureDAO()->deleteFeatures($thing, $delete);
		$changed = $db->featureDAO()->setFeatures($thing);

		// setFeatures generates an audit entry if anything changed, deleteFeatures never does
		// so we may need to generate it manually
		if($deleted && !$changed) {
			$db->featureDAO()->addAuditEntry($thing);
		}

		if($loopback) {
			if($id === null) {
				return new JsonResponse($db->productDAO()->getProduct($thing));
			} else {
				return new JsonResponse($db->itemDAO()->getItem($thing));
			}
		} else {
			return new EmptyResponse(204);
		}
	}

	public static function doSearch(ServerRequestInterface $request): ResponseInterface {
		/** @var Database $db */
		$db = $request->getAttribute('Database');
		/** @var User $user */
		$user = $request->getAttribute('User');
		$payload = $request->getAttribute('ParsedBody', []);
		$parameters = $request->getAttribute('parameters', []);

		Validation::validateRequestBodyIsArray($payload);

		$id = Validation::validateOptionalString($parameters, 'id');

		if($id) {
			// Refining a search: must be owner or admin
			$username = $db->searchDAO()->getOwnerUsername($id);
			if($username !== $user->uid) {
				AuthValidator::ensureLevel($user, User::AUTH_LEVEL_ADMIN);
			}
		}

		$search = SearchBuilder::ofArray($payload);
		$resultId = $db->searchDAO()->search($search, $user, $id);

		return new JsonResponse($resultId);
	}

	public static function getSearch(ServerRequestInterface $request): ResponseInterface {
		/** @var Database $db */
		$db = $request->getAttribute('Database');
		$parameters = $request->getAttribute('parameters', []);
		$query = $request->getQueryParams();

		$id = Validation::validateHasString($parameters, 'id');
		$page = Validation::validateOptionalInt($parameters, 'page', 1);
		$depth = Validation::validateOptionalInt($query, 'depth');
		$perPage = Validation::validateOptionalInt($query, 'perPage', 10);

		if($page <= 0) {
			$page = 1;
		}
		if($perPage <= 0) {
			$perPage = 10;
		}

		$results = $db->searchDAO()->getResults($id, $page, $perPage, $depth);

		return new JsonResponse($results);
	}

	public static function getHistory(ServerRequestInterface $request): ResponseInterface {
		/** @var Database $db */
		$db = $request->getAttribute('Database');
		$query = $request->getQueryParams();
		$parameters = $request->getAttribute('parameters', []);

		$page = Validation::validateOptionalInt($parameters, 'page', 1);
		$limit = Validation::validateOptionalInt($query, 'limit', 20);

		self::range('page', $page, 1, null);
		self::range('limit', $limit, 1, 50);

		$data = $db->auditDAO()->getRecentAudit($limit, $page);

		return new JsonResponse($data);
	}

	public static function getItemHistory(ServerRequestInterface $request): ResponseInterface {
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

		self::range('limit', $length, 1, 50);

		$data = $db->auditDAO()->getItemHistory($item, $length);

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
			throw new SearchException($e->getMessage());
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

	public static function addBulk(ServerRequestInterface $request): ResponseInterface {
		/** @var Database $db */
		$db = $request->getAttribute('Database');
		$parameters = $request->getAttribute('parameters');
		$query = $request->getQueryParams();
		$identifier = Validation::validateOptionalString($parameters, 'identifier');
		$body = $request->getAttribute('ParsedBody', []);

		if($identifier === null) {
			$identifier = 'Automated upload ' . strtoupper(substr(md5(time()), 0, 8));
		}
		$overwrite = boolval($query['overwrite'] ?? false);

		$isDuplicate = $db->bulkDAO()->bulkIdentifierExistsAndLocked($identifier);
		if($overwrite) {
			$db->bulkDAO()->deleteBulkImport($identifier);
		} else if($isDuplicate) {
			throw new DuplicateBulkIdentifierException();
		}
		foreach($body as $item) {
			$type = $item['type'];
			$json = json_encode($item);
			$db->bulkDAO()->addBulk($identifier, $type, $json);
		}

		return new EmptyResponse();
	}

	public static function getItems(ServerRequestInterface $request){
		$db = $request->getAttribute('Database');
		$query = $request->getQueryParams();
		$search = Validation::validateHasString($query, 'q');
		$json = $db->itemDAO()->getItems($search);
		return new JsonResponse($json);
	}

	private static function range(string $parameter, $value, ?int $min, ?int $max) {
		if($max !== null && $value > $max) {
			throw new RangeException($parameter, $min, $max, "Maximum value is $max");
		}
		if($min !== null && $value < $min) {
			throw new RangeException($parameter, $min, $max, "Minimum value is $min");
		}
	}

	/**
	 * @param Database $db
	 * @param string|null $id
	 * @param $parameters
	 *
	 * @return Item|Product
	 */
	private static function getProductOrItemForUpdate(Database $db, ?string $id, $parameters) {
		if($id === null) {
			$brand = Validation::validateOptionalString($parameters, 'brand');
			$model = Validation::validateOptionalString($parameters, 'model');
			$variant = Validation::validateOptionalString($parameters, 'variant');
			$thing = new Product($brand, $model, $variant);
			$db->productDAO()->productMustExist($thing);
		} else {
			$thing = new Item($id);
			$db->itemDAO()->itemMustExist($thing, true);
		}
		return $thing;
	}

	protected static function generateMoveResponse(ItemWithCode $item, $moved, ItemCode $newParent, $newParentActual, $oldParent): array {
		$response = [
			'code' => $item->getCode(),
			'from' => $oldParent,
			'to' => $newParent->getCode(),
			'moved' => $moved,
		];
		if($newParent->compareCode($newParentActual) !== 0) {
			$response['actual'] = $newParentActual->getCode();
		}
		return $response;
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
			new AuthTokenManager(),
			new AuthManager(false),
		];
		if($level !== null) {
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
								$r->get('', [User::AUTH_LEVEL_RO, 'Controller::getItem']);
								$r->post('', [User::AUTH_LEVEL_RW, 'Controller::createItem']);

								$r->addGroup(
									'/{id}',
									function(FastRoute\RouteCollector $r) {
										// TODO: make token access public
										$r->get('[/token/{token}]', [User::AUTH_LEVEL_RO, 'Controller::getItem']);
										$r->get('/history', [User::AUTH_LEVEL_RO, 'Controller::getItemHistory']);
										$r->put('', [User::AUTH_LEVEL_RW, 'Controller::createItem']);
										$r->delete('', [User::AUTH_LEVEL_RW, 'Controller::removeItem']);

										// Useless
										//$r->get('/parent',  [User::AUTH_LEVEL_RW, 'Controller::getItemParent']);
										$r->put('/parent', [User::AUTH_LEVEL_RW, 'Controller::setItemParent']);
										$r->delete('/parent', [User::AUTH_LEVEL_RW, 'Controller::deleteItemParent']);

										//$r->get('/product', [User::AUTH_LEVEL_RW, 'Controller::getItemProduct']);
										//$r->put('/product',  [User::AUTH_LEVEL_RW, 'Controller::setItemProduct']);
										//$r->delete('/product',  [User::AUTH_LEVEL_RW, 'Controller::deleteItemProduct']);

										// Also useless, just get the item
										// $r->get('/features',  [User::AUTH_LEVEL_RW, 'Controller::getItemFeatures']);
										$r->put('/features', [User::AUTH_LEVEL_RW, 'Controller::setFeatures']);
										$r->patch('/features', [User::AUTH_LEVEL_RW, 'Controller::updateFeatures']);

										// TODO: implement this one
										//$r->get('/path',  [User::AUTH_LEVEL_RW, 'Controller::getItemPath']);

										// $r->get('/contents',  [User::AUTH_LEVEL_RW, 'Controller::getItemContents']);
									}
								);
							}
						);
						$r->addGroup('/deleted',
							function(FastRoute\RouteCollector $r) {
								$r->addGroup('/{id}',
									function(FastRoute\RouteCollector $r) {
										$r->get('', [User::AUTH_LEVEL_RO, 'Controller::getDeletedItem']);
										$r->put('/parent', [User::AUTH_LEVEL_RW, 'Controller::restoreItemParent']);
										// TODO: this $r->delete('', [User::AUTH_LEVEL_RW, 'Controller::removeItemPermanently']);
									}
								);
							}
						);

						$r->post('/search', [User::AUTH_LEVEL_RO, 'Controller::doSearch']);
						$r->patch('/search/{id}', [User::AUTH_LEVEL_RO, 'Controller::doSearch']);
						$r->get('/search/{id}[/page/{page}]', [User::AUTH_LEVEL_RO, 'Controller::getSearch']);

						$r->get('/features/{feature}/{value}', [User::AUTH_LEVEL_RO, 'Controller::getByFeature']);

						$r->addGroup('/products',
							function(FastRoute\RouteCollector $r) {
								//$r->get('', [User::AUTH_LEVEL_RO, 'Controller::getProducts']);
								$r->get('/{brand}/{model}', [User::AUTH_LEVEL_RO, 'Controller::getProducts']);
								$r->get('/{brand}/{model}/{variant}', [User::AUTH_LEVEL_RO, 'Controller::getProduct']);
								$r->put('/{brand}/{model}/{variant}', [User::AUTH_LEVEL_RW, 'Controller::createProduct']);
								$r->patch('/{brand}/{model}/{variant}', [User::AUTH_LEVEL_RW, 'Controller::renameProduct']);
								$r->delete('/{brand}/{model}/{variant}', [User::AUTH_LEVEL_RW, 'Controller::deleteProduct']);

								$r->addGroup('/{brand}/{model}/{variant}/features',
									function(FastRoute\RouteCollector $r) {
										//$r->get('',  [User::AUTH_LEVEL_RW, 'Controller::getProductFeatures']);
										$r->post('', [User::AUTH_LEVEL_RW, 'Controller::setFeatures']);
										$r->patch('', [User::AUTH_LEVEL_RW, 'Controller::updateFeatures']);
									}
								);
							}
						);

						$r->get('/history[/page/{page}]', [User::AUTH_LEVEL_RO, 'Controller::getHistory']);

						$r->get('/session', [User::AUTH_LEVEL_RW, 'Controller::sessionWhoami']);
						$r->get('/autosuggest', [User::AUTH_LEVEL_RO, 'Controller::getItems']);

						$r->addGroup(
							'/stats',
							function(FastRoute\RouteCollector $r) {
								$r->get('/getItemByNotFeature/{filter}[/{notFeature}[/{location}[/{limit}[/{creation}[/{deleted}]]]]]', [User::AUTH_LEVEL_RO, 'Controller::ItemsNotFeature']);
								$r->get('/getRecentAuditByType/{type}[/{howMany}]', [User::AUTH_LEVEL_RO, 'Controller::RecentAuditByType']);
								$r->get('/getCountByFeature/{feature}[/{filter}[/{location}[/{creation[/{deleted[/{cutoff}]]]]]', [User::AUTH_LEVEL_RO, 'Controller::CountByFeature']);
							}
						);
						$r->addGroup(
							'/bulk',
							function(FastRoute\RouteCollector $r) {
								$r->post('/add[/{identifier}]', [User::AUTH_LEVEL_RO, 'Controller::addBulk']);
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
