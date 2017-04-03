<?php
use PHPUnit\Framework\TestCase;
use PHPUnit\DbUnit\TestCaseTrait;
use WEEEOpen\Tarallo\Database;
use WEEEOpen\Tarallo\Item;

class DatabaseTest extends TestCase {
	use TestCaseTrait;

	private $db = null;

	private function getPdo() {
		return new PDO('mysql:dbname=tarallo_test;host=10.13.37.6;charset=utf8mb4', 'root', 'root');
	}

	public function getConnection() {
		return $this->createDefaultDBConnection($this->getPdo(), 'tarallo_test');
	}

	public function getDataSet() {
		$this->getConnection();
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

	/**
	 * @covers \WEEEOpen\Tarallo\Database
	 * @uses   \WEEEOpen\Tarallo\User
	 */
	public function testGetUserFromLoginValid() {
		$this->assertEquals((string) $this->getDb()->getUserFromLogin('asd', 'asd'), 'asd');
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Database
	 */
	public function testGetUserFromLoginDisabledAccount() {
		$this->assertEquals((string) $this->getDb()->getUserFromLogin('asd-disabled', 'asd'), null);
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Database
	 * @uses   \WEEEOpen\Tarallo\User
	 */
	public function testGetUserFromLoginWrongPassword() {
		$this->assertEquals((string) $this->getDb()->getUserFromLogin('asd', 'wrong'), null);
	}

	/**
	 * Database tests are really slow and this code is a bit complex to say the least, testing everything
	 * in a sensible manner will be difficult. But some tests are better than no tests at all, right?
	 *
	 * @covers \WEEEOpen\Tarallo\Database
	 * @uses   \WEEEOpen\Tarallo\User
	 * @uses   \WEEEOpen\Tarallo\Item
	 */
	public function testAddingSomeItems() {
		$db = $this->getDb();
		$db->modifcationBegin(new \WEEEOpen\Tarallo\User('asd', 'asd'));
		/** @var $case Item */ // PHPStorm suddenly doesn't recognize chained methods. Only the last one of every chain, specifically.
		$case = (new Item('PC-42'))->addFeature('brand', 'TI')->addFeature('model', 'GreyPC-\'98')->addFeature('type', 'case')->addFeature('motherboard-form-factor', 'atx');
		$discone1 = (new Item('SATAna-1'))->addFeature('capacity', 666)->addFeature('brand', 'SATAn Storage Corporation Inc.')->addFeature('model', 'Discone da 666 byte')->addFeature('type', 'hdd');
		$discone2 = (new Item('SATAna-2'))->addFeature('capacity', 666)->addFeature('brand', 'SATAn Storage Corporation Inc.')->addFeature('model', 'Discone da 666 byte')->addFeature('type', 'hdd');
		$case->addChild($discone1);
		$case->addChild($discone2);
		$db->addItems($case);
		$db->modificationCommit();

		$itemTableRightNow = new PHPUnit\DbUnit\DataSet\QueryTable('Item.Code', 'SELECT Code, IsDefault FROM Item', $this->getConnection());
		$this->assertTableContains(['Code' => 'PC-42', 'IsDefault' => '0'], $itemTableRightNow);
		// TODO: enable when working
		//$this->assertTableContains(['Code' => 'SATAna-1', 'IsDefault' => 0], $itemTableRightNow);
		//$this->assertTableContains(['Code' => 'SATAna-2', 'IsDefault' => 0], $itemTableRightNow);
	}

	/**
	 * @return Database
	 */
	private function getDb() {
		if($this->db === null) {
			$db   = new Database();
			$dbr  = new ReflectionObject($db);
			$prop = $dbr->getProperty('pdo');
			$prop->setAccessible(true);
			$prop->setValue($db, $this->getPdo());
			$this->db = $db;
		}
		return $this->db;
	}
}
