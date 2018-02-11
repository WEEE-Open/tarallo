<?php

namespace WEEEOpen\Tarallo\Server;


class UserAnonymous extends User {
	public function __construct() {
		parent::__construct('anonymous');
	}

	public function getUsername() {
		throw new \LogicException('Anonymous user has no username');
	}

	public function __toString() {
		return '';
	}
}
