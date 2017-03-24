<?php
namespace WEEEOpen\Tarallo\Query;


abstract class QueryFieldPostJSON extends AbstractQueryField implements QueryField {
	public function __construct($parameter) {
		if(!is_string($parameter) || $parameter === '') {
			throw new \InvalidArgumentException('POST requests must contain a body (in JSON format)');
		}

		$array = json_decode($parameter, true);
		if(json_last_error() !== JSON_ERROR_NONE) {
			throw new \InvalidArgumentException('Invalid JSON: ' . json_last_error_msg());
		}

		$this->parseContent($array);
	}

	protected abstract function parseContent($array);

	public function add($parameter) {
		throw new \InvalidArgumentException('Invalid duplicate parameter in query string');
	}

	public function __toString() {
		// TODO: empty objects turn into empty arrays... use JSON_FORCE_OBJECT? Always? Only when field should be an object? Most of them should be arrays
		if($this->isDefault()) {
			return '{}';
		}

		$string = json_encode($this);

		if(json_last_error() !== JSON_ERROR_NONE) {
			throw new \InvalidArgumentException('Failed converting query back to JSON: ' . json_last_error_msg());
		}

		if(!is_string($string)) {
			throw new \InvalidArgumentException('Failed converting query back to JSON: unknown error (this should never happen, HOPEFULLY)');
		}

		return $string;
	}
}