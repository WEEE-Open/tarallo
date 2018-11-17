<?php

namespace WEEEOpen\Tarallo\Server;

/**
 * Class ItemIncomplete
 * An item code and that's it. Serializes to a string.
 *
 * @package WEEEOpen\Tarallo
 */
class ItemIncomplete implements \JsonSerializable {
	protected $code;

	// TODO: more $code validation?
	function __construct($code) {
		if(is_string($code) && trim($code) !== '') {
			self::validateCode($code);
			$this->code = $code;
		} else {
			throw new \InvalidArgumentException('ItemIncomplete code must be a non-null string');
		}
	}

	public static function validateCode($code) {
		if(function_exists('ctype_alnum')) {
			$valid = ctype_alnum($code);
		} else {
			$valid = preg_match('/^[a-zA-Z0-9]+$/', $code);
		}
		if(!$valid) {
			throw new ValidationException("Code must be alphanumeric, '$code' isn't", 3);
		}
	}

	public function getCode() {
		return $this->code;
	}

	public function compareCode(ItemIncomplete $other) {
		return strcasecmp($this->getCode(), $other->getCode());
	}

	public function jsonSerialize() {
		return $this->getCode();
	}

	public function __toString() {
		return $this->getCode();
	}
}