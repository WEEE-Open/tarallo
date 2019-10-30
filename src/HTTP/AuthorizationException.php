<?php

namespace WEEEOpen\Tarallo\HTTP;

class AuthorizationException extends \RuntimeException {
	public $status = 403;

	public function __construct() {
		parent::__construct('Not authorized');
	}
}
