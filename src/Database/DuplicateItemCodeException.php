<?php

namespace WEEEOpen\Tarallo\Database;


class DuplicateItemCodeException extends \Exception {
	public $duplicate;

	public function __construct(string $item) {
		parent::__construct('Duplicate code: ' . $item, 1062);
		$this->duplicate = $item;
	}
}
