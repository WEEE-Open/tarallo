<?php

namespace WEEEOpen\Tarallo;

class Database {
	static $pdo = null;

	private function connect($user, $pass, $dsn) {
		if(self::$pdo != null) {
			return;
		}

		try {
			self::$pdo = new \PDO($dsn, $user, $pass);
		} catch (\PDOException $e) {
			throw new \Exception('Cannot connect to database: ' . $e->getMessage());
		}
	}

	private function disconnect() {

		self::$pdo = null;
	}

	// Don't implement this:
	//public function getPDO() {
	//
	//}
	// Make a function for each query instead!
}