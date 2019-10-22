<?php

namespace WEEEOpen\Tarallo\Test\Database;

use WEEEOpen\Tarallo\Database\TreeDAO;
use WEEEOpen\Tarallo\Feature;
use WEEEOpen\Tarallo\Item;
use WEEEOpen\Tarallo\ItemCode;
use WEEEOpen\Tarallo\NotFoundException;

class TreeDAOTest extends DatabaseTest {
	/**
	 * @covers \WEEEOpen\Tarallo\Database\TreeDAO
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
		$db->itemDAO()->addItem($lab);

		$lab = $db->itemDAO()->getItem(new ItemCode('CHERNOBYL'));
		$this->assertInstanceOf(Item::class, $lab);
		/** @var Item $caseContent */
		$case = $lab->getContent()[0];
		$this->assertEquals('PC42', $case->getCode(), 'PC42 is still there');
		$this->assertTrue($this->itemCompare($lab, $lab), 'Lab should be unchanged');

		$db->treeDAO()->removeFromTree($case);

		$lab = $db->itemDAO()->getItem(new ItemCode('CHERNOBYL'));
		$this->assertInstanceOf(Item::class, $lab);
		$this->assertEquals(0, count($lab->getContent()), 'Lab is empty');

		$ex = null;
		try {
			$db->itemDAO()->getItem(new ItemCode('PC42'));
		} catch(\Throwable $e) {
			$ex = $e;
		}
		$this->assertInstanceOf(NotFoundException::class, $ex, 'Items outside Tree cannot be selected');

		$ex = null;
		try {
			$db->itemDAO()->getItem(new ItemCode('HDD1'));
		} catch(\Throwable $e) {
			$ex = $e;
		}
		$this->assertInstanceOf(NotFoundException::class, $ex, 'Items outside Tree cannot be selected');

		$ex = null;
		try {
			$db->itemDAO()->getItem(new ItemCode('HDD2'));
		} catch(\Throwable $e) {
			$ex = $e;
		}
		$this->assertInstanceOf(NotFoundException::class, $ex, 'Items outside Tree cannot be selected');
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Database\ItemDAO
	 * @covers \WEEEOpen\Tarallo\Database\TreeDAO
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
		$db->itemDAO()->addItem($chernobyl);

		$item = $db->itemDAO()->getItem(new ItemCode('CHERNOBYL'));
		$this->assertInstanceOf(Item::class, $item);

		// Hack of questionable quality to prevent:
		// Integrity constraint violation: 1062 Duplicate entry 'PCTI-2018-05-04 20:23:23-M' for key 'PRIMARY'
		// since timestamps have seconds accuracy
		$this->getPdo()
			->query("UPDATE Audit SET `Time` = DATE_SUB(`Time`, INTERVAL 1 SECOND) WHERE `Code` = 'PCTI' AND `Change` = 'M' ORDER BY `Time` DESC LIMIT 1");

		// Move TI from TAVOLONE to ZonaBlu.
		$db->treeDAO()->moveItem($ti, $zb);

		$item = $db->itemDAO()->getItem(new ItemCode('CHERNOBYL'));
		$this->assertInstanceOf(Item::class, $item);
		/** @var Item $chernobylPost */
		$chernobylPost = $item;
		$zonaBluPost = null;
		$tavolonePost = null;
		$this->assertContainsOnly(Item::class, $itemz = $chernobylPost->getContent());
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
		foreach($zonaBluPost->getContent() as $item) {
			if($item->getCode() === 'PCTI') {
				$tiShouldBeHere = $item;
			}
		}
		foreach($tavolonePost->getContent() as $item) {
			if($item->getCode() === 'PCTI') {
				$tiShouldNotBeHere = $item;
			}
		}
		$this->assertInstanceOf(Item::class, $tiShouldBeHere, 'PCTI should have moved to ZonaBlu');
		$this->assertEquals(null, $tiShouldNotBeHere, 'TAVOLONE should not contain PCTI');
		$this->assertTrue($this->itemCompare($ti, $tiShouldBeHere), 'PCTI should have the same content');
	}

	public function testTreeMoveFromNonexistant() {
		$chernobyl = (new Item('CHERNOBYL'))
			->addFeature(new Feature('type', 'location'));
		$tavolone = (new Item('TAVOLONE'))
			->addFeature(new Feature('type', 'location'));
		$chernobyl
			->addContent($tavolone);

		$db = $this->getDb();
		$db->itemDAO()->addItem($chernobyl);

		$this->expectException(NotFoundException::class);
		$this->expectExceptionCode(TreeDAO::EXCEPTION_CODE_CHILD);
		$db->treeDAO()->moveItem(new ItemCode('SOMETHING'), new ItemCode('TAVOLONE'));
	}

	public function testTreeMoveToNonexistant() {
		$chernobyl = (new Item('CHERNOBYL'))
			->addFeature(new Feature('type', 'location'));
		$tavolone = (new Item('TAVOLONE'))
			->addFeature(new Feature('type', 'location'));
		$chernobyl
			->addContent($tavolone);

		$db = $this->getDb();
		$db->itemDAO()->addItem($chernobyl);

		$this->expectException(NotFoundException::class);
		$this->expectExceptionCode(TreeDAO::EXCEPTION_CODE_PARENT);
		$db->treeDAO()->moveItem(new ItemCode('TAVOLONE'), new ItemCode('NOWHERE'));
	}

	public function testRemoveFromTree() {
		$tree = (new Item('CHERNOBYL'))
			->addContent((new Item('TAVOLONE'))
				->addContent((new Item('PC42'))
					->addContent(new Item('RAM9001'))));

		$db = $this->getDb();
		$db->itemDAO()->addItem($tree);

		$db->treeDAO()->removeFromTree(new ItemCode('PC42'));

		$ex = null;
		try {
			$db->itemDAO()->getItem(new ItemCode('PC42'));
		} catch(NotFoundException $e) {
			$ex = $e;
		}
		$this->assertInstanceOf(NotFoundException::class, $ex);

		$ex = null;
		try {
			$db->itemDAO()->getItem(new ItemCode('RAM9001'));
		} catch(NotFoundException $e) {
			$ex = $e;
		}
		$this->assertInstanceOf(NotFoundException::class, $ex);

		$bigtable = $db->itemDAO()->getItem(new ItemCode('TAVOLONE'));
		$this->assertInstanceOf(Item::class, $bigtable);
	}
}
