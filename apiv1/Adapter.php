<?php

namespace WEEEOpen\Tarallo\Server\v1;

use WEEEOpen\Tarallo\Server\AuthenticationException;
use WEEEOpen\Tarallo\Server\AuthorizationException;
use WEEEOpen\Tarallo\Server\Database\Database;
use WEEEOpen\Tarallo\Server\Database\TreeDAO;
use WEEEOpen\Tarallo\Server\ItemFeatures;
use WEEEOpen\Tarallo\Server\ItemIncomplete;
use WEEEOpen\Tarallo\Server\NotFoundException;
use WEEEOpen\Tarallo\Server\Session;
use WEEEOpen\Tarallo\Server\User;
use WEEEOpen\Tarallo\Server\UserAnonymous;

class Adapter {
	public static function sessionWhoami(User $user, Database $db, $parameters, $querystring, $payload) {
		self::authorize($user);

		return ['username' => $user->getUsername()];
	}

	public static function sessionStart(User $user, Database $db, $parameters, $querystring, $payload) {
		self::validateArray($payload);
		$username = self::validateHasString($payload, 'username');
		$password = self::validateHasString($payload, 'password');
		$user = $db->userDAO()->getUserFromLogin($username, $password);
		if($user === null) {
			throw new InvalidPayloadParameterException('*', '', 'Wrong username or password');
		}
		Session::start($user, $db);

		return null;
	}

	public static function sessionClose(User $user, Database $db, $parameters, $querystring, $payload) {
		// If we ever add another level for e.g. banned users, this at least allows them to log out
		self::authenticate($user);
		Session::close($user, $db);

		return null;
	}

	public static function sessionRefresh(User $user, Database $db, $parameters, $querystring, $payload) {
		// The refresh itself has already been done by Session::restore, sooooo...
		self::authenticate($user);

		return null;
	}

	public static function getItem(User $user, Database $db, $parameters, $querystring, $payload) {
		$id = isset($parameters['id']) ? (string) $parameters['id'] : null;
		$token = isset($parameters['token']) ? (string) $parameters['token'] : null;

		if($token === null) {
			self::authorize($user);
		}

		if($id === null) {
			throw new \LogicException('Not implemented');
		} else {
			return $db->itemDAO()->getItem(new ItemIncomplete($id), $token);
		}
	}

	public static function createItem(User $user, Database $db, $parameters, $querystring, $payload) {
		self::authorize($user);
		$id = isset($parameters['id']) ? (string) $parameters['id'] : null;

		$item = ItemBuilder::ofArray($payload, $id, $parent);
		$db->itemDAO()->addItem($item, $parent);

		return $db->itemDAO()->getItem($item);
	}

	public static function removeItem(User $user, Database $db, $parameters, $querystring, $payload) {
		self::authorize($user);
		$id = isset($parameters['id']) ? (string) $parameters['id'] : null;

		$db->itemDAO()->deleteItem(new ItemIncomplete($id));

		return null;
	}

	public static function setItemParent(User $user, Database $db, $parameters, $querystring, $payload) {
		self::authorize($user);
		self::validateIsString($payload);
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

	public static function setItemFeatures(User $user, Database $db, $parameters, $querystring, $payload) {
		self::authorize($user);
		self::validateArray($payload);
		$id = isset($parameters['id']) ? (string) $parameters['id'] : null;

		$item = new ItemFeatures($id);
		// PUT => delete every feature, replace with new ones
		ItemBuilder::addFeatures($payload, $item);
		$db->featureDAO()->deleteFeaturesAll($item);
		$db->featureDAO()->setFeatures($item);

		return $db->itemDAO()->getItem($item);
	}

	public static function updateItemFeatures(User $user, Database $db, $parameters, $querystring, $payload) {
		self::authorize($user);
		self::validateArray($payload);
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
	 * Check that an user is authorized (and authenticated too, or the entire thing won't make any sense)
	 *
	 * @param User|null $user Current, logged-in user. Or none if not authenticated.
	 * @param int $level Permission level required
	 *
	 * @see User::getLevel()
	 */
	private static function authorize(User $user, $level = 3) {
		self::authenticate($user);
		if($user->getLevel() > $level) {
			throw new AuthorizationException();
		}
	}

	/**
	 * Check that user is a valid user.
	 * You probably want authorize() instead, which also checks permission.
	 *
	 * @see Adapter::authorize()
	 *
	 * @param User $user
	 */
	private static function authenticate(User $user) {
		if($user instanceof UserAnonymous) {
			throw new AuthenticationException();
		}
	}

	/**
	 * Check that payload array has a key and it's not null
	 *
	 * @param array $payload THE array
	 * @param mixed $key Some key
	 *
	 * @return mixed Value for that key
	 */
	private static function validateHas(array $payload, $key) {
		if(isset($payload[$key])) {
			return $payload[$key];
		} else {
			throw new InvalidPayloadParameterException($key);
		}
	}

	/**
	 * Check that key exists and it's a string.
	 * Or an int, which will be cast to a string because nobody cares.
	 *
	 * @param array $payload THE array
	 * @param mixed $key Some key
	 *
	 * @return string
	 */
	private static function validateHasString($payload, $key) {
		$value = self::validateHas($payload, $key);
		if(is_string($value)) {
			return $value;
		} else if(is_int($value)) {
			return (string) $value;
		} else {
			throw new InvalidPayloadParameterException($key, $value);
		}
	}

	/**
	 * Check that an array ($payload, basically) is
	 * actually an array and contains something.
	 *
	 * @param mixed $array
	 */
	private static function validateArray($array) {
		if(!is_array($array)) {
			throw new InvalidPayloadParameterException('*', null, 'Missing request body');
		}
		if(empty($array)) {
			throw new InvalidPayloadParameterException('*', null, 'Empty request body');
		}
	}

	/**
	 * Validate that the entire payload (or some other variable) IS a string
	 * and it's no empty. Or is a number, which will be converted into a string.
	 *
	 * @param $possiblyString
	 */
	private static function validateIsString($possiblyString) {
		if(is_int($possiblyString)) {
			$possiblyString = (string) $possiblyString;
		}
		if(!is_string($possiblyString)) {
			throw new InvalidPayloadParameterException('*', '', 'Request body should be a string');
		}
		if(trim($possiblyString) === '') {
			throw new InvalidPayloadParameterException('*', '', 'Empty string is not acceptable');
		}
	}
}