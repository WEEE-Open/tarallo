<?php

namespace WEEEOpen\Tarallo\HTTP;

class AuthenticationException extends \RuntimeException {
	public function __construct($message = 'Not authenticated or session expired') {
		parent::__construct($message);
	}
}
