<?php

namespace WEEEOpen\Tarallo;

use Throwable;

class SearchException extends \RuntimeException
{
	public $status = 400;
	private $lh;
	private $rh;

	public function __construct($lh = null, $rh = null, $message = null, $code = 0, Throwable $previous = null)
	{
		parent::__construct($message ?? 'Search failed', $code, $previous);
		$this->lh = $lh;
		$this->rh = $rh;
	}

	public function getLh()
	{
		return $this->lh;
	}

	public function getRh()
	{
		return $this->rh;
	}
}
