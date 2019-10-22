<?php

use PHPUnit\Framework\TestCase;
use WEEEOpen\Tarallo\APIv1\ItemBuilder;
use WEEEOpen\Tarallo\HTTP\InvalidPayloadParameterException;
use WEEEOpen\Tarallo\ItemCode;

class ItemBuilderTest extends TestCase {

	/**
	 * @covers \WEEEOpen\Tarallo\APIv1\ItemBuilder
	 */
	public function testInvalidCode() {
		$this->expectException(InvalidPayloadParameterException::class);
		ItemBuilder::ofArray([], 'Foo::bar? & & &', $discarded);
	}

	/**
	 * @covers \WEEEOpen\Tarallo\APIv1\ItemBuilder
	 */
	public function testValidCode() {
		$item = ItemBuilder::ofArray([], 'PC42', $discarded);
		$this->assertInstanceOf(\WEEEOpen\Tarallo\Item::class, $item);
		$this->assertEquals('PC42', $item->getCode());
	}

	/**
	 * @covers \WEEEOpen\Tarallo\APIv1\ItemBuilder
	 */
	public function testInvalidFeaturesType() {
		$this->expectException(InvalidPayloadParameterException::class);
		ItemBuilder::ofArray(['features' => 'foo'], 'PC42', $discarded);
	}

	/**
	 * @covers \WEEEOpen\Tarallo\APIv1\ItemBuilder
	 */
	public function testInvalidFeaturesName() {
		$this->expectException(InvalidPayloadParameterException::class);
		ItemBuilder::ofArray(['features' => ['invalid' => 'stuff']], 'PC42', $discarded);
	}

	/**
	 * @covers \WEEEOpen\Tarallo\APIv1\ItemBuilder
	 */
	public function testInvalidFeaturesValue() {
		$this->expectException(InvalidPayloadParameterException::class);
		ItemBuilder::ofArray(['features' => ['motherboard-form-factor' => 'not a valid form factor']], 'PC42',
			$discarded);
	}

	/**
	 * @covers \WEEEOpen\Tarallo\APIv1\ItemBuilder
	 */
	public function testValidFeatures() {
		$item = ItemBuilder::ofArray(['features' => ['motherboard-form-factor' => 'atx']], 'PC42', $discarded);
		$this->assertInstanceOf(\WEEEOpen\Tarallo\Item::class, $item);
		$this->assertArrayHasKey('motherboard-form-factor', $item->getFeatures());
		$this->assertEquals('atx', $item->getFeatures()['motherboard-form-factor']->value);
	}

	/**
	 * @covers \WEEEOpen\Tarallo\APIv1\ItemBuilder
	 */
	public function testInvalidParent() {
		$this->expectException(InvalidPayloadParameterException::class);
		ItemBuilder::ofArray(['parent' => 'Foo::bar? & & &'], 'PC42', $discarded);
	}

	/**
	 * @covers \WEEEOpen\Tarallo\APIv1\ItemBuilder
	 */
	public function testValidParent() {
		ItemBuilder::ofArray(['parent' => 'ZonaBlu'], 'PC42', $parent);
		$this->assertInstanceOf(ItemCode::class, $parent);
		$this->assertEquals('ZonaBlu', $parent);
	}
}
