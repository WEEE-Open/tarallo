<?php
namespace WEEEOpen\Tarallo\Query\Field;


abstract class AbstractQueryField implements QueryField {
	protected $content = null;

	public function getContent() {
		return $this->content;
	}

	public function __toString() {
		return $this->nonDefaultToString();
	}

	/**
	 * Called by __toString(), exists only for backward (and forwmard, maybe?) compatibility
	 *
	 * @return string
	 */
	protected abstract function nonDefaultToString();

	public function add($parameter) {
		throw new \InvalidArgumentException('Invalid duplicate parameter in query string');
	}
}