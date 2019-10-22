<?php

use PHPUnit\Framework\TestCase;
use WEEEOpen\Tarallo\Feature;
use WEEEOpen\Tarallo\Item;
use WEEEOpen\Tarallo\ItemCode;

class ItemTest extends TestCase {

	/**
	 * @covers \WEEEOpen\Tarallo\Item
	 * @uses   \WEEEOpen\Tarallo\ItemCode
	 */
	public function testItemValidCodeString() {
		$pc77 = new Item('PC77');
		$this->assertEquals('PC77', (string) $pc77);
		$this->assertEquals('PC77', $pc77->getCode());
		$this->assertEmpty($pc77->getFeatures());
		$this->assertEmpty($pc77->getContent());
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Item
	 * @uses   \WEEEOpen\Tarallo\ItemCode
	 */
	public function testItemValidAncestors() {
		$hdd = new Item('HDD123');

		$other1 = new ItemCode('PC999');
		$other2 = new ItemCode('Tavolo');
		$other3 = new ItemCode('Chernobyl');
		$other4 = new ItemCode('Polito');
		$hdd->addAncestors([$other1, $other2, $other3, $other4]);

		$path = $hdd->getPath();
		$this->assertCount(4, $hdd->getPath());
		$this->assertContainsOnly(ItemCode::class, $path);

		$parent = $path[0];

		$this->assertEquals('PC999', $parent->getCode(), 'Parent PC999 should be there');
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Item
	 * @uses   \WEEEOpen\Tarallo\ItemCode
	 */
	public function testItemNoAncestorGet() {
		$hdd = new Item('HDD123');
		$this->assertEquals([], $hdd->getPath());
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Item
	 * @uses   \WEEEOpen\Tarallo\ItemCode
	 */
	public function testItemInvalidCodeInt() {
		$this->expectException(InvalidArgumentException::class);
		new Item(42);
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Item
	 * @uses   \WEEEOpen\Tarallo\ItemCode
	 */
	public function testItemNullCode() {
		$it = new Item(null);
		$this->expectException(LogicException::class);
		$it->getCode();
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Item
	 * @uses   \WEEEOpen\Tarallo\ItemCode
	 */
	public function testItemSetCode() {
		$it = new Item(null);
		$it->setCode('PC22');
		$this->assertEquals('PC22', $it->getCode());
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Item
	 * @uses   \WEEEOpen\Tarallo\ItemCode
	 */
	public function testItemEmptyCode() {
		$this->expectException(InvalidArgumentException::class);
		new Item('');
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Item
	 * @uses   \WEEEOpen\Tarallo\ItemCode
	 */
	public function testItemFeature() {
		$item = new Item('TEST');
		$item->addFeature(new Feature('capacity-byte', 9001));
		$this->assertArrayHasKey('capacity-byte', $item->getFeatures(), 'Feature must be present');
		$this->assertEquals('capacity-byte', $item->getFeatures()['capacity-byte']->name,
			'Feature name must be coherent');
		$this->assertEquals(9001, $item->getFeatures()['capacity-byte']->value, 'Feature value must match');
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Item
	 * @uses   \WEEEOpen\Tarallo\ItemCode
	 */
	public function testItemDeleteFeature() {
		$item = new Item('TEST');
		$item->addFeature(new Feature('capacity-byte', 9001));
		$item->addFeature(new Feature('color', 'yellow'));
		$item->removeFeatureByName('color');

		$this->assertArrayNotHasKey('color', $item->getFeatures());
		$this->assertArrayHasKey('capacity-byte', $item->getFeatures());
		$this->assertEquals(9001, $item->getFeatures()['capacity-byte']->value);
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Item
	 * @uses   \WEEEOpen\Tarallo\ItemCode
	 */
	public function testItemToString() {
		$item = new Item('TEST');
		$this->assertEquals('TEST', (string) $item);
		$item->addFeature(new Feature('type', 'hdd'));
		$this->assertEquals('TEST (hdd)', (string) $item);
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Item
	 * @uses   \WEEEOpen\Tarallo\ItemCode
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
	 * @covers \WEEEOpen\Tarallo\Item
	 * @uses   \WEEEOpen\Tarallo\ItemCode
	 */
	public function testInvalidFeatureNameArray() {
		$this->expectException(InvalidArgumentException::class);
		/** @noinspection PhpParamsInspection */
		(new Item('TEST'))->addFeature(new Feature(['brand'], 'value'));
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Item
	 * @uses   \WEEEOpen\Tarallo\ItemCode
	 */
	public function testInvalidFeatureNameInteger() {
		$this->expectException(InvalidArgumentException::class);
		(new Item('TEST'))->addFeature(new Feature(42, 'value'));
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Item
	 * @uses   \WEEEOpen\Tarallo\ItemCode
	 */
	public function testInvalidFeatureValueNegative() {
		$this->expectException(InvalidArgumentException::class);
		(new Item('TEST'))->addFeature(new Feature('capacity-byte', -50));
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Item
	 * @uses   \WEEEOpen\Tarallo\ItemCode
	 */
	public function testInvalidFeatureValueArray() {
		$this->expectException(InvalidArgumentException::class);
		(new Item('TEST'))->addFeature(new Feature('capacity-byte', [400000]));
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Item
	 * @uses   \WEEEOpen\Tarallo\ItemCode
	 */
	public function testInvalidFeatureDuplicate() {
		$item = (new Item('TEST'))->addFeature(new Feature('capacity-byte', 500));
		$this->expectException(InvalidArgumentException::class);
		$item->addFeature(new Feature('capacity-byte', 123));
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Item
	 * @uses   \WEEEOpen\Tarallo\ItemCode
	 */
	public function testItemMultipleFeaturesArray() {
		$item = new Item('TEST');
		$item
			->addFeature(new Feature('capacity-byte', 9001))
			->addFeature(new Feature('color', 'white'))
			->addFeature(new Feature('brand', 'bar'))
			->addFeature(new Feature('model', 'T'));

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
	 * @covers \WEEEOpen\Tarallo\Item
	 * @uses   \WEEEOpen\Tarallo\ItemCode
	 */
	public function testValidAddChild() {
		$item = new Item('TEST');
		$child = (new Item('CHILD'))
			->addFeature(new Feature('capacity-byte', 9001))
			->addFeature(new Feature('color', 'white'))
			->addFeature(new Feature('brand', 'bar'));

		$item->addContent($child);
		$this->assertContains($child, $item->getContent());
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Item
	 * @uses   \WEEEOpen\Tarallo\ItemCode
	 */
	public function testMultipleAddChild() {
		$item = new Item('TEST');
		$child = (new Item('CHILD'))
			->addFeature(new Feature('capacity-byte', 9001))
			->addFeature(new Feature('color', 'white'))
			->addFeature(new Feature('brand', 'bar'));
		$child2 = (new Item('bar'))
			->addFeature(new Feature('capacity-byte', 999))
			->addFeature(new Feature('color', 'grey'));

		$item->addContent($child)->addContent($child2);
		$this->assertContains($child, $item->getContent());
		$this->assertContains($child2, $item->getContent());
	}
}
