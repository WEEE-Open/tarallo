<?php

use PHPUnit\Framework\TestCase;
use WEEEOpen\Tarallo\Server\Feature;
use WEEEOpen\Tarallo\Server\Item;
use WEEEOpen\Tarallo\Server\ItemNestingException;
use WEEEOpen\Tarallo\Server\ItemValidator;
use WEEEOpen\Tarallo\Server\ValidationException;

/**
 * @covers \WEEEOpen\Tarallo\Server\ItemValidator
 */
class ItemValidatorTest extends TestCase {
	private static function item(string $code, string $type): Item {
		$item = new Item($code);
		$item->addFeature(new Feature('type', $type));
		return $item;
	}

	public function testInvalidRam() {
		$ram = self::item('R2', 'ram');
		$cpu = self::item('C123', 'cpu');

		$this->expectException(ItemNestingException::class);
		ItemValidator::validateLocation($ram, $cpu);
	}

	public function testInvalidRoot() {
		$ram = self::item('R2', 'ram');

		$this->expectException(ValidationException::class);
		ItemValidator::validateLocation($ram, null);
	}

	public function testAnotherInvalidRoot() {
		$case = self::item('CASE', 'case');

		$this->expectException(ValidationException::class);
		ItemValidator::validateLocation($case, null);
	}

	public function testValidRoot() {
		$meh = self::item('MEH', 'location');

		ItemValidator::validateLocation($meh, null);
		$this->assertTrue(true, 'Location is accepted, no random exceptions are thrown');
	}

	public function testValidRootNoType() {
		$meh = new Item('meh');

		ItemValidator::validateLocation($meh, null);
		$this->assertTrue(true, 'Location is accepted, no random exceptions are thrown');
	}

	public function testFixupRam() {
		$pc = self::item('PC77', 'case');
		$mobo = self::item('MOBO', 'motherboard');
		$ram1 = self::item('R1', 'ram');
		$ram2 = self::item('R2', 'ram');
		$cpu = self::item('C123', 'cpu');

		$pc->addContent($mobo);
		$mobo->addContent($ram1)->addContent($cpu);

		$correct = ItemValidator::fixupLocation($ram2, $pc);
		$this->assertInstanceOf(Item::class, $correct, 'Fixup returns an Item');
		$this->assertEquals('MOBO', $correct->getCode(), 'RAM gets moved to motherboard');
		ItemValidator::validateLocation($ram2, $pc); // doesn't throw an exception = we're good to go.
	}

	public function testImpossibleFixup() {
		$pc = self::item('PC77', 'case');
		$mobo = self::item('MOBO', 'motherboard');
		$ram1 = self::item('R1', 'ram');
		$ram2 = self::item('R2', 'ram');
		$cpu = self::item('C123', 'cpu');

		$pc->addContent($mobo);
		$mobo->addContent($ram1)->addContent($ram2);

		$unchanged = ItemValidator::fixupLocation($cpu, $ram1);
		$this->assertInstanceOf(Item::class, $unchanged, 'Fixup returns an Item');
		$this->assertEquals($ram1, $unchanged, 'Fixup shouldn\'t have changed parent since it only moves item down');
		$this->expectException(ItemNestingException::class);
		ItemValidator::validateLocation($unchanged, $ram1);
	}

	public function testNoTypeDescendant() {
		$pc = self::item('PC77', 'case');
		$mobo = self::item('MOBO', 'motherboard');
		$pc->addContent($mobo);

		$item = new Item('Whatever');

		$unchanged = ItemValidator::fixupLocation($item, $pc);
		$this->assertEquals($pc, $unchanged, 'Fixup shouldn\'t have changed parent');
		ItemValidator::validateLocation($item, $pc);
	}

	public function testNoTypeParent() {
		$mobo = self::item('MOBO', 'motherboard');
		$item = new Item('Whatever');

		$unchanged = ItemValidator::fixupLocation($mobo, $item);
		$this->assertEquals($item, $unchanged, 'Fixup shouldn\'t have changed parent');
		ItemValidator::validateLocation($mobo, $item);
	}

	public function testNoTypeBoth() {
		$meh = new Item('meh');
		$item = new Item('Whatever');

		$unchanged = ItemValidator::fixupLocation($meh, $item);
		$this->assertEquals($item, $unchanged, 'Fixup shouldn\'t have changed parent');
		ItemValidator::validateLocation($meh, $item);
	}

	public function testReparentInside() {
		$lab = self::item('Lab', 'location');
		$pc = self::item('PC77', 'case');
		$mobo = self::item('MOBO', 'motherboard');
		$ram1 = self::item('R1', 'ram');
		$ram2 = self::item('R2', 'ram');
		$cpu = self::item('C123', 'cpu');

		$pc->addContent($mobo)->addContent($cpu);
		$mobo->addContent($ram1)->addContent($ram2);

		$unchanged = ItemValidator::fixupLocation($pc, $lab);
		$this->assertEquals($lab, $unchanged, 'Fixup shouldn\'t have changed parent');
		$inmobo = $mobo->getContent();
		$this->assertContains($cpu, $inmobo, 'CPU has been moved to motherboard');
	}

	public function testCaseAsABox() {
		$pc = self::item('PC77', 'case');
		$ram1 = self::item('R1', 'ram');
		$ram2 = self::item('R2', 'ram');
		$cpu = self::item('C123', 'cpu');

		$pc->addContent($ram1)->addContent($ram2);
		$unchanged = ItemValidator::fixupLocation($cpu, $pc);
		$this->assertEquals($pc, $unchanged, 'Fixup shouldn\'t have changed parent, there\'s no motherboard');
	}

	public function testBoxOfJunk() {
		$box = self::item('PC77', 'location');
		$mobo = self::item('B123', 'motherboard');
		$ram = self::item('R555', 'ram');
		$cpu = self::item('C666', 'cpu');

		$box->addContent($ram)->addContent($mobo);
		$unchanged = ItemValidator::fixupLocation($cpu, $box);
		$this->assertEquals($box, $unchanged,
			'Fixup shouldn\'t have changed parent, items don\'t move to random motherboards in random boxes');
		ItemValidator::validateLocation($cpu, $box);
	}

	public function testValidFeatureV() {
		$pc = self::item('PC42', 'case');
		$pc->addFeature(new Feature('motherboard-form-factor', 'proprietary-laptop'));
		$pc->addFeature(new Feature('psu-volt', 19.0));

		ItemValidator::validateFeatures($pc);
		$this->assertTrue(true, 'No exceptions are thrown');
	}

	public function testValidFeatureV2() {
		$pc = self::item('PC42', 'case');
		$pc->addFeature(new Feature('color', 'green')); // no information on form factor
		$pc->addFeature(new Feature('psu-volt', 19.0));

		ItemValidator::validateFeatures($pc);
		$this->assertTrue(true, 'No exceptions are thrown');
	}

	public function testInvalidFeatureV() {
		$pc = self::item('PC42', 'case');
		$pc->addFeature(new Feature('motherboard-form-factor', 'atx'));
		$pc->addFeature(new Feature('psu-volt', 19.0));

		$this->expectException(ValidationException::class);
		ItemValidator::validateFeatures($pc);
	}

	public function testInvalidFeatureA() {
		$pc = self::item('PC42', 'case');
		$pc->addFeature(new Feature('motherboard-form-factor', 'atx'));
		$pc->addFeature(new Feature('psu-ampere', 4.20));

		$this->expectException(ValidationException::class);
		ItemValidator::validateFeatures($pc);
	}

	public function testInvalidFeatureConnector() {
		$pc = self::item('PC42', 'case');
		$pc->addFeature(new Feature('motherboard-form-factor', 'atx'));
		$pc->addFeature(new Feature('power-connector', 'c13'));

		$this->expectException(ValidationException::class);
		ItemValidator::validateFeatures($pc);
	}

	public function testInvalidFeaturePsuFF() {
		$pc = self::item('PC42', 'case');
		$pc->addFeature(new Feature('motherboard-form-factor', 'proprietary-laptop'));
		$pc->addFeature(new Feature('psu-form-factor', 'atx'));

		$this->expectException(ValidationException::class);
		ItemValidator::validateFeatures($pc);
	}

	public function testValidFeatureConnector() {
		$pc = self::item('PC42', 'case');
		$pc->addFeature(new Feature('motherboard-form-factor', 'proprietary'));
		$pc->addFeature(new Feature('power-connector', 'barrel'));

		ItemValidator::validateFeatures($pc);
		$this->assertTrue(true, 'No exceptions are thrown');
	}

	public function testValidFeatureConnector2() {
		$pc = self::item('PC42', 'case');
		$pc->addFeature(new Feature('motherboard-form-factor', 'proprietary-laptop'));
		$pc->addFeature(new Feature('power-connector', 'barrel'));

		ItemValidator::validateFeatures($pc);
		$this->assertTrue(true, 'No exceptions are thrown');
	}

	public function testValidFeatureMiniITX() {
		// MiniITX cases with integrated PSU that requires an external connector... well, maybe we should
		// add it to inventory as a separate item so the pc has 2 PSUs (external and internal), but I want
		// to leave this possibility open, too...
		$pc = self::item('PC42', 'case');
		$pc->addFeature(new Feature('motherboard-form-factor', 'miniitx'));
		$pc->addFeature(new Feature('power-connector', 'barrel'));
		$pc->addFeature(new Feature('psu-ampere', 5.2));
		$pc->addFeature(new Feature('psu-volt', 19.0));

		ItemValidator::validateFeatures($pc);
		$this->assertTrue(true, 'No exceptions are thrown');
	}

	public function testValidFeatureATX() {
		$pc = self::item('PC42', 'case');
		$pc->addFeature(new Feature('motherboard-form-factor', 'atx'));
		$pc->addFeature(new Feature('psu-form-factor', 'atx'));
		$pc->addFeature(new Feature('color', 'red'));

		ItemValidator::validateFeatures($pc);
		$this->assertTrue(true, 'No exceptions are thrown');
	}

	public function testValidUSB() {
		$pc = self::item('PC42', 'case');
		$pc->addFeature(new Feature('motherboard-form-factor', 'btx'));
		$pc->addFeature(new Feature('usb-ports-n', 2));

		ItemValidator::validateFeatures($pc);
		$this->assertTrue(true, 'No exceptions are thrown');
	}

	public function testValidUSB2() {
		$pc = self::item('PC42', 'case');
		$pc->addFeature(new Feature('motherboard-form-factor', 'proprietary'));
		$pc->addFeature(new Feature('usb-ports-n', 2));

		ItemValidator::validateFeatures($pc);
		$this->assertTrue(true, 'No exceptions are thrown');
	}

	public function testValidUSB3() {
		$pc = self::item('PC42', 'case');
		$pc->addFeature(new Feature('motherboard-form-factor', 'proprietary-laptop'));
		$mobo = self::item('MOBO55', 'motherboard');
		$mobo->addFeature(new Feature('usb-ports-n', 4));
		$pc->addContent($mobo);

		ItemValidator::validateFeatures($pc);
		$this->assertTrue(true, 'No exceptions are thrown');
	}

	public function testInvalidUSBLaptop() {
		$pc = self::item('PC42', 'case');
		$pc->addFeature(new Feature('motherboard-form-factor', 'proprietary-laptop'));
		$pc->addFeature(new Feature('usb-ports-n', 4));
		$mobo = self::item('MOBO55', 'motherboard');
		$pc->addContent($mobo);

		$this->expectException(ValidationException::class);
		ItemValidator::validateFeatures($pc);
	}

	public function testInvalidUSBLaptopFixup() {
		$pc = self::item('PC42', 'case');
		$pc->addFeature(new Feature('motherboard-form-factor', 'proprietary-laptop'));
		$pc->addFeature(new Feature('usb-ports-n', 4));
		$mobo = self::item('MOBO55', 'motherboard');
		$pc->addContent($mobo);

		ItemValidator::fixupFeatures($pc);
		ItemValidator::validateFeatures($pc);
		$this->assertTrue(true, 'No exceptions are thrown');
	}

	public function testInvalidUSBLaptopFixupNoMobo() {
		$pc = self::item('PC42', 'case');
		$pc->addFeature(new Feature('motherboard-form-factor', 'proprietary-laptop'));
		$pc->addFeature(new Feature('usb-ports-n', 4));
		$hdd = self::item('S123', 'hdd');
		$pc->addContent($hdd);

		ItemValidator::fixupFeatures($pc);
		$this->expectException(ValidationException::class);
		ItemValidator::validateFeatures($pc);
	}

	public function testInvalidCaseInCase() {
		$pc = self::item('PC42', 'case');
		$pc2 = self::item('PC24', 'case');

		$this->expectException(ItemNestingException::class);
		ItemValidator::validateLocation($pc, $pc2);
	}

	public function testInvalidODD() {
		$mobo = self::item('B42', 'motherboard');
		$item = self::item('Boh', 'odd');

		$this->expectException(ItemNestingException::class);
		ItemValidator::validateLocation($item, $mobo);
	}

	public function testInvalidHDD() {
		$mobo = self::item('B42', 'motherboard');
		$item = self::item('Boh', 'hdd');

		$this->expectException(ItemNestingException::class);
		ItemValidator::validateLocation($item, $mobo);
	}

	public function testRAMFFMismatch() {
		$mobo = self::item('B42', 'motherboard');
		$mobo->addFeature(new Feature('ram-form-factor', 'dimm'));
		$mobo->addFeature(new Feature('ram-type', 'ddr2'));
		$item = self::item('R555', 'ram');
		$item->addFeature(new Feature('ram-form-factor', 'sodimm'));
		$item->addFeature(new Feature('ram-type', 'ddr2'));

		$this->expectException(ItemNestingException::class);
		ItemValidator::validateLocation($item, $mobo);
	}

	public function testRAMFFMismatchDeep() {
		$pc = self::item('PC59', 'case');
		$mobo = self::item('B42', 'motherboard');
		$mobo->addFeature(new Feature('ram-form-factor', 'dimm'));
		$mobo->addFeature(new Feature('ram-type', 'ddr2'));
		$item = self::item('R555', 'ram');
		$item->addFeature(new Feature('ram-form-factor', 'sodimm'));
		$item->addFeature(new Feature('ram-type', 'ddr2'));
		$mobo->addContent($item);

		$this->expectException(ItemNestingException::class);
		ItemValidator::validateLocation($mobo, $pc);
	}

	public function testRAMTypeMismatch() {
		$mobo = self::item('B42', 'motherboard');
		$mobo->addFeature(new Feature('ram-form-factor', 'dimm'));
		$mobo->addFeature(new Feature('ram-type', 'ddr2'));
		$item = self::item('R555', 'ram');
		$item->addFeature(new Feature('ram-form-factor', 'dimm'));
		$item->addFeature(new Feature('ram-type', 'ddr'));

		$this->expectException(ItemNestingException::class);
		ItemValidator::validateLocation($item, $mobo);
	}

	public function testCPUSocketMismatch() {
		$mobo = self::item('B42', 'motherboard');
		$mobo->addFeature(new Feature('cpu-socket', 'socket478'));
		$item = self::item('C555', 'cpu');
		$item->addFeature(new Feature('cpu-socket', 'lga1155'));

		$this->expectException(ItemNestingException::class);
		ItemValidator::validateLocation($item, $mobo);
	}

	public function testCPUSocketUnknown() {
		$mobo = self::item('B42', 'motherboard');
		$item = self::item('C555', 'cpu');
		$item->addFeature(new Feature('cpu-socket', 'lga1155'));

		ItemValidator::validateLocation($item, $mobo);
		$this->assertTrue(true, 'No exceptions are thrown');
	}

	public function testCPUSocketUnknown2() {
		$mobo = self::item('B42', 'motherboard');
		$mobo->addFeature(new Feature('cpu-socket', 'socket478'));
		$item = self::item('C555', 'cpu');

		ItemValidator::validateLocation($item, $mobo);
		$this->assertTrue(true, 'No exceptions are thrown');
	}

	public function testCPUSocketUnknown3() {
		$mobo = self::item('B42', 'motherboard');
		$item = self::item('C555', 'cpu');

		ItemValidator::validateLocation($item, $mobo);
		$this->assertTrue(true, 'No exceptions are thrown');
	}
}