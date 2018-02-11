<?php

use PHPUnit\Framework\TestCase;
use WEEEOpen\Tarallo\Server\InvalidParameterException;
use WEEEOpen\Tarallo\Server\ItemIncomplete;

class ItemIncompleteTest extends TestCase {
	/**
	 * @covers         \WEEEOpen\Tarallo\Server\ItemIncomplete
	 */
	public function testItemValidCodeString() {
		$pc77 = new ItemIncomplete('PC-77');
		$this->assertEquals('PC-77', (string) $pc77);
		$this->assertEquals('PC-77', $pc77->getCode());
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Server\ItemIncomplete
	 */
	public function testItemValidCodeInt() {
		$quarantadue = new ItemIncomplete(42);
		$this->assertEquals('42', (string) $quarantadue);
		$this->assertEquals(42, $quarantadue->getCode());
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Server\ItemIncomplete
	 */
	public function testItemNullCode() {
		$this->expectException(InvalidParameterException::class);
		new ItemIncomplete(null);
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Server\ItemIncomplete
	 */
	public function testItemEmptyCode() {
		$this->expectException(InvalidParameterException::class);
		new ItemIncomplete('');
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Server\ItemIncomplete
	 */
	public function testItemArrayCode() {
		$this->expectException(InvalidParameterException::class);
		new ItemIncomplete(["cose" => "a caso"]);
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Server\ItemIncomplete
	 */
	public function testJsonSerialize() {
		$i = new ItemIncomplete("TEST");
		$this->assertEquals('TEST', $i->jsonSerialize());
		$this->assertEquals('"TEST"', json_encode($i));
	}
}