<?php

namespace WEEEOpen\Tarallo\Test\Query;

use PHPUnit\Framework\TestCase;
use WEEEOpen\Tarallo\Query\Query;

class QueryTest extends TestCase {
	public function testBuildTwice() {
		$this->expectException(\LogicException::class);
		(new Query())->fromString('/Location/test', 'GET')->fromString('/Location/test', 'GET');
	}

	public function testEmptyQueryString() {
		$this->expectException(\InvalidArgumentException::class);
		(new Query())->fromString('', 'GET');
	}

	public function testInvalidMethod() {
		$this->expectException(\InvalidArgumentException::class);
		(new Query())->fromString('', 'PATCH');
	}

	/**
	 * @dataProvider providerTestQueryStringNormalization
	 *
	 * @param string $in query string
	 * @param string $expected expected result from __toString()
	 */
	public function testQueryStringNormalization($in, $expected) {
		$this->assertEquals((string) (new Query())->fromString($in, 'GET'), $expected);
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