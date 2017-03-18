<?php

namespace WEEEOpen\Tarallo\Test\Query;

use PHPUnit\Framework\TestCase;
use WEEEOpen\Tarallo\Query\Query;

class QueryTest extends TestCase {
	/**
	 * @covers \WEEEOpen\Tarallo\Query\Query
	 * @uses   \WEEEOpen\Tarallo\Query\QueryFieldLocation
	 * @uses   \WEEEOpen\Tarallo\Query\QueryFieldMultifield
	 */
	public function testBuildTwice() {
		$this->expectException(\LogicException::class);
		(new Query())->fromString('/Location/test', 'GET')->fromString('/Location/test', 'GET');
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Query\Query
	 */
	public function testEmptyQueryString() {
		$this->expectException(\InvalidArgumentException::class);
		(new Query())->fromString('', 'GET');
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Query\Query
	 * @uses   \WEEEOpen\Tarallo\Query\QueryFieldLocation
	 * @uses   \WEEEOpen\Tarallo\Query\QueryFieldMultifield
	 */
	public function testInvalidMethod() {
		$this->expectException(\InvalidArgumentException::class);
		(new Query())->fromString('/Location/test', 'PATCH');
	}

	/**
	 * @dataProvider providerTestQueryStringNormalization
	 *
	 * @param string $in query string
	 * @param string $expected expected result from __toString()
	 *
	 * @covers       \WEEEOpen\Tarallo\Query\Query
	 * @uses         \WEEEOpen\Tarallo\Query\QueryFieldLocation
	 * @uses         \WEEEOpen\Tarallo\Query\QueryFieldMultifield
	 * @uses         \WEEEOpen\Tarallo\Query\QueryFieldDepth
	 * @uses         \WEEEOpen\Tarallo\Query\AbstractQueryField
	 * @uses         \WEEEOpen\Tarallo\Query\QueryFieldSinglefield
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

	/**
	 * @dataProvider   providerTestUnchangedValidNonDefaultStrings
	 *
	 * @covers         \WEEEOpen\Tarallo\Query\Query
	 * @covers         \WEEEOpen\Tarallo\Query\QueryFieldLocation
	 * @covers         \WEEEOpen\Tarallo\Query\QueryFieldDepth
	 * @covers         \WEEEOpen\Tarallo\Query\QueryFieldLanguage
	 * @covers         \WEEEOpen\Tarallo\Query\QueryFieldParent
	 * @covers         \WEEEOpen\Tarallo\Query\QueryFieldSearch
	 * @covers         \WEEEOpen\Tarallo\Query\QueryFieldSort
	 * @covers         \WEEEOpen\Tarallo\Query\QueryFieldToken
	 * @covers         \WEEEOpen\Tarallo\Query\AbstractQueryField
	 * @uses           \WEEEOpen\Tarallo\Query\QueryFieldMultifield
	 * @uses           \WEEEOpen\Tarallo\Query\QueryFieldSinglefield
	 *
	 * @param $string query string
	 */
	public function testUnchangedValidNonDefaultStrings($string) {
		$this->assertEquals((string) (new Query())->fromString($string, 'GET'), $string);
	}


	public function providerTestUnchangedValidNonDefaultStrings() {
		return [
			['/Location/test'],
			['/Location/test/Depth/3'],
			['/Location/test/Language/fr-FR'],
			['/Location/test/Parent/2'],
			['/Location/test/Search/key=foo'],
			['/Location/test/Sort/+key'],
			['/Location/test/Token/foo'],
		];
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Query\Query
	 * @covers         \WEEEOpen\Tarallo\Query\QueryFieldMultifield
	 * @uses           \WEEEOpen\Tarallo\Query\QueryFieldLocation
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQueryField
	 */
	public function testMultipleFields() {
		$this->assertEquals((string) (new Query())->fromString('/Location/foo/Location/bar', 'GET'),
			'/Location/foo/Location/bar');
	}
}