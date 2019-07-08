<?php


namespace WEEEOpen\Tarallo\Server\HTTP;

use Throwable;

class InvalidParameterException extends \RuntimeException {
	protected $parameter;

	public function __construct(
		string $parameter,
		$value,
		string $message = '',
		int $code = 0,
		Throwable $previous = null
	) {
		$this->parameter = $parameter;
		if($message === '') {
			$message = "$parameter=$value is invalid";
		}
		parent::__construct($message, $code, $previous);
	}

	public function getParameter(): string {
		return $this->parameter;
	}
}
