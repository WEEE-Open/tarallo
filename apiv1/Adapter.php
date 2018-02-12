<?php

namespace WEEEOpen\Tarallo\Server\v1;

use WEEEOpen\Tarallo\Server\AuthenticationException;
use WEEEOpen\Tarallo\Server\AuthorizationException;
use WEEEOpen\Tarallo\Server\Database\Database;
use WEEEOpen\Tarallo\Server\ItemIncomplete;
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
		$username = self::validateString($payload, 'username');
		$password = self::validateString($payload, 'password');
		Session::start(new User($username, $password), $db);
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
			$item = $db->itemDAO()->getItem(new ItemIncomplete($id), $token);
		}
	}

	/**
	 * Check that an user is authorized (and authenticated too, or the entire thing won't make any sense)
	 *
	 * @param User|null $user Current, logged-in user. Or none if not authenticated.
	 * @param int $level Permission level required
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
	 * Or a number, which will be cast to a string because nobody cares.
	 *
	 * @param array $payload THE array
	 * @param mixed $key Some key
	 *
	 * @return string
	 */
	private static function validateString($payload, $key) {
		$value = self::validateHas($payload, $key);
		if(is_string($value)) {
			return $value;
		} else if(is_numeric($value)) {
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
}