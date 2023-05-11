<?php

namespace WEEEOpen\TaralloTest\Database;

use WEEEOpen\Tarallo\Database\Database;
use WEEEOpen\Tarallo\Feature;
use WEEEOpen\Tarallo\Item;
use WEEEOpen\Tarallo\Search;
use WEEEOpen\Tarallo\SearchDiff;
use WEEEOpen\Tarallo\SearchTriplet;
use WEEEOpen\Tarallo\SessionSSO;
use WEEEOpen\Tarallo\UserSSO;

/**
 * @covers \WEEEOpen\Tarallo\Database\SearchDAO
 */
class SearchDAOTest extends DatabaseTest
{
	private function getSample()
	{
		$pc['PC20'] = (new Item('PC20'))
			->addFeature(new Feature('brand', 'Dill'))
			->addFeature(new Feature('model', 'DI-360'))
			->addFeature(new Feature('type', 'case'))
			->addFeature(new Feature('motherboard-form-factor', 'proprietary'))
			->addFeature(new Feature('color', 'black'))
			->addFeature(new Feature('working', 'yes'));
		$pc['PC90'] = (new Item('PC90'))
			->addFeature(new Feature('brand', 'Dill'))
			->addFeature(new Feature('model', 'DI-360'))
			->addFeature(new Feature('type', 'case'))
			->addFeature(new Feature('motherboard-form-factor', 'proprietary'))
			->addFeature(new Feature('color', 'grey'))
			->addFeature(new Feature('working', 'yes'));
		$pc['PC55'] = (new Item('PC55'))
			->addFeature(new Feature('brand', 'TI'))
			->addFeature(new Feature('model', 'GreyPC-\'98'))
			->addFeature(new Feature('type', 'case'))
			->addFeature(new Feature('motherboard-form-factor', 'atx'));
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

		return $pc;
	}

	private function getUser()
	{
		require_once __DIR__ . '/../../config/config.php';

		$session = new SessionSSO();
		$session->cn = 'Asd Asd';
		$session->uid = 'asd';
		$session->groups = ['Admin', 'Test', 'Bogus'];
		return UserSSO::fromSession($session);
	}

	private function loadSample(Database $db)
	{
		$pc = $this->getSample();

		foreach ($pc as $i) {
			$db->itemDAO()->addItem($i);
		}
	}

	public function testItemSearch()
	{
		$db = $this->getDb();
		$this->loadSample($db);

		$id = $db->searchDAO()->searchNew(new Search(null, [new SearchTriplet('color', '=', 'white')]), $this->getUser()->uid);
		$this->assertTrue(is_int($id), 'Search ID should be an integer');
		$items = $db->searchDAO()->getResults($id, 1, 100);
		$this->assertContainsOnly(Item::class, $items);
		$this->assertEquals(1, count($items), 'There should be only one item');
		$this->assertEquals(
			'SCHIFOMACCHINA',
			$items[0]->getCode(),
			'Only SCHIFOMACCHINA should be returned'
		); // excellent piece of hardware, by the way. 2 minutes from power on to POST OK.
	}

	public function testItemSearchByCode()
	{
		$db = $this->getDb();
		$this->loadSample($db);

		$id = $db->searchDAO()->searchNew(new Search('PC%'), $this->getUser()->uid);
		$this->assertTrue(is_int($id), 'Search ID should be an integer');

		$items = $db->searchDAO()->getResults($id, 1, 100);
		$this->assertContainsOnly(Item::class, $items);
		$this->assertEquals(4, count($items), 'There should be only 4 items');
	}

	public function testItemSearchRefinement()
	{
		$db = $this->getDb();
		$this->loadSample($db);

		$search = new Search('PC%');
		$id = $db->searchDAO()->searchNew($search, $this->getUser()->uid);

		$diff = new SearchDiff(["code" => [["key" => null, "value" => "%0"]]]);
		$idRefined = $db->searchDAO()->searchUpdate($search, $diff);
		$this->assertEquals($id, $idRefined, "Search id shouldn't change");

		$items = $db->searchDAO()->getResults($id, 1, 100);
		$this->assertContainsOnly(Item::class, $items);
		$this->assertEquals(2, count($items), 'There should be only 2 items');

		$diff = new SearchDiff(["code" => [["key" => null, "value" => "notacode"]]]);
		$idRefined = $db->searchDAO()->searchUpdate($search, $diff);
		$this->assertEquals($id, $idRefined, "Search id shouldn't change, again");

		$items = $db->searchDAO()->getResults($id, 1, 100);
		$this->assertEquals(0, count($items), 'There should be no results now');
	}

	public function testItemSearchRefinementSorting()
	{
		$db = $this->getDb();
		$this->loadSample($db);

		$search = new Search('PC%');
		$id = $db->searchDAO()->searchNew($search, $this->getUser()->uid);

		$diff = new SearchDiff(["sort" => [["key" => null, "value" => ["feature" => "brand", "direction" => "-"]]]]);
		$idRefined = $db->searchDAO()->searchUpdate($search, $diff);
		$this->assertEquals($id, $idRefined, "Search id shouldn't change");

		$items = $db->searchDAO()->getResults($id, 1, 100);
		$this->assertContainsOnly(Item::class, $items);
		$this->assertEquals(4, count($items), 'There should be only 4 items (nothing was lost while sorting)');
		$this->assertEquals('TI', $items[0]->getFeature('brand')->value, "Sorting is correct");
		$this->assertEquals('Dill', $items[1]->getFeature('brand')->value, "Sorting is correct");
		$this->assertEquals('Dill', $items[2]->getFeature('brand')->value, "Sorting is correct");
		$this->assertEquals('Dill', $items[3]->getFeature('brand')->value, "Sorting is correct");
	}

	public function testSearchSortingOnly()
	{
		$db = $this->getDb();
		$this->expectException(\InvalidArgumentException::class);
		$db->searchDAO()->searchNew(new Search(null, [], [], [], ['brand' => '+']), $this->getUser()->uid);
	}

	public function testItemSearchSorting()
	{
		$db = $this->getDb();
		$this->loadSample($db);

		$quest = new Search(
			null,
			[new SearchTriplet('type', '=', 'case')],
			[],
			[],
			['motherboard-form-factor' => '-']
		);
		$id = $db->searchDAO()->searchNew($quest, $this->getUser()->uid);
		$this->assertTrue(is_int($id), 'Search ID should be an integer');

		$items = $db->searchDAO()->getResults($id, 1, 100);

		$this->assertContainsOnly(Item::class, $items);
		$this->assertEquals(5, count($items), 'There should be 5 items');

		$pc = $this->getSample();
		/** @var Item[] $items */
		foreach (['PC90', 'PC22', 'PC20', 'SCHIFOMACCHINA', 'PC55'] as $pos => $code) {
			$this->assertEquals(
				$code,
				$items[$pos]->getCode(),
				'Item in position ' . $pos . ' should be ' . $code . ' (it\'s ' . $items[$pos]->getCode() . ')'
			);
			$this->assertEquals($pc[$code], $items[$pos], 'Item ' . $code . ' should be unchanged)');
		}
	}

	public function testGettingItemsCheckPath()
	{
		$db = $this->getDb();
		$case = (new Item('PC42'))->addFeature(new Feature('brand', 'TI'));
		$discone1 = (new Item('SATAna1'))->addFeature(new Feature('brand', 'SATAn Storage Corporation Inc.'));
		$discone2 = (new Item('SATAna2'))->addFeature(new Feature('brand', 'SATAn Storage Corporation Inc.'));
		$case->addContent($discone1);
		$case->addContent($discone2);
		$db->itemDAO()->addItem($case);

		$id = $db->searchDAO()->searchNew(new Search(
			null,
			[new SearchTriplet('brand', '=', 'SATAn Storage Corporation Inc.')]
		), $this->getUser()->uid);
		$items = $db->searchDAO()->getResults($id, 1, 100);
		$this->assertEquals(2, count($items));
		$this->assertInstanceOf(Item::class, $items[0]);
		$this->assertInstanceOf(Item::class, $items[1]);
		$this->assertEquals('PC42', $items[0]->getPath()[0]);
		$this->assertEquals('PC42', $items[1]->getPath()[0]);
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Item::jsonSerialize()
	 */
	public function testItemSearchSerialization()
	{
		$db = $this->getDb();
		$this->loadSample($db);

		$id = $db->searchDAO()->searchNew(new Search(
			null,
			[new SearchTriplet('type', '=', 'case')],
			[],
			[],
			['motherboard-form-factor' => '-']
		), $this->getUser()->uid);
		$items = $db->searchDAO()->getResults($id, 1, 100);
		// this ugly code courtesy of var_export(json_decode(json_encode($items), true), true);
		// Items are sorted by what the search says, then code ascending. Or they should, anyway.
		$expected = [
			0 =>
				[
					'code' => 'PC90',
					'features' =>
						[
							'brand' => 'Dill',
							'color' => 'grey',
							'model' => 'DI-360',
							'motherboard-form-factor' => 'proprietary',
							'type' => 'case',
							'working' => 'yes',
						],
					'donations' => [],
				],
			1 =>
				[
					'code' => 'PC22',
					'features' =>
						[
							'brand' => 'Dill',
							'color' => 'black',
							'model' => 'DI-360',
							'motherboard-form-factor' => 'proprietary',
							'type' => 'case',
							'working' => 'yes',
						],
					'donations' => [],
				],
			2 =>
				[
					'code' => 'PC20',
					'features' =>
						[
							'brand' => 'Dill',
							'color' => 'black',
							'model' => 'DI-360',
							'motherboard-form-factor' => 'proprietary',
							'type' => 'case',
							'working' => 'yes',
						],
					'donations' => [],
				],
			3 =>
				[
					'code' => 'SCHIFOMACCHINA',
					'features' =>
						[
							'brand' => 'eMac',
							'color' => 'white',
							'model' => 'EZ1600',
							'motherboard-form-factor' => 'miniitx',
							'type' => 'case',
						],
					'donations' => [],
				],
			4 =>
				[
					'code' => 'PC55',
					'features' =>
						[
							'brand' => 'TI',
							'model' => 'GreyPC-\'98',
							'motherboard-form-factor' => 'atx',
							'type' => 'case',
						],
					'donations' => [],
				],
		];
		$array = [];
		foreach ($items as $item) {
			/** @var $item Item */
			$array[] = $item->jsonSerialize();
		}
		$this->assertEquals($expected, $array, 'Selected items should encode to some meaningful JSON representation');
	}

	public function testItemSearchFiltering()
	{
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

		foreach ($cpu as $c) {
			$db->itemDAO()->addItem($c);
		}

		$id = $db->searchDAO()->searchNew(new Search(null, [new SearchTriplet('type', '=', 'cpu')]), $this->getUser()->uid);
		//$id = $db->searchDAO()->searchNew(new Search(null, [new SearchTriplet('type', '=', 'cpu')], null, null, ['frequency-hertz', '+']), $this->getUser()->$this->uid);
		$items = $db->searchDAO()->getResults($id, 1, 100);
		$this->assertContainsOnly(Item::class, $items);
		$this->assertEquals(6, count($items), 'There should be 6 items');
		/** @var Item[] $items */
		// Sorting by code is not guaranteed, really...
		foreach (['INTEL4', 'INTEL3', 'INTEL2', 'INTEL1', 'AMD737', 'AMD42'] as $pos => $code) {
			$this->assertEquals($code, $items[$pos]->getCode(), "Item in position $pos should be $code");
			$this->assertEquals($cpu[$code], $items[$pos], 'Item ' . $code . ' should be unchanged)');
		}

		$id = $db->searchDAO()->searchNew(new Search(null, [new SearchTriplet('brand', '=', 'Intel')]), $this->getUser()->uid);
		$items = $db->searchDAO()->getResults($id, 1, 100);
		$this->assertEquals(0, count($items), 'No items returned without wildcard');

		$id = $db->searchDAO()->searchNew(new Search(null, [new SearchTriplet('brand', '~', 'Intel%')]), $this->getUser()->uid);
		$itemsLike = $db->searchDAO()->getResults($id, 1, 100);
		$this->assertContainsOnly(Item::class, $itemsLike);
		$this->assertEquals(
			4,
			count($itemsLike),
			'There should be 4 items when using ~ (query should contain LIKE)'
		);
		foreach (['INTEL4', 'INTEL3', 'INTEL2', 'INTEL1'] as $pos => $code) {
			$this->assertEquals($code, $itemsLike[$pos]->getCode(), "Item in position $pos should be $code");
			$this->assertEquals($cpu[$code], $itemsLike[$pos], "Item $code should be unchanged");
		}
	}
}
