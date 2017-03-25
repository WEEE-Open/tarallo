<?php
namespace WEEEOpen\Tarallo\Query\Field;


abstract class AbstractQueryField implements QueryField {
	protected $content = null;

	public function getContent() {
		return $this->content;
	}

	public function add($parameter) {
		throw new \InvalidArgumentException('Invalid duplicate parameter in query string');
	}
}