<?php

namespace WEEEOpen\Tarallo\Query\Field;


class Code extends Multifield implements QueryField {
	public function __construct($parameter) {
		$this->add($parameter);
	}

	public function __toString() {
		$result = '';
		foreach($this->getContent() as $location) {
			$result .= '/Code/' . $location;
		}

		return $result;
	}
}