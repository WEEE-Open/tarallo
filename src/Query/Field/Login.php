<?php
namespace WEEEOpen\Tarallo\Query\Field;

use WEEEOpen\Tarallo\InvalidParameterException;

class Login extends PostJSON implements QueryField, \JsonSerializable {
	protected function parseContent($content) {
		if(!isset($content['username']) || !isset($content['password'])) {
			throw new InvalidParameterException('Request body must contain "username" and "password"');
		}

		$this->content['username'] = (string) $content['username'];
		$this->content['password'] = (string) $content['password'];

		if($this->content['username'] === '') {
			throw new InvalidParameterException('Username cannot be empty');
		}
		if($this->content['password'] === '') {
			throw new InvalidParameterException('Password cannot be empty');
		}
	}

	public function getContent() {
		$array = parent::getContent();
		if(!isset($array['username'])) {
			$array['username'] = null;
		}
		if(!isset($array['password'])) {
			$array['password'] = null;
		}
		return $array;
	}

	function jsonSerialize() {
		$content = $this->getContent();
		if($content === null) {
			return [];
		} else {
			return $content;
		}
	}

	public function __toString() {
		return json_encode($this);
	}
}