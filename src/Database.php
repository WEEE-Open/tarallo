<?php

namespace WEEEOpen\Tarallo;

class Database {
	/** @var \PDO */
	private $pdo = null;

	private function getPDO() {
		if($this->pdo === null) {
			$this->connect(DB_USERNAME, DB_PASSWORD, DB_DSN);
		}
		return $this->pdo;
	}

	private function connect($user, $pass, $dsn) {
		try {
			$this->pdo = new \PDO($dsn, $user, $pass, [
				\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
				\PDO::ATTR_CASE => \PDO::CASE_NATURAL,
				\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
				\PDO::ATTR_AUTOCOMMIT => false,
				\PDO::ATTR_EMULATE_PREPARES => false,
			]);
		} catch (\PDOException $e) {
			throw new \Exception('Cannot connect to database: ' . $e->getMessage());
		}
	}

	public function disconnect() {
		$this->pdo = null;
	}

	public function getUserFromSession($session) {
		// TODO: check that session isn't expired
		$s = $this->getPDO()->prepare('SELECT Name, Password FROM `User` WHERE `Session` = ?');
		$s->execute([$session]);
		if($s->rowCount() > 1) {
			throw new \LogicException('Duplicate session session identifier in database');
		} else if($s->rowCount() === 0) {
			return null;
		} else {
			$user = $s->fetch();
			return new User($user['Name'], $user['Password']);
		}
	}

	public function setSessionFromUser($username, $session, $expiry) {
		$s = $this->getPDO()->prepare('UPDATE `User` SET `Session` = :s, SessionExpiry = :se WHERE `Name` = :n');
		$s->bindValue(':s', $session);
		$s->bindValue(':se', $expiry);
		$s->bindValue(':n', $username);
		$s->execute();
	}
}