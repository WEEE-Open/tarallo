<?php

namespace WEEEOpen\Tarallo;

use Throwable;

class StateChangedException extends \RuntimeException {
	public $status = 409;

	public function __construct($message, bool $usesPrecondition = false, $code = 0, Throwable $previous = null) {
		if($usesPrecondition) {
			// For the etag issue which at some point we'll implement
			$status = 412;
		}
		parent::__construct($message, $code, $previous);
	}
}
