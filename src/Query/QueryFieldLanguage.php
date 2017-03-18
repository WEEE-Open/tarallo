<?php
namespace WEEEOpen\Tarallo\Query;

class QueryFieldLanguage extends AbstractQueryField implements QueryField {
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
		// TODO: change default?
		return 'it-IT';
	}
}