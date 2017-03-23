<?php

namespace WEEEOpen\Tarallo;

class Database {
	private static $pdo = null;

	private function getPDO() {
		if(self::$pdo === null) {
			$this->connect(DB_USERNAME, DB_PASSWORD, DB_DSN);
		}
		return self::$pdo;
	}

	private function connect($user, $pass, $dsn) {
		try {
			self::$pdo = new \PDO($dsn, $user, $pass, [
				\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
				\PDO::ATTR_CASE => \PDO::CASE_NATURAL,
				\PDO::ATTR_AUTOCOMMIT => false,
				\PDO::ATTR_EMULATE_PREPARES => false,
			]);
		} catch (\PDOException $e) {
			throw new \Exception('Cannot connect to database: ' . $e->getMessage());
		}
	}

	private function disconnect() {
		self::$pdo = null;
	}
}