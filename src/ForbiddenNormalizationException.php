<?php

namespace WEEEOpen\Tarallo;

use Throwable;

class ForbiddenNormalizationException extends \RuntimeException
{
	public $status = 400;
}
