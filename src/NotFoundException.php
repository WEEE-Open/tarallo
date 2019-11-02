<?php

namespace WEEEOpen\Tarallo;

use Throwable;

class NotFoundException extends \RuntimeException {
	use ExceptionWithItem;
	public $status = 404;

	public function __construct(?string $item = null, $message = null, $code = 0, Throwable $previous = null) {
		if($item === null) {
			parent::__construct($message ?? 'Not found/no results', $code, $previous);
		} else {
			parent::__construct($message ?? "Item $item does not exist", $code, $previous);
			$this->item = $item;
		}
	}
}
