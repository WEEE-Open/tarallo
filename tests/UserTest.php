<?php
use PHPUnit\Framework\TestCase;
use WEEEOpen\Tarallo\User;
use WEEEOpen\Tarallo\InvalidParameterException;

class UserTest extends TestCase {
    /**
     * @covers \WEEEOpen\Tarallo\User
     */
    public function testValidUserNullPasswordAndHash() {
        $user = new User('asd', null, null);
        $this->assertEquals('asd', (string) $user);
        $this->assertEquals('asd', $user->getUsername());
    }


    /**
     * @covers \WEEEOpen\Tarallo\User
     * @depends testValidUserNullPasswordAndHash
     */
    public function testEmptyUsername() {
        $this->expectException(InvalidParameterException::class);
        new User('', null, null);
    }

    /**
     * @covers \WEEEOpen\Tarallo\User
     * @depends testValidUserNullPasswordAndHash
     */
    public function testNullUsername() {
        $this->expectException(InvalidParameterException::class);
        new User(null, null, null);
    }

    /**
     * @covers \WEEEOpen\Tarallo\User
     */
    public function testEmptyPasswordAndHash() {
        $this->expectException(InvalidParameterException::class);
        new User('asd', '', '');
    }

    /**
     * @covers \WEEEOpen\Tarallo\User
     * @depends testValidUserNullPasswordAndHash
     */
    public function testValidPasswordNoHash() {
        $user = new User('asd', 'asd', null);
        $this->assertEquals('asd', (string) $user);
        $this->assertEquals('asd', $user->getPassword());
    }

    /**
     * @covers \WEEEOpen\Tarallo\User
     * @depends testValidPasswordNoHash
     */
    public function testWrongTypePassword() {
        $this->expectException(InvalidParameterException::class);
        new User('asd', ['foo' => 'bar'], null);
    }

    /**
     * @covers \WEEEOpen\Tarallo\User
     */
    public function testValidHashNoPassword() {
        $this->assertEquals('asd', (string) new User('asd', null, '$2y$10$wXP1ooxhHQ2X63Rgxi8GZeHkotzjwW2/M3HX/so1bwal4zDhsMyW6'));
    }


    /**
     * @covers \WEEEOpen\Tarallo\User
     * @depends testValidHashNoPassword
     */
    public function testReadPasswordNotAvailable() {
        $this->expectException(\LogicException::class);
        (new User('asd', null, '$2y$10$wXP1ooxhHQ2X63Rgxi8GZeHkotzjwW2/M3HX/so1bwal4zDhsMyW6'))->getPassword();
    }

    /**
     * @covers \WEEEOpen\Tarallo\User
     */
    public function testPasswordMatch() {
        $user = new User('asd123', 'asd', '$2y$10$wXP1ooxhHQ2X63Rgxi8GZeHkotzjwW2/M3HX/so1bwal4zDhsMyW6');
        $this->assertEquals('asd123', (string) $user);
        $this->assertEquals('asd', $user->getPassword());
    }

    /**
     * @covers \WEEEOpen\Tarallo\User
     */
    public function testPasswordMismatch() {
        $this->expectException(\InvalidArgumentException::class);
        new User('asd123', 'wrong-password', '$2y$10$wXP1ooxhHQ2X63Rgxi8GZeHkotzjwW2/M3HX/so1bwal4zDhsMyW6');
    }

    /**
     * @covers \WEEEOpen\Tarallo\User
     * @depends testPasswordMatch
     */
    public function testPasswordHashCalculate() {
        $user = new User('asd', 'asd', null);
        $hash = $user->getHash(); // uses random salt, cannot compare string directly
        $this->assertTrue(is_string($hash));
        $user2 = new User('asd', 'asd', $hash);
        $this->assertEquals('asd', (string) $user2); // should not throw
        return [$user2, $hash];
    }

    /**
     * Pointless exercise in style of using output of previous test.
     *
     * @covers  \WEEEOpen\Tarallo\User
     * @depends testPasswordHashCalculate
     * @param $userAndHash array with user and password hash, respectively
     */
    public function testPasswordHashMemoization($userAndHash) {
        /** @var $userAndHash[0] User */
        $this->assertEquals($userAndHash[1], $userAndHash[0]->getHash());
    }
}