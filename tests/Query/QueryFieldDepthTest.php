<?php
namespace WEEEOpen\Tarallo\Test\Query;

use PHPUnit\Framework\TestCase;
use WEEEOpen\Tarallo\Query\Query;

class QueryFieldDepthTest extends TestCase{
	/**
	 * @covers         \WEEEOpen\Tarallo\Query\Query
	 * @uses           \WEEEOpen\Tarallo\Query\QueryFieldLocation
	 * @uses           \WEEEOpen\Tarallo\Query\QueryFieldMultifield
	 * @covers         \WEEEOpen\Tarallo\Query\QueryFieldDepth
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQueryField
	 * @uses           \WEEEOpen\Tarallo\Query\QueryFieldSinglefield
	 */
	public function testInvalidDepthNaN() {
		$this->expectException(\InvalidArgumentException::class);
		(new Query())->fromString('/Location/test/Depth/foo', 'GET');
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Query\Query
	 * @uses           \WEEEOpen\Tarallo\Query\QueryFieldLocation
	 * @uses           \WEEEOpen\Tarallo\Query\QueryFieldMultifield
	 * @covers         \WEEEOpen\Tarallo\Query\QueryFieldDepth
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQueryField
	 * @uses           \WEEEOpen\Tarallo\Query\QueryFieldSinglefield
	 */
	public function testInvalidDepthNegative() {
		$this->expectException(\InvalidArgumentException::class);
		(new Query())->fromString('/Location/test/Depth/-1', 'GET');
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Query\Query
	 * @covers         \WEEEOpen\Tarallo\Query\QueryFieldDepth
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQueryField
	 * @uses           \WEEEOpen\Tarallo\Query\QueryFieldSinglefield
	 * @todo            Implement validation and remove "skipped"
	 */
	public function testInvalidDepthNoOtherFields() {
		$this->expectException(\InvalidArgumentException::class);
		(new Query())->fromString('/Depth/3', 'GET');
		$this->markTestSkipped('Validation to be implemented');
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Query\Query
	 * @uses           \WEEEOpen\Tarallo\Query\QueryFieldLocation
	 * @uses           \WEEEOpen\Tarallo\Query\QueryFieldMultifield
	 * @covers         \WEEEOpen\Tarallo\Query\QueryFieldDepth
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQueryField
	 * @uses           \WEEEOpen\Tarallo\Query\QueryFieldSinglefield
	 */
	public function testDepthZeroDefault() {
		$this->assertEquals((string) (new Query())->fromString('/Location/test/Depth/0', 'GET'), '/Location/test',
			'Depth=0 is default, ignore it when casting to string');
	}
}