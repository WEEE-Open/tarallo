<?php


namespace WEEEOpen\Tarallo\HTTP;

use Throwable;

class InvalidRequestBodyException extends \RuntimeException {

	public function __construct(
		string $message = 'Invalid request body',
		int $code = 0,
		Throwable $previous = null
	) {
		parent::__construct($message, $code, $previous);
	}
}
