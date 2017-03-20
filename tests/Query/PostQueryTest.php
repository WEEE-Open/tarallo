<?php

namespace WEEEOpen\Tarallo\Test\Query;

use PHPUnit\Framework\TestCase;
use WEEEOpen\Tarallo\Query\PostQuery;

/*
 * Why are there tests covering classes other than PostQuery?
 * Well, that functionality could have been implemented in PostQuery, even if
 * it's elsewhere, it should be marked as tested and covered.
 * The important thing is that those query strings should give the expected
 * results (exceptions, etc...), writing a million tests for every method
 * in every abstract class that shouldn't even be accessed durectly by the
 * application seems a bit pointless. Maybe these tests aren't of impeccable
 * quality, but they're better than no tests at all and I can change how
 * PostQuery implements its functionality and see if I've broken anything
 * without rewriting half of the tests, which is the whole point of this
 * effort...
 */

class PostQueryTest extends TestCase {
	/**
	 * @covers \WEEEOpen\Tarallo\Query\PostQuery
	 * @covers \WEEEOpen\Tarallo\Query\AbstractQuery
	 * @uses   \WEEEOpen\Tarallo\Query\QueryFieldEdit
	 * @uses   \WEEEOpen\Tarallo\Query\QueryFieldPostJSON
	 */
	public function testEmptyJSONIsValid() {
		$this->assertEquals((string) (new PostQuery())->fromString('/Edit', '{}'), '{}');
	}

	/**
	 * @covers  \WEEEOpen\Tarallo\Query\PostQuery
	 * @uses    \WEEEOpen\Tarallo\Query\QueryFieldPostJSON
	 * @uses    \WEEEOpen\Tarallo\Query\QueryFieldEdit
	 * @covers  \WEEEOpen\Tarallo\Query\AbstractQuery
	 * @depends testEmptyJSONIsValid
	 */
	public function testBuildTwice() {
		$this->expectException(\LogicException::class);
		(new PostQuery())->fromString('/Edit', '{}')->fromString('/Edit', '{}');
	}

	/**
	 * @covers  \WEEEOpen\Tarallo\Query\PostQuery
	 * @uses    \WEEEOpen\Tarallo\Query\QueryFieldPostJSON
	 * @uses    \WEEEOpen\Tarallo\Query\QueryFieldEdit
	 * @covers  \WEEEOpen\Tarallo\Query\AbstractQuery
	 * @depends testEmptyJSONIsValid
	 */
	public function testDuplicateField() {
		$this->expectException(\InvalidArgumentException::class);
		(new PostQuery())->fromString('/Edit/Edit', '{}');
	}

	/**
	 * @covers  \WEEEOpen\Tarallo\Query\PostQuery
	 * @uses    \WEEEOpen\Tarallo\Query\QueryFieldPostJSON
	 * @uses    \WEEEOpen\Tarallo\Query\QueryFieldEdit
	 * @covers  \WEEEOpen\Tarallo\Query\AbstractQuery
	 */
	public function testToStringWithoutQuery() {
		$this->assertEquals((string) (new PostQuery()), '{}');
	}

	/**
	 * @covers  \WEEEOpen\Tarallo\Query\PostQuery
	 * @uses    \WEEEOpen\Tarallo\Query\QueryFieldPostJSON
	 * @uses    \WEEEOpen\Tarallo\Query\QueryFieldEdit
	 * @uses    \WEEEOpen\Tarallo\Query\QueryFieldLogin
	 * @covers  \WEEEOpen\Tarallo\Query\AbstractQuery
	 * @depends testEmptyJSONIsValid
	 */
	public function testMoreThanOneField() {
		$this->expectException(\InvalidArgumentException::class);
		(new PostQuery())->fromString('/Edit/Login', '{}');
	}

	/**
	 * @covers  \WEEEOpen\Tarallo\Query\PostQuery
	 * @covers  \WEEEOpen\Tarallo\Query\AbstractQuery
	 * @depends testEmptyJSONIsValid
	 */
	public function testUnrecognizedField() {
		$this->expectException(\InvalidArgumentException::class);
		(new PostQuery())->fromString('/NotAField', '{}');
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Query\PostQuery
	 * @uses   \WEEEOpen\Tarallo\Query\QueryFieldPostJSON
	 * @uses   \WEEEOpen\Tarallo\Query\QueryFieldEdit
	 * @covers \WEEEOpen\Tarallo\Query\AbstractQuery
	 */
	public function testEmptyPOSTBody() {
		$this->expectException(\InvalidArgumentException::class);
		(new PostQuery())->fromString('/Edit', '');
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Query\PostQuery
	 * @uses   \WEEEOpen\Tarallo\Query\QueryFieldPostJSON
	 * @uses   \WEEEOpen\Tarallo\Query\QueryFieldEdit
	 * @covers \WEEEOpen\Tarallo\Query\AbstractQuery
	 */
	public function testNullPOSTBody() {
		$this->expectException(\InvalidArgumentException::class);
		(new PostQuery())->fromString('/Edit', null);
	}

	/**
	 * @covers  \WEEEOpen\Tarallo\Query\PostQuery
	 * @uses    \WEEEOpen\Tarallo\Query\QueryFieldPostJSON
	 * @uses    \WEEEOpen\Tarallo\Query\QueryFieldEdit
	 * @covers  \WEEEOpen\Tarallo\Query\AbstractQuery
	 * @depends testEmptyJSONIsValid
	 */
	public function testEmptyQueryStringValidBody() {
		$this->expectException(\InvalidArgumentException::class);
		(new PostQuery())->fromString('', '{}');
	}

	/**
	 * @covers  \WEEEOpen\Tarallo\Query\PostQuery
	 * @uses    \WEEEOpen\Tarallo\Query\QueryFieldPostJSON
	 * @uses    \WEEEOpen\Tarallo\Query\QueryFieldEdit
	 * @covers  \WEEEOpen\Tarallo\Query\AbstractQuery
	 */
	public function testEmptyQueryStringAndBody() {
		$this->expectException(\InvalidArgumentException::class);
		(new PostQuery())->fromString('', '');
	}
}