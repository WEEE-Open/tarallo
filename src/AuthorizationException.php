<?php

namespace WEEEOpen\Tarallo\Server;

class AuthorizationException extends \RuntimeException {
	public function __construct() {
		parent::__construct('Not authorized');
	}
}