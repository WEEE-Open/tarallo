<?php
namespace WEEEOpen\Tarallo\Query;


abstract class AbstractQueryField implements QueryField {
	protected $content = null;

	protected abstract function getDefault();

	public function getContent() {
		if($this->isDefault()) {
			return $this->getDefault();
		} else {
			return $this->content;
		}
	}

	public function isDefault() {
		if($this->content === null || $this->content === $this->getDefault()) {
			return true;
		} else {
			return false;
		}
	}

	public function __toString() {
		if($this->isDefault()) {
			return '';
		} else {
			return $this->nonDefaultToString();
		}
	}

	protected abstract function nonDefaultToString();

	public function add($parameter) {
		throw new \InvalidArgumentException('Invalid duplicate parameter in query string');
	}
}