<?php

namespace WEEEOpen\Tarallo\HTTP;

class AuthorizationException extends \RuntimeException {
	public function __construct() {
		parent::__construct('Not authorized');
	}
}
