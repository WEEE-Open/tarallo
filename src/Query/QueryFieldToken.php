<?php
namespace WEEEOpen\Tarallo\Query;

class QueryFieldToken extends AbstractQueryField implements QueryField {
	public function isKVP() {
		return false;
	}

	public function allowMultipleFields() {
		return false;
	}

	public function validate() {
		return true;
	}

	public function parse($parameter) {
		$this->content = $parameter;
	}

	protected function getDefault() {
		return null;
	}
}