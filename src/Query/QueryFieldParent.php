<?php
namespace WEEEOpen\Tarallo\Query;


class QueryFieldParent extends AbstractQueryField implements QueryField {
	public function isKVP() {
		return false;
	}

	public function allowMultipleFields() {
		return false;
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
		if(!is_numeric($parameter)) {
			throw new \InvalidArgumentException($parameter . ' must be a number');
		}

		$this->content = (int) $parameter;
	}

	protected function getDefault() {
		return 0;
	}
}