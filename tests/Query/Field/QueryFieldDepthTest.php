<?php
namespace WEEEOpen\Tarallo\Test\Query;

use PHPUnit\Framework\TestCase;
use WEEEOpen\Tarallo\Query\GetQuery;

class QueryFieldDepthTest extends TestCase{
	/**
	 * @covers         \WEEEOpen\Tarallo\Query\GetQuery
	 * @uses           \WEEEOpen\Tarallo\Query\Field\Location
	 * @uses           \WEEEOpen\Tarallo\Query\Field\Multifield
	 * @covers         \WEEEOpen\Tarallo\Query\Field\Depth
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQueryField
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQuery
	 */
	public function testInvalidDepthNaN() {
		$this->expectException(\InvalidArgumentException::class);
		(new GetQuery())->fromString('/Location/test/Depth/foo');
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Query\GetQuery
	 * @uses           \WEEEOpen\Tarallo\Query\Field\Location
	 * @uses           \WEEEOpen\Tarallo\Query\Field\Multifield
	 * @covers         \WEEEOpen\Tarallo\Query\Field\Depth
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQueryField
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQuery
	 */
	public function testInvalidDepthNegative() {
		$this->expectException(\InvalidArgumentException::class);
		(new GetQuery())->fromString('/Location/test/Depth/-1');
	}


	//public function testDepthZeroDefault() {
	//	$this->assertEquals((string) (new GetQuery())->fromString('/Location/test/Depth/0'), '/Location/test',
	//		'Depth=0 is default, ignore it when casting to string');
	//}
}