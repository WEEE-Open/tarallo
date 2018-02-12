<?php

use PHPUnit\Framework\TestCase;
use WEEEOpen\Tarallo\Server\ItemIncomplete;

class ItemIncompleteTest extends TestCase {
	/**
	 * @covers \WEEEOpen\Tarallo\Server\ItemIncomplete
	 */
	public function testItemValidCodeString() {
		$pc77 = new ItemIncomplete('PC77');
		$this->assertEquals('PC77', (string) $pc77);
		$this->assertEquals('PC77', $pc77->getCode());
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\ItemIncomplete
	 */
	public function testItemIntCode() {
		$this->expectException(\InvalidArgumentException::class);
		new ItemIncomplete(42);
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\ItemIncomplete
	 */
	public function testItemNullCode() {
		$this->expectException(\InvalidArgumentException::class);
		new ItemIncomplete(null);
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\ItemIncomplete
	 */
	public function testItemEmptyCode() {
		$this->expectException(\InvalidArgumentException::class);
		new ItemIncomplete('');
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\ItemIncomplete
	 */
	public function testItemArrayCode() {
		$this->expectException(\InvalidArgumentException::class);
		new ItemIncomplete(["cose" => "a caso"]);
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\ItemIncomplete
	 */
	public function testJsonSerialize() {
		$i = new ItemIncomplete("TEST");
		$this->assertEquals('TEST', $i->jsonSerialize());
		$this->assertEquals('"TEST"', json_encode($i));
	}
}