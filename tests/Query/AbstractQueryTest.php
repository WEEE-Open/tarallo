<?php
namespace WEEEOpen\Tarallo\Test\Query;

use PHPUnit\Framework\TestCase;
use WEEEOpen\Tarallo\Query\GetQuery;


class AbstractQueryTest extends TestCase{
	/**
	 * @dataProvider providerTestQueryStringNormalization
	 *
	 * @param string $in query string
	 * @param string $expected expected result from __toString()
	 *
	 * @covers       \WEEEOpen\Tarallo\Query\AbstractQuery
	 * @uses         \WEEEOpen\Tarallo\Query\GetQuery
	 * @uses         \WEEEOpen\Tarallo\Query\QueryFieldLocation
	 * @uses         \WEEEOpen\Tarallo\Query\QueryFieldMultifield
	 * @uses         \WEEEOpen\Tarallo\Query\QueryFieldDepth
	 * @uses         \WEEEOpen\Tarallo\Query\AbstractQueryField
	 */
	public function testQueryStringNormalization($in, $expected) {
		$this->assertEquals((string) (new GetQuery())->fromString($in), $expected);
	}

	public function providerTestQueryStringNormalization() {
		return [
			['Location/test/', '/Location/test'],
			['/Location/test/', '/Location/test'],
			['/Location/test', '/Location/test'],
			['Location/test', '/Location/test'],
			['Location/test/Depth/2/', '/Location/test/Depth/2'],
			['/Location/test/Depth/2/', '/Location/test/Depth/2'],
			['/Location/test/Depth/2', '/Location/test/Depth/2'],
			['Location/test/Depth/2', '/Location/test/Depth/2'],
		];
	}
}