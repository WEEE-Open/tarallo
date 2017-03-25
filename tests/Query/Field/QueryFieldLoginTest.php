<?php
namespace WEEEOpen\Tarallo\Test\Query;

use PHPUnit\Framework\TestCase;
use WEEEOpen\Tarallo\Query\PostQuery;


class QueryFieldLoginTest extends TestCase{
	/**
	 * @covers         \WEEEOpen\Tarallo\Query\PostQuery
	 * @covers         \WEEEOpen\Tarallo\Query\Field\Login
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQueryField
	 * @uses           \WEEEOpen\Tarallo\Query\AbstractQuery
	 * @uses           \WEEEOpen\Tarallo\Query\Field\PostJSON
	 */
	public function testInvalidLoginNoContent() {
		$this->expectException(\InvalidArgumentException::class);
		(new PostQuery())->fromString('/Login', '');
	}
}