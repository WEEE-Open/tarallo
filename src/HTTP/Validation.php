<?php

namespace WEEEOpen\Tarallo\Server\HTTP;

use WEEEOpen\Tarallo\Server\ItemCode;
use WEEEOpen\Tarallo\Server\NotFoundException;
use WEEEOpen\Tarallo\Server\User;
use WEEEOpen\Tarallo\Server\ValidationException;

class Validation {
	/**
	 * Check that an user is authorized (and authenticated too, or the entire thing won't make any sense)
	 *
	 * @param User|null $user Current, logged-in user. Or none if not authenticated.
	 * @param int $level Permission level required (default is "read/write")
	 *
	 * @see User::getLevel()
	 */
	public static function authorize(?User $user, $level = 2) {
		self::authenticate($user);
		if($user->getLevel() > $level) {
			throw new AuthorizationException();
		}
	}

	/**
	 * Check that user is a valid user.
	 * You probably want authorize() instead, which also checks permission.
	 *
	 * @see Controller::authorize()
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
	 * @throws InvalidPayloadParameterException if key is missing
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
	 * @param string $key Some key
	 *
	 * @throws InvalidPayloadParameterException if key is missing or not a string
	 *
	 * @return string the string
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
	 * Return string value from a key if it exists (being casted if not a string),
	 * or supplied default value (default null) if it doesn't.
	 * Empty string is considered valid, maybe? I don't know anymore
	 *
	 * TODO: where is the empty string used? Is it valid or not, actually?
	 *
	 * @param array $payload THE array
	 * @param string $key Some key
	 * @param null|string $default Default value if there's no such key
	 * @param null|string $emptyString What to do when you get an empty string (default: return it)
	 *
	 * @return string|null Whatever the value is, or $default
	 */
	public static function validateOptionalString(
		array $payload,
		string $key,
		?string $default = null,
		?string $emptyString = ''
	) {
		try {
			$string = (string) self::validateHas($payload, $key);
			if($string === '') {
				return $emptyString;
			} else {
				return $string;
			}
		} catch(InvalidPayloadParameterException $e) {
			return $default;
		}
	}

	/**
	 * Return int value form a key if it exists (being casted if not a string),
	 * or supplied default value if it doesn't
	 *
	 * @param array $payload THE array
	 * @param string $key Some key
	 *
	 * @param int|null $default Default value if there's no such key
	 *
	 * @return int|null Whatever the value is, or $default
	 */
	public static function validateOptionalInt(array $payload, string $key, ?int $default = null) {
		try {
			return (int) self::validateHas($payload, $key);
		} catch(InvalidPayloadParameterException $e) {
			return $default;
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

	/**
	 * Return a new ItemIncomplete or throw a NotFoundException if code is invalid
	 *
	 * @param string $code
	 *
	 * @return ItemCode
	 * @deprecated handle all exceptions in controllers
	 */
	public static function newItemIncomplete(string $code) {
		try {
			return new ItemCode($code);
		} catch(ValidationException $e) {
			if($e->getCode() === 5) {
				throw new NotFoundException();
			}
			throw $e;
		}
	}

	/**
	 * Split a feature=value pair, or throw an exception if invalid
	 *
	 * @param string $pair
	 *
	 * @return array ['feature', 'value']
	 */
	public static function explodeFeatureValuePair(string $pair) {
		$explosion = explode('=', $pair);
		if(sizeof($explosion) !== 2) {
			throw new \InvalidArgumentException('Invalid format for feature and value: ' . $explosion);
		}
		return $explosion;
	}
}
