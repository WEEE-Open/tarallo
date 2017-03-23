<?php
namespace WEEEOpen\Tarallo;

class User {
	private $username;
	private $password;
	private $hash = null;

	public function __construct($username, $password) {
		if(!is_string($username) || strlen($username) === 0) {
			throw new \InvalidArgumentException('Username must be a non-empty string');
		}

		if(!is_string($password) || strlen($password) === 0) {
			throw new \InvalidArgumentException('Password must be a non-empty string');
		}
	}

	public function getUsername() {
		return $this->username;
	}

	public function getPassword() {
		return $this->password;
	}

//	private function getHash() {
//		if($this->hash === null) {
//			password_hash($this->password, PASSWORD_DEFAULT);
//		}
//
//		return $this->hash;
//	}

}