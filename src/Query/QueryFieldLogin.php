<?php
namespace WEEEOpen\Tarallo\Query;


class QueryFieldLogin extends QueryFieldPostJSON implements QueryField {
	private $username = null;
	private $password = null;

	protected function parseContent() {
		$content = $this->getContent();
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
}