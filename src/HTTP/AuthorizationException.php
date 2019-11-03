<?php

namespace WEEEOpen\Tarallo\HTTP;

use Throwable;

class AuthorizationException extends \RuntimeException {
	public $status = 403;

	public function __construct($message = null, $code = 0, Throwable $previous = null) {
		parent::__construct($message ?? 'Not authorized', $code, $previous);
	}
}
