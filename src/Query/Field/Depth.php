<?php
namespace WEEEOpen\Tarallo\Query\Field;


class Depth extends AbstractQueryField implements QueryField {
	public function __construct($parameter) {
		if(!is_numeric($parameter)) {
			throw new \InvalidArgumentException($parameter . ' must be a number');
		}

		$this->content = (int) $parameter;

		if($this->content < 0) {
			throw new \InvalidArgumentException('Depth must be >= 0, ' . $parameter . ' given');
		}
	}

	public function __toString() {
		return '/Depth/' . $this->getContent();
	}
}