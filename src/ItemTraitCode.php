<?php


namespace WEEEOpen\Tarallo;


trait ItemTraitCode {
	protected $code;

	/**
	 * Create an Item
	 *
	 * @param string $code Item code
	 *
	 * @see ItemTraitOptionalCode if the code should be optional
	 */
	public function __construct($code) {
		if(is_string($code) && trim($code) !== '') {
			self::validateCode($code);
			$this->code = $code;
		} else {
			throw new \InvalidArgumentException('Item code must be a non-empty alphanumeric string or null');
		}
	}

	public static function validateCode($code) {
		if(function_exists('ctype_alnum')) {
			$valid = ctype_alnum($code);
		} else {
			$valid = preg_match('/^[a-zA-Z0-9]+$/', $code);
		}
		if(!$valid) {
			throw new ValidationException($code, "Code must be alphanumeric, '$code' isn't");
		}
	}

	public function getCode(): string {
		return $this->code;
	}

	public function peekCode(): ?string {
		return $this->getCode();
	}

	public function hasCode(): bool {
		return true;
	}

	public function compareCode(ItemWithCode $other): int {
		return strcasecmp($this->getCode(), $other->getCode());
	}

	public function jsonSerialize() {
		return $this->getCode();
	}

	public function __toString() {
		return $this->getCode();
	}
}
