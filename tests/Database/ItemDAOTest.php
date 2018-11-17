<?php

namespace WEEEOpen\Tarallo\Server\Test\Database;

use WEEEOpen\Tarallo\Server\Database\DuplicateItemCodeException;
use WEEEOpen\Tarallo\Server\Feature;
use WEEEOpen\Tarallo\Server\Item;
use WEEEOpen\Tarallo\Server\ItemIncomplete;
use WEEEOpen\Tarallo\Server\NotFoundException;
use WEEEOpen\Tarallo\Server\ValidationException;

class ItemDAOTest extends DatabaseTest {
	/**
	 * Database tests are really slow and this code is a bit complex to say the least, testing everything
	 * in a sensible manner will be difficult. But some tests are better than no tests at all, right?
	 *
	 * @covers \WEEEOpen\Tarallo\Server\Database\Database
	 * @covers \WEEEOpen\Tarallo\Server\Database\FeatureDAO
	 * @covers \WEEEOpen\Tarallo\Server\Database\TreeDAO
	 */
	public function testAddingAndRetrievingSomeItems() {
		$db = $this->getDb();
		/** @var $case Item */ // PHPStorm suddenly doesn't recognize chained methods. Only the last one of every chain, specifically.
		$case = (new Item('PC42'))
			->addFeature(new Feature('brand', 'TI'))
			->addFeature(new Feature('model', 'GreyPC-\'98'))
			->addFeature(new Feature('type', 'case'))
			->addFeature(new Feature('motherboard-form-factor', 'atx'));
		$discone1 = (new Item('SATAna1'))
			->addFeature(new Feature('capacity-byte', 666))
			->addFeature(new Feature('brand', 'SATAn Storage Corporation Inc.'))
			->addFeature(new Feature('model', 'Discone da 666 byte'))
			->addFeature(new Feature('type', 'hdd'));
		$discone2 = (new Item('SATAna2'))
			->addFeature(new Feature('capacity-byte', 666))
			->addFeature(new Feature('brand', 'SATAn Storage Corporation Inc.'))
			->addFeature(new Feature('model', 'Discone da 666 byte'))
			->addFeature(new Feature('type', 'hdd'));
		$case->addContent($discone1);
		$case->addContent($discone2);
		$db->itemDAO()->addItem($case);

		$newCase = $db->itemDAO()->getItem(new ItemIncomplete('PC42'));
		$this->assertInstanceOf(Item::class, $newCase);
		/** @var Item $newCase */
		$this->assertEquals(2, count($newCase->getContents()), 'Two child Item');
		$this->assertContainsOnly(Item::class, $newCase->getContents(), null, 'Only Items are contained in an Item');
		foreach($newCase->getContents() as $child) {
			/** @var Item $child */
			$this->assertTrue($child->getCode() === 'SATAna1' || $child->getCode() === 'SATAna2',
				'Sub-Item is one of the two expected items, ' . (string) $child);
			// this works because the two items are identical except for the code...
			$newFeatures = $child->getFeatures();
			$oldFeatures = $case->getContents()[0]->getFeatures();
			$this->assertEquals(count($oldFeatures), count($newFeatures), 'Feature count should be unchanged');
			foreach($oldFeatures as $name => $feature) {
				$value = $feature->value;
				$this->assertTrue(isset($newFeatures[$name]), "Feature $name should still exist");
				$this->assertEquals($value, $newFeatures[$name]->value,
					"Sub-Item $child should have $name=$value as before");
			}
			$this->assertTrue(empty($child->getContents()), "No children of child Item $child should exist");
		}
	}


	/**
	 * @covers \WEEEOpen\Tarallo\Server\Database\ItemDAO
	 */
	public function testDeleteItem() {
		$db = $this->getDb();
		$case = new Item('PC42');
		$db->itemDAO()->addItem($case);

		$deleteMe = new ItemIncomplete('PC42');

		$this->assertTrue($db->itemDAO()->itemExists($deleteMe), 'Item should exist before deletion');
		$this->assertTrue($db->itemDAO()->itemVisible($deleteMe), 'Item shouldn be visible before deletion');
		$this->assertNull($db->itemDAO()->itemDeletedAt($deleteMe), 'Item shouldn\'t have been deleted');
		$beforeTime = new \DateTime();

		$db->itemDAO()->deleteItem($deleteMe);

		$this->assertTrue($db->itemDAO()->itemExists($deleteMe), 'Item should still exist');
		$this->assertFalse($db->itemDAO()->itemVisible($deleteMe), 'Item shouldn\'t be visible');
		$afterTime = new \DateTime($db->itemDAO()->itemDeletedAt($deleteMe), new \DateTimeZone('UTC'));
		$this->assertGreaterThanOrEqual(0, $afterTime->getTimestamp() - $beforeTime->getTimestamp(), 'Item should have a valid deletion date');
		$this->assertInstanceOf(Item::class, $db->itemDAO()->getItem($deleteMe));
	}

	public function testDeleteItemTwice() {
		$db = $this->getDb();
		$case = new Item('PC42');
		$db->itemDAO()->addItem($case);

		$deleteMe = new ItemIncomplete('PC42');
		$db->itemDAO()->deleteItem($deleteMe);
		$this->assertTrue($db->itemDAO()->itemExists($deleteMe), 'Item should still exist');
		$this->assertFalse($db->itemDAO()->itemVisible($deleteMe), 'Item shouldn\'t be visible');

		$this->expectException(NotFoundException::class);
		$db->itemDAO()->deleteItem($deleteMe);
	}

	public function testDeleteItemWithContents() {
		$db = $this->getDb();
		$case = new Item('PC42');
		$mobo = new Item('MOBO42');
		$case->addContent($mobo);
		$db->itemDAO()->addItem($case);

		$deleteMe = new ItemIncomplete('PC42');

		$this->expectException(ValidationException::class);
		$db->itemDAO()->deleteItem($deleteMe);
	}

	public function testDuplicateCode() {
		$db = $this->getDb();
		$case = new Item('PC42');
		$db->itemDAO()->addItem($case);

		$case = new Item('PC42');
		$this->expectException(DuplicateItemCodeException::class);
		$db->itemDAO()->addItem($case);
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\Database\ItemDAO
	 */
	public function testNonExistingItem() {
		$db = $this->getDb();

		$notHere = new ItemIncomplete('PC9001');
		$this->assertFalse($db->itemDAO()->itemExists($notHere), 'Item shouldn\'t exist');
		$this->assertFalse($db->itemDAO()->itemVisible($notHere), 'Item shouldn\'t be recoverable');
		$this->assertNull($db->itemDAO()->itemDeletedAt($notHere), 'Item shouldn\'t be marked as deleted');
		$this->expectException(NotFoundException::class);
		$db->itemDAO()->getItem($notHere);
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\Database\ItemDAO
	 */
	public function testAutogeneratedCodes() {
		$db = $this->getDb();
		$keyboard = (new Item(null))->addFeature(new Feature('type', 'keyboard'));
		$mouse = (new Item(null))->addFeature(new Feature('type', 'mouse'));

		$this->assertFalse($keyboard->hasCode());
		$this->assertFalse($mouse->hasCode());

		$db->itemDAO()->addItem($keyboard);
		$db->itemDAO()->addItem($mouse);

		$this->assertTrue($keyboard->hasCode());
		$this->assertTrue($mouse->hasCode());
		$this->assertEquals('T76', $keyboard->getCode());
		$this->assertEquals('M11', $mouse->getCode());
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\Database\ItemDAO
	 */
	public function testGettingPrefixesSkippingDuplicates() {
		$db = $this->getDb();

		$keyboardz = [];
		for($i = 74; $i < 77; $i++) {
			$keyboardz[] = (new Item('T' . $i))->addFeature(new Feature('type', 'keyboard'));
		}
		$keyboardz[] = $keyboardWithNoCode = (new Item(null))->addFeature(new Feature('type', 'keyboard'));

		$this->assertFalse($keyboardWithNoCode->hasCode());
		foreach($keyboardz as $k) {
			$db->itemDAO()->addItem($k);
		}

		$this->assertTrue($keyboardWithNoCode->hasCode());
		$this->assertEquals('T77', $keyboardWithNoCode->getCode());
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\Database\ItemDAO
	 */
	public function testAddItemToken() {
		$db = $this->getDb();
		$case = (new Item('PC42'))->addFeature(new Feature('motherboard-form-factor', 'atx'));
		$case->token = 'this-is-a-token';
		$db->itemDAO()->addItem($case);

		$newCase = $db->itemDAO()->getItem(new ItemIncomplete('PC42'));
		$this->assertInstanceOf(Item::class, $newCase);
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\Database\ItemDAO
	 */
	public function testGetItemToken() {
		$db = $this->getDb();
		$case = (new Item('PC42'))->addFeature(new Feature('motherboard-form-factor', 'atx'));
		$case->token = 'this-is-a-token';
		$db->itemDAO()->addItem($case);

		$getMe = new ItemIncomplete('PC42');
		$newCase = $db->itemDAO()->getItem($getMe, 'this-is-a-token');
		$this->assertInstanceOf(Item::class, $newCase);
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\Database\ItemDAO
	 */
	public function testGetItemWrongToken() {
		$db = $this->getDb();
		$case = (new Item('PC42'))->addFeature(new Feature('motherboard-form-factor', 'atx'));
		$case->token = 'this-is-a-token';
		$db->itemDAO()->addItem($case);

		$getMe = new ItemIncomplete('PC42');
		$this->expectException(NotFoundException::class);
		$db->itemDAO()->getItem($getMe, 'WRONGWRONGWRONG');
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\Database\ItemDAO
	 * @covers \WEEEOpen\Tarallo\Server\Item
	 */
	public function testItemSerializable() {
		$db = $this->getDb();

		$where = (new Item('Chernobyl'));
		$where2 = (new Item('Tavolo'));
		$where3 = (new Item('ZonaBlu'));
		$where->addContent($where2)->addContent($where3);

		$case = (new Item('PC42'))
			->addFeature(new Feature('type', 'case'))
			->addFeature(new Feature('motherboard-form-factor', 'atx'));
		$discone1 = (new Item('SATAna1'))
			->addFeature(new Feature('capacity-byte', 666))
			->addFeature(new Feature('brand', 'SATAn Storage Corporation Inc.'))
			->addFeature(new Feature('model', 'Discone da 666 byte'))
			->addFeature(new Feature('type', 'hdd'));
		$discone2 = (new Item('SATAna2'))
			->addFeature(new Feature('capacity-byte', 666))
			->addFeature(new Feature('brand', 'SATAn Storage Corporation Inc.'))
			->addFeature(new Feature('model', 'Discone da 666 byte'))
			->addFeature(new Feature('type', 'hdd'));
		$case->addContent($discone1)->addContent($discone2);
		$where->addContent($case);

		$db->itemDAO()->addItem($where);
		$getMe = new ItemIncomplete('PC42');
		$result = $db->itemDAO()->getItem($getMe);
		json_encode($result);
		$this->assertEquals(JSON_ERROR_NONE, json_last_error());
	}

}
