<?php

namespace WEEEOpen\Tarallo;

use Throwable;

// TODO: use the ExceptionWith- traits
class ProductException extends \RuntimeException
{
	public $status = 400;
	private $productCode = null;

	public function __construct(ProductCode $productCode, $message = null, $code = 0, Throwable $previous = null)
	{
		parent::__construct($message ?? 'Product exception related to ' . $productCode, $code, $previous);
		$this->productCode = $productCode;
	}
}
