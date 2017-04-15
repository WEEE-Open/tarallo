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
	public function testCreateSimpleItem() {
		$out = json_decode( json_encode(new EditQuery('{"create":{"PC-72":{}}}')),true);
		$this->assertEquals(['create' => ['PC-72' => []]], $out);

		$out = json_decode( json_encode(new EditQuery('{"create":{"PC-72":{"is_default":false}}}')),true);
		$this->assertEquals(['create' => ['PC-72' => []]], $out);

		$out = json_decode( json_encode(new EditQuery('{"create":{"PC-72":{"is_default":true}}}')),true);
		$this->assertEquals(['create' => ['PC-72' => ['is_default' => true]]], $out);

		$out = json_decode( json_encode(new EditQuery('{"create":{"PC-72":{"is_default":false,"default":"ASD"}}}')),true);
		$this->assertEquals(['create' => ['PC-72' => ['default' => 'ASD']]], $out);
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