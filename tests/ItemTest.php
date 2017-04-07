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
}