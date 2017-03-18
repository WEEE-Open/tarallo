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

	public function testInvalidDepthNaN() {
		$this->expectException(\InvalidArgumentException::class);
		(new Query())->fromString('/Location/test/Depth/foo', 'GET');
	}

	public function testInvalidDepthNegative() {
		$this->expectException(\InvalidArgumentException::class);
		(new Query())->fromString('/Location/test/Depth/-1', 'GET');
	}

	public function testInvalidDepthNoOtherFields() {
		$this->expectException(\InvalidArgumentException::class);
		(new Query())->fromString('/Depth/3', 'GET');
	}

	public function testDepthZeroDefault() {
		$this->assertEquals((string) (new Query())->fromString('/Location/test/Depth/0', 'GET'), '/Location/test', 'Depth=0 is default');
	}

	/**
	 * @dataProvider providerTestUnchangedValidNonDefaultStrings
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
}