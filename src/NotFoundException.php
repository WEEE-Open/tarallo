<?php

namespace WEEEOpen\Tarallo\Server;

class NotFoundException extends \RuntimeException {
	public function __construct($code = 0) {
		parent::__construct('Not found/no results', $code);
	}
}