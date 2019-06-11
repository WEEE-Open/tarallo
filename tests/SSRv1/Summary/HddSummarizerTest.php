<?php

use PHPUnit\Framework\TestCase;
use WEEEOpen\Tarallo\Server\Feature;
use WEEEOpen\Tarallo\Server\Item;
use WEEEOpen\Tarallo\SSRv1\Summary\HddSummarizer;

/**
 * @covers \WEEEOpen\Tarallo\SSRv1\Summary\HddSummarizer
 */
class HddSummarizerTest extends TestCase {
	public function testHdd() {
		$item = new Item('H123');
		$item
			->addFeature(new Feature('brand', 'Seagate'))
			->addFeature(new Feature('family', 'Barracuda'))
			->addFeature(new Feature('capacity-byte', 80000000000))
			->addFeature(new Feature('spin-rate-rpm', 7200))
			->addFeature(new Feature('model', 'STM123456XYZ'))
			->addFeature(new Feature('owner', 'Area IT'))
			->addFeature(new Feature('sn', 'ABCD123123123'))
			->addFeature(new Feature('sata-ports-n', 1))
			->addFeature(new Feature('hdd-form-factor', "3.5"))
			->addFeature(new Feature('data-erased', 'yes'))
			->addFeature(new Feature('smart-data', 'ok'))
			->addFeature(new Feature('surface-scan', 'pass'))
			->addFeature(new Feature('software', 'Xubuntu 18.04 LTS'))
			->addFeature(new Feature('type', 'hdd'))
			->addFeature(new Feature('working', 'yes'));

		$summary = HddSummarizer::summarize($item);
		$this->assertEquals('HDD 80 GB SATA 3.5 in. 7200 rpm, ESP, Seagate Barracuda STM123456XYZ, Xubuntu 18.04 LTS', $summary);

		return $summary;
	}

	public function testHdd2() {
		$item = new Item('H456');
		$item
			->addFeature(new Feature('brand', 'Western Digital'))
			->addFeature(new Feature('family', 'Caviar'))
			->addFeature(new Feature('capacity-byte', 40000000000))
			->addFeature(new Feature('spin-rate-rpm', 5400))
			->addFeature(new Feature('model', 'WD40ASD'))
			->addFeature(new Feature('owner', 'Area IT'))
			->addFeature(new Feature('sn', 'ABCD123123123'))
			->addFeature(new Feature('sata-ports-n', 1))
			->addFeature(new Feature('hdd-form-factor', "2.5-9.5mm"))
			->addFeature(new Feature('data-erased', 'yes'))
			->addFeature(new Feature('smart-data', 'ok'))
			->addFeature(new Feature('surface-scan', 'pass'))
			->addFeature(new Feature('software', 'Windows XP'))
			->addFeature(new Feature('type', 'hdd'))
			->addFeature(new Feature('working', 'yes'));

		$summary = HddSummarizer::summarize($item);
		// "2.5 in. (9.5 mm)" is "hdd-form-factor"
		$this->assertEquals('HDD 40 GB SATA 2.5 in. (9.5 mm) 5400 rpm, ESP, Western Digital Caviar WD40ASD, Windows XP',
			$summary);

		return $summary;
	}
	
	public function testHddNoFamily() {
		$item = new Item('H123');
		$item
			->addFeature(new Feature('brand', 'Seagate'))
			->addFeature(new Feature('capacity-byte', 80000000000))
			->addFeature(new Feature('spin-rate-rpm', 7200))
			->addFeature(new Feature('model', 'STM123456XYZ'))
			->addFeature(new Feature('owner', 'Area IT'))
			->addFeature(new Feature('sn', 'ABCD123123123'))
			->addFeature(new Feature('sata-ports-n', 1))
			->addFeature(new Feature('hdd-form-factor', "3.5"))
			->addFeature(new Feature('data-erased', 'yes'))
			->addFeature(new Feature('smart-data', 'ok'))
			->addFeature(new Feature('surface-scan', 'pass'))
			->addFeature(new Feature('software', 'Xubuntu 18.04 LTS'))
			->addFeature(new Feature('type', 'hdd'))
			->addFeature(new Feature('working', 'yes'));

		$summary = HddSummarizer::summarize($item);
		$this->assertEquals('HDD 80 GB SATA 3.5 in. 7200 rpm, ESP, Seagate STM123456XYZ, Xubuntu 18.04 LTS', $summary);

		return $summary;
	}

	public function testHddNoCommercial() {
		$item = new Item('H123');
		$item
			->addFeature(new Feature('capacity-byte', 80000000000))
			->addFeature(new Feature('spin-rate-rpm', 7200))
			->addFeature(new Feature('owner', 'Area IT'))
			->addFeature(new Feature('sn', 'ABCD123123123'))
			->addFeature(new Feature('sata-ports-n', 1))
			->addFeature(new Feature('hdd-form-factor', "3.5"))
			->addFeature(new Feature('data-erased', 'yes'))
			->addFeature(new Feature('smart-data', 'ok'))
			->addFeature(new Feature('surface-scan', 'pass'))
			->addFeature(new Feature('software', 'Xubuntu 18.04 LTS'))
			->addFeature(new Feature('type', 'hdd'))
			->addFeature(new Feature('working', 'yes'));

		$summary = HddSummarizer::summarize($item);
		$this->assertEquals('HDD 80 GB SATA 3.5 in. 7200 rpm, ESP, Xubuntu 18.04 LTS', $summary);

		return $summary;
	}

	public function testHddNoOs() {
		$item = new Item('H123');
		$item
			->addFeature(new Feature('brand', 'Seagate'))
			->addFeature(new Feature('family', 'Barracuda'))
			->addFeature(new Feature('capacity-byte', 80000000000))
			->addFeature(new Feature('spin-rate-rpm', 7200))
			->addFeature(new Feature('model', 'STM123456XYZ'))
			->addFeature(new Feature('owner', 'Area IT'))
			->addFeature(new Feature('sn', 'ABCD123123123'))
			->addFeature(new Feature('sata-ports-n', 1))
			->addFeature(new Feature('hdd-form-factor', "3.5"))
			->addFeature(new Feature('data-erased', 'yes'))
			->addFeature(new Feature('smart-data', 'ok'))
			->addFeature(new Feature('surface-scan', 'pass'))
			->addFeature(new Feature('type', 'hdd'))
			->addFeature(new Feature('working', 'yes'));

		$summary = HddSummarizer::summarize($item);
		$this->assertEquals('HDD 80 GB SATA 3.5 in. 7200 rpm, ESP, Seagate Barracuda STM123456XYZ', $summary);

		return $summary;
	}

	public function testHddNoOsNoCommercial() {
		$item = new Item('H123');
		$item
			->addFeature(new Feature('capacity-byte', 80000000000))
			->addFeature(new Feature('spin-rate-rpm', 7200))
			->addFeature(new Feature('owner', 'Area IT'))
			->addFeature(new Feature('sn', 'ABCD123123123'))
			->addFeature(new Feature('sata-ports-n', 1))
			->addFeature(new Feature('hdd-form-factor', "3.5"))
			->addFeature(new Feature('data-erased', 'yes'))
			->addFeature(new Feature('smart-data', 'ok'))
			->addFeature(new Feature('surface-scan', 'pass'))
			->addFeature(new Feature('type', 'hdd'))
			->addFeature(new Feature('working', 'yes'));

		$summary = HddSummarizer::summarize($item);
		$this->assertEquals('HDD 80 GB SATA 3.5 in. 7200 rpm, ESP', $summary);

		return $summary;
	}

	public function testHdd2Sata() {
		$item = new Item('H123');
		$item
			->addFeature(new Feature('brand', 'Seagate'))
			->addFeature(new Feature('family', 'Barracuda'))
			->addFeature(new Feature('capacity-byte', 80000000000))
			->addFeature(new Feature('spin-rate-rpm', 7200))
			->addFeature(new Feature('model', 'STM123456XYZ'))
			->addFeature(new Feature('owner', 'Area IT'))
			->addFeature(new Feature('sn', 'ABCD123123123'))
			->addFeature(new Feature('sata-ports-n', 2))
			->addFeature(new Feature('hdd-form-factor', "3.5"))
			->addFeature(new Feature('data-erased', 'yes'))
			->addFeature(new Feature('smart-data', 'ok'))
			->addFeature(new Feature('surface-scan', 'pass'))
			->addFeature(new Feature('software', 'Xubuntu 18.04 LTS'))
			->addFeature(new Feature('type', 'hdd'))
			->addFeature(new Feature('working', 'yes'));

		$summary = HddSummarizer::summarize($item);
		$this->assertEquals('HDD 80 GB 2×SATA 3.5 in. 7200 rpm, ESP, Seagate Barracuda STM123456XYZ, Xubuntu 18.04 LTS', $summary);

		return $summary;
	}

	public function testHddIde() {
		$item = new Item('H123');
		$item
			->addFeature(new Feature('brand', 'Seagate'))
			->addFeature(new Feature('family', 'Barracuda'))
			->addFeature(new Feature('capacity-byte', 80000000000))
			->addFeature(new Feature('spin-rate-rpm', 7200))
			->addFeature(new Feature('model', 'STM123456XYZ'))
			->addFeature(new Feature('owner', 'Area IT'))
			->addFeature(new Feature('sn', 'ABCD123123123'))
			->addFeature(new Feature('ide-ports-n', 1))
			->addFeature(new Feature('hdd-form-factor', "3.5"))
			->addFeature(new Feature('data-erased', 'yes'))
			->addFeature(new Feature('smart-data', 'ok'))
			->addFeature(new Feature('surface-scan', 'pass'))
			->addFeature(new Feature('software', 'Xubuntu 18.04 LTS'))
			->addFeature(new Feature('type', 'hdd'))
			->addFeature(new Feature('working', 'yes'));

		$summary = HddSummarizer::summarize($item);
		$this->assertEquals('HDD 80 GB IDE/ATA 3.5 in. 7200 rpm, ESP, Seagate Barracuda STM123456XYZ, Xubuntu 18.04 LTS', $summary);

		return $summary;
	}

	public function testHddMiniIde() {
		$item = new Item('H123');
		$item
			->addFeature(new Feature('brand', 'Seagate'))
			->addFeature(new Feature('family', 'Barracuda'))
			->addFeature(new Feature('capacity-byte', 80000000000))
			->addFeature(new Feature('spin-rate-rpm', 7200))
			->addFeature(new Feature('model', 'STM123456XYZ'))
			->addFeature(new Feature('owner', 'Area IT'))
			->addFeature(new Feature('sn', 'ABCD123123123'))
			->addFeature(new Feature('mini-ide-ports-n', 1))
			->addFeature(new Feature('hdd-form-factor', "3.5"))
			->addFeature(new Feature('data-erased', 'yes'))
			->addFeature(new Feature('smart-data', 'ok'))
			->addFeature(new Feature('surface-scan', 'pass'))
			->addFeature(new Feature('software', 'Xubuntu 18.04 LTS'))
			->addFeature(new Feature('type', 'hdd'))
			->addFeature(new Feature('working', 'yes'));

		$summary = HddSummarizer::summarize($item);
		$this->assertEquals('HDD 80 GB SATA 3.5 in. 7200 rpm, ESP, Seagate Barracuda STM123456XYZ, Xubuntu 18.04 LTS', $summary);

		return $summary;
	}

	public function testHddScsi() {
		$item = new Item('H123');
		$item
			->addFeature(new Feature('brand', 'Seagate'))
			->addFeature(new Feature('family', 'Barracuda'))
			->addFeature(new Feature('capacity-byte', 80000000000))
			->addFeature(new Feature('spin-rate-rpm', 7200))
			->addFeature(new Feature('model', 'STM123456XYZ'))
			->addFeature(new Feature('owner', 'Area IT'))
			->addFeature(new Feature('sn', 'ABCD123123123'))
			->addFeature(new Feature('scsi-sca2-ports-n', 1))
			->addFeature(new Feature('hdd-form-factor', "3.5"))
			->addFeature(new Feature('data-erased', 'yes'))
			->addFeature(new Feature('smart-data', 'ok'))
			->addFeature(new Feature('surface-scan', 'pass'))
			->addFeature(new Feature('software', 'Xubuntu 18.04 LTS'))
			->addFeature(new Feature('type', 'hdd'))
			->addFeature(new Feature('working', 'yes'));

		$summary = HddSummarizer::summarize($item);
		$this->assertEquals('HDD 80 GB SCSI SCA2 (80 pin) 3.5 in. 7200 rpm, ESP, Seagate Barracuda STM123456XYZ, Xubuntu 18.04 LTS', $summary);

		return $summary;
	}

	public function testHddScsi2() {
		$item = new Item('H123');
		$item
			->addFeature(new Feature('brand', 'Seagate'))
			->addFeature(new Feature('family', 'Barracuda'))
			->addFeature(new Feature('capacity-byte', 80000000000))
			->addFeature(new Feature('spin-rate-rpm', 7200))
			->addFeature(new Feature('model', 'STM123456XYZ'))
			->addFeature(new Feature('owner', 'Area IT'))
			->addFeature(new Feature('sn', 'ABCD123123123'))
			->addFeature(new Feature('scsi-db68-ports-n', 1))
			->addFeature(new Feature('hdd-form-factor', "3.5"))
			->addFeature(new Feature('data-erased', 'yes'))
			->addFeature(new Feature('smart-data', 'ok'))
			->addFeature(new Feature('surface-scan', 'pass'))
			->addFeature(new Feature('software', 'Xubuntu 18.04 LTS'))
			->addFeature(new Feature('type', 'hdd'))
			->addFeature(new Feature('working', 'yes'));

		$summary = HddSummarizer::summarize($item);
		$this->assertEquals('HDD 80 GB SCSI DB68 (68 pin) 3.5 in. 7200 rpm, ESP, Seagate Barracuda STM123456XYZ, Xubuntu 18.04 LTS', $summary);

		return $summary;
	}

	public function testHddWTF() {
		$item = new Item('H123');
		$item
			->addFeature(new Feature('brand', 'Seagate'))
			->addFeature(new Feature('family', 'Barracuda'))
			->addFeature(new Feature('capacity-byte', 80000000000))
			->addFeature(new Feature('spin-rate-rpm', 7200))
			->addFeature(new Feature('model', 'STM123456XYZ'))
			->addFeature(new Feature('owner', 'Area IT'))
			->addFeature(new Feature('sn', 'ABCD123123123'))
			->addFeature(new Feature('sata-ports-n', 1))
			->addFeature(new Feature('ide-ports-n', 1))
			->addFeature(new Feature('hdd-form-factor', "3.5"))
			->addFeature(new Feature('data-erased', 'yes'))
			->addFeature(new Feature('smart-data', 'ok'))
			->addFeature(new Feature('surface-scan', 'pass'))
			->addFeature(new Feature('software', 'Xubuntu 18.04 LTS'))
			->addFeature(new Feature('type', 'hdd'))
			->addFeature(new Feature('working', 'yes'));

		$summary = HddSummarizer::summarize($item);
		$this->assertEquals('HDD 80 GB SATA + IDE/ATA 3.5 in. 7200 rpm, ESP, Seagate Barracuda STM123456XYZ, Xubuntu 18.04 LTS', $summary);

		return $summary;
	}

	public function testHddWTF2() {
		$item = new Item('H123');
		$item
			->addFeature(new Feature('brand', 'Seagate'))
			->addFeature(new Feature('family', 'Barracuda'))
			->addFeature(new Feature('capacity-byte', 80000000000))
			->addFeature(new Feature('spin-rate-rpm', 7200))
			->addFeature(new Feature('model', 'STM123456XYZ'))
			->addFeature(new Feature('owner', 'Area IT'))
			->addFeature(new Feature('sn', 'ABCD123123123'))
			->addFeature(new Feature('sata-ports-n', 1))
			->addFeature(new Feature('ide-ports-n', 1))
			->addFeature(new Feature('mini-ide-ports-n', 1))
			->addFeature(new Feature('hdd-form-factor', "3.5"))
			->addFeature(new Feature('data-erased', 'yes'))
			->addFeature(new Feature('smart-data', 'ok'))
			->addFeature(new Feature('surface-scan', 'pass'))
			->addFeature(new Feature('software', 'Xubuntu 18.04 LTS'))
			->addFeature(new Feature('type', 'hdd'))
			->addFeature(new Feature('working', 'yes'));

		$summary = HddSummarizer::summarize($item);
		$this->assertEquals('HDD 80 GB SATA + IDE/ATA + Mini IDE 3.5 in. 7200 rpm, ESP, Seagate Barracuda STM123456XYZ, Xubuntu 18.04 LTS', $summary);

		return $summary;
	}


	public function testHddWTFThisIsAJoke() {
		$item = new Item('H123');
		$item
			->addFeature(new Feature('brand', 'Seagate'))
			->addFeature(new Feature('family', 'Barracuda'))
			->addFeature(new Feature('capacity-byte', 80000000000))
			->addFeature(new Feature('spin-rate-rpm', 7200))
			->addFeature(new Feature('model', 'STM123456XYZ'))
			->addFeature(new Feature('owner', 'Area IT'))
			->addFeature(new Feature('sn', 'ABCD123123123'))
			->addFeature(new Feature('sata-ports-n', 2))
			->addFeature(new Feature('ide-ports-n', 2))
			->addFeature(new Feature('mini-ide-ports-n', 2))
			->addFeature(new Feature('hdd-form-factor', "3.5"))
			->addFeature(new Feature('data-erased', 'yes'))
			->addFeature(new Feature('smart-data', 'ok'))
			->addFeature(new Feature('surface-scan', 'pass'))
			->addFeature(new Feature('software', 'Xubuntu 18.04 LTS'))
			->addFeature(new Feature('type', 'hdd'))
			->addFeature(new Feature('working', 'yes'));

		$summary = HddSummarizer::summarize($item);
		$this->assertEquals('HDD 80 GB 2×SATA + 2×IDE/ATA + 2×Mini IDE 3.5 in. 7200 rpm, ESP, Seagate Barracuda STM123456XYZ, Xubuntu 18.04 LTS', $summary);

		return $summary;
	}

	public function testHddNotErased() {
		$item = new Item('H123');
		$item
			->addFeature(new Feature('brand', 'Seagate'))
			->addFeature(new Feature('family', 'Barracuda'))
			->addFeature(new Feature('capacity-byte', 80000000000))
			->addFeature(new Feature('spin-rate-rpm', 7200))
			->addFeature(new Feature('model', 'STM123456XYZ'))
			->addFeature(new Feature('owner', 'Area IT'))
			->addFeature(new Feature('sn', 'ABCD123123123'))
			->addFeature(new Feature('sata-ports-n', 1))
			->addFeature(new Feature('hdd-form-factor', "3.5"))
			->addFeature(new Feature('smart-data', 'ok'))
			->addFeature(new Feature('surface-scan', 'pass'))
			->addFeature(new Feature('software', 'Xubuntu 18.04 LTS'))
			->addFeature(new Feature('type', 'hdd'))
			->addFeature(new Feature('working', 'yes'));

		$summary = HddSummarizer::summarize($item);
		$this->assertEquals('HDD 80 GB SATA 3.5 in. 7200 rpm, _SP, Seagate Barracuda STM123456XYZ, Xubuntu 18.04 LTS', $summary);

		return $summary;
	}

	public function testHddNoSmart() {
		$item = new Item('H123');
		$item
			->addFeature(new Feature('brand', 'Seagate'))
			->addFeature(new Feature('family', 'Barracuda'))
			->addFeature(new Feature('capacity-byte', 80000000000))
			->addFeature(new Feature('spin-rate-rpm', 7200))
			->addFeature(new Feature('model', 'STM123456XYZ'))
			->addFeature(new Feature('owner', 'Area IT'))
			->addFeature(new Feature('sn', 'ABCD123123123'))
			->addFeature(new Feature('sata-ports-n', 1))
			->addFeature(new Feature('hdd-form-factor', "3.5"))
			->addFeature(new Feature('data-erased', 'yes'))
			->addFeature(new Feature('surface-scan', 'pass'))
			->addFeature(new Feature('software', 'Xubuntu 18.04 LTS'))
			->addFeature(new Feature('type', 'hdd'))
			->addFeature(new Feature('working', 'yes'));

		$summary = HddSummarizer::summarize($item);
		$this->assertEquals('HDD 80 GB SATA 3.5 in. 7200 rpm, E_P, Seagate Barracuda STM123456XYZ, Xubuntu 18.04 LTS', $summary);

		return $summary;
	}

	public function testHddNoSurface() {
		$item = new Item('H123');
		$item
			->addFeature(new Feature('brand', 'Seagate'))
			->addFeature(new Feature('family', 'Barracuda'))
			->addFeature(new Feature('capacity-byte', 80000000000))
			->addFeature(new Feature('spin-rate-rpm', 7200))
			->addFeature(new Feature('model', 'STM123456XYZ'))
			->addFeature(new Feature('owner', 'Area IT'))
			->addFeature(new Feature('sn', 'ABCD123123123'))
			->addFeature(new Feature('sata-ports-n', 1))
			->addFeature(new Feature('hdd-form-factor', "3.5"))
			->addFeature(new Feature('data-erased', 'yes'))
			->addFeature(new Feature('smart-data', 'ok'))
			->addFeature(new Feature('software', 'Xubuntu 18.04 LTS'))
			->addFeature(new Feature('type', 'hdd'))
			->addFeature(new Feature('working', 'yes'));

		$summary = HddSummarizer::summarize($item);
		$this->assertEquals('HDD 80 GB SATA 3.5 in. 7200 rpm, ES_, Seagate Barracuda STM123456XYZ, Xubuntu 18.04 LTS', $summary);

		return $summary;
	}

	public function testHddNoSmartScan() {
		$item = new Item('H123');
		$item
			->addFeature(new Feature('brand', 'Seagate'))
			->addFeature(new Feature('family', 'Barracuda'))
			->addFeature(new Feature('capacity-byte', 80000000000))
			->addFeature(new Feature('spin-rate-rpm', 7200))
			->addFeature(new Feature('model', 'STM123456XYZ'))
			->addFeature(new Feature('owner', 'Area IT'))
			->addFeature(new Feature('sn', 'ABCD123123123'))
			->addFeature(new Feature('sata-ports-n', 1))
			->addFeature(new Feature('hdd-form-factor', "3.5"))
			->addFeature(new Feature('data-erased', 'yes'))
			->addFeature(new Feature('software', 'Xubuntu 18.04 LTS'))
			->addFeature(new Feature('type', 'hdd'))
			->addFeature(new Feature('working', 'yes'));

		$summary = HddSummarizer::summarize($item);
		$this->assertEquals('HDD 80 GB SATA 3.5 in. 7200 rpm, E__, Seagate Barracuda STM123456XYZ, Xubuntu 18.04 LTS',
			$summary);

		return $summary;
	}

	public function testHddNoProcedures() {
		$item = new Item('H123');
		$item
			->addFeature(new Feature('brand', 'Seagate'))
			->addFeature(new Feature('family', 'Barracuda'))
			->addFeature(new Feature('capacity-byte', 80000000000))
			->addFeature(new Feature('spin-rate-rpm', 7200))
			->addFeature(new Feature('model', 'STM123456XYZ'))
			->addFeature(new Feature('owner', 'Area IT'))
			->addFeature(new Feature('sn', 'ABCD123123123'))
			->addFeature(new Feature('sata-ports-n', 1))
			->addFeature(new Feature('surface-scan', 'pass'))
			->addFeature(new Feature('software', 'Xubuntu 18.04 LTS'))
			->addFeature(new Feature('type', 'hdd'))
			->addFeature(new Feature('working', 'yes'));

		$summary = HddSummarizer::summarize($item);
		$this->assertEquals('HDD 80 GB SATA 3.5 in. 7200 rpm, ___, Seagate Barracuda STM123456XYZ, Xubuntu 18.04 LTS',
			$summary);

		return $summary;
	}


	public function testHddNothing() {
		$item = new Item('H123');
		$item
			->addFeature(new Feature('type', 'hdd'));

		$summary = HddSummarizer::summarize($item);
		$this->assertEquals('HDD, ___', $summary);

		return $summary;
	}


	public function testHddSmartOld() {
		$item = new Item('H123');
		$item
			->addFeature(new Feature('brand', 'Seagate'))
			->addFeature(new Feature('family', 'Barracuda'))
			->addFeature(new Feature('capacity-byte', 80000000000))
			->addFeature(new Feature('spin-rate-rpm', 7200))
			->addFeature(new Feature('model', 'STM123456XYZ'))
			->addFeature(new Feature('owner', 'Area IT'))
			->addFeature(new Feature('sn', 'ABCD123123123'))
			->addFeature(new Feature('sata-ports-n', 1))
			->addFeature(new Feature('hdd-form-factor', "3.5"))
			->addFeature(new Feature('data-erased', 'yes'))
			->addFeature(new Feature('smart-data', 'old'))
			->addFeature(new Feature('surface-scan', 'pass'))
			->addFeature(new Feature('software', 'Xubuntu 18.04 LTS'))
			->addFeature(new Feature('type', 'hdd'))
			->addFeature(new Feature('working', 'yes'));

		$summary = HddSummarizer::summarize($item);
		$this->assertEquals('HDD 80 GB SATA 3.5 in. 7200 rpm, EOP, Seagate Barracuda STM123456XYZ, Xubuntu 18.04 LTS', $summary);

		return $summary;
	}


	public function testHddScanFail() {
		$item = new Item('H123');
		$item
			->addFeature(new Feature('brand', 'Seagate'))
			->addFeature(new Feature('family', 'Barracuda'))
			->addFeature(new Feature('capacity-byte', 80000000000))
			->addFeature(new Feature('spin-rate-rpm', 7200))
			->addFeature(new Feature('model', 'STM123456XYZ'))
			->addFeature(new Feature('owner', 'Area IT'))
			->addFeature(new Feature('sn', 'ABCD123123123'))
			->addFeature(new Feature('sata-ports-n', 1))
			->addFeature(new Feature('hdd-form-factor', "3.5"))
			->addFeature(new Feature('data-erased', 'yes'))
			->addFeature(new Feature('smart-data', 'ok'))
			->addFeature(new Feature('surface-scan', 'fail'))
			->addFeature(new Feature('software', 'Xubuntu 18.04 LTS'))
			->addFeature(new Feature('type', 'hdd'))
			->addFeature(new Feature('working', 'yes'));

		$summary = HddSummarizer::summarize($item);
		$this->assertEquals('HDD 80 GB SATA 3.5 in. 7200 rpm, ESX, Seagate Barracuda STM123456XYZ, Xubuntu 18.04 LTS', $summary);

		return $summary;
	}


	public function testHddSmartFailed() {
		$item = new Item('H123');
		$item
			->addFeature(new Feature('brand', 'Seagate'))
			->addFeature(new Feature('family', 'Barracuda'))
			->addFeature(new Feature('capacity-byte', 80000000000))
			->addFeature(new Feature('spin-rate-rpm', 7200))
			->addFeature(new Feature('model', 'STM123456XYZ'))
			->addFeature(new Feature('owner', 'Area IT'))
			->addFeature(new Feature('sn', 'ABCD123123123'))
			->addFeature(new Feature('sata-ports-n', 1))
			->addFeature(new Feature('hdd-form-factor', "3.5"))
			->addFeature(new Feature('data-erased', 'yes'))
			->addFeature(new Feature('smart-data', 'fail'))
			->addFeature(new Feature('surface-scan', 'pass'))
			->addFeature(new Feature('software', 'Xubuntu 18.04 LTS'))
			->addFeature(new Feature('type', 'hdd'))
			->addFeature(new Feature('working', 'yes'));

		$summary = HddSummarizer::summarize($item);
		$this->assertEquals('HDD 80 GB SATA 3.5 in. 7200 rpm, EXP, Seagate Barracuda STM123456XYZ, Xubuntu 18.04 LTS', $summary);

		return $summary;
	}


	public function testHddNoFF() {
		$item = new Item('H123');
		$item
			->addFeature(new Feature('brand', 'Seagate'))
			->addFeature(new Feature('family', 'Barracuda'))
			->addFeature(new Feature('capacity-byte', 80000000000))
			->addFeature(new Feature('spin-rate-rpm', 7200))
			->addFeature(new Feature('model', 'STM123456XYZ'))
			->addFeature(new Feature('owner', 'Area IT'))
			->addFeature(new Feature('sn', 'ABCD123123123'))
			->addFeature(new Feature('sata-ports-n', 1))
			->addFeature(new Feature('data-erased', 'yes'))
			->addFeature(new Feature('smart-data', 'ok'))
			->addFeature(new Feature('surface-scan', 'pass'))
			->addFeature(new Feature('software', 'Xubuntu 18.04 LTS'))
			->addFeature(new Feature('type', 'hdd'))
			->addFeature(new Feature('working', 'yes'));

		$summary = HddSummarizer::summarize($item);
		$this->assertEquals('HDD 80 GB SATA 7200 rpm, ESP, Seagate Barracuda STM123456XYZ, Xubuntu 18.04 LTS', $summary);

		return $summary;
	}

	public function testHddNoRpm() {
		$item = new Item('H123');
		$item
			->addFeature(new Feature('brand', 'Seagate'))
			->addFeature(new Feature('family', 'Barracuda'))
			->addFeature(new Feature('capacity-byte', 80000000000))
			->addFeature(new Feature('model', 'STM123456XYZ'))
			->addFeature(new Feature('owner', 'Area IT'))
			->addFeature(new Feature('sn', 'ABCD123123123'))
			->addFeature(new Feature('sata-ports-n', 1))
			->addFeature(new Feature('hdd-form-factor', "3.5"))
			->addFeature(new Feature('data-erased', 'yes'))
			->addFeature(new Feature('smart-data', 'ok'))
			->addFeature(new Feature('surface-scan', 'pass'))
			->addFeature(new Feature('software', 'Xubuntu 18.04 LTS'))
			->addFeature(new Feature('type', 'hdd'))
			->addFeature(new Feature('working', 'yes'));

		$summary = HddSummarizer::summarize($item);
		$this->assertEquals('HDD 80 GB SATA 3.5 in., ESP, Seagate Barracuda STM123456XYZ, Xubuntu 18.04 LTS', $summary);

		return $summary;
	}

	public function testHddNoCapacity() {
		$item = new Item('H123');
		$item
			->addFeature(new Feature('brand', 'Seagate'))
			->addFeature(new Feature('family', 'Barracuda'))
			->addFeature(new Feature('capacity-byte', 80000000000))
			->addFeature(new Feature('spin-rate-rpm', 7200))
			->addFeature(new Feature('model', 'STM123456XYZ'))
			->addFeature(new Feature('owner', 'Area IT'))
			->addFeature(new Feature('sn', 'ABCD123123123'))
			->addFeature(new Feature('sata-ports-n', 1))
			->addFeature(new Feature('hdd-form-factor', "3.5"))
			->addFeature(new Feature('data-erased', 'yes'))
			->addFeature(new Feature('smart-data', 'ok'))
			->addFeature(new Feature('surface-scan', 'pass'))
			->addFeature(new Feature('software', 'Xubuntu 18.04 LTS'))
			->addFeature(new Feature('type', 'hdd'))
			->addFeature(new Feature('working', 'yes'));

		$summary = HddSummarizer::summarize($item);
		$this->assertEquals('HDD SATA 3.5 in. 7200 rpm, ESP, Seagate Barracuda STM123456XYZ, Xubuntu 18.04 LTS', $summary);

		return $summary;
	}
	public function testHddNoPortos() {
		$item = new Item('H123');
		$item
			->addFeature(new Feature('brand', 'Seagate'))
			->addFeature(new Feature('family', 'Barracuda'))
			->addFeature(new Feature('capacity-byte', 80000000000))
			->addFeature(new Feature('spin-rate-rpm', 7200))
			->addFeature(new Feature('model', 'STM123456XYZ'))
			->addFeature(new Feature('owner', 'Area IT'))
			->addFeature(new Feature('sn', 'ABCD123123123'))
			->addFeature(new Feature('hdd-form-factor', "3.5"))
			->addFeature(new Feature('data-erased', 'yes'))
			->addFeature(new Feature('smart-data', 'ok'))
			->addFeature(new Feature('surface-scan', 'pass'))
			->addFeature(new Feature('software', 'Xubuntu 18.04 LTS'))
			->addFeature(new Feature('type', 'hdd'))
			->addFeature(new Feature('working', 'yes'));

		$summary = HddSummarizer::summarize($item);
		$this->assertEquals('HDD 80 GB 3.5 in. 7200 rpm, ESP, Seagate Barracuda STM123456XYZ, Xubuntu 18.04 LTS', $summary);

		return $summary;
	}

	public function testHddMissingData() {
		$item = new Item('H123');
		$item
			->addFeature(new Feature('brand', 'Seagate'))
			->addFeature(new Feature('family', 'Barracuda'))
			->addFeature(new Feature('capacity-byte', 80000000000))
			->addFeature(new Feature('model', 'STM123456XYZ'))
			->addFeature(new Feature('owner', 'Area IT'))
			->addFeature(new Feature('sn', 'ABCD123123123'))
			->addFeature(new Feature('hdd-form-factor', "3.5"))
			->addFeature(new Feature('data-erased', 'yes'))
			->addFeature(new Feature('smart-data', 'ok'))
			->addFeature(new Feature('surface-scan', 'pass'))
			->addFeature(new Feature('software', 'Xubuntu 18.04 LTS'))
			->addFeature(new Feature('type', 'hdd'))
			->addFeature(new Feature('working', 'yes'));

		$summary = HddSummarizer::summarize($item);
		$this->assertEquals('HDD 80 GB 3.5 in., ESP, Seagate Barracuda STM123456XYZ, Xubuntu 18.04 LTS', $summary);

		return $summary;
	}

	public function testHddMissingData2() {
		$item = new Item('H123');
		$item
			->addFeature(new Feature('brand', 'Seagate'))
			->addFeature(new Feature('family', 'Barracuda'))
			->addFeature(new Feature('model', 'STM123456XYZ'))
			->addFeature(new Feature('owner', 'Area IT'))
			->addFeature(new Feature('sn', 'ABCD123123123'))
			->addFeature(new Feature('data-erased', 'yes'))
			->addFeature(new Feature('smart-data', 'ok'))
			->addFeature(new Feature('surface-scan', 'pass'))
			->addFeature(new Feature('software', 'Xubuntu 18.04 LTS'))
			->addFeature(new Feature('type', 'hdd'))
			->addFeature(new Feature('working', 'yes'));

		$summary = HddSummarizer::summarize($item);
		$this->assertEquals('HDD, ESP, Seagate Barracuda STM123456XYZ, Xubuntu 18.04 LTS', $summary);

		return $summary;
	}
}
