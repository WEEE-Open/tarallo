<?php

namespace WEEEOpen\Tarallo\Test\Database;

use PHPUnit\DbUnit\DataSet\YamlDataSet;
use PHPUnit\Framework\TestCase;
use PHPUnit\DbUnit\TestCaseTrait;
use WEEEOpen\Tarallo\Database\Database;
use WEEEOpen\Tarallo\Item;

class DatabaseTest extends TestCase {
	use TestCaseTrait;

	private $db = null;

	// this cannot be done, PLAIN AND SIMPLE. Even though it comes straight from an example inside documentation.
	// setUp() comes from a trait, so there's no way to override it AND call it. parent::setUp() calls a pointless empty function.
	// Excellent documentation, very clear, would rate it 10/10.
    //protected function setUp() {
    //    if(!extension_loaded('pdo_mysql')) {
    //        $this->markTestSkipped('The PDO MySQL extension is not available.');
    //    }
    //}

	private function getDsn() {
		return 'mysql:dbname=tarallo_test;host=10.13.37.6;charset=utf8mb4';
	}

	private function getPdo() {
		return new \PDO($this->getDsn(), 'root', 'root');
	}

	public function getConnection() {
		return $this->createDefaultDBConnection($this->getPdo(), 'tarallo_test');
	}

	public function getDataSet() {
		return new YamlDataSet(
			dirname(__FILE__) . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR . "database.yml"
		);
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Database\Database
	 * @covers \WEEEOpen\Tarallo\Database\UserDAO
	 * @uses  \WEEEOpen\Tarallo\Database\DAO
	 */
	public function testGetUserInvalidSession() {
		$this->assertEquals(null, $this->getDb()->userDAO()->getUserFromSession('foo'));
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Database\Database
	 * @covers \WEEEOpen\Tarallo\Database\UserDAO
	 * @uses   \WEEEOpen\Tarallo\Database\DAO
	 */
	public function testGetUserAccountDisabled() {
		$this->assertEquals(null, $this->getDb()->userDAO()->getUserFromSession('this-really-is-a-session-1234567'));
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Database\Database
	 * @covers \WEEEOpen\Tarallo\Database\UserDAO
	 * @uses   \WEEEOpen\Tarallo\Database\DAO
	 */
	public function testGetUserAccountExpiredSession() {
		$this->assertEquals(null, $this->getDb()->userDAO()->getUserFromSession('this-really-is-a-session-7654321'));
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Database\Database
	 * @uses   \WEEEOpen\Tarallo\User
	 * @covers \WEEEOpen\Tarallo\Database\UserDAO
	 * @uses   \WEEEOpen\Tarallo\Database\DAO
	 */
	public function testGetUserAccountValidSession() {
		$this->assertEquals('asd-valid', (string) $this->getDb()->userDAO()->getUserFromSession('this-really-is-a-valid-session-1'));
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Database\Database
	 * @uses   \WEEEOpen\Tarallo\User
	 * @covers \WEEEOpen\Tarallo\Database\UserDAO
	 * @uses   \WEEEOpen\Tarallo\Database\DAO
	 */
	public function testGetUserFromLoginValid() {
		$this->assertEquals('asd', (string) $this->getDb()->userDAO()->getUserFromLogin('asd', 'asd'));
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Database\Database
	 * @covers \WEEEOpen\Tarallo\Database\UserDAO
	 * @uses   \WEEEOpen\Tarallo\Database\DAO
	 */
	public function testGetUserFromLoginDisabledAccount() {
		$this->assertEquals(null, (string) $this->getDb()->userDAO()->getUserFromLogin('asd-disabled', 'asd'));
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Database\Database
	 * @uses   \WEEEOpen\Tarallo\User
	 * @covers \WEEEOpen\Tarallo\Database\UserDAO
	 * @uses   \WEEEOpen\Tarallo\Database\DAO
	 */
	public function testGetUserFromLoginWrongPassword() {
		$this->assertEquals(null, (string) $this->getDb()->userDAO()->getUserFromLogin('asd', 'wrong'));
	}

    /**
     * @covers \WEEEOpen\Tarallo\Database\Database
     * @uses   \WEEEOpen\Tarallo\User
     * @covers \WEEEOpen\Tarallo\Database\UserDAO
     * @uses   \WEEEOpen\Tarallo\Database\DAO
     */
    public function testUserLoginLogout() {
        $this->assertEquals(null, $this->getDb()->userDAO()->getUserFromSession('session-started-in-test-12345678'));
        $this->getDb()->userDAO()->setSessionFromUser('asd', 'session-started-in-test-12345678', 9223372036854775807);
        $this->assertEquals('asd', (string) $this->getDb()->userDAO()->getUserFromSession('session-started-in-test-12345678'));
        $this->getDb()->userDAO()->setSessionFromUser('asd', null, null);
        $this->assertEquals(null, $this->getDb()->userDAO()->getUserFromSession('session-started-in-test-12345678'));
    }

	/**
	 * Database tests are really slow and this code is a bit complex to say the least, testing everything
	 * in a sensible manner will be difficult. But some tests are better than no tests at all, right?
	 *
	 * @covers \WEEEOpen\Tarallo\Database\Database
	 * @uses   \WEEEOpen\Tarallo\User
	 * @uses   \WEEEOpen\Tarallo\Item
	 * @covers \WEEEOpen\Tarallo\Database\ItemDAO
	 * @covers \WEEEOpen\Tarallo\Database\FeatureDAO
	 * @uses   \WEEEOpen\Tarallo\Database\DAO
	 */
	public function testAddingSomeItems() {
		$db = $this->getDb();
		$db->modifcationBegin(new \WEEEOpen\Tarallo\User('asd', 'asd'));
		/** @var $case Item */ // PHPStorm suddenly doesn't recognize chained methods. Only the last one of every chain, specifically.
		$case = (new Item('PC-42'))->addFeature('brand', 'TI')->addFeature('model', 'GreyPC-\'98')->addFeature('type', 'case')->addFeature('motherboard-form-factor', 'atx');
		$discone1 = (new Item('SATAna-1'))->addFeature('capacity-byte', 666)->addFeature('brand', 'SATAn Storage Corporation Inc.')->addFeature('model', 'Discone da 666 byte')->addFeature('type', 'hdd');
		$discone2 = (new Item('SATAna-2'))->addFeature('capacity-byte', 666)->addFeature('brand', 'SATAn Storage Corporation Inc.')->addFeature('model', 'Discone da 666 byte')->addFeature('type', 'hdd');
		$case->addChild($discone1);
		$case->addChild($discone2);
		$db->itemDAO()->addItems($case);
		$db->modificationCommit();

		// TODO: checking database rows adds too much coupling between code and database tables, maybe getting the Item would be better?
		$itemTableRightNow = new \PHPUnit\DbUnit\DataSet\QueryTable('Item.Code', 'SELECT Code, IsDefault FROM Item', $this->getConnection());
		$this->assertTableContains(['Code' => 'PC-42', 'IsDefault' => '0'], $itemTableRightNow);
		$this->assertTableContains(['Code' => 'SATAna-1', 'IsDefault' => 0], $itemTableRightNow);
		$this->assertTableContains(['Code' => 'SATAna-2', 'IsDefault' => 0], $itemTableRightNow);
	}

	/**
	 * @return Database
	 */
	private function getDb() {
		if($this->db === null) {
			$this->db = new Database('root', 'root', $this->getDsn());
		}
		return $this->db;
	}
}
