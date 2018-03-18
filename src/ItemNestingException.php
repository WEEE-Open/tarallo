<?php

namespace WEEEOpen\Tarallo\Server;

class ItemNestingException extends \RuntimeException {
	public $itemCode;
	public $parentCode;

	public function __construct(string $message = "", string $itemCode, string $parentCode) {
		parent::__construct($message);
		$this->itemCode = $itemCode;
		$this->parentCode = $parentCode;
	}
}
