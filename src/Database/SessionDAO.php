<?php

namespace WEEEOpen\Tarallo\Database;

use WEEEOpen\Tarallo\SessionLocal;
use WEEEOpen\Tarallo\SessionSSO;

final class SessionDAO extends DAO {
	/**
	 * Get user data from a session, or null
	 *
	 * @param string $sessionId
	 *
	 * @return SessionSSO
	 */
	public function getSession(string $sessionId): ?SessionSSO {
		try {
			$s = $this->getPDO()->prepare('SELECT `Data` FROM Session WHERE `Session` = ?');
			$result = $s->execute([$sessionId]);
			assert($result !== false, 'get session');
			$rows = $s->rowCount();
			if($rows === 0) {
				return null;
			}
			$data = $s->fetch(\PDO::FETCH_NUM)[0];
			if($data === null) {
				return null;
			}
			return unserialize($data);
		} finally {
			$s->closeCursor();
		}
	}

	public function getRedirect(string $sessionId): ?string {
		try {
			$s = $this->getPDO()->prepare('SELECT `Redirect` FROM Session WHERE `Session` = ?');
			$result = $s->execute([$sessionId]);
			assert($result !== false, 'get session redirect');
			$rows = $s->rowCount();
			if($rows === 0) {
				return null;
			}

			$redirect = $s->fetch(\PDO::FETCH_NUM)[0];
			if($redirect === null) {
				return '/';
			}
			// PHPStorm thinks that an if-else here provides a path where nothing is returned... Yeah right...
			return $redirect;
		} finally {
			$s->closeCursor();
		}
	}

	public function deleteSession(string $sessionId) {
		try {
			$s = $this->getPDO()->prepare('DELETE FROM Session WHERE `Session` = ?');
			$result = $s->execute([$sessionId]);
			assert($result !== false, 'delete sessions');
		} finally {
			$s->closeCursor();
		}
	}


	/**
	 * Check if a session exists. If it does, lock the row for update so it won't disappear or change in the meantime.
	 *
	 * @param string $sessionID
	 *
	 * @return bool Does it exist or not?
	 */
	public function sessionExists(string $sessionID): bool {
		try {
			$s = $this->getPDO()->prepare('SELECT `Data`, `Redirect` FROM Session WHERE `Session` = :s FOR UPDATE');
			$s->bindValue(':s', $sessionID, \PDO::PARAM_STR);
			$result = $s->execute();
			assert($result !== false, 'session exists');
			$rows = $s->rowCount();
			return $rows > 0;
		} finally {
			if(isset($s)) {
				$s->closeCursor();
			}
		}
	}

	/**
	 * Get a token if it exists, or null. If it does, lock the row for update so it won't disappear or change in the meantime.
	 *
	 * @param string $token
	 * @param \DateTimeImmutable|null $lastAccess Token last access time
	 *
	 * @return SessionLocal Session for the token, or null if it doesn't exist
	 */
	public function getToken(string $token, &$lastAccess): ?SessionLocal {
		try {
			$s = $this->getPDO()->prepare('SELECT `Hash`, `Data`, `LastAccess` FROM SessionToken WHERE `Token` = :t FOR UPDATE');
			list($token, $pass) = self::splitToken($token);
			$s->bindValue(':t', $token, \PDO::PARAM_STR);
			$result = $s->execute();
			assert($result !== false, 'token exists');
			$rows = $s->rowCount();
			if($rows > 0) {
				$row = $s->fetchAll(\PDO::FETCH_ASSOC)[0];
				$lastAccess = $row['LastAccess'];
				$hash = $row['Hash'];
				if(!password_verify($pass, $hash)) {
					return null;
				}
				try {
					$lastAccess = new \DateTimeImmutable($lastAccess, new \DateTimeZone('Europe/Rome'));
				} catch(\Exception $e) {
					return null;
				}
				return unserialize($row['Data']);
			} else {
				return null;
			}
			/** @noinspection PhpUnreachableStatementInspection It's either this, or "missing return statement"... */
			return null;
		} finally {
			$s->closeCursor();
		}
	}

	public function deleteToken( string $token) {
		try {
			$s = $this->getPDO()->prepare('DELETE FROM SessionToken WHERE Token = :t');
			$s->bindValue(':t', $token, \PDO::PARAM_STR);
			$result = $s->execute();
			assert($result !== false, 'invalid token');
		} finally {
			$s->closeCursor();
		}
	}

	public function getUserTokens(string $user): array {
		try {
			$s = $this->getPDO()->prepare('SELECT `Token`, `Data`, LastAccess FROM SessionToken WHERE `Owner` = :o');
			$s->bindValue(':o', $user, \PDO::PARAM_STR);
			$result = $s->execute();
			assert($result !== false, 'get user tokens');
			$tokens = [];
			foreach($s as $row) {
				try {
					$dt = new \DateTimeImmutable($row['LastAccess'], new \DateTimeZone('Europe/Rome'));
				} catch(\Exception $e) {
					$dt = null;
				}

				$tokens[] = [
					'Token' => $row['Token'],
					'Session' => unserialize($row['Data']),
					'LastAccess' => $dt,
					];
			}
			return $tokens;
		} finally {
			$s->closeCursor();
		}
	}

	/**
	 * Set or delete the redirect field for a session, create if it does not exist
	 *
	 * @param string $sessionID
	 * @param string|null $redirect
	 *
	 * @throws DatabaseException if session does not exist
	 */
	public function setRedirectForSession(string $sessionID, ?string $redirect = null) {
		if(!$this->sessionExists($sessionID)) {
			$this->createSession($sessionID);
			if($redirect === null) {
				// It's already null by default
				return;
			}
		}

		try {
			$s = $this->getPDO()->prepare('UPDATE Session SET Redirect = :r, LastAccess = CURRENT_TIMESTAMP() WHERE `Session` = :s');
			$s->bindValue(':s', $sessionID, \PDO::PARAM_STR);
			$s->bindValue(':r', $redirect, $redirect === null ? \PDO::PARAM_BOOL : \PDO::PARAM_STR);
			$result = $s->execute();
			assert($result !== false, 'session exists');
		} finally {
			$s->closeCursor();
		}
	}

	/**
	 * Set or delete data for a session, if it exists
	 *
	 * @param string $sessionID
	 * @param SessionSSO $data
	 */
	public function setDataForSession(string $sessionID, ?SessionSSO $data) {
		if(!$this->sessionExists($sessionID)) {
			$this->createSession($sessionID);
			if($data === null) {
				// It's already null by default
				return;
			}
		}

		try {
			$s = $this->getPDO()->prepare('UPDATE Session SET Data = :d, LastAccess = CURRENT_TIMESTAMP() WHERE `Session` = :s');
			$s->bindValue(':s', $sessionID, \PDO::PARAM_STR);
			if($data === null) {
				$s->bindValue(':d', null, \PDO::PARAM_NULL);
			} else {
				$s->bindValue(':d', serialize($data), \PDO::PARAM_STR);
			}
			$result = $s->execute();
			assert($result !== false, 'session exists');
		} finally {
			$s->closeCursor();
		}
	}

	/**
	 * Set data for a token, create it if it does not exist.
	 *
	 * @param string $token
	 * @param SessionLocal $data
	 */
	public function setDataForToken(string $token, SessionLocal $data) {
		try {
			$s = $this->getPDO()->prepare('REPLACE INTO SessionToken(Token, Hash, Data, Owner, LastAccess) VALUES (:t, :h, :d, :o, CURRENT_TIMESTAMP)');
			list($token, $pass) = self::splitToken($token);
			$s->bindValue(':t', $token, \PDO::PARAM_STR);
			$s->bindValue(':h', password_hash($pass, PASSWORD_DEFAULT), \PDO::PARAM_STR);
			$s->bindValue(':d', serialize($data), \PDO::PARAM_STR);
			$s->bindValue(':o', $data->owner, \PDO::PARAM_STR);
			$result = $s->execute();
			assert($result !== false, 'token upsert');
		} finally {
			$s->closeCursor();
		}
	}

	/**
	 * @param string $token
	 */
	public function bumpToken(string $token) {
		try {
			$s = $this->getPDO()->prepare('UPDATE SessionToken SET LastAccess = CURRENT_TIMESTAMP WHERE Token = :t');
			$token = self::splitToken($token)[0];
			$s->bindValue(':t', $token, \PDO::PARAM_STR);
			$result = $s->execute();
			assert($result !== false, 'token bump');
		} finally {
			$s->closeCursor();
		}
	}

	private function createSession(string $sessionID) {
		try {
			$s = $this->getPDO()->prepare('INSERT INTO Session(Session, Data, Redirect) VALUES (:s, NULL, NULL)');
			$s->bindValue(':s', $sessionID, \PDO::PARAM_STR);
			$result = $s->execute();
			assert($result !== false, 'create session');
		} finally {
			$s->closeCursor();
		}
	}

	/**
	 * Set the MySQL global variable taralloAuditUsername.
	 *
	 * @param $username
	 */
	public function setAuditUsername($username) {
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

	private static function splitToken(string $token): array {
		$pieces = explode(':', $token, 2);
		if(count($pieces) == 2) {
			return $pieces;
		} else {
			return [$token, ''];
		}
	}
}
