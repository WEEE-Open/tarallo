<?php

namespace WEEEOpen\Tarallo\Database;

use WEEEOpen\Tarallo\NotFoundException;
use WEEEOpen\Tarallo\User;

final class UserDAO extends DAO {

	public function getUserFromSession($session) {
		$s = $this->getPDO()
			->prepare(
				'SELECT `Name`, `Password` AS `Hash`, `Level` FROM `User` WHERE `Session` = ? AND `SessionExpiry` > NOW() AND `Enabled` > 0'
			);
		$s->execute([$session]);
		if($s->rowCount() > 1) {
			$s->closeCursor();
			throw new \LogicException('Duplicate session identifier in database');
		} else if($s->rowCount() === 0) {
			$s->closeCursor();

			return null;
		} else {
			$user = $s->fetch(\PDO::FETCH_ASSOC);
			$s->closeCursor();

			return new User($user['Name'], null, $user['Hash'], $user['Level']);
		}
	}

	/**
	 * Set session id and expiration timestamp for a user
	 *
	 * @param string $username
	 * @param string|null $session
	 */
	public function setSessionFromUser($username, $session) {
		try {
			$s = $this->getPDO()
				->prepare(
					'UPDATE `User` SET `Session` = :s, SessionExpiry = TIMESTAMPADD(HOUR, 6, NOW()) WHERE `Name` = :n AND `Enabled` > 0'
				);
			$s->bindValue(':s', $session, $session === null ? \PDO::PARAM_NULL : \PDO::PARAM_STR);
			$s->bindValue(':n', $username, \PDO::PARAM_STR);
			$result = $s->execute();
			assert($result !== false, 'set session from user');

			$this->setAuditUsername($username);
		} finally {
			$s->closeCursor();
		}
	}

	/**
	 * Set new password for a user.
	 *
	 * @param string $username
	 * @param string $hash
	 */
	public function setPasswordFromUser(string $username, string $hash) {
		try {
			$s = $this->getPDO()->prepare('UPDATE `User` SET `Password` = :p WHERE `Name` = :n');
			$s->bindValue(':p', $hash, \PDO::PARAM_STR);
			$s->bindValue(':n', $username, \PDO::PARAM_STR);
			$result = $s->execute();
			assert($result !== false, 'update password hash');
			if($s->rowCount() === 0) {
				throw new NotFoundException(8);
			}
		} finally {
			$s->closeCursor();
		}
	}

	/**
	 * Create a user
	 *
	 * @param string $username
	 * @param string $hash
	 */
	public function createUser(string $username, string $hash) {
		try {
			$s = $this->getPDO()->prepare('INSERT INTO `User`(`Name`, `Password`, `Enabled`) VALUES (:n, :p, 1)');
			$s->bindValue(':n', $username, \PDO::PARAM_STR);
			$s->bindValue(':p', $hash, \PDO::PARAM_STR);
			$result = $s->execute();
			assert($result !== false, "create user");
		} finally {
			$s->closeCursor();
		}
	}

	/**
	 * Log in a user, via username and password. Doesn't start any session!
	 *
	 * @param $username string username
	 * @param $password string plaintext password
	 *
	 * @return null|User User if found and password is valid, null otherwise
	 */
	public function getUserFromLogin($username, $password) {
		$s = $this->getPDO()
			->prepare('SELECT `Password` AS `Hash`, `Level` FROM `User` WHERE `Name` = ? AND `Enabled` > 0');
		$s->execute([$username]);
		if($s->rowCount() > 1) {
			$s->closeCursor();
			throw new \LogicException(
				'Duplicate username in database (should never happen altough MySQL doesn\'t allow TEXT fields to be UNIQUE, since that would be too easy and suitable for the current millennium)'
			);
		} else if($s->rowCount() === 0) {
			$s->closeCursor();

			return null;
		} else {
			$user = $s->fetch(\PDO::FETCH_ASSOC);
			$s->closeCursor();
			try {
				$this->setAuditUsername($username);
				return new User($username, $password, $user['Hash'], $user['Level']);
			} catch(\InvalidArgumentException $e) {
				if($e->getCode() === 72) {
					return null;
				} else {
					throw $e;
				}
			}
		}
	}

	/**
	 * Set the MySQL global variable taralloAuditUsername.
	 *
	 * @param $username
	 */
	private function setAuditUsername($username) {
		try {
			$s = $this->getPDO()->prepare(
			/** @lang MySQL */
				'CALL SetUser(?)'
			);
			$result = $s->execute([$username]);
			assert($result !== false, 'set audit username');
		} finally {
			$s->closeCursor();
		}
	}

}