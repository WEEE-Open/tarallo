<?php

namespace WEEEOpen\TaralloTest\Database;

use PHPUnit\Framework\TestCase;
use WEEEOpen\Tarallo\Database\Database;
use WEEEOpen\Tarallo\Item;

abstract class DatabaseTest extends TestCase {
	protected $db = null;

	// this cannot be done, PLAIN AND SIMPLE. Even though it comes straight from an example inside documentation.
	// setUp() comes from a trait, so there's no way to override it AND call it. parent::setUp() calls a pointless empty function.
	// Excellent documentation, very clear, would rate it 10/10.
	//protected function setUp() {
	// if(!extension_loaded('pdo_mysql')) {
	// $this->markTestSkipped('The PDO MySQL extension is not available.');
	// }
	//}

	protected static function getPdo(): \PDO {
		require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
		return new \PDO(TARALLO_DB_DSN, TARALLO_DB_USERNAME, TARALLO_DB_PASSWORD, [
			\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
			\PDO::ATTR_CASE => \PDO::CASE_NATURAL,
			\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
			// \PDO::ATTR_AUTOCOMMIT => false, // PHPUnit crashes and burns with autocommits disabled and, for some unfathomable reason, two SEPARATE, DISTINCT, UNIQUE PDO object will forcefully share the same connection to MySQL (apparently?), so there's no way to have a connection with autocommits and another one without.
			\PDO::ATTR_EMULATE_PREPARES => false,
		]);
	}

	public static function setUpBeforeClass(): void {
		$pdo = null;
		$retries = 0;
		$started = false;
		while($retries <= 20) {
			try {
				$pdo = self::getPdo();
				$started = true;
				break;
			} catch(\PDOException $e) {
				$retries++;
				sleep(1);
			}
		}
		if(!$started) {
			throw new \RuntimeException("Database not up after $retries seconds");
		}

		$retries = 0;
		$found = false;
		while($retries <= 20) {
			$result = $pdo->query("SHOW EVENTS LIKE 'DuplicateItemProductFeaturesCleanup'");
			if($result !== false) {
				$result->fetchAll(\PDO::FETCH_ASSOC);
				$found = true;
				break;
			}
			sleep(1);
			$retries++;
		}
		if(!$found) {
			throw new \RuntimeException("Database not ready after $retries seconds (2)");
		}
	}

	public function setUp(): void {
		$pdo = self::getPdo();

		$pdo->exec(/** @lang MariaDB */ "SET FOREIGN_KEY_CHECKS = 0; TRUNCATE TABLE Audit; SET FOREIGN_KEY_CHECKS = 1;");
		$pdo->exec(/** @lang MariaDB */ "SET FOREIGN_KEY_CHECKS = 0; TRUNCATE TABLE AuditProduct; SET FOREIGN_KEY_CHECKS = 1;");
		$pdo->exec(/** @lang MariaDB */ "SET FOREIGN_KEY_CHECKS = 0; TRUNCATE TABLE Item; SET FOREIGN_KEY_CHECKS = 1;");
		$pdo->exec(/** @lang MariaDB */ "SET FOREIGN_KEY_CHECKS = 0; TRUNCATE TABLE ItemFeature; SET FOREIGN_KEY_CHECKS = 1;");
		$pdo->exec(/** @lang MariaDB */ "SET FOREIGN_KEY_CHECKS = 0; TRUNCATE TABLE Product; SET FOREIGN_KEY_CHECKS = 1;");
		$pdo->exec(/** @lang MariaDB */ "SET FOREIGN_KEY_CHECKS = 0; TRUNCATE TABLE ProductFeature; SET FOREIGN_KEY_CHECKS = 1;");
		$pdo->exec(/** @lang MariaDB */ "SET FOREIGN_KEY_CHECKS = 0; TRUNCATE TABLE Tree; SET FOREIGN_KEY_CHECKS = 1;");
		$pdo->exec(/** @lang MariaDB */ "SET FOREIGN_KEY_CHECKS = 0; TRUNCATE TABLE Prefixes; SET FOREIGN_KEY_CHECKS = 1;");
		$pdo->exec(/** @lang MariaDB */ "SET FOREIGN_KEY_CHECKS = 0; TRUNCATE TABLE Product; SET FOREIGN_KEY_CHECKS = 1;");
		$pdo->exec(/** @lang MariaDB */ "SET FOREIGN_KEY_CHECKS = 0; TRUNCATE TABLE SearchResult; SET FOREIGN_KEY_CHECKS = 1;");
		$pdo->exec(/** @lang MariaDB */ "SET FOREIGN_KEY_CHECKS = 0; TRUNCATE TABLE Search; SET FOREIGN_KEY_CHECKS = 1;");
		$pdo->exec(/** @lang MariaDB */ "DELETE FROM Configuration WHERE `Key` NOT IN ('SchemaVersion', 'DataVersion');");
		$pdo->exec(/** @lang MariaDB */ "INSERT INTO Prefixes(Prefix, `Integer`) VALUES ('M', 10), ('T', 75), ('', 60);");
	}

	/**
	 * @return Database
	 */
	protected function getDb(): Database {
		if($this->db === null) {
			$this->getPdo();
			$db = new Database(TARALLO_DB_USERNAME, TARALLO_DB_PASSWORD, TARALLO_DB_DSN);
			//$dbr = new \ReflectionObject($db);
			//$prop = $dbr->getProperty('pdo');
			//$prop->setAccessible(true);
			//$prop->setValue($db, $this->getPdo());
			$this->db = $db;
		}

		return $this->db;
	}


	protected static function itemCompare(Item $a, Item $b): bool {
		if($a->getCode() !== $b->getCode()) {
			return false;
		}
		// TODO: compare recursively
		//if($a->getProductFromStrings() !== $b->getProductFromStrings()) {
		//	return false;
		//}
		if(count($a->getFeatures()) !== count($b->getFeatures())) {
			return false;
		}
		if(!empty(array_diff_assoc($a->getFeatures(), $b->getFeatures()))) {
			return false;
		}
		if(count($a->getContent()) !== count($b->getContent())) {
			return false;
		}
		$bContent = $b->getContent();
		foreach($a->getContent() as $item) {
			$code = $item->getCode();
			foreach($bContent as $item2) {
				if($code === $item2->getCode()) {
					if(!static::itemCompare($item, $item2)) {
						return false;
					}
				}
			}
		}

		return true;
	}
}