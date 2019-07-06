<?php

use PHPUnit\Framework\TestCase;
use WEEEOpen\Tarallo\Server\ItemCode;

class ItemCodeTest extends TestCase {
	/**
	 * @covers \WEEEOpen\Tarallo\Server\ItemCode
	 */
	public function testItemValidCodeString() {
		$pc77 = new ItemCode('PC77');
		$this->assertEquals('PC77', (string) $pc77);
		$this->assertEquals('PC77', $pc77->getCode());
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\ItemCode
	 */
	public function testItemIntCode() {
		$this->expectException(InvalidArgumentException::class);
		new ItemCode(42);
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\ItemCode
	 */
	public function testItemNullCode() {
		$this->expectException(InvalidArgumentException::class);
		new ItemCode(null);
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\ItemCode
	 */
	public function testItemEmptyCode() {
		$this->expectException(InvalidArgumentException::class);
		new ItemCode('');
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\ItemCode
	 */
	public function testItemArrayCode() {
		$this->expectException(InvalidArgumentException::class);
		/** @noinspection PhpParamsInspection it's part of the test */
		new ItemCode(["cose" => "a caso"]);
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\ItemCode
	 */
	public function testJsonSerialize() {
		$i = new ItemCode("TEST");
		$this->assertEquals('TEST', $i->jsonSerialize());
		$this->assertEquals('"TEST"', json_encode($i));
	}
}