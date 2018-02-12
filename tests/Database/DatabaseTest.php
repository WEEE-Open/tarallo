<?php

namespace WEEEOpen\Tarallo\Server\Test\Database;

use PHPUnit\DbUnit\DataSet\YamlDataSet;
use PHPUnit\DbUnit\TestCaseTrait;
use PHPUnit\Framework\TestCase;
use WEEEOpen\Tarallo\Server\Database\Database;
use WEEEOpen\Tarallo\Server\Feature;
use WEEEOpen\Tarallo\Server\Item;
use WEEEOpen\Tarallo\Server\SearchTriplet;
use WEEEOpen\Tarallo\Server\User;

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
		return new \PDO('mysql:dbname=tarallo_test;host=localhost;charset=utf8mb4', 'root', 'root', [
			\PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
			\PDO::ATTR_CASE               => \PDO::CASE_NATURAL,
			\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
			// \PDO::ATTR_AUTOCOMMIT => false, // PHPUnit crashes and burns with autocommits disabled and, for some unfathomable reason, two SEPARATE, DISTINCT, UNIQUE PDO object will forcefully share the same connection to MySQL (apparently?), so there's no way to have a connection with autocommits and another one without.
			\PDO::ATTR_EMULATE_PREPARES   => false,
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
	 * @covers \WEEEOpen\Tarallo\Server\Database\Database
	 * @covers \WEEEOpen\Tarallo\Server\Database\UserDAO
	 * @uses   \WEEEOpen\Tarallo\Server\Database\DAO
	 */
	public function testGetUserInvalidSession() {
		$this->assertEquals(null, $this->getDb()->userDAO()->getUserFromSession('foo'));
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\Database\Database
	 * @covers \WEEEOpen\Tarallo\Server\Database\UserDAO
	 * @uses   \WEEEOpen\Tarallo\Server\Database\DAO
	 */
	public function testGetUserAccountDisabled() {
		$this->assertEquals(null, $this->getDb()->userDAO()->getUserFromSession('this-really-is-a-session-1234567'));
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\Database\Database
	 * @covers \WEEEOpen\Tarallo\Server\Database\UserDAO
	 * @uses   \WEEEOpen\Tarallo\Server\Database\DAO
	 */
	public function testGetUserAccountExpiredSession() {
		$this->assertEquals(null, $this->getDb()->userDAO()->getUserFromSession('this-really-is-a-session-7654321'));
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\Database\Database
	 * @uses   \WEEEOpen\Tarallo\Server\User
	 * @covers \WEEEOpen\Tarallo\Server\Database\UserDAO
	 * @uses   \WEEEOpen\Tarallo\Server\Database\DAO
	 */
	public function testGetUserAccountValidSession() {
		$this->assertEquals('asd-valid',
			(string) $this->getDb()->userDAO()->getUserFromSession('this-really-is-a-valid-session-1'));
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\Database\Database
	 * @uses   \WEEEOpen\Tarallo\Server\User
	 * @covers \WEEEOpen\Tarallo\Server\Database\UserDAO
	 * @uses   \WEEEOpen\Tarallo\Server\Database\DAO
	 */
	public function testGetUserFromLoginValid() {
		$this->assertEquals('asd', (string) $this->getDb()->userDAO()->getUserFromLogin('asd', 'asd'));
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\Database\Database
	 * @covers \WEEEOpen\Tarallo\Server\Database\UserDAO
	 * @uses   \WEEEOpen\Tarallo\Server\Database\DAO
	 */
	public function testGetUserFromLoginDisabledAccount() {
		$this->assertEquals(null, (string) $this->getDb()->userDAO()->getUserFromLogin('asd-disabled', 'asd'));
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\Database\Database
	 * @uses   \WEEEOpen\Tarallo\Server\User
	 * @covers \WEEEOpen\Tarallo\Server\Database\UserDAO
	 * @uses   \WEEEOpen\Tarallo\Server\Database\DAO
	 */
	public function testGetUserFromLoginWrongPassword() {
		$this->assertEquals(null, (string) $this->getDb()->userDAO()->getUserFromLogin('asd', 'wrong'));
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\Database\Database
	 * @uses   \WEEEOpen\Tarallo\Server\User
	 * @covers \WEEEOpen\Tarallo\Server\Database\UserDAO
	 * @uses   \WEEEOpen\Tarallo\Server\Database\DAO
	 */
	public function testUserLoginLogout() {
		$this->assertEquals(null, $this->getDb()->userDAO()->getUserFromSession('session-started-in-test-12345678'));
		$this->getDb()->userDAO()->setSessionFromUser('asd', 'session-started-in-test-12345678', 9223372036854775807);
		$this->assertEquals('asd',
			(string) $this->getDb()->userDAO()->getUserFromSession('session-started-in-test-12345678'));
		$this->getDb()->userDAO()->setSessionFromUser('asd', null, null);
		$this->assertEquals(null, $this->getDb()->userDAO()->getUserFromSession('session-started-in-test-12345678'));
	}

	/**
	 * @uses   \WEEEOpen\Tarallo\Server\Database\Database
	 * @uses   \WEEEOpen\Tarallo\Server\Database\DAO
	 * @covers \WEEEOpen\Tarallo\Server\Database\FeatureDAO
	 */
	public function testFeatureList() {
		$features = $this->getDb()->featureDAO()->getFeatureList();
		$this->assertTrue(count($features) > 0, 'There should be some features');
		$this->assertContainsOnly("string", $features, 'Feature names should be only strings');
	}

	/**
	 * Database tests are really slow and this code is a bit complex to say the least, testing everything
	 * in a sensible manner will be difficult. But some tests are better than no tests at all, right?
	 *
	 * @covers  \WEEEOpen\Tarallo\Server\Database\Database
	 * @uses    \WEEEOpen\Tarallo\Server\User
	 * @uses    \WEEEOpen\Tarallo\Server\Item
	 * @uses    \WEEEOpen\Tarallo\Server\ItemIncomplete
	 * @covers  \WEEEOpen\Tarallo\Server\Database\ItemDAO
	 * @covers  \WEEEOpen\Tarallo\Server\Database\FeatureDAO
	 * @covers  \WEEEOpen\Tarallo\Server\Database\TreeDAO
	 * @uses    \WEEEOpen\Tarallo\Server\Database\DAO
	 * @uses    \WEEEOpen\Tarallo\Server\Database\ModificationDAO
	 */
	public function testAddingAndRetrievingSomeItems() {
		$db = $this->getDb();
		$db->modificationDAO()->modifcationBegin(new User('asd', 'asd'));
		/** @var $case Item */ // PHPStorm suddenly doesn't recognize chained methods. Only the last one of every chain, specifically.
		$case = (new Item('PC-42'))
			->addFeature(new Feature('brand', 'TI'))
			->addFeature(new Feature('model', 'GreyPC-\'98'))
			->addFeature(new Feature('type', 'case'))
			->addFeature(new Feature('motherboard-form-factor', 'atx'));
		$discone1 = (new Item('SATAna-1'))
			->addFeature(new Feature('capacity-byte', 666))
			->addFeature(new Feature('brand', 'SATAn Storage Corporation Inc.'))
			->addFeature(new Feature('model', 'Discone da 666 byte'))
			->addFeature(new Feature('type', 'hdd'));
		$discone2 = (new Item('SATAna-2'))
			->addFeature(new Feature('capacity-byte', 666))
			->addFeature(new Feature('brand', 'SATAn Storage Corporation Inc.'))
			->addFeature(new Feature('model', 'Discone da 666 byte'))
			->addFeature(new Feature('type', 'hdd'));
		$case->addContent($discone1);
		$case->addContent($discone2);
		$db->itemDAO()->addItems($case);
		$db->modificationDAO()->modificationCommit();

		$items = $db->itemDAO()->getItems(['PC-42'], null, null, null, null, null);
		$this->assertContainsOnly(Item::class, $items);
		$this->assertEquals(1, count($items), 'Only one root Item');
		/** @var Item $newCase */
		$newCase = reset($items); // get the only Item
		$this->assertEquals(2, count($newCase->getContents()), 'Two child Item');
		$this->assertContainsOnly(Item::class, $newCase->getContents(), null, 'Only Items are contained in an Item');
		foreach($newCase->getContents() as $child) {
			/** @var Item $child */
			$this->assertTrue($child->getCode() === 'SATAna-1' || $child->getCode() === 'SATAna-2',
				'Sub-Item is one of the two expected items, ' . (string) $child);
			/** @noinspection PhpUndefinedMethodInspection */
			$this->assertTrue($case->getContents()[0]->getFeatures() == $child->getFeatures(),
				'Sub-Item ' . (string) $child . ' has same features as before'); // this works because the two items are identical except for the code...
			$this->assertTrue(empty($child->getContents()), 'No children of child Item ' . (string) $child);
			$features = $child->getFeatures();
			$this->assertEquals(4, count($features), 'Items should still have all their features and none more');
			$this->assertArrayHasKey('capacity-byte', $features);
			$this->assertEquals(666, $features['capacity-byte']->value);
			$this->assertArrayHasKey('brand', $features);
			$this->assertEquals('SATAn Storage Corporation Inc.', $features['brand']->value);
		}
	}

	/**
	 * @uses    \WEEEOpen\Tarallo\Server\Database\Database
	 * @uses    \WEEEOpen\Tarallo\Server\User
	 * @uses    \WEEEOpen\Tarallo\Server\Item
	 * @uses    \WEEEOpen\Tarallo\Server\ItemIncomplete
	 * @covers  \WEEEOpen\Tarallo\Server\Database\ItemDAO
	 * @uses    \WEEEOpen\Tarallo\Server\Database\FeatureDAO
	 * @uses    \WEEEOpen\Tarallo\Server\Database\TreeDAO
	 * @uses    \WEEEOpen\Tarallo\Server\Database\DAO
	 * @uses    \WEEEOpen\Tarallo\Server\Database\ModificationDAO
	 */
	public function testGettingPrefixes() {
		$db = $this->getDb();

		$codes = $db->itemDAO()->getNextCodes([0 => 'M', 'asd' => 'T']);
		$this->assertEquals(2, count($codes));
		$this->assertArrayHasKey(0, $codes);
		$this->assertArrayHasKey('asd', $codes);
		$this->assertEquals('M11', $codes[0]);
		$this->assertEquals('T76', $codes['asd']);
	}

	/**
	 * @uses    \WEEEOpen\Tarallo\Server\Database\Database
	 * @uses    \WEEEOpen\Tarallo\Server\User
	 * @uses    \WEEEOpen\Tarallo\Server\Item
	 * @uses    \WEEEOpen\Tarallo\Server\ItemIncomplete
	 * @covers  \WEEEOpen\Tarallo\Server\Database\ItemDAO
	 * @uses    \WEEEOpen\Tarallo\Server\Database\FeatureDAO
	 * @uses    \WEEEOpen\Tarallo\Server\Database\TreeDAO
	 * @uses    \WEEEOpen\Tarallo\Server\Database\DAO
	 * @uses    \WEEEOpen\Tarallo\Server\Database\ModificationDAO
	 */
	public function testGettingPrefixesSkippingDuplicates() {
		$db = $this->getDb();

		$db->modificationDAO()->modifcationBegin(new User('asd', 'asd'));
		for($i = 74; $i < 77; $i++) {
			$db->itemDAO()->addItems((new Item('T' . $i))->addFeature(new Feature('type', 'keyboard')));
		}
		$db->modificationDAO()->modificationCommit();

		$codes = $db->itemDAO()->getNextCodes([0 => 'T']);
		$this->assertEquals(1, count($codes));
		$this->assertArrayHasKey(0, $codes);
		$this->assertEquals('T77', $codes[0]);
	}


	/**
	 * Database tests are really slow and this code is a bit complex to say the least, testing everything
	 * in a sensible manner will be difficult. But some tests are better than no tests at all, right?
	 *
	 * @covers \WEEEOpen\Tarallo\Server\Database\Database
	 * @uses   \WEEEOpen\Tarallo\Server\User
	 * @uses   \WEEEOpen\Tarallo\Server\Item
	 * @uses   \WEEEOpen\Tarallo\Server\ItemIncomplete
	 * @covers \WEEEOpen\Tarallo\Server\Database\ItemDAO
	 * @covers \WEEEOpen\Tarallo\Server\Database\FeatureDAO
	 * @covers \WEEEOpen\Tarallo\Server\Database\TreeDAO
	 * @uses   \WEEEOpen\Tarallo\Server\Database\DAO
	 * @uses   \WEEEOpen\Tarallo\Server\Database\ModificationDAO
	 */
	public function testSubtreeRemoval() {
		$db = $this->getDb();
		$db->modificationDAO()->modifcationBegin(new User('asd', 'asd'));
		/** @var $case Item */
		$lab = (new Item('CHERNOBYL'));
		$case = (new Item('PC-42'));
		$discone1 = (new Item('HDD-1'));
		$discone2 = (new Item('HDD-2'));
		$case->addContent($discone1);
		$case->addContent($discone2);
		$lab->addContent($case);
		$db->itemDAO()->addItems($lab);
		$db->modificationDAO()->modificationCommit();

		$items = $db->itemDAO()->getItems(['CHERNOBYL'], null, null, null, null, null);
		$this->assertContainsOnly(Item::class, $items);
		/** @var Item[] $items */
		$this->assertEquals(1, count($items), 'Only one root Item');
		/** @var Item $caseContent */
		$case = $items[0]->getContents()[0];
		$this->assertEquals('PC-42', $case->getCode(), 'PC-42 is still there');
		$this->assertTrue($this->itemCompare($lab, $items[0]), 'Lab should be unchanged');

		$db->modificationDAO()->modifcationBegin(new User('asd', 'asd'));
		$db->treeDAO()->removeFromTree($case);
		$db->modificationDAO()->modificationCommit();

		$items = $db->itemDAO()->getItems(['CHERNOBYL'], null, null, null, null, null);
		$this->assertContainsOnly(Item::class, $items);
		$this->assertEquals(1, count($items), 'Still only one root Item');
		$this->assertEquals(0, count($items[0]->getContents()), 'Lab is empty');

		$items = $db->itemDAO()->getItems(['PC-42'], null, null, null, null, null);
		$this->assertEquals(0, count($items), 'Item outside Tree cannot be selected');

		$items = $db->itemDAO()->getItems(['HDD-1'], null, null, null, null, null);
		$this->assertEquals(0, count($items), 'Item outside Tree cannot be selected');

		$items = $db->itemDAO()->getItems(['HDD-2'], null, null, null, null, null);
		$this->assertEquals(0, count($items), 'Item outside Tree cannot be selected');

	}

	/**
	 * @covers  \WEEEOpen\Tarallo\Server\Database\Database
	 * @uses    \WEEEOpen\Tarallo\Server\User
	 * @uses    \WEEEOpen\Tarallo\Server\Item
	 * @uses    \WEEEOpen\Tarallo\Server\ItemIncomplete
	 * @covers  \WEEEOpen\Tarallo\Server\Database\ItemDAO
	 * @covers  \WEEEOpen\Tarallo\Server\Database\FeatureDAO
	 * @covers  \WEEEOpen\Tarallo\Server\Database\TreeDAO
	 * @uses    \WEEEOpen\Tarallo\Server\Database\DAO
	 * @uses    \WEEEOpen\Tarallo\Server\Database\ModificationDAO
	 */
	public function testItemSearchSorting() {
		$db = $this->getDb();
		$pc['PC-55'] = (new Item('PC-55'))
			->addFeature(new Feature('brand', 'TI'))
			->addFeature(new Feature('model', 'GreyPC-\'98'))
			->addFeature(new Feature('type', 'case'))
			->addFeature(new Feature('motherboard-form-factor', 'atx'));
		$pc['PC-20'] = (new Item('PC-20'))
			->addFeature(new Feature('brand', 'Dill'))
			->addFeature(new Feature('model', 'DI-360'))
			->addFeature(new Feature('type', 'case'))
			->addFeature(new Feature('motherboard-form-factor', 'proprietary'))
			->addFeature(new Feature('color', 'black'))
			->addFeature(new Feature('working', 'yes'));
		$pc['PC-21'] = (new Item('PC-21'))
			->addFeature(new Feature('brand', 'Dill'))
			->addFeature(new Feature('model', 'DI-360'))
			->addFeature(new Feature('type', 'case'))
			->addFeature(new Feature('motherboard-form-factor', 'proprietary'))
			->addFeature(new Feature('color', 'grey'))
			->addFeature(new Feature('working', 'yes'));
		$pc['PC-22'] = (new Item('PC-22'))
			->addFeature(new Feature('brand', 'Dill'))
			->addFeature(new Feature('model', 'DI-360'))
			->addFeature(new Feature('type', 'case'))
			->addFeature(new Feature('motherboard-form-factor', 'proprietary'))
			->addFeature(new Feature('color', 'black'))
			->addFeature(new Feature('working', 'yes'));
		$pc['SCHIFOMACCHINA'] = (new Item('SCHIFOMACCHINA'))
			->addFeature(new Feature('brand', 'eMac'))
			->addFeature(new Feature('model', 'EZ1600'))
			->addFeature(new Feature('type', 'case'))
			->addFeature(new Feature('motherboard-form-factor', 'miniitx'))
			->addFeature(new Feature('color', 'white')); // based on a real PC we have in our laboratory.

		$db->modificationDAO()->modifcationBegin(new User('asd', 'asd'));
		$db->itemDAO()->addItems($pc);
		$db->modificationDAO()->modificationCommit();

		$items = $db->itemDAO()->getItems(null, [new SearchTriplet('type', '=', 'case')], null, null,
			['motherboard-form-factor' => '-', 'color' => '+'], null);
		$this->assertContainsOnly(Item::class, $items);
		$this->assertEquals(5, count($items), 'There should be 5 items');
		/** @var Item[] $items */
		foreach(['PC-20', 'PC-22', 'PC-21', 'SCHIFOMACCHINA', 'PC-55'] as $pos => $code) {
			$this->assertEquals($code, $items[$pos]->getCode(),
				'Item in position ' . $pos . ' should be ' . $code . ' (it\'s ' . $items[$pos]->getCode() . ')');
			$this->assertEquals($pc[$code], $items[$pos], 'Item ' . $code . ' should be unchanged)');
		}

		$items = $db->itemDAO()->getItems(null, [new SearchTriplet('type', '=', 'case')], null, null, null, null);
		$this->assertContainsOnly(Item::class, $items);
		$this->assertEquals(5, count($items), 'There should be 5 items');
		// no sorting options => sort by code
		foreach(['PC-20', 'PC-21', 'PC-22', 'PC-55', 'SCHIFOMACCHINA'] as $pos => $code) {
			$this->assertEquals($code, $items[$pos]->getCode(),
				'Item in position ' . $pos . ' should be ' . $code . '(it\'s ' . $items[$pos]->getCode() . ')');
			$this->assertEquals($pc[$code], $items[$pos], 'Item ' . $code . ' should be unchanged)');
		}

		$items = $db->itemDAO()->getItems(null, [new SearchTriplet('color', '=', 'white')], null, null, null, null);
		$this->assertContainsOnly(Item::class, $items);
		$this->assertEquals(1, count($items), 'There should be only one item');
		$this->assertEquals(reset($items), $pc['SCHIFOMACCHINA'],
			'Only SCHIFOMACCHINA should be returned'); // excellent piece of hardware, by the way. 2 minutes from power on to POST OK.
	}

	/**
	 * @uses    \WEEEOpen\Tarallo\Server\Database\Database
	 * @uses    \WEEEOpen\Tarallo\Server\User
	 * @uses    \WEEEOpen\Tarallo\Server\Item
	 * @uses    \WEEEOpen\Tarallo\Server\ItemIncomplete
	 * @covers  \WEEEOpen\Tarallo\Server\Database\ItemDAO
	 * @uses    \WEEEOpen\Tarallo\Server\Database\FeatureDAO
	 * @uses    \WEEEOpen\Tarallo\Server\Database\TreeDAO
	 * @uses    \WEEEOpen\Tarallo\Server\Database\DAO
	 * @uses    \WEEEOpen\Tarallo\Server\Database\ModificationDAO
	 */
	public function testGettingItemsWithLocation() {
		$db = $this->getDb();
		$db->modificationDAO()->modifcationBegin(new User('asd', 'asd'));
		$case = (new Item('PC-42'))->addFeature(new Feature('brand', 'TI'));
		$discone1 = (new Item('SATAna-1'))->addFeature(new Feature('brand', 'SATAn Storage Corporation Inc.'));
		$discone2 = (new Item('SATAna-2'))->addFeature(new Feature('brand', 'SATAn Storage Corporation Inc.'));
		$case->addContent($discone1);
		$case->addContent($discone2);
		$db->itemDAO()->addItems($case);
		$db->modificationDAO()->modificationCommit();

		$items = $db->itemDAO()->getItems(null, [new SearchTriplet('brand', '=', 'SATAn Storage Corporation Inc.')],
			null, null, null, null);
		$this->assertEquals(2, count($items));
		$this->assertInstanceOf(Item::class, $items[0]);
		$this->assertInstanceOf(Item::class, $items[1]);
		/** @noinspection PhpUndefinedMethodInspection */
		$this->assertEquals('PC-42', $items[0]->getAncestor(1));
		/** @noinspection PhpUndefinedMethodInspection */
		$this->assertEquals(null, $items[0]->getAncestor(2));
		/** @noinspection PhpUndefinedMethodInspection */
		$this->assertEquals('PC-42', $items[1]->getAncestor(1));
		/** @noinspection PhpUndefinedMethodInspection */
		$this->assertEquals(null, $items[1]->getAncestor(2));
	}

	/**
	 * @uses    \WEEEOpen\Tarallo\Server\Database\Database
	 * @uses    \WEEEOpen\Tarallo\Server\User
	 * @uses    \WEEEOpen\Tarallo\Server\Item
	 * @covers  \WEEEOpen\Tarallo\Server\Item::jsonSerialize()
	 * @uses    \WEEEOpen\Tarallo\Server\ItemIncomplete
	 * @uses    \WEEEOpen\Tarallo\Server\Database\ItemDAO
	 * @uses    \WEEEOpen\Tarallo\Server\Database\FeatureDAO
	 * @uses    \WEEEOpen\Tarallo\Server\Database\TreeDAO
	 * @uses    \WEEEOpen\Tarallo\Server\Database\DAO
	 * @uses    \WEEEOpen\Tarallo\Server\Database\ModificationDAO
	 */
	public function testItemSearchSerialization() {
		$db = $this->getDb();
		$pc['PC-55'] = (new Item('PC-55'))
			->addFeature(new Feature('brand', 'TI'))
			->addFeature(new Feature('model', 'GreyPC-\'98'))
			->addFeature(new Feature('type', 'case'))
			->addFeature(new Feature('motherboard-form-factor', 'atx'));
		$pc['PC-20'] = (new Item('PC-20'))
			->addFeature(new Feature('brand', 'Dill'))
			->addFeature(new Feature('model', 'DI-360'))
			->addFeature(new Feature('type', 'case'))
			->addFeature(new Feature('motherboard-form-factor', 'proprietary'))
			->addFeature(new Feature('color', 'black'))
			->addFeature(new Feature('working', 'yes'));
		$pc['PC-21'] = (new Item('PC-21'))
			->addFeature(new Feature('brand', 'Dill'))
			->addFeature(new Feature('model', 'DI-360'))
			->addFeature(new Feature('type', 'case'))
			->addFeature(new Feature('motherboard-form-factor', 'proprietary'))
			->addFeature(new Feature('color', 'grey'))
			->addFeature(new Feature('working', 'yes'));
		$pc['PC-22'] = (new Item('PC-22'))
			->addFeature(new Feature('brand', 'Dill'))
			->addFeature(new Feature('model', 'DI-360'))
			->addFeature(new Feature('type', 'case'))
			->addFeature(new Feature('motherboard-form-factor', 'proprietary'))
			->addFeature(new Feature('color', 'black'))
			->addFeature(new Feature('working', 'yes'));
		$pc['SCHIFOMACCHINA'] = (new Item('SCHIFOMACCHINA'))// based on a real PC we have in our laboratory.
		->addFeature(new Feature('brand', 'eMac'))
			->addFeature(new Feature('model', 'EZ1600'))
			->addFeature(new Feature('type', 'case'))
			->addFeature(new Feature('motherboard-form-factor', 'miniitx'))
			->addFeature(new Feature('color', 'white'));

		$db->modificationDAO()->modifcationBegin(new User('asd', 'asd'));
		$db->itemDAO()->addItems($pc);
		$db->modificationDAO()->modificationCommit();
		$items = $db->itemDAO()->getItems(null, [new SearchTriplet('type', '=', 'case')], null, null,
			['motherboard-form-factor' => '-', 'color' => '+'], null);
		$expected = [ // this ugly code courtesy of var_export
			0 =>
				[
					'code'     => 'PC-20',
					'features' =>
						[
							'brand'                   => 'Dill',
							'color'                   => 'black',
							'model'                   => 'DI-360',
							'motherboard-form-factor' => 'proprietary',
							'type'                    => 'case',
							'working'                 => 'yes',
						],
				],
			1 =>
				[
					'code'     => 'PC-22',
					'features' =>
						[
							'brand'                   => 'Dill',
							'color'                   => 'black',
							'model'                   => 'DI-360',
							'motherboard-form-factor' => 'proprietary',
							'type'                    => 'case',
							'working'                 => 'yes',
						],
				],
			2 =>
				[
					'code'     => 'PC-21',
					'features' =>
						[
							'brand'                   => 'Dill',
							'color'                   => 'grey',
							'model'                   => 'DI-360',
							'motherboard-form-factor' => 'proprietary',
							'type'                    => 'case',
							'working'                 => 'yes',
						],
				],
			3 =>
				[
					'code'     => 'SCHIFOMACCHINA',
					'features' =>
						[
							'brand'                   => 'eMac',
							'color'                   => 'white',
							'model'                   => 'EZ1600',
							'motherboard-form-factor' => 'miniitx',
							'type'                    => 'case',
						],
				],
			4 =>
				[
					'code'     => 'PC-55',
					'features' =>
						[
							'brand'                   => 'TI',
							'model'                   => 'GreyPC-\'98',
							'motherboard-form-factor' => 'atx',
							'type'                    => 'case',
						],
				],
		];
		$array = [];
		foreach($items as $item) {
			/** @var $item Item */
			$array[] = $item->jsonSerialize();
		}
		$this->assertEquals($expected, $array, 'Selected items should encode to some meaningful JSON representation');
	}

	/**
	 * @covers  \WEEEOpen\Tarallo\Server\Database\Database
	 * @uses    \WEEEOpen\Tarallo\Server\User
	 * @uses    \WEEEOpen\Tarallo\Server\Item
	 * @uses    \WEEEOpen\Tarallo\Server\ItemIncomplete
	 * @covers  \WEEEOpen\Tarallo\Server\Database\ItemDAO
	 * @covers  \WEEEOpen\Tarallo\Server\Database\FeatureDAO
	 * @covers  \WEEEOpen\Tarallo\Server\Database\TreeDAO
	 * @uses    \WEEEOpen\Tarallo\Server\Database\DAO
	 * @uses    \WEEEOpen\Tarallo\Server\Database\ModificationDAO
	 */
	public function testItemSearchFiltering() {
		$cpu['INTEL-1'] = (new Item('INTEL-1'))
			->addFeature(new Feature('type', 'cpu'))
			->addFeature(new Feature('brand', 'Intel-lighenzia'))
			->addFeature(new Feature('model', 'Core 2.0 Trio'))
			->addFeature(new Feature('frequency-hertz', 1400000000));
		$cpu['INTEL-2'] = (new Item('INTEL-2'))
			->addFeature(new Feature('type', 'cpu'))
			->addFeature(new Feature('brand', 'Intel-lighenzia'))
			->addFeature(new Feature('model', 'Core 3.0 Quadrio'))
			->addFeature(new Feature('frequency-hertz', 2000000000));
		$cpu['INTEL-3'] = (new Item('INTEL-3'))
			->addFeature(new Feature('type', 'cpu'))
			->addFeature(new Feature('brand', 'Intel-lighenzia'))
			->addFeature(new Feature('model', 'Atomic 5L0W-NE55'))
			->addFeature(new Feature('frequency-hertz', 42));
		$cpu['INTEL-4'] = (new Item('INTEL-4'))
			->addFeature(new Feature('type', 'cpu'))
			->addFeature(new Feature('brand', 'Intel-lighenzia'))
			->addFeature(new Feature('model', 'Centrino DellaNonna'))
			->addFeature(new Feature('frequency-hertz', 1000000000));
		$cpu['AMD-42'] = (new Item('AMD-42'))
			->addFeature(new Feature('type', 'cpu'))
			->addFeature(new Feature('brand', 'Advanced Magnificent Processors'))
			->addFeature(new Feature('model', 'A4-200g'))
			->addFeature(new Feature('notes', 'A4, 200 g/cm², come la carta.'))
			->addFeature(new Feature('frequency-hertz', 1900000000));
		$cpu['AMD-737'] = (new Item('AMD-737'))
			->addFeature(new Feature('frequency-hertz', 3700000000))
			->addFeature(new Feature('type', 'cpu'))
			->addFeature(new Feature('brand', 'Advanced Magnificent Processors'))
			->addFeature(new Feature('model', '737-800'));
		$db = $this->getDb();

		$db->modificationDAO()->modifcationBegin(new User('asd', 'asd'));
		$db->itemDAO()->addItems($cpu);
		$db->modificationDAO()->modificationCommit();

		$items = $db->itemDAO()->getItems(null, [new SearchTriplet('type', '=', 'cpu')], null, null, null, null);
		$this->assertContainsOnly(Item::class, $items);
		$this->assertEquals(6, count($items), 'There should be 6 items');
		/** @var Item[] $items */
		foreach(['AMD-42', 'AMD-737', 'INTEL-1', 'INTEL-2', 'INTEL-3', 'INTEL-4'] as $pos => $code) {
			$this->assertEquals($code, $items[$pos]->getCode(), "Item in position $pos should be $code");
			$this->assertEquals($cpu[$code], $items[$pos], 'Item ' . $code . ' should be unchanged)');
		}

		$items = $db->itemDAO()->getItems(null, [new SearchTriplet('type', '=', 'cpu')], null, null,
			['frequency-hertz' => '-'], null);
		$this->assertContainsOnly(Item::class, $items);
		$this->assertEquals(6, count($items), 'There should be 6 items');
		foreach(['AMD-737', 'INTEL-2', 'AMD-42', 'INTEL-1', 'INTEL-4', 'INTEL-3'] as $pos => $code) {
			$this->assertEquals($code, $items[$pos]->getCode(), "Item in position $pos should be $code");
			$this->assertEquals($cpu[$code], $items[$pos], "Item $code should be unchanged");
		}

		$items = $db->itemDAO()->getItems(null, [new SearchTriplet('brand', '=', 'Intel')], null, null, null, null);
		$this->assertEquals(0, count($items), 'No items returned without wildcard');

		$itemsGeq = $db->itemDAO()->getItems(null, [new SearchTriplet('brand', '>', 'Intel%')], null, null, null, null);
		$this->assertContainsOnly(Item::class, $itemsGeq);
		$this->assertEquals(4, count($itemsGeq),
			'There should be 4 items when using > (query should contain LIKE regardless)');

		$items = $db->itemDAO()->getItems(null, [new SearchTriplet('brand', '=', 'Intel%')], null, null, null, null);
		$this->assertContainsOnly(Item::class, $items);
		$this->assertEquals(4, count($items),
			'There should be 4 items when using = (in this case query should use LIKE)');
		$this->assertEquals($items, $itemsGeq,
			'Same result set in same order when using >, < or = on a field that uses LIKE');
		foreach(['INTEL-1', 'INTEL-2', 'INTEL-3', 'INTEL-4'] as $pos => $code) {
			$this->assertEquals($code, $items[$pos]->getCode(), "Item in position $pos should be $code");
			$this->assertEquals($cpu[$code], $items[$pos], "Item $code should be unchanged");
		}
	}

	/**
	 * @covers  \WEEEOpen\Tarallo\Server\Database\Database
	 * @uses    \WEEEOpen\Tarallo\Server\User
	 * @uses    \WEEEOpen\Tarallo\Server\Item
	 * @uses    \WEEEOpen\Tarallo\Server\ItemIncomplete
	 * @covers  \WEEEOpen\Tarallo\Server\Database\ItemDAO
	 * @covers  \WEEEOpen\Tarallo\Server\Database\FeatureDAO
	 * @covers  \WEEEOpen\Tarallo\Server\Database\TreeDAO
	 * @uses    \WEEEOpen\Tarallo\Server\Database\DAO
	 * @uses    \WEEEOpen\Tarallo\Server\Database\ModificationDAO
	 */
	public function testTreeMove() {
		// These items should be added in database.yml, but that just increases the amount of data to import for each test
		// and I find more readable this than a YAML file full of numbers.
		$chernobyl = (new Item('CHERNOBYL'))
			->addFeature(new Feature('type', 'location'));
		$tavolone = (new Item('TAVOLONE'))
			->addFeature(new Feature('type', 'location'));
		$chernobyl
			->addContent($tavolone)
			->addContent((new Item('Armadio L'))
				->addFeature(new Feature('type', 'location')))
			->addContent((new Item('Armadio R'))
				->addFeature(new Feature('type', 'location')));
		$tavolone
			->addContent(
				(new Item('SCHIFOMACCHINA'))
					->addFeature(new Feature('brand', 'eMac'))
					->addFeature(new Feature('model', 'EZ1600'))
					->addFeature(new Feature('type', 'case'))
					->addFeature(new Feature('motherboard-form-factor', 'miniitx'))
					->addFeature(new Feature('color', 'white'))
			);
		$ti = (new Item('PC-TI'))
			->addFeature(new Feature('brand', 'TI'))
			->addFeature(new Feature('type', 'case'))
			->addFeature(new Feature('motherboard-form-factor', 'miniitx'))
			->addFeature(new Feature('color', 'white'))
			->addContent(
				(new Item('RAM-22'))
					->addFeature(new Feature('type', 'ram'))
					->addFeature(new Feature('capacity-byte', 32))
			)
			->addContent(
				(new Item('RAM-23'))
					->addFeature(new Feature('type', 'ram'))
					->addFeature(new Feature('capacity-byte', 32))
			)
			->addContent(
				(new Item('PC-TI-MOBO'))
					->addFeature(new Feature('type', 'motherboard'))
					->addFeature(new Feature('color', 'green'))
			)
			->addContent(
				(new Item('PC-TI-CPU'))
					->addFeature(new Feature('type', 'cpu'))
					->addFeature(new Feature('brand', 'Intel-lighenzia'))
					->addFeature(new Feature('model', 'Atomic 5L0W-NE55'))
					->addFeature(new Feature('frequency-hertz', 42))
			);
		$tavolone->addContent(
			(new Item('ROSETTA'))
				->addFeature(new Feature('brand', 'pH'))
				->addFeature(new Feature('model', 'ReliaPro MLG555')))
			->addFeature(new Feature('type', 'case'))
			->addFeature(new Feature('motherboard-form-factor', 'atx'))
			->addFeature(new Feature('color', 'grey'))
			->addContent(
				(new Item('RAM-3342'))
					->addFeature(new Feature('type', 'ram'))
					->addFeature(new Feature('capacity-byte', 1073741824))
			)
			->addContent(
				(new Item('RAM-2452'))
					->addFeature(new Feature('type', 'ram'))
					->addFeature(new Feature('capacity-byte', 1073741824))
			);
		$chernobyl->addContent($zb = (new Item('Zona blu'))->addFeature(new Feature('type', 'location')));
		$tavolone->addContent($ti);

		$db = $this->getDb();

		$db->modificationDAO()->modifcationBegin(new User('asd', 'asd'));
		$db->itemDAO()->addItems($chernobyl);
		$db->modificationDAO()->modificationCommit();

		$items = $db->itemDAO()->getItems(['CHERNOBYL'], null, null, null, null, null);
		$this->assertContainsOnly(Item::class, $items);
		$this->assertEquals(1, count($items), 'Only one root Item');

		// Move TI from TAVOLONE to Zona blu.
		$db->modificationDAO()->modifcationBegin(new User('asd', 'asd'));
		$db->treeDAO()->moveItem($ti, $zb);
		$db->modificationDAO()->modificationCommit();

		$items = $db->itemDAO()->getItems(['CHERNOBYL'], null, null, null, null, null);
		$this->assertContainsOnly(Item::class, $items);
		$this->assertEquals(1, count($items), 'Only one root Item');
		/** @var Item $chernobylPost */
		$chernobylPost = $items[0];
		$zonaBluPost = null;
		$tavolonePost = null;
		$this->assertContainsOnly(Item::class, $itemz = $chernobylPost->getContents());
		/** @var Item[] $itemz */
		foreach($itemz as $item) {
			if($item->getCode() === 'Zona blu') {
				$zonaBluPost = $item;
			} else if($item->getCode() === 'TAVOLONE') {
				$tavolonePost = $item;
			}
		}
		$this->assertInstanceOf(Item::class, $zonaBluPost, 'Zona blu should still exist');
		$this->assertInstanceOf(Item::class, $tavolonePost, 'TAVOLONE should still exist');
		/** @var Item $zonaBluPost */
		/** @var Item $tavolonePost */
		$tiShouldBeHere = null;
		$tiShouldNotBeHere = null;
		foreach($zonaBluPost->getContents() as $item) {
			if($item->getCode() === 'PC-TI') {
				$tiShouldBeHere = $item;
			}
		}
		foreach($tavolonePost->getContents() as $item) {
			if($item->getCode() === 'PC-TI') {
				$tiShouldNotBeHere = $item;
			}
		}
		$this->assertInstanceOf(Item::class, $tiShouldBeHere, 'PC-TI should have moved to Zona blu');
		$this->assertEquals(null, $tiShouldNotBeHere, 'TAVOLONE should not contain PC-TI');
		$this->assertTrue($this->itemCompare($ti, $tiShouldBeHere), 'PC-TI should have the same content');
	}

	/**
	 * @return Database
	 */
	private function getDb() {
		if($this->db === null) {
			$db = new Database('root', 'root', 'mysql:dbname=tarallo_test;host=127.0.0.1');
			//$dbr  = new \ReflectionObject($db);
			//$prop = $dbr->getProperty('pdo');
			//$prop->setAccessible(true);
			//$prop->setValue($db, $this->getPdo());
			$this->db = $db;
		}

		return $this->db;
	}

	private static function itemCompare(Item $a, Item $b) {
		if($a->getCode() !== $b->getCode()) {
			return false;
		}
		// TODO: compare recursively
		//if($a->getProduct() !== $b->getProduct()) {
		//	return false;
		//}
		if(count($a->getFeatures()) !== count($b->getFeatures())) {
			return false;
		}
		if(!empty(array_diff_assoc($a->getFeatures(), $b->getFeatures()))) {
			return false;
		}
		if(count($a->getContents()) !== count($b->getContents())) {
			return false;
		}
		/** @var Item[] $bContent */
		$bContent = $b->getContents();
		foreach($a->getContents() as $item) {
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
