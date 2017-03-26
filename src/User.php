<?php
namespace WEEEOpen\Tarallo;

class User {
	private $username;
	private $password;
	private $hash;

	/**
	 * User constructor.
	 *
	 * @param string $username username
	 * @param null|string $password plaintext password
	 * @param null|string $hash hashed password (from database)
	 *
	 * @throws InvalidParameterException for empty strings or things that aren't neither strings nor null
	 * @throws \InvalidArgumentException when password and hash aren't null and don't match (code 72, chosen at random)
	 */
	public function __construct($username, $password = null, $hash = null) {
		if(!is_string($username) || strlen($username) === 0) {
			throw new InvalidParameterException('Username must be a non-empty string');
		}

		if($password != null && strlen($password) === 0) {
			throw new InvalidParameterException('Password must be null or a non-empty string');
		}

		if($password != null && strlen($password) === 0) {
			throw new InvalidParameterException('Hash must be null or a non-empty string');
		}

		if(is_string($password) && is_string($hash)) {
			if(!self::password_verify($password, $hash)) {
				throw new \InvalidArgumentException('Password doesn\'t match supplied hash', 72);
			}
		}

		$this->username = $username;
		$this->password = $password;
		$this->password = $hash;
	}

	private static function password_verify($password, $hash) {
		return password_verify($password, $hash);
	}

	private static function password_hash($password) {
		return password_hash($password, PASSWORD_DEFAULT);
	}

	public function getUsername() {
		return $this->username;
	}

	/**
	 * Gets the plaintext password, if it's available
	 *
	 * @throws \LogicException when it's not available (use hash instead)
	 * @return string
	 * @see getHash
	 */
	public function getPassword() {
		if($this->password === null) {
			throw new \LogicException('Tried to read plaintext password when it\'s not available (try reading the hash instead)');
		}
		return $this->password;
	}

	public function getHash() {
		if($this->hash === null) {
			if($this->password === null) {
				throw new \LogicException('Tried to read hash without hash nor password available');
			}
			$this->hash = self::password_hash($this->password);
		}
		return $this->hash;
	}

//	private function getHash() {
//		if($this->hash === null) {
//			password_hash($this->password, PASSWORD_DEFAULT);
//		}
//
//		return $this->hash;
//	}

}