<?php

namespace WEEEOpen\Tarallo\Database;


class DuplicateItemCodeException extends \Exception {
	public $duplicate;

	public function __construct(string $code) {
		parent::__construct('Duplicate code: ' . $code, 1062);
		$this->duplicate = $code;
	}
}
