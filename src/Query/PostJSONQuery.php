<?php

namespace WEEEOpen\Tarallo\Query;

use WEEEOpen\Tarallo\InvalidParameterException;

abstract class PostJSONQuery extends AbstractQuery implements \JsonSerializable {
	public function __construct($parameter) {
		if(!is_string($parameter) || $parameter === '') {
			throw new InvalidParameterException('POST requests must contain a body (in JSON format)');
		}

		$array = json_decode($parameter, true);
		if(json_last_error() !== JSON_ERROR_NONE) {
			throw new InvalidParameterException('Invalid JSON: ' . json_last_error_msg());
		}

		$this->parseContent($array);

		return $this;
	}

	protected abstract function parseContent($array);

	public function __toString() {
		return json_encode($this);
	}

	abstract function jsonSerialize();
}