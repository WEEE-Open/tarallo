<?php

namespace WEEEOpen\Tarallo\Test\Query;

use PHPUnit\Framework\TestCase;
use WEEEOpen\Tarallo\Query\GetQuery;

/*
 * Why are there tests covering classes other than GetQuery?
 * Well, that functionality could have been implemented in GetQuery, even if
 * it's elsewhere, it should be marked as tested and covered.
 * The important thing is that those query strings should give the expected
 * results (exceptions, etc...), writing a million tests for every method
 * in every abstract class that shouldn't even be accessed durectly by the
 * application seems a bit pointless. Maybe these tests aren't of impeccable
 * quality, but they're better than no tests at all and I can change how
 * GetQuery implements its functionality and see if I've broken anything
 * without rewriting half of the tests, which is the whole point of this
 * effort...
 */
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
	 * @uses   \WEEEOpen\Tarallo\Query\QueryFieldLocation
	 * @uses   \WEEEOpen\Tarallo\Query\QueryFieldMultifield
	 * @uses   \WEEEOpen\Tarallo\Query\AbstractQuery
	 * @uses   \WEEEOpen\Tarallo\Query\QueryFieldDepth
	 * @covers \WEEEOpen\Tarallo\Query\QueryFieldSinglefield
	 */
	public function testDuplicateField() {
		$this->expectException(\InvalidArgumentException::class);
		(new GetQuery())->fromString('/Depth/2/Depth/6');
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Query\GetQuery
	 * @uses   \WEEEOpen\Tarallo\Query\AbstractQuery
	 */
	public function testUnrecognizedField() {
		$this->expectException(\InvalidArgumentException::class);
		(new GetQuery())->fromString('/NotAField/test');
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