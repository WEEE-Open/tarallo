<?php

namespace WEEEOpen\Tarallo;

use Throwable;

class FoundException extends \RuntimeException
{
	use ExceptionWithItem;

	public $status = 404;

	public function __construct(?string $item = null, $message = null, $code = 0, Throwable $previous = null)
	{
		if ($item === null) {
			parent::__construct($message ?? 'Item already exists', $code, $previous);
		} else {
			parent::__construct($message ?? "Item $item already exist", $code, $previous);
			$this->item = $item;
		}
	}
}
