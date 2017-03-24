<?php
namespace WEEEOpen\Tarallo\Test\Query;

use PHPUnit\Framework\TestCase;
use WEEEOpen\Tarallo\Query\GetQuery;

class QueryFieldSortTest extends TestCase{
	/**
	 * @uses           \WEEEOpen\Tarallo\Query\GetQuery
	 * @covers         \WEEEOpen\Tarallo\Query\QueryFieldSort
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQueryField
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQuery
	 */
	public function testInvalidSortNoKey() {
		$this->expectException(\InvalidArgumentException::class);
		(new GetQuery())->fromString('/Sort/+');
	}

	/**
	 * @uses           \WEEEOpen\Tarallo\Query\GetQuery
	 * @covers         \WEEEOpen\Tarallo\Query\QueryFieldSort
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQueryField
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQuery
	 */
	public function testInvalidSortNoOrder() {
		$this->expectException(\InvalidArgumentException::class);
		(new GetQuery())->fromString('/Sort/key');
	}

	/**
	 * @uses           \WEEEOpen\Tarallo\Query\GetQuery
	 * @covers         \WEEEOpen\Tarallo\Query\QueryFieldSort
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQueryField
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQuery
	 * @depends        testInvalidSortNoKey
	 */
	public function testInvalidSortNoKeyDouble() {
		$this->expectException(\InvalidArgumentException::class);
		(new GetQuery())->fromString('/Sort/+key,foo');
	}

	/**
	 * @uses           \WEEEOpen\Tarallo\Query\GetQuery
	 * @covers         \WEEEOpen\Tarallo\Query\QueryFieldSort
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQueryField
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQuery
	 * @depends        testInvalidSortNoOrder
	 */
	public function testInvalidSortNoOrderDouble() {
		$this->expectException(\InvalidArgumentException::class);
		(new GetQuery())->fromString('/Sort/+key,+');
	}

	/**
	 * @uses           \WEEEOpen\Tarallo\Query\GetQuery
	 * @covers         \WEEEOpen\Tarallo\Query\QueryFieldSort
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQueryField
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQuery
	 * @depends        testInvalidSortNoKey
	 */
	public function testInvalidSortNoKeyDoubleReverse() {
		$this->expectException(\InvalidArgumentException::class);
		(new GetQuery())->fromString('/Sort/foo,+key');
	}

	/**
	 * @uses           \WEEEOpen\Tarallo\Query\GetQuery
	 * @covers         \WEEEOpen\Tarallo\Query\QueryFieldSort
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQueryField
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQuery
	 * @depends        testInvalidSortNoOrder
	 */
	public function testInvalidSortNoOrderDoubleReverse() {
		$this->expectException(\InvalidArgumentException::class);
		(new GetQuery())->fromString('/Sort/+,+key');
	}

	/**
	 * @uses           \WEEEOpen\Tarallo\Query\GetQuery
	 * @covers         \WEEEOpen\Tarallo\Query\QueryFieldSort
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQueryField
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQuery
	 */
	public function testSortValidSingle() {
		$this->assertEquals((new GetQuery())->fromString('/Sort/+foo'), '/Sort/+foo');
	}

	/**
	 * @uses           \WEEEOpen\Tarallo\Query\GetQuery
	 * @covers         \WEEEOpen\Tarallo\Query\QueryFieldSort
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQueryField
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQuery
	 * @depends        testSortValidSingle
	 */
	public function testSortValidDouble() {
		$this->assertEquals((new GetQuery())->fromString('/Sort/+foo,-bar'), '/Sort/+foo,-bar');
	}

	/**
	 * @uses           \WEEEOpen\Tarallo\Query\GetQuery
	 * @covers         \WEEEOpen\Tarallo\Query\QueryFieldSort
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQueryField
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQuery
	 * @depends        testSortValidSingle
	 */
	public function testSortInvalidDuplicateKey() {
		$this->expectException(\InvalidArgumentException::class);
		(new GetQuery())->fromString('/Sort/+foo,-foo');
	}

	/**
	 * @uses           \WEEEOpen\Tarallo\Query\GetQuery
	 * @covers         \WEEEOpen\Tarallo\Query\QueryFieldSort
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQueryField
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQuery
	 * @depends        testSortInvalidDuplicateKey
	 */
	public function testSortInvalidDuplicateKeyAlternative() {
		$this->expectException(\InvalidArgumentException::class);
		(new GetQuery())->fromString('/Sort/+foo,+foo');
	}
}