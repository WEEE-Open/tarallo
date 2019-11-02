<?php

use PHPUnit\Framework\TestCase;
use WEEEOpen\Tarallo\APIv2\ItemBuilder;
use WEEEOpen\Tarallo\ItemCode;

/**
 * @covers \WEEEOpen\Tarallo\APIv2\ItemBuilder
 * @covers \WEEEOpen\Tarallo\ValidationException
 * @covers \WEEEOpen\Tarallo\FeatureValidationException
 */
class ItemBuilderTest extends TestCase {
	public function testInvalidCode() {
		$this->expectException(\WEEEOpen\Tarallo\ValidationException::class);
		try {
			ItemBuilder::ofArray([], 'Foo::bar? & & &', $discarded);
		} catch(\WEEEOpen\Tarallo\ValidationException $e) {
			$this->assertEquals('Foo::bar? & & &', $e->getItem());
			$this->assertEquals([0], $e->getItemPath());
			throw $e;
		}
	}

	public function testValidCode() {
		$item = ItemBuilder::ofArray([], 'PC42', $discarded);
		$this->assertInstanceOf(\WEEEOpen\Tarallo\Item::class, $item);
		$this->assertEquals('PC42', $item->getCode());
	}

	public function testEmptyCode() {
		$item = ItemBuilder::ofArray(['type' => 'case'], null, $discarded);
		$this->assertInstanceOf(\WEEEOpen\Tarallo\Item::class, $item);
		$this->assertEquals(null, $item->peekCode());
	}

	public function testInvalidFeaturesType() {
		$this->expectException(\WEEEOpen\Tarallo\ValidationException::class);
		try {
			ItemBuilder::ofArray(['features' => 'foo'], 'PC42', $discarded);
		} catch(\WEEEOpen\Tarallo\ValidationException $e) {
			$this->assertEquals('PC42', $e->getItem());
			$this->assertEquals([0], $e->getItemPath());
			throw $e;
		}
	}

	public function testInvalidFeaturesName() {
		$this->expectException(\WEEEOpen\Tarallo\FeatureValidationException::class);
		try {
			ItemBuilder::ofArray(['features' => ['invalid' => 'stuff']], 'PC42', $discarded);
		} catch(\WEEEOpen\Tarallo\FeatureValidationException $e) {
			$this->assertEquals('PC42', $e->getItem());
			$this->assertEquals([0], $e->getItemPath());
			$this->assertEquals('invalid', $e->getFeature());
			$this->assertEquals('stuff', $e->getFeatureValue());
			throw $e;
		}
	}

	public function testInvalidFeaturesNameInt() {
		$this->expectException(\WEEEOpen\Tarallo\FeatureValidationException::class);
		try {
			ItemBuilder::ofArray(['features' => [9001 => 'stuff']], 'PC42', $discarded);
		} catch(\WEEEOpen\Tarallo\FeatureValidationException $e) {
			$this->assertEquals('PC42', $e->getItem());
			$this->assertEquals([0], $e->getItemPath());
			$this->assertEquals(9001, $e->getFeature());
			$this->assertEquals('stuff', $e->getFeatureValue());
			throw $e;
		}
	}

	public function testInvalidFeaturesValue() {
		$this->expectException(\WEEEOpen\Tarallo\FeatureValidationException::class);
		try {
			ItemBuilder::ofArray(['features' => ['motherboard-form-factor' => 'not a valid form factor']], 'PC42', $discarded);
		} catch(\WEEEOpen\Tarallo\FeatureValidationException $e) {
			$this->assertEquals('PC42', $e->getItem());
			$this->assertEquals([0], $e->getItemPath());
			$this->assertEquals('motherboard-form-factor', $e->getFeature());
			$this->assertEquals('not a valid form factor', $e->getFeatureValue());
			throw $e;
		}
	}

	public function testInvalidFeaturesValueInt() {
		$this->expectException(\WEEEOpen\Tarallo\FeatureValidationException::class);
		try {
			ItemBuilder::ofArray(['features' => ['motherboard-form-factor' => 15]], 'PC42', $discarded);
		} catch(\WEEEOpen\Tarallo\FeatureValidationException $e) {
			$this->assertEquals('PC42', $e->getItem());
			$this->assertEquals([0], $e->getItemPath());
			$this->assertEquals('motherboard-form-factor', $e->getFeature());
			$this->assertEquals(15, $e->getFeatureValue());
			throw $e;
		}
	}

	public function testInvalidFeaturesValueArray() {
		$this->expectException(\WEEEOpen\Tarallo\FeatureValidationException::class);
		try {
			ItemBuilder::ofArray(['features' => ['motherboard-form-factor' => ['hi' => 'there']]], 'PC42', $discarded);
		} catch(\WEEEOpen\Tarallo\FeatureValidationException $e) {
			$this->assertEquals('PC42', $e->getItem());
			$this->assertEquals([0], $e->getItemPath());
			$this->assertEquals('motherboard-form-factor', $e->getFeature());
			$this->assertEquals(['hi' => 'there'], $e->getFeatureValue());
			throw $e;
		}
	}

	public function testInvalidFeaturesTypeDeep() {
		$this->expectException(\WEEEOpen\Tarallo\ValidationException::class);
		$array = [
			'features' => [
				'type' => 'case',
			],
			'contents' => [
				[
					'features' => [
						'type' => 'motherboard',
						'brand' => 'Foo',
					]
				],
				[
					'code' => 'SOMETHING',
					'features' => 'I-am-error'
				],
			]
		];
		try {
			ItemBuilder::ofArray($array, 'PC42', $discarded);
		} catch(\WEEEOpen\Tarallo\ValidationException $e) {
			$this->assertEquals('SOMETHING', $e->getItem());
			$this->assertEquals([0, 1], $e->getItemPath());
			throw $e;
		}
	}

	public function testInvalidFeaturesTypeDeepAlt() {
		$this->expectException(\WEEEOpen\Tarallo\ValidationException::class);
		$array = [
			'features' => [
				'type' => 'case',
			],
			'contents' => [
				[
					'features' => 'I-am-error'
				],
				[
					'features' => [
						'type' => 'motherboard',
						'brand' => 'Foo',
					]
				],
			]
		];
		try {
			ItemBuilder::ofArray($array, 'PC42', $discarded);
		} catch(\WEEEOpen\Tarallo\ValidationException $e) {
			$this->assertEquals(null, $e->getItem());
			$this->assertEquals([0, 0], $e->getItemPath());
			throw $e;
		}
	}

	public function testInvalidFeaturesNameDeep() {
		$this->expectException(\WEEEOpen\Tarallo\FeatureValidationException::class);
		$array = [
			'features' => [
				'type' => 'case',
			],
			'contents' => [
				[
					'features' => [
						'type' => 'motherboard',
						'brand' => 'Foo',
					]
				],
				[
					'features' => [
						'type' => 'hdd',
						'brand' => 'Example',
						'invalid-feature-name' => 'foo'
					]
				],
			]
		];
		try {
			ItemBuilder::ofArray($array, 'PC42', $discarded);
		} catch(\WEEEOpen\Tarallo\FeatureValidationException $e) {
			$this->assertEquals(null, $e->getItem());
			$this->assertEquals([0, 1], $e->getItemPath());
			$this->assertEquals('invalid-feature-name', $e->getFeature());
			throw $e;
		}
	}

	public function testInvalidFeaturesValueDeep() {
		$this->expectException(\WEEEOpen\Tarallo\FeatureValidationException::class);
		$array = [
			'features' => [
				'type' => 'case',
			],
			'contents' => [
				[
					'features' => [
						'type' => 'motherboard',
						'brand' => 'Foo',
					]
				],
				[
					'features' => [
						'type' => 'hdd',
						'brand' => 'Example',
						'color' => 'invalid-value'
					]
				],
			]
		];
		try {
			ItemBuilder::ofArray($array, 'PC42', $discarded);
		} catch(\WEEEOpen\Tarallo\FeatureValidationException $e) {
			$this->assertEquals(null, $e->getItem());
			$this->assertEquals([0, 1], $e->getItemPath());
			$this->assertEquals('color', $e->getFeature());
			$this->assertEquals('invalid-value', $e->getFeatureValue());
			throw $e;
		}
	}

	public function testValidFeatures() {
		$item = ItemBuilder::ofArray(['features' => ['motherboard-form-factor' => 'atx']], 'PC42', $discarded);
		$this->assertInstanceOf(\WEEEOpen\Tarallo\Item::class, $item);
		$this->assertArrayHasKey('motherboard-form-factor', $item->getFeatures());
		$this->assertEquals('atx', $item->getFeatures()['motherboard-form-factor']->value);
	}

	public function testInvalidParent() {
		$this->expectException(\WEEEOpen\Tarallo\ValidationException::class);
		try {
			ItemBuilder::ofArray(['parent' => 'Foo::bar? & & &'], 'PC42', $discarded);
		} catch(\WEEEOpen\Tarallo\ValidationException $e) {
			$this->assertEquals('Foo::bar? & & &', $e->getItem());
			$this->assertEquals([], $e->getItemPath());
			throw $e;
		}
	}

	public function testValidParent() {
		ItemBuilder::ofArray(['parent' => 'ZonaBlu'], 'PC42', $parent);
		$this->assertInstanceOf(ItemCode::class, $parent);
		$this->assertEquals('ZonaBlu', $parent);
	}
}
