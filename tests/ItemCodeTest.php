<?php

use PHPUnit\Framework\TestCase;
use WEEEOpen\Tarallo\ItemCode;

class ItemCodeTest extends TestCase {
	/**
	 * @covers \WEEEOpen\Tarallo\ItemCode
	 */
	public function testItemValidCodeString() {
		$pc77 = new ItemCode('PC77');
		$this->assertEquals('PC77', (string) $pc77);
		$this->assertEquals('PC77', $pc77->getCode());
	}

	/**
	 * @covers \WEEEOpen\Tarallo\ItemCode
	 */
	public function testItemIntCode() {
		$this->expectException(\WEEEOpen\Tarallo\ValidationException::class);
		new ItemCode(42);
	}

	/**
	 * @covers \WEEEOpen\Tarallo\ItemCode
	 */
	public function testItemNullCode() {
		$this->expectException(\WEEEOpen\Tarallo\ValidationException::class);
		new ItemCode(null);
	}

	/**
	 * @covers \WEEEOpen\Tarallo\ItemCode
	 */
	public function testItemEmptyCode() {
		$this->expectException(\WEEEOpen\Tarallo\ValidationException::class);
		new ItemCode('');
	}

	/**
	 * @covers \WEEEOpen\Tarallo\ItemCode
	 */
	public function testItemWrongCode() {
		$this->expectException(\WEEEOpen\Tarallo\ValidationException::class);
		new ItemCode('PC_42');
	}

	/**
	 * @covers \WEEEOpen\Tarallo\ItemCode
	 */
	public function testItemArrayCode() {
		$this->expectException(\WEEEOpen\Tarallo\ValidationException::class);
		/** @noinspection PhpParamsInspection it's part of the test */
		new ItemCode(["cose" => "a caso"]);
	}

	/**
	 * @covers \WEEEOpen\Tarallo\ItemCode
	 */
	public function testJsonSerialize() {
		$i = new ItemCode("TEST");
		$this->assertEquals('TEST', $i->jsonSerialize());
		$this->assertEquals('"TEST"', json_encode($i));
	}
}