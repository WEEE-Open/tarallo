<?php
namespace WEEEOpen\Tarallo\Query\Field;

use WEEEOpen\Tarallo\InvalidParameterException;

abstract class PostJSON extends AbstractQueryField implements QueryField {
	public function __construct($parameter) {
		if(!is_string($parameter) || $parameter === '') {
			throw new InvalidParameterException('POST requests must contain a body (in JSON format)');
		}

		$array = json_decode($parameter, true);
		if(json_last_error() !== JSON_ERROR_NONE) {
			throw new InvalidParameterException('Invalid JSON: ' . json_last_error_msg());
		}

		$this->parseContent($array);
	}

	protected abstract function parseContent($array);

	public function add($parameter) {
		throw new InvalidParameterException('Invalid duplicate parameter in query string');
	}
}