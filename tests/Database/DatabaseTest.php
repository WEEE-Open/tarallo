<?php

namespace WEEEOpen\Tarallo\Test\Database;

use PHPUnit\DbUnit\DataSet\YamlDataSet;
use PHPUnit\Framework\TestCase;
use PHPUnit\DbUnit\TestCaseTrait;
use WEEEOpen\Tarallo\Database\Database;
use WEEEOpen\Tarallo\Item;
use WEEEOpen\Tarallo\Query\SearchTriplet;

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
	 * @covers \WEEEOpen\Tarallo\Database\Database
	 * @uses   \WEEEOpen\Tarallo\User
	 * @uses   \WEEEOpen\Tarallo\Item
	 * @uses   \WEEEOpen\Tarallo\ItemIncomplete
	 * @covers \WEEEOpen\Tarallo\Database\ItemDAO
	 * @covers \WEEEOpen\Tarallo\Database\FeatureDAO
	 * @uses   \WEEEOpen\Tarallo\Database\DAO
	 * @uses   \WEEEOpen\Tarallo\Query\SearchTriplet
	 * @depends testAddingAndRetrievingSomeItems
	 */
	public function testItemSearchSorting() {
		$db = $this->getDb();
		$pc['PC-55'] = (new Item('PC-55'))->addFeature('brand', 'TI')->addFeature('model', 'GreyPC-\'98')->addFeature('type', 'case')->addFeature('motherboard-form-factor', 'atx');
		$pc['PC-20'] = (new Item('PC-20'))->addFeature('brand', 'Dill')->addFeature('model', 'DI-360')->addFeature('type', 'case')->addFeature('motherboard-form-factor', 'proprietary')->addFeature('color', 'black')->addFeature('working', 'yes');
		$pc['PC-21'] = (new Item('PC-21'))->addFeature('brand', 'Dill')->addFeature('model', 'DI-360')->addFeature('type', 'case')->addFeature('motherboard-form-factor', 'proprietary')->addFeature('color', 'grey')->addFeature('working', 'yes');
		$pc['PC-22'] = (new Item('PC-22'))->addFeature('brand', 'Dill')->addFeature('model', 'DI-360')->addFeature('type', 'case')->addFeature('motherboard-form-factor', 'proprietary')->addFeature('color', 'black')->addFeature('working', 'yes');
		$pc['SCHIFOMACCHINA'] = (new Item('SCHIFOMACCHINA'))->addFeature('brand', 'eMac')->addFeature('model', 'EZ1600')->addFeature('type', 'case')->addFeature('motherboard-form-factor', 'miniitx')->addFeature('color', 'white'); // based on a real PC we have in our laboratory.

		$db->modifcationBegin(new \WEEEOpen\Tarallo\User('asd', 'asd'));
		$db->itemDAO()->addItems($pc);
		$db->modificationCommit();

		$items = $db->itemDAO()->getItem(null, [new SearchTriplet('type', '=', 'case')], null, null, ['-motherboard-form-factor', '+color'], null);
		$this->assertContainsOnly(Item::class, $items);
		$this->assertEquals(5, count($items), 'There should be 5 items');
		/** @var Item[] $items */
		foreach(['PC-20', 'PC-22', 'PC-21', 'SCHIFOMACCHINA', 'PC-55'] as $pos => $code) {
			$this->assertEquals($code, $items[$pos]->getCode(), 'Item in position ' . $pos . ' should be ' . $code . '(it\'s ' . $items[$pos]->getCode() . ')');
			$this->assertEquals($pc[$code], $items[$pos], 'Item ' . $code . ' should be unchanged)');
		}

		$items = $db->itemDAO()->getItem(null, [new SearchTriplet('type', '=', 'case')], null, null, null, null);
		$this->assertContainsOnly(Item::class, $items);
		$this->assertEquals(5, count($items), 'There should be 5 items');
		// no sorting options => sort by code
		foreach(['PC-20', 'PC-21', 'PC-22', 'PC-55', 'SCHIFOMACCHINA'] as $pos => $code) {
			$this->assertEquals($code, $items[$pos]->getCode(), 'Item in position ' . $pos . ' should be ' . $code . '(it\'s ' . $items[$pos]->getCode() . ')');
			$this->assertEquals($pc[$code], $items[$pos], 'Item ' . $code . ' should be unchanged)');
		}

		$items = $db->itemDAO()->getItem(null, [new SearchTriplet('color', '=', 'white')], null, null, null, null);
		$this->assertContainsOnly(Item::class, $items);
		$this->assertEquals(1, count($items), 'There should be only one item');
		$this->assertEquals(reset($items), $pc['SCHIFOMACCHINA'], 'Only SCHIFOMACCHINA should be returned'); // excellent piece of hardware, by the way. 2 minutes from power on to POST OK.
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Database\Database
	 * @uses   \WEEEOpen\Tarallo\User
	 * @uses   \WEEEOpen\Tarallo\Item
	 * @uses   \WEEEOpen\Tarallo\ItemIncomplete
	 * @covers \WEEEOpen\Tarallo\Database\ItemDAO
	 * @covers \WEEEOpen\Tarallo\Database\FeatureDAO
	 * @uses   \WEEEOpen\Tarallo\Database\DAO
	 * @uses   \WEEEOpen\Tarallo\Query\SearchTriplet
	 * @depends testAddingAndRetrievingSomeItems
	 */
	public function testItemSearchFiltering() {
		$cpu['INTEL-1'] = (new Item('INTEL-1'))->addFeature('type', 'cpu')->addFeature('brand', 'Intel-lighenzia')->addFeature('model', 'Core 2.0 Trio')->addFeature('frequency-hz',    1400000000);
		$cpu['INTEL-2'] = (new Item('INTEL-2'))->addFeature('type', 'cpu')->addFeature('brand', 'Intel-lighenzia')->addFeature('model', 'Core 3.0 Quadrio')->addFeature('frequency-hz', 2000000000);
		$cpu['INTEL-3'] = (new Item('INTEL-3'))->addFeature('type', 'cpu')->addFeature('brand', 'Intel-lighenzia')->addFeature('model', 'Atomic 5L0W-NE55')->addFeature('frequency-hz', 42);
		$cpu['INTEL-4'] = (new Item('INTEL-4'))->addFeature('type', 'cpu')->addFeature('brand', 'Intel-lighenzia')->addFeature('model', 'Centrino DellaNonna')->addFeature('frequency-hz', 1000000000);
		$cpu['AMD-42']  = (new Item('AMD-42') )->addFeature('type', 'cpu')->addFeature('brand', 'Advanced Magnificent Processors')->addFeature('model', 'A4-200g')->addFeature('notes', 'A4, 200 g/cm², come la carta.')->addFeature('frequency-hz', 1900000000);
		$cpu['AMD-737'] = (new Item('AMD-737'))->addFeature('type', 'cpu')->addFeature('brand', 'Advanced Magnificent Processors')->addFeature('model', '737-800')->addFeature('frequency-hz', 3700000000);
		$db = $this->getDb();

		$db->modifcationBegin(new \WEEEOpen\Tarallo\User('asd', 'asd'));
		$db->itemDAO()->addItems($cpu);
		$db->modificationCommit();

		$items = $db->itemDAO()->getItem(null, [new SearchTriplet('type', '=', 'cpu')], null, null, null, null);
		$this->assertContainsOnly(Item::class, $items);
		$this->assertEquals(6, count($items), 'There should be 6 items');
		/** @var Item[] $items */
		foreach(['AMD-42', 'AMD-737', 'INTEL-1', 'INTEL-2', 'INTEL-3', 'INTEL-4'] as $pos => $code) {
			$this->assertEquals($code, $items[$pos]->getCode(), 'Item in position ' . $pos . ' should be ' . $code . '(it\'s ' . $items[$pos]->getCode() . ')');
			$this->assertEquals($cpu[$code], $items[$pos], 'Item ' . $code . ' should be unchanged)');
		}

		$items = $db->itemDAO()->getItem(null, [new SearchTriplet('type', '=', 'cpu')], null, null, ['-frequency-hz'], null);
		$this->assertContainsOnly(Item::class, $items);
		$this->assertEquals(6, count($items), 'There should be 6 items');
		foreach(['AMD-737', 'INTEL-2', 'AMD-42', 'INTEL-1', 'INTEL-4', 'INTEL-3'] as $pos => $code) {
			$this->assertEquals($code, $items[$pos]->getCode(), 'Item in position ' . $pos . ' should be ' . $code . '(it\'s ' . $items[$pos]->getCode() . ')');
			$this->assertEquals($cpu[$code], $items[$pos], 'Item ' . $code . ' should be unchanged)');
		}

		$items = $db->itemDAO()->getItem(null, [new SearchTriplet('brand', '=', 'Intel')], null, null, null, null);
		$this->assertEquals(0, count($items), 'No items returned without wildcard');

		$itemsGeq = $db->itemDAO()->getItem(null, [new SearchTriplet('brand', '>', 'Intel%')], null, null, null, null);
		$this->assertContainsOnly(Item::class, $itemsGeq);
		$this->assertEquals(4, count($itemsGeq), 'There should be 4 items when using > (query should contain LIKE regardless)');

		$items = $db->itemDAO()->getItem(null, [new SearchTriplet('brand', '=', 'Intel%')], null, null, null, null);
		$this->assertContainsOnly(Item::class, $items);
		$this->assertEquals(4, count($items), 'There should be 4 items when using = (in this case query should use LIKE)');
		$this->assertEquals($items, $itemsGeq, 'Same result set in same order when using >, < or = on a field that uses LIKE');
		foreach(['INTEL-1', 'INTEL-2', 'INTEL-3', 'INTEL-4'] as $pos => $code) {
			$this->assertEquals($code, $items[$pos]->getCode(), 'Item in position ' . $pos . ' should be ' . $code . '(it\'s ' . $items[$pos]->getCode() . ')');
			$this->assertEquals($cpu[$code], $items[$pos], 'Item ' . $code . ' should be unchanged)');
		}
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Database\Database
	 * @uses   \WEEEOpen\Tarallo\User
	 * @uses   \WEEEOpen\Tarallo\Item
	 * @uses   \WEEEOpen\Tarallo\ItemIncomplete
	 * @covers \WEEEOpen\Tarallo\Database\ItemDAO
	 * @covers \WEEEOpen\Tarallo\Database\FeatureDAO
	 * @uses   \WEEEOpen\Tarallo\Database\DAO
	 * @uses   \WEEEOpen\Tarallo\Query\SearchTriplet
	 * @depends testAddingAndRetrievingSomeItems
	 */
	public function testTreeMove() {
		// These items should be added in database.yml, but that just increases the amount of data to import for each test
		// and I find more readable this than a YAML file full of numbers.
		$chernobyl = (new Item('CHERNOBYL'))->addFeature('type', 'location');
		$tavolone = (new Item('TAVOLONE'))->addFeature('type', 'location');
		$chernobyl->addChild($tavolone)->addChild((new Item('Armadio L'))->addFeature('type', 'location'))->addChild((new Item('Armadio R'))->addFeature('type', 'location'));
		$tavolone->addChild(
					(new Item('SCHIFOMACCHINA'))->addFeature('brand', 'eMac')->addFeature('model', 'EZ1600')->addFeature('type', 'case')->addFeature('motherboard-form-factor', 'miniitx')->addFeature('color', 'white'));
		$ti = (new Item('PC-TI'))->addFeature('brand', 'TI')->addFeature('type', 'case')->addFeature('motherboard-form-factor', 'miniitx')->addFeature('color', 'white')
					->addChild(
						(new Item('RAM-22'))->addFeature('type', 'ram')->addFeature('capacity-byte', 32)
					)
					->addChild(
						(new Item('RAM-23'))->addFeature('type', 'ram')->addFeature('capacity-byte', 32)
					)
					->addChild(
						(new Item('PC-TI-MOBO'))->addFeature('type', 'motherboard')->addFeature('color', 'green')
					)
					->addChild(
						(new Item('PC-TI-CPU'))->addFeature('type', 'cpu')->addFeature('brand', 'Intel-lighenzia')->addFeature('model', 'Atomic 5L0W-NE55')->addFeature('frequency-hz', 42)
					);
		$tavolone->addChild(
					(new Item('ROSETTA'))->addFeature('brand', 'pH')->addFeature('model', 'ReliaPro MLG555')->addFeature('type', 'case')->addFeature('motherboard-form-factor', 'atx')->addFeature('color', 'grey'))
					->addChild(
						(new Item('RAM-3342'))->addFeature('type', 'ram')->addFeature('capacity-byte', 1073741824)
					)
					->addChild(
						(new Item('RAM-2452'))->addFeature('type', 'ram')->addFeature('capacity-byte', 1073741824)
					);

		$chernobylPre = $chernobyl;
		$chernobylPost = clone $chernobyl;

		// Move TI from TAVOLONE to Zona blu.
		$tavolone->addChild($ti);
		$chernobylPost->addChild((new Item('Zona blu'))->addFeature('type', 'location')->addChild($ti));

		// TODO: actually move, compare result fetched from database
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
