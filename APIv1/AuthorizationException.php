<?php

namespace WEEEOpen\Tarallo\APIv1;

class AuthorizationException extends \RuntimeException {
	public function __construct() {
		parent::__construct('Not authorized');
	}
}
