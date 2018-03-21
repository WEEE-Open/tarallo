<?php

use PHPUnit\Framework\TestCase;
use WEEEOpen\Tarallo\Server\User;

class UserTest extends TestCase {
	/**
	 * @covers \WEEEOpen\Tarallo\Server\User
	 */
	public function testValidUserNullPasswordAndHash() {
		$user = new User('asd', null, null);
		$this->assertEquals('asd', (string) $user);
		$this->assertEquals('asd', $user->getUsername());

		return $user;
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\User
	 */
	public function testReadHashNotAvailable() {
		$user = new User('asd');
		$this->expectException(LogicException::class);
		$user->getHash();
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\User
	 */
	public function testEmptyUsername() {
		$this->expectException(InvalidArgumentException::class);
		new User('', null, null);
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\User
	 */
	public function testNullUsername() {
		$this->expectException(InvalidArgumentException::class);
		new User(null, null, null);
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\User
	 */
	public function testEmptyPasswordAndHash() {
		$this->expectException(InvalidArgumentException::class);
		new User('asd', '', '');
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\User
	 */
	public function testValidPasswordNoHash() {
		$user = new User('asd', 'asd', null);
		$this->assertEquals('asd', (string) $user);
		$this->assertEquals('asd', $user->getPassword());
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\User
	 */
	public function testWrongTypePassword() {
		$this->expectException(InvalidArgumentException::class);
		new User('asd', ['foo' => 'bar'], null);
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\User
	 */
	public function testEmptyHash() {
		$this->expectException(InvalidArgumentException::class);
		new User('asd', null, '');
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\User
	 */
	public function testValidHashNoPassword() {
		$this->assertEquals('asd',
			(string) new User('asd', null, '$2y$10$wXP1ooxhHQ2X63Rgxi8GZeHkotzjwW2/M3HX/so1bwal4zDhsMyW6'));
	}


	/**
	 * @covers \WEEEOpen\Tarallo\Server\User
	 */
	public function testReadPasswordNotAvailable() {
		$this->expectException(\LogicException::class);
		(new User('asd', null, '$2y$10$wXP1ooxhHQ2X63Rgxi8GZeHkotzjwW2/M3HX/so1bwal4zDhsMyW6'))->getPassword();
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\User
	 */
	public function testPasswordMatch() {
		$user = new User('asd123', 'asd', '$2y$10$wXP1ooxhHQ2X63Rgxi8GZeHkotzjwW2/M3HX/so1bwal4zDhsMyW6');
		$this->assertEquals('asd123', (string) $user);
		$this->assertEquals('asd', $user->getPassword());
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\User
	 */
	public function testPasswordMismatch() {
		$this->expectException(\InvalidArgumentException::class);
		new User('asd123', 'wrong-password', '$2y$10$wXP1ooxhHQ2X63Rgxi8GZeHkotzjwW2/M3HX/so1bwal4zDhsMyW6');
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\User
	 */
	public function testPasswordHashCalculate() {
		$user = new User('asd', 'asd', null);
		$hash = $user->getHash(); // uses random salt, cannot compare string directly
		$this->assertTrue(is_string($hash));
		$user2 = new User('asd', 'asd', $hash);
		$this->assertEquals('asd', (string) $user2); // should not throw

		return [$user2, $hash];
	}
}
