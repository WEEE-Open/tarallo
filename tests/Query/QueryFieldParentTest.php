<?php
namespace WEEEOpen\Tarallo\Test\Query;

use PHPUnit\Framework\TestCase;
use WEEEOpen\Tarallo\Query\GetQuery;

class QueryFieldParentTest extends TestCase{
	/**
	 * @covers         \WEEEOpen\Tarallo\Query\GetQuery
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQuery
	 * @uses           \WEEEOpen\Tarallo\Query\QueryFieldLocation
	 * @uses           \WEEEOpen\Tarallo\Query\QueryFieldMultifield
	 * @covers         \WEEEOpen\Tarallo\Query\QueryFieldParent
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQueryField
	 */
	public function testInvalidParentNaN() {
		$this->expectException(\InvalidArgumentException::class);
		(new GetQuery())->fromString('/Location/test/Parent/foo');
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Query\GetQuery
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQuery
	 * @uses           \WEEEOpen\Tarallo\Query\QueryFieldLocation
	 * @uses           \WEEEOpen\Tarallo\Query\QueryFieldMultifield
	 * @covers         \WEEEOpen\Tarallo\Query\QueryFieldParent
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQueryField
	 */
	public function testInvalidParentNegative() {
		$this->expectException(\InvalidArgumentException::class);
		(new GetQuery())->fromString('/Location/test/Parent/-1');
	}

	//public function testParentZeroDefault() {
	//	$this->assertEquals((string) (new GetQuery())->fromString('/Location/test/Parent/0'), '/Location/test',
	//		'Parent=0 is default, ignore it when casting to string');
	//}
}