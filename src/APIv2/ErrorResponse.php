<?php

namespace WEEEOpen\Tarallo\APIv2;

class ErrorResponse implements \JsonSerializable
{
	public $exceptionName;
	public $message;
	public $code;
	public $item;
	public $otherItem;
	public $feature;
	public $featureValue;
	public $itemPath;
	public $parameter;
	public $min;
	public $max;
	public $status = 500;
	public $trace;

	public static function fromException(\Exception $e): ErrorResponse
	{
		$error = new ErrorResponse();

		$error->exceptionName = get_class($e);
		$error->message = $e->getMessage();
		if (is_int($e->getCode())) {
			$error->code = $e->getCode();
		}
		if (method_exists($e, 'getItem')) {
			$error->item = $e->getItem();
		}
		if (method_exists($e, 'getOtherItem')) {
			$error->otherItem = $e->getOtherItem();
		}
		if (isset($e->status)) {
			$error->status = (int) $e->status;
		}
		if (method_exists($e, 'getFeature')) {
			$error->feature = $e->getFeature();
		}
		if (method_exists($e, 'getFeatureValue')) {
			$error->featureValue = $e->getFeatureValue();
		}
		if (method_exists($e, 'getItemPath')) {
			$error->itemPath = $e->getItemPath();
		}
		if (method_exists($e, 'getParameter')) {
			$error->parameter = $e->getParameter();
		}
		if (method_exists($e, 'getMin')) {
			$error->min = $e->getMin();
		}
		if (method_exists($e, 'getMax')) {
			$error->max = $e->getMax();
		}
		if (TARALLO_DEVELOPMENT_ENVIRONMENT) {
			$error->trace .= $e->getFile();
			$error->trace .= ' on line ';
			$error->trace .= $e->getLine();
			$error->trace .= "\n";
			$error->trace .= $e->getTraceAsString();
		}

		return $error;
	}

	public static function fromMessage(string $message): ErrorResponse
	{
		$error = new ErrorResponse();
		$error->message = $message;
		return $error;
	}

	public function jsonSerialize(): array
    {
		$result = [];
		if (isset($this->exceptionName)) {
			$result['exception'] = $this->exceptionName;
		}
		if (isset($this->message)) {
			$result['message'] = $this->message;
		}
		if (isset($this->code) && $this->code !== 0) {
			$result['code'] = $this->code;
		}
		if (isset($this->item)) {
			$result['item'] = $this->item;
		}
		if (isset($this->otherItem)) {
			$result['other_item'] = $this->otherItem;
		}
		if (isset($this->feature)) {
			$result['feature'] = $this->feature;
		}
		if (isset($this->featureValue)) {
			$result['feature_value'] = $this->featureValue;
		}
		if (isset($this->itemPath)) {
			$result['item_path'] = $this->itemPath;
		}
		if (isset($this->parameter)) {
			$result['parameter'] = $this->parameter;
		}
		if (isset($this->min)) {
			$result['min'] = $this->min;
		}
		if (isset($this->max)) {
			$result['max'] = $this->max;
		}
//		if(isset($this->status)) {
//			$result['status'] = $this->status;
//		}
		if (isset($this->trace)) {
			$result['trace'] = $this->trace;
		}
		return $result;
	}
}
