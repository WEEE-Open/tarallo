<?php

namespace WEEEOpen\Tarallo\Server;

class AuthenticationException extends \RuntimeException {
	public function __construct() {
		parent::__construct('Authentication required');
	}
}