<?php
namespace WEEEOpen\Tarallo\Test\Query;

use PHPUnit\Framework\TestCase;
use WEEEOpen\Tarallo\InvalidParameterException;
use WEEEOpen\Tarallo\Query\EditQuery;


class EditQueryTest extends TestCase{
	/**
	 * @covers         \WEEEOpen\Tarallo\Query\PostJSONQuery
	 * @covers         \WEEEOpen\Tarallo\Query\EditQuery
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQuery
	 * @uses           \WEEEOpen\Tarallo\Query\PostJSONQuery
	 */
	public function testInvalidQueryNull() {
		$this->expectException(InvalidParameterException::class);
		new EditQuery(null);
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Query\PostJSONQuery
	 * @covers         \WEEEOpen\Tarallo\Query\EditQuery
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQuery
	 * @uses           \WEEEOpen\Tarallo\Query\PostJSONQuery
	 */
	public function testInvalidQueryEmptyString() {
		$this->expectException(InvalidParameterException::class);
		new EditQuery('');
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Query\PostJSONQuery
	 * @covers         \WEEEOpen\Tarallo\Query\EditQuery
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQuery
	 * @uses           \WEEEOpen\Tarallo\Query\PostJSONQuery
	 * @uses           \WEEEOpen\Tarallo\Item
	 * @uses           \WEEEOpen\Tarallo\ItemDefault
	 * @uses           \WEEEOpen\Tarallo\ItemIncomplete
	 * @uses           \WEEEOpen\Tarallo\ItemUpdate
	 */
	public function testUnknownAction() {
		$this->expectException(InvalidParameterException::class);
		new EditQuery('{"foo":{"PC-72":{}}');
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Query\PostJSONQuery
	 * @covers         \WEEEOpen\Tarallo\Query\EditQuery
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQuery
	 * @uses           \WEEEOpen\Tarallo\Query\PostJSONQuery
	 * @uses           \WEEEOpen\Tarallo\Item
	 * @uses           \WEEEOpen\Tarallo\ItemDefault
	 * @uses           \WEEEOpen\Tarallo\ItemIncomplete
	 * @uses           \WEEEOpen\Tarallo\ItemUpdate
	 */
	public function testInvalidActionNumber() {
		$this->expectException(InvalidParameterException::class);
		new EditQuery('{1000:{"PC-72":{}}');
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Query\PostJSONQuery
	 * @covers         \WEEEOpen\Tarallo\Query\EditQuery
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQuery
	 * @uses           \WEEEOpen\Tarallo\Query\PostJSONQuery
	 * @uses           \WEEEOpen\Tarallo\Item
	 * @uses           \WEEEOpen\Tarallo\ItemDefault
	 * @uses           \WEEEOpen\Tarallo\ItemIncomplete
	 * @uses           \WEEEOpen\Tarallo\ItemUpdate
	 */
	public function testInvalidActionParameter() {
		$this->expectException(InvalidParameterException::class);
		new EditQuery('{"create":"PC-72"}');
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Query\PostJSONQuery
	 * @covers         \WEEEOpen\Tarallo\Query\EditQuery
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQuery
	 * @uses           \WEEEOpen\Tarallo\Query\PostJSONQuery
	 * @uses           \WEEEOpen\Tarallo\Item
	 * @uses           \WEEEOpen\Tarallo\ItemDefault
	 * @uses           \WEEEOpen\Tarallo\ItemIncomplete
	 * @uses           \WEEEOpen\Tarallo\ItemUpdate
	 */
	public function testCreateSimpleItem() {
		$out = json_decode( json_encode(new EditQuery('{"create":{"PC-72":{}}}')),true);
		$this->assertEquals(['create' => [['code' => 'PC-72']]], $out);

		$out = json_decode( json_encode(new EditQuery('{"create":{"PC-72":{"is_default":false}}}')),true);
		$this->assertEquals(['create' => [['code' => 'PC-72']]], $out);

		$out = json_decode( json_encode(new EditQuery('{"create":{"PC-72":{"is_default":true}}}')),true);
		$this->assertEquals(['create' => [['code' => 'PC-72', 'is_default' => true]]], $out);

		$out = json_decode( json_encode(new EditQuery('{"create":{"PC-72":{"is_default":false,"default":"ASD"}}}')),true);
		$this->assertEquals(['create' => [['code' => 'PC-72', 'default' => 'ASD']]], $out);
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Query\PostJSONQuery
	 * @covers         \WEEEOpen\Tarallo\Query\EditQuery
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQuery
	 * @uses           \WEEEOpen\Tarallo\Query\PostJSONQuery
	 * @uses           \WEEEOpen\Tarallo\Item
	 * @uses           \WEEEOpen\Tarallo\ItemDefault
	 * @uses           \WEEEOpen\Tarallo\ItemIncomplete
	 * @uses           \WEEEOpen\Tarallo\ItemUpdate
	 */
	public function testCreateSimpleItemWithNotes() {
		$out = json_decode(json_encode(new EditQuery('{"create":{"PC-72":{}},"notes":"Created PC-72, ya its gud"}')), true);
		$this->assertEquals(['create' => [['code' => 'PC-72']], 'notes' => 'Created PC-72, ya its gud'], $out);

		$out = json_decode(json_encode(new EditQuery('{"create":{"PC-72":{}},"notes":null}')), true);
		$this->assertEquals(['create' => [['code' => 'PC-72']]], $out);
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Query\PostJSONQuery
	 * @covers         \WEEEOpen\Tarallo\Query\EditQuery
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQuery
	 * @uses           \WEEEOpen\Tarallo\Query\PostJSONQuery
	 * @uses           \WEEEOpen\Tarallo\Item
	 * @uses           \WEEEOpen\Tarallo\ItemDefault
	 * @uses           \WEEEOpen\Tarallo\ItemIncomplete
	 * @uses           \WEEEOpen\Tarallo\ItemUpdate
	 */
	public function testNullAction() {
		$out = json_decode(json_encode(new EditQuery('{"create":{"PC-72":{}},"update":null}')), true);
		$this->assertEquals(['create' => [['code' => 'PC-72']]], $out);
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Query\PostJSONQuery
	 * @covers         \WEEEOpen\Tarallo\Query\EditQuery
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQuery
	 * @uses           \WEEEOpen\Tarallo\Query\PostJSONQuery
	 * @uses           \WEEEOpen\Tarallo\Item
	 * @uses           \WEEEOpen\Tarallo\ItemDefault
	 * @uses           \WEEEOpen\Tarallo\ItemIncomplete
	 * @uses           \WEEEOpen\Tarallo\ItemUpdate
	 * @depends        testCreateSimpleItem
	 */
	public function testCreateInvalidItemDefaultAndIsDefault() {
		$this->expectException(InvalidParameterException::class);
		new EditQuery('{"create":{"PC-72":{"is_default":true,"default":"ASD"}}}');
	}
}