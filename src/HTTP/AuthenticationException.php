<?php

namespace WEEEOpen\Tarallo\Server\HTTP;

class AuthenticationException extends \RuntimeException {
	public function __construct() {
		parent::__construct('Authentication required');
	}
}
