<?php

namespace WEEEOpen\Tarallo;

use Throwable;

class SearchException extends \RuntimeException
{
	public $status = 400;

	public function __construct($message = null, $code = 0, Throwable $previous = null)
	{
		parent::__construct($message ?? 'Search failed', $code, $previous);
	}
}
