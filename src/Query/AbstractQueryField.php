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
		if($this->content === null) {
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
}