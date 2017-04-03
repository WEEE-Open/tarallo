<?php
use PHPUnit\Framework\TestCase;
use PHPUnit\DbUnit\TestCaseTrait;
use WEEEOpen\Tarallo\Database;

class DatabaseTest extends TestCase {
	use TestCaseTrait;

	private function getPdo() {
		return new PDO('mysql:dbname=tarallo_test;host=10.13.37.6;charset=utf8mb4', 'root', 'root');
	}

	public function getConnection() {
		return $this->createDefaultDBConnection($this->getPdo(), 'tarallo_test');
	}

	public function getDataSet() {
		return new PHPUnit\DbUnit\DataSet\YamlDataSet(
			dirname(__FILE__) . "/database.yml"
		);
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Database
	 */
	public function testGetUserInvalidSession() {
		$this->assertEquals($this->getDb()->getUserFromSession('foo'), null);
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Database
	 */
	public function testGetUserAccountDisabled() {
		$this->assertEquals($this->getDb()->getUserFromSession('this-really-is-a-session-1234567'), null);
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Database
	 */
	public function testGetUserAccountExpiredSession() {
		$this->assertEquals($this->getDb()->getUserFromSession('this-really-is-a-session-7654321'), null);
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Database
	 * @uses   \WEEEOpen\Tarallo\User
	 */
	public function testGetUserAccountValidSession() {
		$this->assertEquals((string) $this->getDb()->getUserFromSession('this-really-is-a-valid-session-1'), 'asd-valid');
	}

//if($user !== null) {
//try {
//$db->modifcationBegin($user);
//$case = (new Item('PC-69'))->addFeature('brand', 'TI')->addFeature('model', 'GreyPC-\'98');
//$discone1 = (new Item('SATAna-1'))->addFeature('capacity', 666)->addFeature('brand', 'SATAn Storage Corporation Inc.')->addFeature('model', 'Discone da 666 byte');
//$discone2 = (new Item('SATAna-2'))->addFeature('capacity', 666)->addFeature('brand', 'SATAn Storage Corporation Inc.')->addFeature('model', 'Discone da 666 byte');
//$case->addChild($discone1);
//$case->addChild($discone2);
//$db->addItems($case);
//$db->modificationCommit();
//} catch(\Exception $e) {
//	$db->modificationRollback();
//	Response::sendError('Error: ' . $e->getMessage());
//}
//	}

	/**
	 * @return Database
	 */
	private function getDb() {
		$db = new Database();
		$dbr = new ReflectionObject($db);
		$prop = $dbr->getProperty('pdo');
		$prop->setAccessible(true);
		$prop->setValue($db, $this->getPdo());
		return $db;
	}
}
