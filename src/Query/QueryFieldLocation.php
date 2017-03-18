<?php
namespace WEEEOpen\Tarallo\Query;


class QueryFieldLocation extends QueryFieldMultifield implements QueryField {
	public function validate() {
		if($this->isDefault()) {
			return true;
		}

		$content = $this->getContent();
		// TODO: implement
		return true;
	}

	public function parse($parameter) {
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