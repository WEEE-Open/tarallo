<?php

namespace WEEEOpen\Tarallo\Server;

class NotFoundException extends \RuntimeException {
	public function __construct() {
		parent::__construct('Not found/no results');
	}
}