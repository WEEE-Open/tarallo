<?php

namespace WEEEOpen\Tarallo\Test\Query;

use PHPUnit\Framework\TestCase;
use WEEEOpen\Tarallo\Query\AbstractQuery;
use WEEEOpen\Tarallo\Query\LoginQuery;
use WEEEOpen\Tarallo\Query\PostJSONQuery;
use WEEEOpen\Tarallo;
use WEEEOpen\Tarallo\InvalidParameterException;

/*
 * Why are there tests covering classes other than PostJSONQuery?
 * Well, that functionality could have been implemented in PostJSONQuery, even if
 * it's elsewhere, it should be marked as tested and covered.
 * The important thing is that those query strings should give the expected
 * results (exceptions, etc...), writing a million tests for every method
 * in every abstract class that shouldn't even be accessed durectly by the
 * application seems a bit pointless. Maybe these tests aren't of impeccable
 * quality, but they're better than no tests at all and I can change how
 * PostJSONQuery implements its functionality and see if I've broken anything
 * without rewriting half of the tests, which is the whole point of this
 * effort...
 */

class PostJSONQueryTest extends TestCase {
	/**
	 * @covers \WEEEOpen\Tarallo\Query\PostJSONQuery
	 * @uses   \WEEEOpen\Tarallo\Query\AbstractQuery
	 * @covers \WEEEOpen\Tarallo\Query\LoginQuery
	 * @uses   \WEEEOpen\Tarallo\User
	 */
	public function testNullDatabaseError() {
		$this->expectException(\TypeError::class);
		(new LoginQuery())->fromString('{"username": "test", "password": "test"}')->run(new Tarallo\User('example', 'example'), null);
	}

	/**
	 * @covers  \WEEEOpen\Tarallo\Query\PostJSONQuery
	 * @covers  \WEEEOpen\Tarallo\Query\AbstractQuery
	 */
	public function testUnrecognizedField() {
		$this->expectException(InvalidParameterException::class);
		AbstractQuery::factory('POST', '/NotAField', '');
	}
}