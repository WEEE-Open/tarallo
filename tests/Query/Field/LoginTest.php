<?php
namespace WEEEOpen\Tarallo\Test\Query;

use PHPUnit\Framework\TestCase;
use WEEEOpen\Tarallo\InvalidParameterException;
use WEEEOpen\Tarallo\Query\PostQuery;


class LoginTest extends TestCase{
	/**
	 * @covers         \WEEEOpen\Tarallo\Query\PostQuery
	 * @covers         \WEEEOpen\Tarallo\Query\Field\Login
	 * @uses           \WEEEOpen\Tarallo\Query\Field\AbstractQueryField
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQuery
	 * @uses           \WEEEOpen\Tarallo\Query\Field\PostJSON
	 */
	public function testInvalidLoginNoContent() {
		$this->expectException(InvalidParameterException::class);
		(new PostQuery())->fromString('/Login', '');
	}
}