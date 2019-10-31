<?php

namespace WEEEOpen\Tarallo;

use Throwable;

/**
 * When items are invalid other than for their location.
 */
class ValidationException extends \RuntimeException {
	public $status = 400;
	public $item = null;

	public function __construct(?string $item = null, $message = null, $code = 0, Throwable $previous = null) {
		if($item === null) {
			parent::__construct($message ?? "Validation failed", $code, $previous);
		} else {
			parent::__construct($message ?? "$item is invalid", $code, $previous);
		}
		$this->item = $item;
	}
}
