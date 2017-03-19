<?php

namespace WEEEOpen\Tarallo\Test\Query;

use PHPUnit\Framework\TestCase;
use WEEEOpen\Tarallo\Query\GetQuery;

class GetQueryTest extends TestCase {
	/**
	 * @covers \WEEEOpen\Tarallo\Query\GetQuery
	 * @uses   \WEEEOpen\Tarallo\Query\QueryFieldLocation
	 * @uses   \WEEEOpen\Tarallo\Query\QueryFieldMultifield
	 * @covers \WEEEOpen\Tarallo\Query\AbstractQuery
	 */
	public function testBuildTwice() {
		$this->expectException(\LogicException::class);
		(new GetQuery())->fromString('/Location/test')->fromString('/Location/test');
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Query\GetQuery
	 * @uses   \WEEEOpen\Tarallo\Query\AbstractQuery
	 */
	public function testEmptyQueryString() {
		$this->expectException(\InvalidArgumentException::class);
		(new GetQuery())->fromString('');
	}

	/**
	 * @dataProvider   providerTestUnchangedValidNonDefaultStrings
	 *
	 * @covers         \WEEEOpen\Tarallo\Query\GetQuery
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
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQuery
	 *
	 * @param $string GetQuery string
	 */
	public function testUnchangedValidNonDefaultStrings($string) {
		$this->assertEquals((string) (new GetQuery())->fromString($string), $string);
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
	 * @covers         \WEEEOpen\Tarallo\Query\GetQuery
	 * @covers         \WEEEOpen\Tarallo\Query\QueryFieldMultifield
	 * @uses           \WEEEOpen\Tarallo\Query\QueryFieldLocation
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQueryField
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQuery
	 */
	public function testMultipleFields() {
		$this->assertEquals((string) (new GetQuery())->fromString('/Location/foo/Location/bar'),
			'/Location/foo/Location/bar');
	}
}