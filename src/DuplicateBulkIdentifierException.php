<?php

namespace WEEEOpen\Tarallo;

use Throwable;

class DuplicateBulkIdentifierException extends \RuntimeException {
	public $status = 409;

	public function __construct($message = null, $code = 0, Throwable $previous = null) {
		parent::__construct($message ?? 'This BulkIdentifer already exist', $code, $previous);
	}
}
