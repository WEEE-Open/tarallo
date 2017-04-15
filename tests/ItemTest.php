<?php

use PHPUnit\Framework\TestCase;
use WEEEOpen\Tarallo\InvalidParameterException;
use WEEEOpen\Tarallo\Item;

class ItemTest extends TestCase {

	/**
	 * @covers         \WEEEOpen\Tarallo\Item
	 * @covers         \WEEEOpen\Tarallo\ItemIncomplete
	 */
	public function testItemValidCodeString() {
		$pc77 = new Item('PC-77');
		$this->assertEquals('PC-77', (string) $pc77);
		$this->assertEquals('PC-77', $pc77->getCode());
		$this->assertEmpty($pc77->getFeatures());
		$this->assertEmpty($pc77->getContent());
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Item
	 * @covers         \WEEEOpen\Tarallo\ItemIncomplete
	 */
	public function testItemValidParent() {
		$pc77 = new Item('HDD-238947283', 'HDD-ASD');
		$this->assertEquals('HDD-238947283', $pc77->getCode());
		$this->assertEquals('HDD-ASD', $pc77->getDefaultCode(), 'Parent should be set');

		$pc77 = new Item('HDD-238947283');
		$this->assertEquals('HDD-238947283', $pc77->getCode());
		$this->assertEquals(null, $pc77->getDefaultCode(), 'No parent should mean "null"');

		$pc77 = new Item('HDD-238947283', null);
		$this->assertEquals('HDD-238947283', $pc77->getCode());
		$this->assertEquals(null, $pc77->getDefaultCode(), 'Explicitly setting parent to null should be allowed');
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Item
	 * @covers         \WEEEOpen\Tarallo\ItemIncomplete
	 */
	public function testItemInvalidParentEmptyString() {
		$this->expectException(InvalidParameterException::class);
		new Item('HDD-238947283', '');
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Item
	 * @covers         \WEEEOpen\Tarallo\ItemIncomplete
	 */
	public function testItemValidCodeInt() {
		$quarantadue = new Item(42);
		$this->assertEquals('42', (string) $quarantadue);
		$this->assertEquals(42, $quarantadue->getCode());
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Item
	 * @covers         \WEEEOpen\Tarallo\ItemIncomplete
	 */
	public function testItemNullCode() {
		$this->expectException(InvalidParameterException::class);
		new Item(null);
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Item
	 * @covers         \WEEEOpen\Tarallo\ItemIncomplete
	 */
	public function testItemEmptyCode() {
		$this->expectException(InvalidParameterException::class);
		new Item('');
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Item
	 * @uses           \WEEEOpen\Tarallo\ItemIncomplete
	 */
	public function testItemFeature() {
		$item = new Item('TEST');
		$item->addFeature('capacity-byte', 9001);
		$this->assertArrayHasKey('capacity-byte', $item->getFeatures());
		$this->assertEquals(9001, $item->getFeatures()['capacity-byte']);
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Item
	 * @uses           \WEEEOpen\Tarallo\ItemIncomplete
	 */
	public function testItemFeatureDefault() {
		$item = new Item('TEST');
		$item->addFeatureDefault('type', 'hdd');
		$this->assertArrayHasKey('type', $item->getFeaturesDefault());
		$this->assertEquals('hdd', $item->getFeaturesDefault()['type']);
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Item
	 * @uses           \WEEEOpen\Tarallo\ItemIncomplete
	 */
	public function testItemFeatureDefaultOverride() {
		$item = new Item('TEST');
		$item->addFeatureDefault('capacity-byte', 9000000000);
		$item->addFeature('capacity-byte', 8999999999);
		$this->assertArrayHasKey('capacity-byte', $item->getFeatures());
		$this->assertEquals(8999999999, $item->getFeatures()['capacity-byte']);
		$this->assertArrayHasKey('capacity-byte', $item->getFeaturesDefault());
		$this->assertEquals(9000000000, $item->getFeaturesDefault()['capacity-byte']);
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Item
	 * @uses           \WEEEOpen\Tarallo\ItemIncomplete
	 */
	public function testItemToString() {
		$item = new Item('TEST');
		$this->assertEquals('TEST', (string) $item);
		$item->addFeature('type', 'hdd');
		$this->assertEquals('TEST (hdd)', (string) $item);

		$item = new Item('TEST2');
		$item->addFeatureDefault('type', 'hdd');
		$this->assertEquals('TEST2 (hdd)', (string) $item);
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Item
	 * @uses           \WEEEOpen\Tarallo\ItemIncomplete
	 * @depends        testItemFeature
	 */
	public function testItemMultipleFeatures() {
		$item = new Item('TEST');
		$item->addFeature('capacity-byte', 9001)->addFeature('color', 'white');
		$item->addFeature('foo', 'bar');

		$this->assertArrayHasKey('capacity-byte', $item->getFeatures());
		$this->assertEquals(9001, $item->getFeatures()['capacity-byte']);

		$this->assertArrayHasKey('color', $item->getFeatures());
		$this->assertEquals('white', $item->getFeatures()['color']);

		$this->assertArrayHasKey('foo', $item->getFeatures());
		$this->assertEquals('bar', $item->getFeatures()['foo']);
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Item
	 * @uses           \WEEEOpen\Tarallo\ItemIncomplete
	 */
	public function testInvalidFeatureNameArray() {
		$this->expectException(\InvalidArgumentException::class);
		(new Item('TEST'))->addFeature(['foo'], 'value');
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Item
	 * @uses           \WEEEOpen\Tarallo\ItemIncomplete
	 */
	public function testInvalidFeatureNameInteger() {
		$this->expectException(\InvalidArgumentException::class);
		(new Item('TEST'))->addFeature(42, 'value');
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Item
	 * @uses           \WEEEOpen\Tarallo\ItemIncomplete
	 */
	public function testInvalidFeatureValueNegative() {
		$this->expectException(\InvalidArgumentException::class);
		(new Item('TEST'))->addFeature('capacity-byte', -50);
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Item
	 * @uses           \WEEEOpen\Tarallo\ItemIncomplete
	 */
	public function testInvalidFeatureValueArray() {
		$this->expectException(\InvalidArgumentException::class);
		(new Item('TEST'))->addFeature('capacity-byte', [400000]);
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Item
	 * @uses           \WEEEOpen\Tarallo\ItemIncomplete
	 */
	public function testInvalidFeatureDuplicate() {
		$item = (new Item('TEST'))->addFeature('capacity-byte', 500);
		$this->expectException(InvalidParameterException::class);
		$item->addFeature('capacity-byte', 123);
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Item
	 * @uses           \WEEEOpen\Tarallo\ItemIncomplete
	 */
	public function testInvalidFeatures() {
		$this->expectException(\InvalidArgumentException::class);
		(new Item('TEST'))->addMultipleFeatures('foo');
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Item
	 * @uses           \WEEEOpen\Tarallo\ItemIncomplete
	 */
	public function testItemMultipleFeaturesArray() {
		$item = new Item('TEST');
		$item->addMultipleFeatures(['capacity-byte' => 9001, 'color' => 'white', 'foo' => 'bar'])->addFeature('test', 'test');

		$this->assertArrayHasKey('capacity-byte', $item->getFeatures());
		$this->assertEquals(9001, $item->getFeatures()['capacity-byte']);

		$this->assertArrayHasKey('color', $item->getFeatures());
		$this->assertEquals('white', $item->getFeatures()['color']);

		$this->assertArrayHasKey('foo', $item->getFeatures());
		$this->assertEquals('bar', $item->getFeatures()['foo']);

		$this->assertArrayHasKey('test', $item->getFeatures());
		$this->assertEquals('test', $item->getFeatures()['test']);
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Item
	 * @uses           \WEEEOpen\Tarallo\ItemIncomplete
	 */
	public function testValidAddChild() {
		$item = new Item('TEST');
		$child = (new Item('foo'))->addMultipleFeatures(['capacity-byte' => 9001, 'color' => 'white', 'foo' => 'bar']);

		$item->addContent($child);
		$this->assertContains($child, $item->getContent());
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Item
	 * @uses           \WEEEOpen\Tarallo\ItemIncomplete
	 */
	public function testMultipleAddChild() {
		$item = new Item('TEST');
		$child = (new Item('foo'))->addMultipleFeatures(['capacity-byte' => 9001, 'color' => 'white', 'foo' => 'bar']);
		$child2 = (new Item('bar'))->addMultipleFeatures(['capacity-byte' => 999, 'color' => 'grey']);

		$item->addContent($child)->addContent($child2);
		$this->assertContains($child, $item->getContent());
		$this->assertContains($child2, $item->getContent());
	}
}