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
			$this->code = $code;
		} else {
			throw new \InvalidArgumentException('ItemIncomplete code must be a non-null string');
		}
	}

	public function getCode() {
		return $this->code;
	}

	public function jsonSerialize() {
		return $this->getCode();
	}

	public function __toString() {
		return $this->getCode();
	}
}