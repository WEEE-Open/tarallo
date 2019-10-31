<?php

namespace WEEEOpen\Tarallo\HTTP;

use WEEEOpen\Tarallo\ItemCode;
use WEEEOpen\Tarallo\NotFoundException;
use WEEEOpen\Tarallo\ValidationException;

class Validation {
	/**
	 * Check that key exists and it's a non-empty string.
	 * If it's not a string, it will be cast to a string: this is used for query parameters and FastRoute parameters,
	 * mainly, which are always strings so that case shouldn't even happen.
	 *
	 * @param array $payload THE array
	 * @param string $key Some key
	 *
	 * @return string the string
	 * @throws MissingMandatoryParameterException
	 */
	public static function validateHasString(array $payload, string $key): string {
		if(!isset($payload[$key])) {
			throw new MissingMandatoryParameterException($key);
		}
		$value = $payload[$key];
		if(is_string($value)) {
			return $value;
		} else {
			return (string) $value;
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
		if(!isset($payload[$key])) {
			return $default;
		}
		$value = $payload[$key];
		if($value === '') {
			return $emptyString;
		}
		return $value;
	}

	/**
	 * Return int value form a key if it exists (being casted if not an int),
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
		if(isset($payload[$key])) {
			return (int) $payload[$key];
		} else {
			return $default;
		}
	}

	/**
	 * Check that the JSON request body contains something: that is, it is an array and is not empty
	 *
	 * @param mixed $possiblyArray
	 *
	 * @throws InvalidRequestBodyException
	 */
	public static function validateRequestBodyIsArray($possiblyArray) {
		if(!is_array($possiblyArray)) {
			throw new InvalidRequestBodyException('Invalid request body, send some JSON instead');
		}
		if(empty($possiblyArray)) {
			throw new InvalidRequestBodyException('Empty request body, add something to that JSON array/object instead');
		}
	}

	/**
	 * Validate that the entire payload IS a string and it's not empty.
	 *
	 * @param $possiblyString
	 *
	 * @throws InvalidRequestBodyException
	 */
	public static function validateRequestBodyIsString($possiblyString) {
		if(is_int($possiblyString)) {
			$possiblyString = (string) $possiblyString;
		}
		if(!is_string($possiblyString)) {
			throw new InvalidRequestBodyException('Invalid request body, send a string instead');
		}
		if(trim($possiblyString) === '') {
			throw new InvalidRequestBodyException('Empty request body, send a string instead');
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
