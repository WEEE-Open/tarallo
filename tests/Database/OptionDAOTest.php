<?php

namespace WEEEOpen\TaralloTest\Database;


/**
 * @covers \WEEEOpen\Tarallo\Database\OptionDAO
 */
class OptionDAOTest extends DatabaseTest {
	public function testReadingNonExistingOption() {
		$db = $this->getDb();

		$value = $db->optionDAO()->getOptionValue('NonExistingOption');
		$this->assertNull($value);
	}

	public function testInsertAndReadOption() {
		$db = $this->getDb();

		$value = $db->optionDAO()->getOptionValue('TestOption');
		$this->assertNull($value);
		$value = $db->optionDAO()->getOptionValue('NonExistingOption');
		$this->assertNull($value);

		$value = $db->optionDAO()->setOptionValue('TestOption', 'foo bar');
		$this->assertNull($value);

		$value = $db->optionDAO()->getOptionValue('TestOption');
		$this->assertEquals('foo bar', $value);
		$value = $db->optionDAO()->getOptionValue('NonExistingOption');
		$this->assertNull($value);
	}

	public function testInsertAndReadTwice() {
		$db = $this->getDb();

		$value = $db->optionDAO()->getOptionValue('TestOption');
		$this->assertNull($value);

		$value = $db->optionDAO()->setOptionValue('TestOption', 'foo');
		$this->assertNull($value);

		$value = $db->optionDAO()->getOptionValue('TestOption');
		$this->assertEquals('foo', $value);

		$value = $db->optionDAO()->setOptionValue('TestOption', 'bar');
		$this->assertNull($value);

		$value = $db->optionDAO()->getOptionValue('TestOption');
		$this->assertEquals('bar', $value);
	}

	public function testReadTwice() {
		$db = $this->getDb();

		$value = $db->optionDAO()->getOptionValue('TestOption');
		$this->assertNull($value);

		$value = $db->optionDAO()->setOptionValue('TestOption', 'foo');
		$this->assertNull($value);

		$value = $db->optionDAO()->getOptionValue('TestOption');
		$this->assertEquals('foo', $value);

		$value = $db->optionDAO()->getOptionValue('TestOption');
		$this->assertEquals('foo', $value);
	}
}
