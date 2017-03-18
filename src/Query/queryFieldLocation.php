<?php
namespace WEEEOpen\Tarallo\Query;


class queryFieldLocation implements QueryField {
	private $set = false;
	private $content = null;

	public function allowMultipleFields() {
		return true;
	}

	public function allowLists() {
		return false;
	}

	public function allowKVP() {
		return false;
	}

	public function validate() {
		// TODO: implement
	}

	public function parse($parameter) {
		$this->content = $parameter;
	}

	public function getContent() {
		return $this->content;
	}

	public function isParsed() {
		return $this->set;
	}
}