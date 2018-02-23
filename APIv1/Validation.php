<?php

namespace WEEEOpen\Tarallo\APIv1;

use WEEEOpen\Tarallo\Server\User;

class Validation {
	/**
	 * Check that an user is authorized (and authenticated too, or the entire thing won't make any sense)
	 *
	 * @param User|null $user Current, logged-in user. Or none if not authenticated.
	 * @param int $level Permission level required
	 *
	 * @see User::getLevel()
	 */
	public static function authorize(User $user = null, $level = 3) {
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
	public static function authenticate(User $user = null) {
		if(!($user instanceof User)) {
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
	public static function validateHas(array $payload, $key) {
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
	public static function validateHasString($payload, $key) {
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
	public static function validateArray($array) {
		if(!is_array($array)) {
			throw new InvalidPayloadParameterException('*', null, 'Missing request body');
		}
		if(empty($array)) {
			throw new InvalidPayloadParameterException('*', null, 'Empty request body');
		}
	}

	/**
	 * Validate that the entire payload (or some other variable) IS a string
	 * and it's not empty. Or is a number, which will be converted into a string.
	 *
	 * @param $possiblyString
	 */
	public static function validateIsString($possiblyString) {
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
