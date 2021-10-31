<?php

namespace WEEEOpen\Tarallo\APIv2;

use Throwable;

class RangeException extends \RuntimeException
{
	public $status = 400;
	private $parameter = null;
	private $min = null;
	private $max = null;

	public function __construct(?string $parameter = null, ?int $min = null, ?int $max = null, $message = null, $code = 0, Throwable $previous = null)
	{
		parent::__construct($message ?? 'Out of range', $code, $previous);
		$this->parameter = $parameter;
		$this->min = $min;
		$this->max = $max;
	}

	public function getParameter()
	{
		return $this->parameter;
	}

	public function getMin()
	{
		return $this->min;
	}

	public function getMax()
	{
		return $this->max;
	}
}
