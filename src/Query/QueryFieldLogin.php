<?php
namespace WEEEOpen\Tarallo\Query;


class QueryFieldLogin extends QueryFieldPostJSON implements QueryField, \JsonSerializable {
	private $username = null;
	private $password = null;

	protected function parseContent($content) {
		if(!isset($content['username']) || !isset($content['password'])) {
			throw new \InvalidArgumentException('Request body must contain "username" and "password"');
		}

		$this->username = (string) $content['username'];
		$this->password = (string) $content['password'];

		if($this->username === '') {
			throw new \InvalidArgumentException('Username cannot be empty');
		}
		if($this->password === '') {
			throw new \InvalidArgumentException('Password cannot be empty');
		}


	}

	function jsonSerialize() {
		return ['username' => $this->username, 'password' => $this->password];
	}
}