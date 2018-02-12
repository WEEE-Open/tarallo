<?php

use PHPUnit\Framework\TestCase;
use WEEEOpen\Tarallo\Server\Feature;
use WEEEOpen\Tarallo\Server\InvalidParameterException;
use WEEEOpen\Tarallo\Server\Item;

class ItemTest extends TestCase {

	/**
	 * @covers         \WEEEOpen\Tarallo\Server\Item
	 * @uses           \WEEEOpen\Tarallo\Server\ItemIncomplete
	 */
	public function testItemValidCodeString() {
		$pc77 = new Item('PC-77');
		$this->assertEquals('PC-77', (string) $pc77);
		$this->assertEquals('PC-77', $pc77->getCode());
		$this->assertEmpty($pc77->getFeatures());
		$this->assertEmpty($pc77->getContents());
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Server\Item
	 * @covers         \WEEEOpen\Tarallo\Server\ItemIncomplete
	 */
	public function testItemInvalidDefaultEmptyString() {
		$this->expectException(\TypeError::class);
		new Item('HDD-238947283', '');
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Server\Item
	 * @uses           \WEEEOpen\Tarallo\Server\ItemIncomplete
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
	 * @covers         \WEEEOpen\Tarallo\Server\Item
	 * @uses           \WEEEOpen\Tarallo\Server\ItemIncomplete
	 */
	public function testItemInvalidAncestorAdd() {
		$hdd = new Item('HDD-123');
		$this->expectException(InvalidArgumentException::class);
		$hdd->addAncestor(0, 'PC-999');
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Server\Item
	 * @uses           \WEEEOpen\Tarallo\Server\ItemIncomplete
	 */
	public function testItemInvalidAncestorAdd2() {
		$hdd = new Item('HDD-123');
		$this->expectException(InvalidArgumentException::class);
		$hdd->addAncestor(-1, 'PC-999');
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Server\Item
	 * @uses           \WEEEOpen\Tarallo\Server\ItemIncomplete
	 */
	public function testItemInvalidAncestorGet() {
		$hdd = new Item('HDD-123');
		$this->expectException(InvalidArgumentException::class);
		$hdd->getAncestor(0);
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Server\Item
	 * @uses           \WEEEOpen\Tarallo\Server\ItemIncomplete
	 */
	public function testItemInvalidAncestorGet2() {
		$hdd = new Item('HDD-123');
		$this->expectException(InvalidArgumentException::class);
		$hdd->getAncestor(-1);
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Server\Item
	 * @uses           \WEEEOpen\Tarallo\Server\ItemIncomplete
	 */
	public function testItemValidCodeInt() {
		$quarantadue = new Item(42);
		$this->assertEquals('42', (string) $quarantadue);
		$this->assertEquals(42, $quarantadue->getCode());
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Server\Item
	 * @uses           \WEEEOpen\Tarallo\Server\ItemIncomplete
	 */
	public function testItemNullCode() {
		$it = new Item(null);
		$this->expectException(\LogicException::class);
		$it->getCode();
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Server\Item
	 * @uses           \WEEEOpen\Tarallo\Server\ItemIncomplete
	 */
	public function testItemSetCode() {
		$it = new Item(null);
		$it->setCode('PC-22');
		$this->assertEquals('PC-22', $it->getCode());
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Server\Item
	 * @uses           \WEEEOpen\Tarallo\Server\ItemIncomplete
	 */
	public function testItemEmptyCode() {
		$this->expectException(InvalidParameterException::class);
		new Item('');
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Server\Item
	 * @uses           \WEEEOpen\Tarallo\Server\ItemIncomplete
	 */
	public function testItemFeature() {
		$item = new Item('TEST');
		$item->addFeature(new Feature('capacity-byte', 9001));
		$this->assertArrayHasKey('capacity-byte', $item->getFeatures());
		$this->assertEquals(9001, $item->getFeatures()['capacity-byte']);
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Server\Item
	 * @uses           \WEEEOpen\Tarallo\Server\ItemIncomplete
	 */
	public function testItemDeleteFeature() {
		$item = new Item('TEST');
		$item->addFeature(new Feature('capacity-byte', 9001));
		$item->addFeature(new Feature('color', 'yellow'));
		$item->deleteFeature('color');

		$this->assertArrayNotHasKey('color', $item->getFeatures());
		$this->assertArrayHasKey('capacity-byte', $item->getFeatures());
		$this->assertEquals(9001, $item->getFeatures()['capacity-byte']);
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Server\Item
	 * @uses           \WEEEOpen\Tarallo\Server\ItemIncomplete
	 */
	public function testItemToString() {
		$item = new Item('TEST');
		$this->assertEquals('TEST', (string) $item);
		$item->addFeature(new Feature('type', 'hdd'));
		$this->assertEquals('TEST (hdd)', (string) $item);
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Server\Item
	 * @uses           \WEEEOpen\Tarallo\Server\ItemIncomplete
	 */
	public function testItemMultipleFeatures() {
		$item = new Item('TEST');
		$item->addFeature(new Feature('capacity-byte', 9001))->addFeature(new Feature('color', 'white'));
		$item->addFeature(new Feature('brand', 'bar'));

		$this->assertArrayHasKey('capacity-byte', $item->getFeatures());
		$this->assertEquals(9001, $item->getFeatures()['capacity-byte']);

		$this->assertArrayHasKey('color', $item->getFeatures());
		$this->assertEquals('white', $item->getFeatures()['color']);

		$this->assertArrayHasKey('brand', $item->getFeatures());
		$this->assertEquals('bar', $item->getFeatures()['brand']);
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Server\Item
	 * @uses           \WEEEOpen\Tarallo\Server\ItemIncomplete
	 */
	public function testInvalidFeatureNameArray() {
		$this->expectException(\InvalidArgumentException::class);
		(new Item('TEST'))->addFeature(new Feature(['brand'], 'value'));
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Server\Item
	 * @uses           \WEEEOpen\Tarallo\Server\ItemIncomplete
	 */
	public function testInvalidFeatureNameInteger() {
		$this->expectException(\InvalidArgumentException::class);
		(new Item('TEST'))->addFeature(new Feature(42, 'value'));
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Server\Item
	 * @uses           \WEEEOpen\Tarallo\Server\ItemIncomplete
	 */
	public function testInvalidFeatureValueNegative() {
		$this->expectException(\InvalidArgumentException::class);
		(new Item('TEST'))->addFeature(new Feature('capacity-byte', -50));
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Server\Item
	 * @uses           \WEEEOpen\Tarallo\Server\ItemIncomplete
	 */
	public function testInvalidFeatureValueArray() {
		$this->expectException(\InvalidArgumentException::class);
		(new Item('TEST'))->addFeature(new Feature('capacity-byte', [400000]));
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Server\Item
	 * @uses           \WEEEOpen\Tarallo\Server\ItemIncomplete
	 */
	public function testInvalidFeatureDuplicate() {
		$item = (new Item('TEST'))->addFeature(new Feature('capacity-byte', 500));
		$this->expectException(InvalidParameterException::class);
		$item->addFeature(new Feature('capacity-byte', 123));
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Server\Item
	 * @uses           \WEEEOpen\Tarallo\Server\ItemIncomplete
	 */
	public function testItemMultipleFeaturesArray() {
		$item = new Item('TEST');
		$item->addMultipleFeatures([
			'capacity-byte' => 9001,
			'color'         => 'white',
			'brand'         => 'bar'
		])->addFeature(new Feature('test', 'test'));

		$this->assertArrayHasKey('capacity-byte', $item->getFeatures());
		$this->assertEquals(9001, $item->getFeatures()['capacity-byte']);

		$this->assertArrayHasKey('color', $item->getFeatures());
		$this->assertEquals('white', $item->getFeatures()['color']);

		$this->assertArrayHasKey('brand', $item->getFeatures());
		$this->assertEquals('bar', $item->getFeatures()['brand']);

		$this->assertArrayHasKey('test', $item->getFeatures());
		$this->assertEquals('test', $item->getFeatures()['test']);
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Server\Item
	 * @uses           \WEEEOpen\Tarallo\Server\ItemIncomplete
	 */
	public function testValidAddChild() {
		$item = new Item('TEST');
		$child = (new Item('brand'))->addMultipleFeatures([
			'capacity-byte' => 9001,
			'color'         => 'white',
			'brand'         => 'bar'
		]);

		$item->addContent($child);
		$this->assertContains($child, $item->getContents());
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Server\Item
	 * @uses           \WEEEOpen\Tarallo\Server\ItemIncomplete
	 */
	public function testMultipleAddChild() {
		$item = new Item('TEST');
		$child = (new Item('brand'))->addMultipleFeatures([
			'capacity-byte' => 9001,
			'color'         => 'white',
			'brand'         => 'bar'
		]);
		$child2 = (new Item('bar'))->addMultipleFeatures(['capacity-byte' => 999, 'color' => 'grey']);

		$item->addContent($child)->addContent($child2);
		$this->assertContains($child, $item->getContents());
		$this->assertContains($child2, $item->getContents());
	}
}