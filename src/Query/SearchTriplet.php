<?php

namespace WEEEOpen\Tarallo\Query;


use WEEEOpen\Tarallo\InvalidParameterException;

class SearchTriplet {
	private $key;
	private $compare;
	private $value;
	private static $separators = ['=', '>', '<'];

	public function __construct($key, $compare, $value) {
		if(!is_string($key) || strlen($key) === 0) {
			throw new InvalidParameterException('Search key must be a non-empty string');
		}

		if(!is_string($value) || strlen($value) === 0) {
			throw new InvalidParameterException('Search key must be a non-empty string');
		}

		if(!in_array($compare, static::$separators)) {
			throw new InvalidParameterException('"' . $compare . '" is not a valid comparison operator (allowed: =, >, <)');
		}

		$this->key = $key;
		$this->compare = $compare;
		$this->value = $value;
	}

	public static function getSeparators() {
		return static::$separators;
	}

	public function __toString() {
		return $this->key . $this->compare . $this->value;
	}

	/**
	 * @return string
	 */
	public function getKey() {
		return $this->key;
	}

	/**
	 * @return mixed
	 */
	public function getCompare() {
		return $this->compare;
	}

	/**
	 * @return string
	 */
	public function getValue() {
		return $this->value;
	}
}