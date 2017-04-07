<?php
namespace WEEEOpen\Tarallo\Test\Query;

use PHPUnit\Framework\TestCase;
use WEEEOpen\Tarallo\InvalidParameterException;
use WEEEOpen\Tarallo\Query\GetQuery;

class SearchTest extends TestCase{
	/**
	 * @covers         \WEEEOpen\Tarallo\Query\GetQuery
	 * @covers         \WEEEOpen\Tarallo\Query\Field\Search
	 * @uses           \WEEEOpen\Tarallo\Query\Field\AbstractQueryField
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQuery
	 * @uses           \WEEEOpen\Tarallo\Query\SearchTriplet
	 */
	public function testInvalidSearchNoKey() {
		$this->expectException(InvalidParameterException::class);
		new GetQuery('/Search/key=');
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Query\GetQuery
	 * @covers         \WEEEOpen\Tarallo\Query\Field\Search
	 * @uses           \WEEEOpen\Tarallo\Query\Field\AbstractQueryField
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQuery
	 * @uses           \WEEEOpen\Tarallo\Query\SearchTriplet
	 */
	public function testInvalidSearchNoValue() {
		$this->expectException(InvalidParameterException::class);
		new GetQuery('/Search/=value');
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Query\GetQuery
	 * @covers         \WEEEOpen\Tarallo\Query\Field\Search
	 * @uses           \WEEEOpen\Tarallo\Query\Field\AbstractQueryField
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQuery
	 * @uses           \WEEEOpen\Tarallo\Query\SearchTriplet
	 * @uses           \WEEEOpen\Tarallo\Query\Field\Multifield
	 */
	public function testSearchValid() {
		$this->assertEquals('/Search/foo=bar', new GetQuery('/Search/foo=bar'));
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Query\GetQuery
	 * @covers         \WEEEOpen\Tarallo\Query\Field\Search
	 * @uses           \WEEEOpen\Tarallo\Query\Field\AbstractQueryField
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQuery
	 * @uses           \WEEEOpen\Tarallo\Query\SearchTriplet
	 * @uses           \WEEEOpen\Tarallo\Query\Field\Multifield
	 */
	public function testSearchValidDouble() {
		$this->assertEquals('/Search/foo=bar/Search/other=stuff', new GetQuery('/Search/foo=bar/Search/other=stuff'));
	}
}