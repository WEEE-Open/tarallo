<?php

use PHPUnit\Framework\TestCase;
use WEEEOpen\Tarallo\Feature;

class FeatureTest extends TestCase {
	/**
	 * @covers WEEEOpen\Tarallo\Feature
	 */
	public function testNonexistantFeature() {
		$this->expectException(InvalidArgumentException::class);
		new Feature('INVALID', 'some value');
	}

	/**
	 * @covers WEEEOpen\Tarallo\Feature
	 */
	public function testInvalidFeatureName() {
		$this->expectException(InvalidArgumentException::class);
		/** @noinspection PhpParamsInspection - that's the point... */
		new Feature(['freuquency-hertz'], 999);
	}

	/**
	 * @covers WEEEOpen\Tarallo\Feature
	 */
	public function testInvalidFeatureValueString() {
		$this->expectException(InvalidArgumentException::class);
		new Feature('freuquency-hertz', '500');
	}

	/**
	 * @covers WEEEOpen\Tarallo\Feature
	 */
	public function testInvalidFeatureValueInteger() {
		$this->expectException(InvalidArgumentException::class);
		new Feature('sn', 1337);
	}

	/**
	 * @covers WEEEOpen\Tarallo\Feature
	 */
	public function testInvalidFeatureValueNegativeInteger() {
		$this->expectException(InvalidArgumentException::class);
		new Feature('sn', -1000);
	}

	/**
	 * @covers WEEEOpen\Tarallo\Feature
	 */
	public function testInvalidFeatureValueArray() {
		$this->expectException(InvalidArgumentException::class);
		new Feature('sn', ['some' => 'value']);
	}

	/**
	 * @covers WEEEOpen\Tarallo\Feature
	 */
	public function testInvalidFeatureValueEnum() {
		$this->expectException(InvalidArgumentException::class);
		new Feature('ram-form-factor', 'invalid');
	}

	/**
	 * @covers WEEEOpen\Tarallo\Feature
	 */
	public function testValidFeatureValueEnum() {
		$feature = new Feature('ram-form-factor', 'sodimm');
		$this->assertEquals('sodimm', $feature->value);
		$this->assertEquals('ram-form-factor', $feature->name);
	}

	/**
	 * @covers WEEEOpen\Tarallo\Feature
	 */
	public function testValidFeatureValueString() {
		$feature = new Feature('frequency-hertz', 500);
		$this->assertEquals(500, $feature->value);
		$this->assertEquals('frequency-hertz', $feature->name);
	}

	/**
	 * @covers WEEEOpen\Tarallo\Feature
	 */
	public function testValidFeatureValueInteger() {
		$feature = new Feature('sn', 'F00B4R-1337-ASD');
		$this->assertEquals('F00B4R-1337-ASD', $feature->value);
		$this->assertEquals('sn', $feature->name);
	}

	/**
	 * @covers WEEEOpen\Tarallo\Feature
	 */
	public function testInvalidFeatureOfString() {
		$this->expectException(InvalidArgumentException::class);
		Feature::ofString('frequency-hertz', '12345NotANumber');
	}

	/**
	 * @covers WEEEOpen\Tarallo\Feature
	 */
	public function testValidFeatureOfString() {
		$feature = Feature::ofString('frequency-hertz', '9001');
		$this->assertEquals(9001, $feature->value);
		$this->assertEquals('frequency-hertz', $feature->name);
	}

}