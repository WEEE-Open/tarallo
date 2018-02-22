<?php

namespace WEEEOpen\Tarallo\APIv1;

class AuthenticationException extends \RuntimeException {
	public function __construct() {
		parent::__construct('Authentication required');
	}
}
