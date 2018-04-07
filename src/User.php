<?php

namespace WEEEOpen\Tarallo\Server;

class User {
	private $username;
	private $password;
	private $hash;
	private $level;

	/**
	 * User constructor.
	 *
	 * @param string $username username
	 * @param null|string $password plaintext password
	 * @param null|string $hash hashed password (from database)
	 * @param int $level authorization level (0 = root, 3 = everyone else)
	 *
	 * @throws \InvalidArgumentException when password and hash aren't null and don't match (code 72, chosen at random)
	 */
	public function __construct($username, $password = null, $hash = null, $level = 3) {
		if(!is_string($username) || strlen($username) === 0) {
			throw new \InvalidArgumentException('Username must be a non-empty string');
		}

		if(!$this->nullOrNonEmptyString($password)) {
			throw new \InvalidArgumentException('Password must be null or a non-empty string', 5);
		}

		if(!$this->nullOrNonEmptyString($hash)) {
			throw new \InvalidArgumentException('Hash must be null or a non-empty string');
		}

		if(is_string($password) && is_string($hash)) {
			if(!self::password_verify($password, $hash)) {
				throw new \InvalidArgumentException('Password doesn\'t match supplied hash', 72);
			}
		}

		$this->username = $username;
		$this->password = $password;
		$this->hash = $hash;
		$this->level = $level;
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

	public function setPassword(string $password, string $confirm) {
		if(!$this->nullOrNonEmptyString($password)) {
			throw new \InvalidArgumentException('Password must be a non-empty string', 5);
		}
		if($password !== $confirm) {
			throw new \InvalidArgumentException('Password and confirm don\'t match', 6);
		}
		// Not multibyte-safe: who cares.
		if(strlen($password) < 8) {
			throw new \InvalidArgumentException('Password too short', 7);
		}

		$this->password = $password;
		$this->hash = null;
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

	public function __toString() {
		return $this->username;
	}

	private function nullOrNonEmptyString($string) {
		if($string === null) {
			return true;
		}
		if(!is_string($string)) {
			return false;
		}
		if($string === '') {
			return false;
		}

		return true;
	}

	/**
	 * Get authorization level for user.
	 * 0 = root, 3 = everyone else, like the ring 0 ... ring 3 thing,
	 * just hope you don't find a buggy fork of Minix running at ring -3...
	 *
	 * @return int
	 */
	public function getLevel() {
		return $this->level;
	}
}
