<?php

use PHPUnit\Framework\TestCase;
use WEEEOpen\Tarallo\ItemDefault;

class ItemDefaultTest extends TestCase {

	/**
	 * @covers         \WEEEOpen\Tarallo\ItemDefault
	 * @uses           \WEEEOpen\Tarallo\ItemIncomplete
	 * @uses           \WEEEOpen\Tarallo\Item
	 */
	public function testItemValidCodeString() {
		$pc77 = new ItemDefault('PC-77');
		$this->assertEquals('PC-77', (string) $pc77);
		$this->assertEquals('PC-77', $pc77->getCode());
		$this->assertEmpty($pc77->getFeatures());
		$this->assertEmpty($pc77->getContent());
		$this->assertEmpty($pc77->getFeaturesDefault());
		$this->assertNull($pc77->getDefaultCode());
		$decoded = json_decode(json_encode($pc77), true);
		$this->assertArrayHasKey('is_default', $decoded);
		$this->assertTrue($decoded['is_default']);
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\ItemDefault
	 * @uses           \WEEEOpen\Tarallo\ItemIncomplete
	 * @uses           \WEEEOpen\Tarallo\Item
	 */
	public function testItemFeature() {
		$item = new ItemDefault('TEST');
		$item->addFeature('capacity-byte', 9001);
		$this->assertArrayHasKey('capacity-byte', $item->getFeatures());
		$this->assertEquals(9001, $item->getFeatures()['capacity-byte']);
		$this->assertEmpty($item->getFeaturesDefault());
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\ItemDefault
	 * @uses           \WEEEOpen\Tarallo\ItemIncomplete
	 * @uses           \WEEEOpen\Tarallo\Item
	 * @depends        testItemFeature
	 */
	public function testItemMultipleFeaturesArray() {
		$item = new ItemDefault('TEST');
		$item->addMultipleFeatures(['capacity-byte' => 9001, 'color' => 'white', 'foo' => 'bar'])->addFeature('test', 'test');
		$this->assertEmpty($item->getFeaturesDefault());
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\ItemDefault
	 * @uses           \WEEEOpen\Tarallo\ItemIncomplete
	 * @uses           \WEEEOpen\Tarallo\Item
	 */
	public function testInvalidAddFeatureDefault() {
		$this->expectException(LogicException::class);
		(new ItemDefault('TEST'))->addFeatureDefault('capacity-byte', 9002);
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\ItemDefault
	 * @uses           \WEEEOpen\Tarallo\ItemIncomplete
	 * @uses           \WEEEOpen\Tarallo\Item
	 */
	public function testAddAncestor() {
		$this->expectException(LogicException::class);
		(new ItemDefault('TEST'))->addAncestor(1, "STUFF");
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\ItemDefault
	 * @uses           \WEEEOpen\Tarallo\ItemIncomplete
	 * @uses           \WEEEOpen\Tarallo\Item
	 */
	public function testGetAncestor() {
		$item = new ItemDefault('TEST');
		$this->assertEquals(null, $item->getAncestor(1), 'ItemDefault should have null ancestors (for compatibility with Item)');
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\ItemDefault
	 * @uses           \WEEEOpen\Tarallo\ItemIncomplete
	 * @uses           \WEEEOpen\Tarallo\Item
	 */
	public function testItemNullCode() {
		$this->expectException(\InvalidArgumentException::class);
		$it = new ItemDefault(null);
	}
}