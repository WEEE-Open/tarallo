<?php

namespace WEEEOpen\TaralloTest\Database;

use PHPUnit\DbUnit\DataSet\YamlDataSet;
use PHPUnit\DbUnit\TestCase;
use PHPUnit\DbUnit\TestCaseTrait;
use WEEEOpen\Tarallo\Database\Database;
use WEEEOpen\Tarallo\Item;

abstract class DatabaseTest extends TestCase {
	use TestCaseTrait;

	protected $db = null;

	// this cannot be done, PLAIN AND SIMPLE. Even though it comes straight from an example inside documentation.
	// setUp() comes from a trait, so there's no way to override it AND call it. parent::setUp() calls a pointless empty function.
	// Excellent documentation, very clear, would rate it 10/10.
	//protected function setUp() {
	// if(!extension_loaded('pdo_mysql')) {
	// $this->markTestSkipped('The PDO MySQL extension is not available.');
	// }
	//}

	protected function getPdo() {
		require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
		return new \PDO(TARALLO_DB_DSN, TARALLO_DB_USERNAME, TARALLO_DB_PASSWORD, [
			\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
			\PDO::ATTR_CASE => \PDO::CASE_NATURAL,
			\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
			// \PDO::ATTR_AUTOCOMMIT => false, // PHPUnit crashes and burns with autocommits disabled and, for some unfathomable reason, two SEPARATE, DISTINCT, UNIQUE PDO object will forcefully share the same connection to MySQL (apparently?), so there's no way to have a connection with autocommits and another one without.
			\PDO::ATTR_EMULATE_PREPARES => false,
		]);
	}

	public function getConnection() {
		return $this->createDefaultDBConnection($this->getPdo(), 'tarallo_test');
	}

	public function getDataSet() {
		$file = dirname(__FILE__) . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR . "database.yml";

		return new YamlDataSet($file);
	}

	/**
	 * @return Database
	 */
	protected function getDb() {
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


	protected static function itemCompare(Item $a, Item $b) {
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
		/** @var Item[] $bContent */
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