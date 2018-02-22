<?php

namespace WEEEOpen\Tarallo\Server\Database;

use WEEEOpen\Tarallo\Server\User;

final class UserDAO extends DAO {

	public function getUserFromSession($session) {
		$s = $this->getPDO()->prepare('SELECT `Name`, `Password` FROM `User` WHERE `Session` = ? AND `SessionExpiry` > ? AND `Enabled` > 0');
		$s->execute([$session, time()]);
		if($s->rowCount() > 1) {
			$s->closeCursor();
			throw new \LogicException('Duplicate session session identifier in database');
		} else if($s->rowCount() === 0) {
			$s->closeCursor();

			return null;
		} else {
			$user = $s->fetch(\PDO::FETCH_ASSOC);
			$s->closeCursor();

			return new User($user['Name'], null, $user['Password']);
		}
	}

	/**
	 * Set session id and expiration timestamp for a user
	 *
	 * @param string $username
	 * @param string|null $session
	 * @param int|null $expiry
	 */
	public function setSessionFromUser($username, $session, $expiry) {
		try {
			$s = $this->getPDO()->prepare('UPDATE `User` SET `Session` = :s, SessionExpiry = :se WHERE `Name` = :n AND `Enabled` > 0');
			$s->bindValue(':s', $session, $session === null ? \PDO::PARAM_NULL : \PDO::PARAM_STR);
			$s->bindValue(':se', $expiry === null ? 0 : (int) $expiry, \PDO::PARAM_INT);
			$s->bindValue(':n', $username, \PDO::PARAM_STR);
			if(!$s->execute()) {
				throw new DatabaseException("Cannot update session for user $username for unknown reasons");
			}
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
		$s = $this->getPDO()->prepare('SELECT Password FROM `User` WHERE `Name` = ? AND `Enabled` > 0');
		$s->execute([$username]);
		if($s->rowCount() > 1) {
			$s->closeCursor();
			throw new \LogicException('Duplicate username in database (should never happen altough MySQL doesn\'t allow TEXT fields to be UNIQUE, since that would be too easy and suitable for the current millennium)');
		} else if($s->rowCount() === 0) {
			$s->closeCursor();

			return null;
		} else {
			$user = $s->fetch(\PDO::FETCH_ASSOC);
			$s->closeCursor();
			try {
				return new User($username, $password, $user['Password']);
			} catch(\InvalidArgumentException $e) {
				if($e->getCode() === 72) {
					return null;
				} else {
					throw $e;
				}
			}
		}
	}

}