<?php
namespace WEEEOpen\Tarallo\Query;

class QueryFieldToken extends QueryFieldSinglefield implements QueryField {
	public function validate() {
		return true;
	}

	public function parse($parameter) {
		$this->stopIfAlreadyParsed();
		$this->content = $parameter;
	}

	protected function getDefault() {
		return null;
	}
}