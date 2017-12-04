<?php

namespace WEEEOpen\Tarallo\Query\Field;


class Location extends Multifield implements QueryField {
	public function __construct($parameter) {
		$this->add($parameter);
	}

	public function __toString() {
		$result = '';
		foreach($this->getContent() as $location) {
			$result .= '/Location/' . $location;
		}

		return $result;
	}
}