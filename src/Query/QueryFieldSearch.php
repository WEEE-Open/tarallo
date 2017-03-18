<?php
namespace WEEEOpen\Tarallo\Query;


class QueryFieldSearch extends QueryFieldMultifield implements QueryField {
	public function isKVP() {
		return true;
	}

	public function validate() {
		if($this->isDefault()) {
			return true;
		}

		$content = $this->getContent();
		// TODO: implement
		return true;
	}

	public function parse($parameter) {
		$pieces = explode("=", $parameter);
		if(count($pieces) !== 2) {
			throw new \InvalidArgumentException($parameter . ' must a key-value pair separated by an "="');
		}
		$this->add($pieces);
	}

	protected function getDefault() {
		return [];
	}
}