<?php
namespace WEEEOpen\Tarallo\Test\Query;

use PHPUnit\Framework\TestCase;
use WEEEOpen\Tarallo\InvalidParameterException;
use WEEEOpen\Tarallo\Query\GetQuery;

class DepthTest extends TestCase{
	/**
	 * @covers         \WEEEOpen\Tarallo\Query\GetQuery
	 * @uses           \WEEEOpen\Tarallo\Query\Field\Code
	 * @uses           \WEEEOpen\Tarallo\Query\Field\Multifield
	 * @covers         \WEEEOpen\Tarallo\Query\Field\Depth
	 * @uses           \WEEEOpen\Tarallo\Query\Field\AbstractQueryField
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQuery
	 */
	public function testInvalidDepthNaN() {
		$this->expectException(InvalidParameterException::class);
		new GetQuery('/Code/test/Depth/foo');
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Query\GetQuery
	 * @uses           \WEEEOpen\Tarallo\Query\Field\Code
	 * @uses           \WEEEOpen\Tarallo\Query\Field\Multifield
	 * @covers         \WEEEOpen\Tarallo\Query\Field\Depth
	 * @uses           \WEEEOpen\Tarallo\Query\Field\AbstractQueryField
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQuery
	 */
	public function testInvalidDepthNegative() {
		$this->expectException(InvalidParameterException::class);
		new GetQuery('/Code/test/Depth/-1');
	}
}