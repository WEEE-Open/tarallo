<?php
namespace WEEEOpen\Tarallo;

/**
 * Class ItemIncomplete
 * An item code and that's it. Sanitizes codes, serializes to a string.
 *
 * @package WEEEOpen\Tarallo
 */
class ItemIncomplete implements \JsonSerializable {
	private $code;

	function __construct($code) {
		$this->code = $this->sanitizeCode($code);
	}

	/**
	 * @param string|int $code some Item code
	 *
	 * @return string the code, casted to string
	 * @throws InvalidParameterException if it's not a valid code
	 */
	public static function sanitizeCode($code) {
		if(is_int($code)) {
			return (string) $code;
		} else if(!is_string($code) || $code === '') {
			throw new InvalidParameterException('Item code must be a non-empty string or integer, ' . gettype($code) . ' given');
		} else {
			return $code;
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