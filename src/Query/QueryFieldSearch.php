<?php
namespace WEEEOpen\Tarallo\Query;


class QueryFieldSearch extends QueryFieldMultifield implements QueryField {
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
		if(count($pieces) !== 2 || strlen($pieces[0]) === 0 || strlen($pieces[1]) === 0) {
			throw new \InvalidArgumentException($parameter . ' must be a key-value pair separated by an "="');
		}
		$this->add($pieces);
	}

	protected function getDefault() {
		return [];
	}

	protected function nonDefaultToString() {
		$result = '';
		foreach($this->getContent() as $kvp) {
			$result .= '/Search/' . implode('=', $kvp);
		}
		return $result;
	}
}