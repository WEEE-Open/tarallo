<?php

namespace WEEEOpen\Tarallo\Server\Test\Database;

use WEEEOpen\Tarallo\Server\Feature;
use WEEEOpen\Tarallo\Server\Item;
use WEEEOpen\Tarallo\Server\Search;
use WEEEOpen\Tarallo\Server\SearchTriplet;
use WEEEOpen\Tarallo\Server\User;

class SearchDAOTest extends DatabaseTest {
	/**
	 * @covers \WEEEOpen\Tarallo\Server\Database\SearchDAO
	 */
	public function testItemSearchSorting() {
		$db = $this->getDb();
		$pc['PC55'] = (new Item('PC55'))
			->addFeature(new Feature('brand', 'TI'))
			->addFeature(new Feature('model', 'GreyPC-\'98'))
			->addFeature(new Feature('type', 'case'))
			->addFeature(new Feature('motherboard-form-factor', 'atx'));
		$pc['PC20'] = (new Item('PC20'))
			->addFeature(new Feature('brand', 'Dill'))
			->addFeature(new Feature('model', 'DI-360'))
			->addFeature(new Feature('type', 'case'))
			->addFeature(new Feature('motherboard-form-factor', 'proprietary'))
			->addFeature(new Feature('color', 'black'))
			->addFeature(new Feature('working', 'yes'));
		$pc['PC21'] = (new Item('PC21'))
			->addFeature(new Feature('brand', 'Dill'))
			->addFeature(new Feature('model', 'DI-360'))
			->addFeature(new Feature('type', 'case'))
			->addFeature(new Feature('motherboard-form-factor', 'proprietary'))
			->addFeature(new Feature('color', 'grey'))
			->addFeature(new Feature('working', 'yes'));
		$pc['PC22'] = (new Item('PC22'))
			->addFeature(new Feature('brand', 'Dill'))
			->addFeature(new Feature('model', 'DI-360'))
			->addFeature(new Feature('type', 'case'))
			->addFeature(new Feature('motherboard-form-factor', 'proprietary'))
			->addFeature(new Feature('color', 'black'))
			->addFeature(new Feature('working', 'yes'));
		$pc['SCHIFOMACCHINA'] = (new Item('SCHIFOMACCHINA'))
			->addFeature(new Feature('brand', 'eMac'))
			->addFeature(new Feature('model', 'EZ1600'))
			->addFeature(new Feature('type', 'case'))
			->addFeature(new Feature('motherboard-form-factor', 'miniitx'))
			->addFeature(new Feature('color', 'white')); // based on a real PC we have in our laboratory.

		foreach($pc as $i) {
			$db->itemDAO()->addItem($i);
		}

		$id = $db->searchDAO()->search(new Search(null, [new SearchTriplet('type', '=', 'case')]), new User('asd'));
		// TODO: sort ['motherboard-form-factor' => '-', 'color' => '+']
		$this->assertTrue(is_int($id), 'Search ID should be an integer');

		$this->assertContainsOnly(Item::class, $items);
		$this->assertEquals(5, count($items), 'There should be 5 items');
		/** @var Item[] $items */
		foreach(['PC20', 'PC22', 'PC21', 'SCHIFOMACCHINA', 'PC55'] as $pos => $code) {
			$this->assertEquals($code, $items[$pos]->getCode(),
				'Item in position ' . $pos . ' should be ' . $code . ' (it\'s ' . $items[$pos]->getCode() . ')');
			$this->assertEquals($pc[$code], $items[$pos], 'Item ' . $code . ' should be unchanged)');
		}

		$items = $db->searchDAO()->getItems(null, [new SearchTriplet('type', '=', 'case')], null, null, null, null);
		$this->assertContainsOnly(Item::class, $items);
		$this->assertEquals(5, count($items), 'There should be 5 items');
		// no sorting options => sort by code
		foreach(['PC20', 'PC21', 'PC22', 'PC55', 'SCHIFOMACCHINA'] as $pos => $code) {
			$this->assertEquals($code, $items[$pos]->getCode(),
				'Item in position ' . $pos . ' should be ' . $code . '(it\'s ' . $items[$pos]->getCode() . ')');
			$this->assertEquals($pc[$code], $items[$pos], 'Item ' . $code . ' should be unchanged)');
		}

		$items = $db->searchDAO()->getItems(null, [new SearchTriplet('color', '=', 'white')], null, null, null, null);
		$this->assertContainsOnly(Item::class, $items);
		$this->assertEquals(1, count($items), 'There should be only one item');
		$this->assertEquals(reset($items), $pc['SCHIFOMACCHINA'],
			'Only SCHIFOMACCHINA should be returned'); // excellent piece of hardware, by the way. 2 minutes from power on to POST OK.
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\Database\SearchDAO
	 */
	public function testGettingItemsWithLocation() {
		$db = $this->getDb();
		$case = (new Item('PC42'))->addFeature(new Feature('brand', 'TI'));
		$discone1 = (new Item('SATAna1'))->addFeature(new Feature('brand', 'SATAn Storage Corporation Inc.'));
		$discone2 = (new Item('SATAna2'))->addFeature(new Feature('brand', 'SATAn Storage Corporation Inc.'));
		$case->addContent($discone1);
		$case->addContent($discone2);
		$db->itemDAO()->addItem($case);

		$items = $db->searchDAO()->getItems(null, [new SearchTriplet('brand', '=', 'SATAn Storage Corporation Inc.')],
			null, null, null, null);
		$this->assertEquals(2, count($items));
		$this->assertInstanceOf(Item::class, $items[0]);
		$this->assertInstanceOf(Item::class, $items[1]);
		/** @noinspection PhpUndefinedMethodInspection */
		$this->assertEquals('PC42', $items[0]->getAncestor(1));
		/** @noinspection PhpUndefinedMethodInspection */
		$this->assertEquals(null, $items[0]->getAncestor(2));
		/** @noinspection PhpUndefinedMethodInspection */
		$this->assertEquals('PC42', $items[1]->getAncestor(1));
		/** @noinspection PhpUndefinedMethodInspection */
		$this->assertEquals(null, $items[1]->getAncestor(2));
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\Item::jsonSerialize()
	 */
	public function testItemSearchSerialization() {
		$db = $this->getDb();
		$pc['PC55'] = (new Item('PC55'))
			->addFeature(new Feature('brand', 'TI'))
			->addFeature(new Feature('model', 'GreyPC-\'98'))
			->addFeature(new Feature('type', 'case'))
			->addFeature(new Feature('motherboard-form-factor', 'atx'));
		$pc['PC20'] = (new Item('PC20'))
			->addFeature(new Feature('brand', 'Dill'))
			->addFeature(new Feature('model', 'DI-360'))
			->addFeature(new Feature('type', 'case'))
			->addFeature(new Feature('motherboard-form-factor', 'proprietary'))
			->addFeature(new Feature('color', 'black'))
			->addFeature(new Feature('working', 'yes'));
		$pc['PC21'] = (new Item('PC21'))
			->addFeature(new Feature('brand', 'Dill'))
			->addFeature(new Feature('model', 'DI-360'))
			->addFeature(new Feature('type', 'case'))
			->addFeature(new Feature('motherboard-form-factor', 'proprietary'))
			->addFeature(new Feature('color', 'grey'))
			->addFeature(new Feature('working', 'yes'));
		$pc['PC22'] = (new Item('PC22'))
			->addFeature(new Feature('brand', 'Dill'))
			->addFeature(new Feature('model', 'DI-360'))
			->addFeature(new Feature('type', 'case'))
			->addFeature(new Feature('motherboard-form-factor', 'proprietary'))
			->addFeature(new Feature('color', 'black'))
			->addFeature(new Feature('working', 'yes'));
		$pc['SCHIFOMACCHINA'] = (new Item('SCHIFOMACCHINA'))// based on a real PC we have in our laboratory.
		->addFeature(new Feature('brand', 'eMac'))
			->addFeature(new Feature('model', 'EZ1600'))
			->addFeature(new Feature('type', 'case'))
			->addFeature(new Feature('motherboard-form-factor', 'miniitx'))
			->addFeature(new Feature('color', 'white'));

		foreach($pc as $i) {
			$db->itemDAO()->addItem($i);
		}

		$items = $db->searchDAO()->getItems(null, [new SearchTriplet('type', '=', 'case')], null, null,
			['motherboard-form-factor' => '-', 'color' => '+'], null);
		$expected = [ // this ugly code courtesy of var_export
			0 =>
				[
					'code'     => 'PC20',
					'features' =>
						[
							'brand'                   => 'Dill',
							'color'                   => 'black',
							'model'                   => 'DI-360',
							'motherboard-form-factor' => 'proprietary',
							'type'                    => 'case',
							'working'                 => 'yes',
						],
				],
			1 =>
				[
					'code'     => 'PC22',
					'features' =>
						[
							'brand'                   => 'Dill',
							'color'                   => 'black',
							'model'                   => 'DI-360',
							'motherboard-form-factor' => 'proprietary',
							'type'                    => 'case',
							'working'                 => 'yes',
						],
				],
			2 =>
				[
					'code'     => 'PC21',
					'features' =>
						[
							'brand'                   => 'Dill',
							'color'                   => 'grey',
							'model'                   => 'DI-360',
							'motherboard-form-factor' => 'proprietary',
							'type'                    => 'case',
							'working'                 => 'yes',
						],
				],
			3 =>
				[
					'code'     => 'SCHIFOMACCHINA',
					'features' =>
						[
							'brand'                   => 'eMac',
							'color'                   => 'white',
							'model'                   => 'EZ1600',
							'motherboard-form-factor' => 'miniitx',
							'type'                    => 'case',
						],
				],
			4 =>
				[
					'code'     => 'PC55',
					'features' =>
						[
							'brand'                   => 'TI',
							'model'                   => 'GreyPC-\'98',
							'motherboard-form-factor' => 'atx',
							'type'                    => 'case',
						],
				],
		];
		$array = [];
		foreach($items as $item) {
			/** @var $item Item */
			$array[] = $item->jsonSerialize();
		}
		$this->assertEquals($expected, $array, 'Selected items should encode to some meaningful JSON representation');
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\Database\SearchDAO
	 */
	public function testItemSearchFiltering() {
		$cpu['INTEL1'] = (new Item('INTEL1'))
			->addFeature(new Feature('type', 'cpu'))
			->addFeature(new Feature('brand', 'Intel-lighenzia'))
			->addFeature(new Feature('model', 'Core 2.0 Trio'))
			->addFeature(new Feature('frequency-hertz', 1400000000));
		$cpu['INTEL2'] = (new Item('INTEL2'))
			->addFeature(new Feature('type', 'cpu'))
			->addFeature(new Feature('brand', 'Intel-lighenzia'))
			->addFeature(new Feature('model', 'Core 3.0 Quadrio'))
			->addFeature(new Feature('frequency-hertz', 2000000000));
		$cpu['INTEL3'] = (new Item('INTEL3'))
			->addFeature(new Feature('type', 'cpu'))
			->addFeature(new Feature('brand', 'Intel-lighenzia'))
			->addFeature(new Feature('model', 'Atomic 5L0W-NE55'))
			->addFeature(new Feature('frequency-hertz', 42));
		$cpu['INTEL4'] = (new Item('INTEL4'))
			->addFeature(new Feature('type', 'cpu'))
			->addFeature(new Feature('brand', 'Intel-lighenzia'))
			->addFeature(new Feature('model', 'Centrino DellaNonna'))
			->addFeature(new Feature('frequency-hertz', 1000000000));
		$cpu['AMD42'] = (new Item('AMD42'))
			->addFeature(new Feature('type', 'cpu'))
			->addFeature(new Feature('brand', 'Advanced Magnificent Processors'))
			->addFeature(new Feature('model', 'A4-200g'))
			->addFeature(new Feature('notes', 'A4, 200 g/cm², come la carta.'))
			->addFeature(new Feature('frequency-hertz', 1900000000));
		$cpu['AMD737'] = (new Item('AMD737'))
			->addFeature(new Feature('frequency-hertz', 3700000000))
			->addFeature(new Feature('type', 'cpu'))
			->addFeature(new Feature('brand', 'Advanced Magnificent Processors'))
			->addFeature(new Feature('model', '737-800'));
		$db = $this->getDb();

		foreach($cpu as $c) {
			$db->itemDAO()->addItem($c);
		}

		$items = $db->searchDAO()->getItems(null, [new SearchTriplet('type', '=', 'cpu')], null, null, null, null);
		$this->assertContainsOnly(Item::class, $items);
		$this->assertEquals(6, count($items), 'There should be 6 items');
		/** @var Item[] $items */
		foreach(['AMD42', 'AMD737', 'INTEL1', 'INTEL2', 'INTEL3', 'INTEL4'] as $pos => $code) {
			$this->assertEquals($code, $items[$pos]->getCode(), "Item in position $pos should be $code");
			$this->assertEquals($cpu[$code], $items[$pos], 'Item ' . $code . ' should be unchanged)');
		}

		$items = $db->searchDAO()->getItems(null, [new SearchTriplet('type', '=', 'cpu')], null, null,
			['frequency-hertz' => '-'], null);
		$this->assertContainsOnly(Item::class, $items);
		$this->assertEquals(6, count($items), 'There should be 6 items');
		foreach(['AMD737', 'INTEL2', 'AMD42', 'INTEL1', 'INTEL4', 'INTEL3'] as $pos => $code) {
			$this->assertEquals($code, $items[$pos]->getCode(), "Item in position $pos should be $code");
			$this->assertEquals($cpu[$code], $items[$pos], "Item $code should be unchanged");
		}

		$items = $db->searchDAO()->getItems(null, [new SearchTriplet('brand', '=', 'Intel')], null, null, null, null);
		$this->assertEquals(0, count($items), 'No items returned without wildcard');

		$itemsGeq = $db->searchDAO()->getItems(null, [new SearchTriplet('brand', '>', 'Intel%')], null, null, null,
			null);
		$this->assertContainsOnly(Item::class, $itemsGeq);
		$this->assertEquals(4, count($itemsGeq),
			'There should be 4 items when using > (query should contain LIKE regardless)');

		$items = $db->searchDAO()->getItems(null, [new SearchTriplet('brand', '=', 'Intel%')], null, null, null, null);
		$this->assertContainsOnly(Item::class, $items);
		$this->assertEquals(4, count($items),
			'There should be 4 items when using = (in this case query should use LIKE)');
		$this->assertEquals($items, $itemsGeq,
			'Same result set in same order when using >, < or = on a field that uses LIKE');
		foreach(['INTEL1', 'INTEL2', 'INTEL3', 'INTEL4'] as $pos => $code) {
			$this->assertEquals($code, $items[$pos]->getCode(), "Item in position $pos should be $code");
			$this->assertEquals($cpu[$code], $items[$pos], "Item $code should be unchanged");
		}
	}
}