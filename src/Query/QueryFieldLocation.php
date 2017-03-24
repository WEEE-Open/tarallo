<?php
namespace WEEEOpen\Tarallo\Query;


class QueryFieldLocation extends QueryFieldMultifield implements QueryField {
	public function __construct($parameter) {
		$this->add($parameter);
	}

	protected function getDefault() {
		return [];
	}

	protected function nonDefaultToString() {
		$result = '';
		foreach($this->getContent() as $location) {
			$result .= '/Location/' . $location;
		}
		return $result;
	}
}