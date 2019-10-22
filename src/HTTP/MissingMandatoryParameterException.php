<?php


namespace WEEEOpen\Tarallo\HTTP;

use Throwable;

class MissingMandatoryParameterException extends \RuntimeException {
	protected $parameter;

	public function __construct(string $parameter, string $message = '', int $code = 0, Throwable $previous = null) {
		$this->parameter = $parameter;
		if($message === '') {
			$message = "Missing $parameter";
		}
		parent::__construct($message, $code, $previous);
	}

	public function getParameter(): string {
		return $this->parameter;
	}
}
