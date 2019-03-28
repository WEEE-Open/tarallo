<?php

namespace WEEEOpen\Tarallo\Server;

class NotFoundException extends \RuntimeException {
	/**
	 * NotFoundException constructor.
	 *
	 * @param int $code
	 * @param null $message
	 */
	public function __construct($code = 0, $message = null) {
		parent::__construct($message ?? 'Not found/no results', $code);
	}
}