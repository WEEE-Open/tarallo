<?php

namespace WEEEOpen\Tarallo\Query\Field;

class ParentField extends Search implements QueryField {
	public function __toString() {
		return '/Parent/' . (string) implode(',', $this->getContent());
	}
}