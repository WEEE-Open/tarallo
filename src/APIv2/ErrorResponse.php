<?php


namespace WEEEOpen\Tarallo\APIv2;


class ErrorResponse implements \JsonSerializable {
	public $exceptionName;
	public $message;
	public $code;
	public $item;
	public $otherItem;
	public $feature;
	public $featurePath;
	public $status = 500;
	public $trace;

	public static function fromException(\Exception $e): ErrorResponse {
		$error = new ErrorResponse();

		$error->exceptionName = get_class($e);
		$error->message = $e->getMessage();
		if(is_int($e->getCode())) {
			$error->code = $e->getCode();
		}
		if(isset($e->item)) {
			$error->item = $e->item;
		}
		if(isset($e->otherItem)) {
			$error->otherItem = $e->otherItem;
		}
		if(isset($e->status)) {
			$error->status = (int) $e->status;
		}
		if(isset($e->feature)) {
			$error->feature = $e->feature;
		}
		if(isset($e->featurePath)) {
			$error->featurePath = $e->featurePath;
		}
		if(TARALLO_DEVELOPMENT_ENVIRONMENT) {
			$error->trace = $e->getTraceAsString();
		}

		return $error;
	}

	public static function fromMessage(string $message) {
		$error = new ErrorResponse();
		$error->message = $message;
	}

	public function jsonSerialize() {
		$result = [];
		if(isset($this->exceptionName)) {
			$result['exception'] = $this->exceptionName;
		}
		if(isset($this->message)) {
			$result['message'] = $this->message;
		}
		if(isset($this->code) && $this->code !== 0) {
			$result['code'] = $this->code;
		}
		if(isset($this->item)) {
			$result['item'] = $this->item;
		}
		if(isset($this->otherItem)) {
			$result['other_item'] = $this->otherItem;
		}
		if(isset($this->feature)) {
			$result['feature'] = $this->feature;
		}
		if(isset($this->featurePath)) {
			$result['feature_path'] = $this->featurePath;
		}
//		if(isset($this->status)) {
//			$result['status'] = $this->status;
//		}
		if(isset($this->trace)) {
			$result['trace'] = $this->trace;
		}
		return $result;
	}
}