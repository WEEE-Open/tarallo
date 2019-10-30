<?php

namespace WEEEOpen\Tarallo;

class NotFoundException extends \RuntimeException {
	public $status = 404;
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