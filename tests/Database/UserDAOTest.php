<?php

namespace WEEEOpen\Tarallo\Server\Test\Database;


class UserDAOTest extends DatabaseTest {
	/**
	 * @covers \WEEEOpen\Tarallo\Server\Database\Database
	 * @covers \WEEEOpen\Tarallo\Server\Database\UserDAO
	 */
	public function testGetUserInvalidSession() {
		$this->assertEquals(null, $this->getDb()->userDAO()->getUserFromSession('foo'));
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\Database\Database
	 * @covers \WEEEOpen\Tarallo\Server\Database\UserDAO
	 */
	public function testGetUserAccountDisabled() {
		$this->assertEquals(null, $this->getDb()->userDAO()->getUserFromSession('this-really-is-a-session-1234567'));
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\Database\Database
	 * @covers \WEEEOpen\Tarallo\Server\Database\UserDAO
	 */
	public function testGetUserAccountExpiredSession() {
		$this->assertEquals(null, $this->getDb()->userDAO()->getUserFromSession('this-really-is-a-session-7654321'));
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\Database\Database
	 * @covers \WEEEOpen\Tarallo\Server\Database\UserDAO
	 */
	public function testGetUserAccountValidSession() {
		$this->assertEquals('asd-valid',
			(string) $this->getDb()->userDAO()->getUserFromSession('this-really-is-a-valid-session-1'));
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\Database\Database
	 * @covers \WEEEOpen\Tarallo\Server\Database\UserDAO
	 */
	public function testGetUserFromLoginValid() {
		$this->assertEquals('asd', (string) $this->getDb()->userDAO()->getUserFromLogin('asd', 'asd'));
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\Database\Database
	 * @covers \WEEEOpen\Tarallo\Server\Database\UserDAO
	 */
	public function testGetUserFromLoginDisabledAccount() {
		$this->assertEquals(null, (string) $this->getDb()->userDAO()->getUserFromLogin('asd-disabled', 'asd'));
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\Database\Database
	 * @covers \WEEEOpen\Tarallo\Server\Database\UserDAO
	 */
	public function testGetUserFromLoginWrongPassword() {
		$this->assertEquals(null, (string) $this->getDb()->userDAO()->getUserFromLogin('asd', 'wrong'));
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\Database\Database
	 * @covers \WEEEOpen\Tarallo\Server\Database\UserDAO
	 */
	public function testUserLoginLogout() {
		$this->assertEquals(null, $this->getDb()->userDAO()->getUserFromSession('session-started-in-test-12345678'));
		$this->getDb()->userDAO()->setSessionFromUser('asd', 'session-started-in-test-12345678');
		$this->assertEquals('asd',
			(string) $this->getDb()->userDAO()->getUserFromSession('session-started-in-test-12345678'));
		$this->getDb()->userDAO()->setSessionFromUser('asd', null);
		$this->assertEquals(null, $this->getDb()->userDAO()->getUserFromSession('session-started-in-test-12345678'));
	}

}
