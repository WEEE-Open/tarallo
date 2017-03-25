<?php
namespace WEEEOpen\Tarallo\Query\Field;

class Token extends AbstractQueryField implements QueryField {
	public function __construct($parameter) {
		$this->content = $parameter;
	}

	public function __toString() {
		return '/Token/' . $this->getContent();
	}
}