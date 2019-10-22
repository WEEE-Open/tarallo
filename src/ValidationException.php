<?php

namespace WEEEOpen\Tarallo;

use Throwable;

/**
 * When items are invalid other than for their location.
 */
class ValidationException extends \RuntimeException {
	public function __construct(string $message = "", int $code = 0, Throwable $previous = null) {
		parent::__construct($message, $code, $previous);
	}
}
