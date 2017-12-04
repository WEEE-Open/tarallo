<?php
namespace WEEEOpen\Tarallo\Test\Query;

use PHPUnit\Framework\TestCase;
use WEEEOpen\Tarallo\InvalidParameterException;
use WEEEOpen\Tarallo\Query\GetQuery;

class ParentTest extends TestCase{
	/**
	 * @covers         \WEEEOpen\Tarallo\Query\GetQuery
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQuery
	 * @uses           \WEEEOpen\Tarallo\Query\Field\Code
	 * @uses           \WEEEOpen\Tarallo\Query\Field\Multifield
	 * @covers         \WEEEOpen\Tarallo\Query\Field\ParentField
	 * @uses           \WEEEOpen\Tarallo\Query\Field\AbstractQueryField
	 */
	public function testInvalidParentNaN() {
		$this->expectException(InvalidParameterException::class);
		new GetQuery('/Code/test/Parent/foo');
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Query\GetQuery
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQuery
	 * @uses           \WEEEOpen\Tarallo\Query\Field\Code
	 * @uses           \WEEEOpen\Tarallo\Query\Field\Multifield
	 * @covers         \WEEEOpen\Tarallo\Query\Field\ParentField
	 * @uses           \WEEEOpen\Tarallo\Query\Field\AbstractQueryField
	 */
	public function testInvalidParentNegative() {
		$this->expectException(InvalidParameterException::class);
		new GetQuery('/Code/test/Parent/-1');
	}
}