<?php
namespace WEEEOpen\Tarallo\Test\Query;

use PHPUnit\Framework\TestCase;
use WEEEOpen\Tarallo\InvalidParameterException;
use WEEEOpen\Tarallo\Query\GetQuery;

class SortTest extends TestCase{
	/**
	 * @uses           \WEEEOpen\Tarallo\Query\GetQuery
	 * @covers         \WEEEOpen\Tarallo\Query\Field\Sort
	 * @uses           \WEEEOpen\Tarallo\Query\Field\AbstractQueryField
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQuery
	 */
	public function testInvalidSortNoKey() {
		$this->expectException(InvalidParameterException::class);
		new GetQuery('/Sort/+');
	}

	/**
	 * @uses           \WEEEOpen\Tarallo\Query\GetQuery
	 * @covers         \WEEEOpen\Tarallo\Query\Field\Sort
	 * @uses           \WEEEOpen\Tarallo\Query\Field\AbstractQueryField
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQuery
	 */
	public function testInvalidSortNoOrder() {
		$this->expectException(InvalidParameterException::class);
		new GetQuery('/Sort/key');
	}

	/**
	 * @uses           \WEEEOpen\Tarallo\Query\GetQuery
	 * @covers         \WEEEOpen\Tarallo\Query\Field\Sort
	 * @uses           \WEEEOpen\Tarallo\Query\Field\AbstractQueryField
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQuery
	 * @depends        testInvalidSortNoKey
	 */
	public function testInvalidSortNoKeyDouble() {
		$this->expectException(InvalidParameterException::class);
		new GetQuery('/Sort/+key,foo');
	}

	/**
	 * @uses           \WEEEOpen\Tarallo\Query\GetQuery
	 * @covers         \WEEEOpen\Tarallo\Query\Field\Sort
	 * @uses           \WEEEOpen\Tarallo\Query\Field\AbstractQueryField
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQuery
	 * @depends        testInvalidSortNoOrder
	 */
	public function testInvalidSortNoOrderDouble() {
		$this->expectException(InvalidParameterException::class);
		new GetQuery('/Sort/+key,+');
	}

	/**
	 * @uses           \WEEEOpen\Tarallo\Query\GetQuery
	 * @covers         \WEEEOpen\Tarallo\Query\Field\Sort
	 * @uses           \WEEEOpen\Tarallo\Query\Field\AbstractQueryField
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQuery
	 * @depends        testInvalidSortNoKey
	 */
	public function testInvalidSortNoKeyDoubleReverse() {
		$this->expectException(InvalidParameterException::class);
		new GetQuery('/Sort/foo,+key');
	}

	/**
	 * @uses           \WEEEOpen\Tarallo\Query\GetQuery
	 * @covers         \WEEEOpen\Tarallo\Query\Field\Sort
	 * @uses           \WEEEOpen\Tarallo\Query\Field\AbstractQueryField
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQuery
	 * @depends        testInvalidSortNoOrder
	 */
	public function testInvalidSortNoOrderDoubleReverse() {
		$this->expectException(InvalidParameterException::class);
		new GetQuery('/Sort/+,+key');
	}

	/**
	 * @uses           \WEEEOpen\Tarallo\Query\GetQuery
	 * @covers         \WEEEOpen\Tarallo\Query\Field\Sort
	 * @uses           \WEEEOpen\Tarallo\Query\Field\AbstractQueryField
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQuery
	 */
	public function testSortValidSingle() {
		$this->assertEquals('/Sort/+foo', new GetQuery('/Sort/+foo'));
	}

	/**
	 * @uses           \WEEEOpen\Tarallo\Query\GetQuery
	 * @covers         \WEEEOpen\Tarallo\Query\Field\Sort
	 * @uses           \WEEEOpen\Tarallo\Query\Field\AbstractQueryField
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQuery
	 * @depends        testSortValidSingle
	 */
	public function testSortValidDouble() {
		$this->assertEquals('/Sort/+foo,-bar', new GetQuery('/Sort/+foo,-bar'));
	}

	/**
	 * @uses           \WEEEOpen\Tarallo\Query\GetQuery
	 * @covers         \WEEEOpen\Tarallo\Query\Field\Sort
	 * @uses           \WEEEOpen\Tarallo\Query\Field\AbstractQueryField
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQuery
	 * @depends        testSortValidSingle
	 */
	public function testSortInvalidDuplicateKey() {
		$this->expectException(InvalidParameterException::class);
		new GetQuery('/Sort/+foo,-foo');
	}

	/**
	 * @uses           \WEEEOpen\Tarallo\Query\GetQuery
	 * @covers         \WEEEOpen\Tarallo\Query\Field\Sort
	 * @uses           \WEEEOpen\Tarallo\Query\Field\AbstractQueryField
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQuery
	 * @depends        testSortInvalidDuplicateKey
	 */
	public function testSortInvalidDuplicateKeyAlternative() {
		$this->expectException(InvalidParameterException::class);
		new GetQuery('/Sort/+foo,+foo');
	}
}