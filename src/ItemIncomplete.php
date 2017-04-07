<?php
namespace WEEEOpen\Tarallo;


class ItemIncomplete {
	private $code;

	function __construct($code) {
		if(is_int($code)) {
			$code = (string) $code;
		} else if(!is_string($code) || $code === '') {
			throw new InvalidParameterException('Item code must be a non-empty string or integer');
		}
		$this->code = $code;
	}

	public function getCode() {
		return $this->code;
	}
}