<?php

use PHPUnit\Framework\TestCase;
use WEEEOpen\Tarallo\ItemIncomplete;

class ItemIncompleteTest extends TestCase {
	/**
	 * @covers \WEEEOpen\Tarallo\ItemCode
	 * @covers \WEEEOpen\Tarallo\ItemIncomplete
	 */
	public function testItemIncomplete() {
		$pc77 = new ItemIncomplete('PC77');
		$this->assertEquals('PC77', (string) $pc77);
		$this->assertEquals('PC77', $pc77->getCode());
		$this->assertEquals('PC77', $pc77->peekCode());
		$this->assertTrue($pc77->hasCode());
	}

	/**
	 * @covers \WEEEOpen\Tarallo\ItemCode
	 * @covers \WEEEOpen\Tarallo\ItemIncomplete
	 */
	public function testItemIncompleteNoCode() {
		$pc77 = new ItemIncomplete(null);
		$this->assertEquals(null, $pc77->peekCode());
		$this->assertFalse($pc77->hasCode());
	}

	/**
	 * @covers \WEEEOpen\Tarallo\ItemCode
	 * @covers \WEEEOpen\Tarallo\ItemIncomplete
	 */
	public function testItemIncompleteNoCodeException() {
		$pc77 = new ItemIncomplete(null);
		$this->expectException(LogicException::class);
		$pc77->getCode();
	}
}