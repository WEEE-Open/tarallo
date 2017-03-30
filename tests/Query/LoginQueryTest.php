<?php
namespace WEEEOpen\Tarallo\Test\Query;

use PHPUnit\Framework\TestCase;
use WEEEOpen\Tarallo\InvalidParameterException;
use WEEEOpen\Tarallo\Query\LoginQuery;


class LoginQueryTest extends TestCase{
	/**
	 * @covers         \WEEEOpen\Tarallo\Query\PostJSONQuery
	 * @covers         \WEEEOpen\Tarallo\Query\LoginQuery
	 * @uses           \WEEEOpen\Tarallo\Query\Field\AbstractQueryField
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQuery
	 * @uses           \WEEEOpen\Tarallo\Query\PostJSONQuery
	 */
	public function testInvalidLoginNull() {
		$this->expectException(InvalidParameterException::class);
		(new LoginQuery())->fromString(null);
	}

	/**
	 * @covers         \WEEEOpen\Tarallo\Query\PostJSONQuery
	 * @covers         \WEEEOpen\Tarallo\Query\LoginQuery
	 * @uses           \WEEEOpen\Tarallo\Query\Field\AbstractQueryField
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQuery
	 * @uses           \WEEEOpen\Tarallo\Query\PostJSONQuery
	 */
	public function testInvalidLoginEmptyString() {
		$this->expectException(InvalidParameterException::class);
		(new LoginQuery())->fromString('');
	}
}