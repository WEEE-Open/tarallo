<?php

namespace WEEEOpen\Tarallo\Server\Test\Database;

use WEEEOpen\Tarallo\Server\Feature;
use WEEEOpen\Tarallo\Server\Item;
use WEEEOpen\Tarallo\Server\ItemIncomplete;
use WEEEOpen\Tarallo\Server\NotFoundException;

class TreeDAOTest extends DatabaseTest {
	/**
	 * @covers \WEEEOpen\Tarallo\Server\Database\TreeDAO
	 */
	public function testSubtreeRemoval() {
		$db = $this->getDb();
		/** @var $case Item */
		$lab = (new Item('CHERNOBYL'));
		$case = (new Item('PC42'));
		$discone1 = (new Item('HDD1'));
		$discone2 = (new Item('HDD2'));
		$case->addContent($discone1);
		$case->addContent($discone2);
		$lab->addContent($case);
		$db->itemDAO()->addItems([$lab]);

		$lab = $db->itemDAO()->getItem(new ItemIncomplete('CHERNOBYL'));
		$this->assertInstanceOf(Item::class, $lab);
		/** @var Item $caseContent */
		$case = $lab->getContents()[0];
		$this->assertEquals('PC42', $case->getCode(), 'PC42 is still there');
		$this->assertTrue($this->itemCompare($lab, $lab), 'Lab should be unchanged');

		$db->beginTransaction();
		$db->treeDAO()->removeFromTree($case);
		$db->commit();

		$lab = $db->itemDAO()->getItem(new ItemIncomplete('CHERNOBYL'));
		$this->assertInstanceOf(Item::class, $lab);
		$this->assertEquals(0, count($lab->getContents()), 'Lab is empty');

		$ex = null;
		try {
			$db->itemDAO()->getItem(new ItemIncomplete('PC42'));
		} catch(\Throwable $e) {
			$ex = $e;
		}
		$this->assertInstanceOf(NotFoundException::class, $ex, 'Items outside Tree cannot be selected');

		$ex = null;
		try {
			$db->itemDAO()->getItem(new ItemIncomplete('HDD1'));
		} catch(\Throwable $e) {
			$ex = $e;
		}
		$this->assertInstanceOf(NotFoundException::class, $ex, 'Items outside Tree cannot be selected');

		$ex = null;
		try {
			$db->itemDAO()->getItem(new ItemIncomplete('HDD2'));
		} catch(\Throwable $e) {
			$ex = $e;
		}
		$this->assertInstanceOf(NotFoundException::class, $ex, 'Items outside Tree cannot be selected');
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Server\Database\ItemDAO
	 * @covers \WEEEOpen\Tarallo\Server\Database\TreeDAO
	 */
	public function testTreeMove() {
		// These items should be added in database.yml, but that just increases the amount of data to import for each test
		// and I find more readable this than a YAML file full of numbers.
		$chernobyl = (new Item('CHERNOBYL'))
			->addFeature(new Feature('type', 'location'));
		$tavolone = (new Item('TAVOLONE'))
			->addFeature(new Feature('type', 'location'));
		$chernobyl
			->addContent($tavolone)
			->addContent((new Item('ArmadioL'))
				->addFeature(new Feature('type', 'location')))
			->addContent((new Item('ArmadioR'))
				->addFeature(new Feature('type', 'location')));
		$tavolone
			->addContent(
				(new Item('SCHIFOMACCHINA'))
					->addFeature(new Feature('brand', 'eMac'))
					->addFeature(new Feature('model', 'EZ1600'))
					->addFeature(new Feature('type', 'case'))
					->addFeature(new Feature('motherboard-form-factor', 'miniitx'))
					->addFeature(new Feature('color', 'white'))
			);
		$ti = (new Item('PCTI'))
			->addFeature(new Feature('brand', 'TI'))
			->addFeature(new Feature('type', 'case'))
			->addFeature(new Feature('motherboard-form-factor', 'miniitx'))
			->addFeature(new Feature('color', 'white'))
			->addContent(
				(new Item('RAM22'))
					->addFeature(new Feature('type', 'ram'))
					->addFeature(new Feature('capacity-byte', 32))
			)
			->addContent(
				(new Item('RAM23'))
					->addFeature(new Feature('type', 'ram'))
					->addFeature(new Feature('capacity-byte', 32))
			)
			->addContent(
				(new Item('PCTIMOBO'))
					->addFeature(new Feature('type', 'motherboard'))
					->addFeature(new Feature('color', 'green'))
			)
			->addContent(
				(new Item('PCTICPU'))
					->addFeature(new Feature('type', 'cpu'))
					->addFeature(new Feature('brand', 'Intel-lighenzia'))
					->addFeature(new Feature('model', 'Atomic 5L0W-NE55'))
					->addFeature(new Feature('frequency-hertz', 42))
			);
		$tavolone->addContent(
			(new Item('ROSETTA'))
				->addFeature(new Feature('brand', 'pH'))
				->addFeature(new Feature('model', 'ReliaPro MLG555'))
				->addFeature(new Feature('type', 'case'))
				->addFeature(new Feature('motherboard-form-factor', 'atx'))
				->addFeature(new Feature('color', 'grey'))
				->addContent(
					(new Item('RAM3342'))
						->addFeature(new Feature('type', 'ram'))
						->addFeature(new Feature('capacity-byte', 1073741824))
				)
				->addContent(
					(new Item('RAM2452'))
						->addFeature(new Feature('type', 'ram'))
						->addFeature(new Feature('capacity-byte', 1073741824))
				)
		);
		$chernobyl->addContent($zb = (new Item('ZonaBlu'))->addFeature(new Feature('type', 'location')));
		$tavolone->addContent($ti);

		$db = $this->getDb();
		$db->itemDAO()->addItems([$chernobyl]);

		$item = $db->itemDAO()->getItem(new ItemIncomplete('CHERNOBYL'));
		$this->assertInstanceOf(Item::class, $item);

		// Move TI from TAVOLONE to ZonaBlu.
		$db->beginTransaction();
		$db->treeDAO()->moveItem($ti, $zb);

		$item = $db->itemDAO()->getItem(new ItemIncomplete('CHERNOBYL'));
		$this->assertInstanceOf(Item::class, $item);
		/** @var Item $chernobylPost */
		$chernobylPost = $item;
		$zonaBluPost = null;
		$tavolonePost = null;
		$this->assertContainsOnly(Item::class, $itemz = $chernobylPost->getContents());
		/** @var Item[] $itemz */
		foreach($itemz as $item) {
			if($item->getCode() === 'ZonaBlu') {
				$zonaBluPost = $item;
			} else if($item->getCode() === 'TAVOLONE') {
				$tavolonePost = $item;
			}
		}
		$this->assertInstanceOf(Item::class, $zonaBluPost, 'ZonaBlu should still exist');
		$this->assertInstanceOf(Item::class, $tavolonePost, 'TAVOLONE should still exist');
		/** @var Item $zonaBluPost */
		/** @var Item $tavolonePost */
		$tiShouldBeHere = null;
		$tiShouldNotBeHere = null;
		foreach($zonaBluPost->getContents() as $item) {
			if($item->getCode() === 'PCTI') {
				$tiShouldBeHere = $item;
			}
		}
		foreach($tavolonePost->getContents() as $item) {
			if($item->getCode() === 'PCTI') {
				$tiShouldNotBeHere = $item;
			}
		}
		$this->assertInstanceOf(Item::class, $tiShouldBeHere, 'PCTI should have moved to ZonaBlu');
		$this->assertEquals(null, $tiShouldNotBeHere, 'TAVOLONE should not contain PCTI');
		$this->assertTrue($this->itemCompare($ti, $tiShouldBeHere), 'PCTI should have the same content');
	}
}
