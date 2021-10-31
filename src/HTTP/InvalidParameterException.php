<?php

namespace WEEEOpen\Tarallo\HTTP;

use Throwable;

// TODO: refactor it
class InvalidParameterException extends \RuntimeException
{
	public $status = 400;
	protected $parameter;

	public function __construct(
		string $parameter,
		$value,
		string $message = '',
		int $code = 0,
		Throwable $previous = null
	) {
		$this->parameter = $parameter;
		if ($message === '') {
			$message = "$parameter=$value is invalid";
		}
		parent::__construct($message, $code, $previous);
	}

	public function getParameter(): string
	{
		return $this->parameter;
	}
}
