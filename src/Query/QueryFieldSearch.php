<?php
namespace WEEEOpen\Tarallo\Query;


class QueryFieldSearch extends QueryFieldMultifield implements QueryField {
	public function __construct($parameter) {
		$pieces = explode("=", $parameter);
		if(count($pieces) !== 2 || strlen($pieces[0]) === 0 || strlen($pieces[1]) === 0) {
			throw new \InvalidArgumentException($parameter . ' must be a key-value pair separated by an "="');
		}
		// TODO: check that key is a valid key
		$this->add($pieces);
	}

	protected function nonDefaultToString() {
		$result = '';
		foreach($this->getContent() as $kvp) {
			$result .= '/Search/' . implode('=', $kvp);
		}
		return $result;
	}
}