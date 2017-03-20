<?php
namespace WEEEOpen\Tarallo\Query;


abstract class QueryFieldPostJSON implements QueryField {
	private $queryContent = null;

	public function parse($parameter) {
		if(!is_string($parameter) || $parameter === '') {
			throw new \InvalidArgumentException('POST requests must contain a body (in JSON format)');
		}

		$array = json_decode($parameter, true);
		if(json_last_error() !== JSON_ERROR_NONE) {
			throw new \InvalidArgumentException('Invalid JSON: ' . json_last_error_msg());
		}

		$this->setContent($array);
		$this->parseContent();
	}

	protected abstract function parseContent();

	public function isDefault() {
		if($this->queryContent === null || empty($this->queryContent)) {
			return true;
		} else {
			return false;
		}
	}

	public function __toString() {
		// TODO: empty objects turn into empty arrays... use JSON_FORCE_OBJECT? Always? Only when field should be an object? Most of them should be arrays
		if($this->isDefault()) {
			return '{}';
		}
		$JSON = $this->getContent();
		$string = json_encode($JSON);

		if(json_last_error() !== JSON_ERROR_NONE) {
			throw new \InvalidArgumentException('Failed converting query back to JSON: ' . json_last_error_msg());
		}

		if(!is_string($string)) {
			throw new \InvalidArgumentException('Failed converting query back to JSON: unknown error (this should never happen, HOPEFULLY)');
		}

		return $string;
	}

	public function getContent() {
		if($this->isDefault()) {
			return [];
		} else {
			return $this->queryContent;
		}
	}

	private function setContent($array) {
		if(is_array($array)) {
			$this->queryContent = $array;
		}
	}
}