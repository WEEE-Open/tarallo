<?php

namespace WEEEOpen\Tarallo\Query\Field;

use WEEEOpen\Tarallo\InvalidParameterException;

abstract class AbstractQueryField implements QueryField {
	protected $content = null;

	public function getContent() {
		return $this->content;
	}

	public function add($parameter) {
		throw new InvalidParameterException('Invalid duplicate parameter in query string');
	}
}