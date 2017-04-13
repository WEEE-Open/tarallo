<?php
namespace WEEEOpen\Tarallo;


class ItemIncomplete {
	private $code;

	function __construct($code) {
		$this->code = $this->sanitizeCode($code);
	}

	protected function sanitizeCode($code) {
		if(is_int($code)) {
			return (string) $code;
		} else if(!is_string($code) || $code === '') {
			throw new InvalidParameterException('Item code must be a non-empty string or integer');
		} else {
			return $code;
		}
	}

	public function getCode() {
		return $this->code;
	}
}