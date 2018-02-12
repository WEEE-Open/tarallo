<?php

use PHPUnit\Framework\TestCase;
use WEEEOpen\Tarallo\Server\Feature;
use WEEEOpen\Tarallo\Server\InvalidParameterException;
use WEEEOpen\Tarallo\Server\Item;

class ItemTest extends TestCase {

	/**
	 * @covers \WEEEOpen\Tarallo\Server\Item
	 * @uses \WEEEOpen\Tarallo\Server\ItemIncomplete
	 */
	public function testItemValidCodeString() {
		$pc77 = new Item('PC-77');
		$this->assertEquals('PC-77', (string) $pc77);
		$this->assertEquals('PC-77', $pc77->getCode());
		$this->assertEmpty($pc77->getFeatures());
		$this->assertEmpty($pc77->getContents());
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\Item
	 * @covers \WEEEOpen\Tarallo\Server\ItemIncomplete
	 */
	public function testItemInvalidDefaultEmptyString() {
		$this->expectException(\TypeError::class);
		new Item('HDD-238947283', '');
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\Item
	 * @uses \WEEEOpen\Tarallo\Server\ItemIncomplete
	 */
	public function testItemValidAncestors() {
		$hdd = new Item('HDD-123');

		$hdd->addAncestor(1, 'PC-999');
		$this->assertTrue($hdd->getAncestor(1) instanceof \WEEEOpen\Tarallo\Server\ItemIncomplete,
			'Ancestors are ItemIncomplete objects');

		$this->assertEquals('PC-999', $hdd->getAncestor(1)->getCode(), 'Ancestor 1 should be there');
		$this->assertEquals(null, $hdd->getAncestor(2), 'Ancestor 2 shouldn\'t exist yet');
		$hdd->addAncestor(2, 'CHERNOBYL');
		$this->assertEquals('PC-999', $hdd->getAncestor(1)->getCode(), 'Ancestor 1 should still be there');
		$this->assertEquals('CHERNOBYL', $hdd->getAncestor(2)->getCode(), 'Ancestor 2 should be there');

		$hdd->addAncestor(2, 'ZONA-BLU');
		$this->assertEquals('PC-999', $hdd->getAncestor(1)->getCode(), 'Unchanged ancestor should still be there');
		$this->assertEquals('ZONA-BLU', $hdd->getAncestor(2)->getCode(), 'Replaced ancestor should be there');
		$this->assertEquals(null, $hdd->getAncestor(3), 'Ancestor 3 shouldn\'t exist');
	}

	public function testItemValidAncestorSkip() {
		$hdd = new Item('HDD-123');
		$hdd->addAncestor(1, 'PC-999');
		$hdd->addAncestor(2, 'ZONA-BLU');
		$hdd->addAncestor(4, 'CHERNOBYL');
		$this->assertEquals('PC-999', $hdd->getAncestor(1)->getCode(), 'Ancestor 1 should be there');
		$this->assertEquals('ZONA-BLU', $hdd->getAncestor(2)->getCode(), 'Ancestor 2 should be there');
		$this->assertEquals(null, $hdd->getAncestor(3), 'Ancestor 3 should be null');
		$this->assertEquals('CHERNOBYL', $hdd->getAncestor(4)->getCode(), 'Ancestor 4 should be there');
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\Item
	 * @uses \WEEEOpen\Tarallo\Server\ItemIncomplete
	 */
	public function testItemInvalidAncestorAdd() {
		$hdd = new Item('HDD-123');
		$this->expectException(InvalidArgumentException::class);
		$hdd->addAncestor(0, 'PC-999');
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\Item
	 * @uses \WEEEOpen\Tarallo\Server\ItemIncomplete
	 */
	public function testItemInvalidAncestorAdd2() {
		$hdd = new Item('HDD-123');
		$this->expectException(InvalidArgumentException::class);
		$hdd->addAncestor(-1, 'PC-999');
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\Item
	 * @uses \WEEEOpen\Tarallo\Server\ItemIncomplete
	 */
	public function testItemInvalidAncestorGet() {
		$hdd = new Item('HDD-123');
		$this->expectException(InvalidArgumentException::class);
		$hdd->getAncestor(0);
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\Item
	 * @uses \WEEEOpen\Tarallo\Server\ItemIncomplete
	 */
	public function testItemInvalidAncestorGet2() {
		$hdd = new Item('HDD-123');
		$this->expectException(\InvalidArgumentException::class);
		$hdd->getAncestor(-1);
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\Item
	 * @uses \WEEEOpen\Tarallo\Server\ItemIncomplete
	 */
	public function testItemInvalidCodeInt() {
		$this->expectException(\InvalidArgumentException::class);
		new Item(42);
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\Item
	 * @uses \WEEEOpen\Tarallo\Server\ItemIncomplete
	 */
	public function testItemNullCode() {
		$it = new Item(null);
		$this->expectException(\LogicException::class);
		$it->getCode();
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\Item
	 * @uses \WEEEOpen\Tarallo\Server\ItemIncomplete
	 */
	public function testItemSetCode() {
		$it = new Item(null);
		$it->setCode('PC-22');
		$this->assertEquals('PC-22', $it->getCode());
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\Item
	 * @uses \WEEEOpen\Tarallo\Server\ItemIncomplete
	 */
	public function testItemEmptyCode() {
		$this->expectException(\InvalidArgumentException::class);
		new Item('');
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\Item
	 * @uses \WEEEOpen\Tarallo\Server\ItemIncomplete
	 */
	public function testItemFeature() {
		$item = new Item('TEST');
		$item->addFeature(new Feature('capacity-byte', 9001));
		$this->assertArrayHasKey('capacity-byte', $item->getFeatures(), 'Feature must be present');
		$this->assertEquals('capacity-byte', $item->getFeatures()['capacity-byte']->name, 'Feature name must be coherent');
		$this->assertEquals(9001, $item->getFeatures()['capacity-byte']->value, 'Feature value must match');
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\Item
	 * @uses \WEEEOpen\Tarallo\Server\ItemIncomplete
	 */
	public function testItemDeleteFeature() {
		$item = new Item('TEST');
		$item->addFeature(new Feature('capacity-byte', 9001));
		$item->addFeature(new Feature('color', 'yellow'));
		$item->deleteFeature('color');

		$this->assertArrayNotHasKey('color', $item->getFeatures());
		$this->assertArrayHasKey('capacity-byte', $item->getFeatures());
		$this->assertEquals(9001, $item->getFeatures()['capacity-byte']->value);
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\Item
	 * @uses \WEEEOpen\Tarallo\Server\ItemIncomplete
	 */
	public function testItemToString() {
		$item = new Item('TEST');
		$this->assertEquals('TEST', (string) $item);
		$item->addFeature(new Feature('type', 'hdd'));
		$this->assertEquals('TEST (hdd)', (string) $item);
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\Item
	 * @uses \WEEEOpen\Tarallo\Server\ItemIncomplete
	 */
	public function testItemMultipleFeatures() {
		$item = new Item('TEST');
		$item->addFeature(new Feature('capacity-byte', 9001))->addFeature(new Feature('color', 'white'));
		$item->addFeature(new Feature('brand', 'bar'));

		$this->assertArrayHasKey('capacity-byte', $item->getFeatures());
		$this->assertEquals('capacity-byte', $item->getFeatures()['capacity-byte']->name);
		$this->assertEquals(9001, $item->getFeatures()['capacity-byte']->value);

		$this->assertArrayHasKey('color', $item->getFeatures());
		$this->assertEquals('color', $item->getFeatures()['color']->name);
		$this->assertEquals('white', $item->getFeatures()['color']->value);

		$this->assertArrayHasKey('brand', $item->getFeatures());
		$this->assertEquals('brand', $item->getFeatures()['brand']->name);
		$this->assertEquals('bar', $item->getFeatures()['brand']->value);
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\Item
	 * @uses \WEEEOpen\Tarallo\Server\ItemIncomplete
	 */
	public function testInvalidFeatureNameArray() {
		$this->expectException(\InvalidArgumentException::class);
		(new Item('TEST'))->addFeature(new Feature(['brand'], 'value'));
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\Item
	 * @uses \WEEEOpen\Tarallo\Server\ItemIncomplete
	 */
	public function testInvalidFeatureNameInteger() {
		$this->expectException(\InvalidArgumentException::class);
		(new Item('TEST'))->addFeature(new Feature(42, 'value'));
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\Item
	 * @uses \WEEEOpen\Tarallo\Server\ItemIncomplete
	 */
	public function testInvalidFeatureValueNegative() {
		$this->expectException(\InvalidArgumentException::class);
		(new Item('TEST'))->addFeature(new Feature('capacity-byte', -50));
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\Item
	 * @uses \WEEEOpen\Tarallo\Server\ItemIncomplete
	 */
	public function testInvalidFeatureValueArray() {
		$this->expectException(\InvalidArgumentException::class);
		(new Item('TEST'))->addFeature(new Feature('capacity-byte', [400000]));
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\Item
	 * @uses \WEEEOpen\Tarallo\Server\ItemIncomplete
	 */
	public function testInvalidFeatureDuplicate() {
		$item = (new Item('TEST'))->addFeature(new Feature('capacity-byte', 500));
		$this->expectException(\InvalidArgumentException::class);
		$item->addFeature(new Feature('capacity-byte', 123));
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\Item
	 * @uses \WEEEOpen\Tarallo\Server\ItemIncomplete
	 */
	public function testItemMultipleFeaturesArray() {
		$item = new Item('TEST');
		$item->addMultipleFeatures([
			new Feature('capacity-byte', 9001),
			new Feature('color', 'white'),
			new Feature('brand', 'bar')
		])->addFeature(new Feature('model', 'T'));

		$this->assertArrayHasKey('capacity-byte', $item->getFeatures());
		$this->assertEquals(9001, $item->getFeatures()['capacity-byte']->value);

		$this->assertArrayHasKey('color', $item->getFeatures());
		$this->assertEquals('white', $item->getFeatures()['color']->value);

		$this->assertArrayHasKey('brand', $item->getFeatures());
		$this->assertEquals('bar', $item->getFeatures()['brand']->value);

		$this->assertArrayHasKey('model', $item->getFeatures());
		$this->assertEquals('T', $item->getFeatures()['model']->value);
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\Item
	 * @uses \WEEEOpen\Tarallo\Server\ItemIncomplete
	 */
	public function testValidAddChild() {
		$item = new Item('TEST');
		$child = (new Item('CHILD'))->addMultipleFeatures([
			new Feature('capacity-byte', 9001),
			new Feature('color', 'white'),
			new Feature('brand', 'bar')
		]);

		$item->addContent($child);
		$this->assertContains($child, $item->getContents());
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\Item
	 * @uses \WEEEOpen\Tarallo\Server\ItemIncomplete
	 */
	public function testMultipleAddChild() {
		$item = new Item('TEST');
		$child = (new Item('CHILD'))->addMultipleFeatures([
			new Feature('capacity-byte', 9001),
			new Feature('color', 'white'),
			new Feature('brand', 'bar')
		]);
		$child2 = (new Item('bar'))->addMultipleFeatures([new Feature('capacity-byte', 999), new Feature('color', 'grey')]);

		$item->addContent($child)->addContent($child2);
		$this->assertContains($child, $item->getContents());
		$this->assertContains($child2, $item->getContents());
	}
}