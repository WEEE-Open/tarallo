<?php

namespace WEEEOpen\Tarallo\Query\Field;

use WEEEOpen\Tarallo\InvalidParameterException;

class Page extends AbstractQueryField implements QueryField {
	public function __construct($parameter) {
		$parameter = (int) $parameter;
		if(!is_int($parameter)) {
			// this should never happen, but still...
			throw new InvalidParameterException('Page should be an integer, ' . gettype($parameter) . ' given');
		}
		if($parameter <= 0) {
			throw new InvalidParameterException('Page should be a positive integer, ' . $parameter . ' given');
		}
		$this->content = $parameter;
	}

	public function __toString() {
		return '/Page/' . $this->getContent();
	}
}