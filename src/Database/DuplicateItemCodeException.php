<?php

namespace WEEEOpen\Tarallo\Database;


use Throwable;
use WEEEOpen\Tarallo\ExceptionWithItem;

class DuplicateItemCodeException extends \RuntimeException {
	use ExceptionWithItem;
	public $status = 400;

	public function __construct(string $item, $message = null, $code = 0, Throwable $previous = null) {
		parent::__construct($message ?? 'Duplicate code: ' . $item, $code, $previous);
		$this->item = $item;
	}
}
