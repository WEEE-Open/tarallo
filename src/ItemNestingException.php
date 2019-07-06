<?php

namespace WEEEOpen\Tarallo\Server;

class ItemNestingException extends \RuntimeException {
	public $itemCode;
	public $parentCode;

	/**
	 * When items are placed in impossible places.
	 *
	 * @param string $message Explanation of the impossible nesting
	 * @param string $itemCode Item that can't be placed there
	 * @param string $parentCode Parent item that can't accept it
	 */
	public function __construct(string $message, string $itemCode, string $parentCode) {
		parent::__construct($message);
		$this->itemCode = $itemCode;
		$this->parentCode = $parentCode;
	}
}
