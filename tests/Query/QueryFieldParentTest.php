<?php
namespace WEEEOpen\Tarallo\Test\Query;

use PHPUnit\Framework\TestCase;
use WEEEOpen\Tarallo\Query\Query;

class QueryFieldParentTest extends TestCase{
	/**
	 * @covers         \WEEEOpen\Tarallo\Query\Query
	 * @uses           \WEEEOpen\Tarallo\Query\QueryFieldLocation
	 * @uses           \WEEEOpen\Tarallo\Query\QueryFieldMultifield
	 * @covers         \WEEEOpen\Tarallo\Query\QueryFieldParent
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQueryField
	 * @uses           \WEEEOpen\Tarallo\Query\QueryFieldSinglefield
	 */
	public function testInvalidParentNaN() {
		$this->expectException(\InvalidArgumentException::class);
		(new Query())->fromString('/Location/test/Parent/foo', 'GET');
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Query\Query
	 * @uses           \WEEEOpen\Tarallo\Query\QueryFieldLocation
	 * @uses           \WEEEOpen\Tarallo\Query\QueryFieldMultifield
	 * @covers         \WEEEOpen\Tarallo\Query\QueryFieldParent
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQueryField
	 * @uses           \WEEEOpen\Tarallo\Query\QueryFieldSinglefield
	 */
	public function testInvalidParentNegative() {
		$this->expectException(\InvalidArgumentException::class);
		(new Query())->fromString('/Location/test/Parent/-1', 'GET');
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Query\Query
	 * @covers         \WEEEOpen\Tarallo\Query\QueryFieldParent
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQueryField
	 * @uses           \WEEEOpen\Tarallo\Query\QueryFieldSinglefield
	 * @todo            Implement validation and remove "skipped"
	 */
	public function testInvalidParentNoOtherFields() {
		$this->expectException(\InvalidArgumentException::class);
		(new Query())->fromString('/Parent/3', 'GET');
		$this->markTestSkipped('Validation to be implemented');
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Query\Query
	 * @uses           \WEEEOpen\Tarallo\Query\QueryFieldLocation
	 * @uses           \WEEEOpen\Tarallo\Query\QueryFieldMultifield
	 * @covers         \WEEEOpen\Tarallo\Query\QueryFieldParent
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQueryField
	 * @uses           \WEEEOpen\Tarallo\Query\QueryFieldSinglefield
	 */
	public function testParentZeroDefault() {
		$this->assertEquals((string) (new Query())->fromString('/Location/test/Parent/0', 'GET'), '/Location/test',
			'Parent=0 is default, ignore it when casting to string');
	}
}