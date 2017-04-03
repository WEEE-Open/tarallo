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
	 * @uses         \WEEEOpen\Tarallo\Query\Field\Location
	 * @uses         \WEEEOpen\Tarallo\Query\Field\Multifield
	 * @uses         \WEEEOpen\Tarallo\Query\Field\Depth
	 * @uses         \WEEEOpen\Tarallo\Query\Field\AbstractQueryField
	 */
	public function testQueryStringNormalization($in, $expected) {
		$this->assertEquals($expected, (string) new GetQuery($in));
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