<?php

namespace WEEEOpen\Tarallo;

use Throwable;

class ItemPrefixerException extends \RuntimeException
{
	use ExceptionWithPath;

	public function __construct(?array $itemPath = null, $message = null, $code = 0, Throwable $previous = null)
	{
		parent::__construct($message ?? 'Cannot generate code for item', $code, $previous);
		$this->itemPath = $itemPath;
	}
}
