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

	private function getPdo() {
		return new \PDO('mysql:dbname=tarallo_test;host=10.13.37.6;charset=utf8mb4', 'root', 'root', [
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
	 * @uses   \WEEEOpen\Tarallo\ItemIncomplete
	 * @covers \WEEEOpen\Tarallo\Database\ItemDAO
	 * @covers \WEEEOpen\Tarallo\Database\FeatureDAO
	 * @uses   \WEEEOpen\Tarallo\Database\DAO
	 */
	public function testAddingAndRetrievingSomeItems() {
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

		$db = $this->getDb();
		$items = $db->itemDAO()->getItem(['PC-42'], null, null, null, null, null);
		$this->assertContainsOnly(Item::class, $items);
		$this->assertEquals(1, count($items), 'Only one root Item');
		/** @var Item $newCase */
		$newCase = reset($items); // get the only Item
		$this->assertEquals(2, count($newCase->getChildren()), 'Two child Item');
		$this->assertContainsOnly(Item::class, $newCase->getChildren(), null, 'Only Items are contained in an Item');
		foreach($newCase->getChildren() as $child) {
			/** @var Item $child */
			$this->assertTrue($child->getCode() === 'SATAna-1' || $child->getCode() === 'SATAna-2', 'Sub-Item is one of the two expected items, ' . (string) $child);
			/** @noinspection PhpUndefinedMethodInspection */
			$this->assertTrue($case->getChildren()[0]->getFeatures() == $child->getFeatures(), 'Sub-Item ' . (string) $child . ' has same features as before'); // this works because the two items are identical except for the code...
			$this->assertTrue(empty($child->getChildren()), 'No children of child Item ' . (string) $child);
		}
	}

	/**
	 * @return Database
	 */
	private function getDb() {
		if($this->db === null) {
			$db = new Database('root', 'root', 'mysql:dbname=tarallo_test;host=mysql.local');
			//$dbr  = new \ReflectionObject($db);
			//$prop = $dbr->getProperty('pdo');
			//$prop->setAccessible(true);
			//$prop->setValue($db, $this->getPdo());
			$this->db = $db;
		}
		return $this->db;
	}
}
