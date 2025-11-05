<?php

use WEEEOpen\Tarallo\Feature;
use WEEEOpen\Tarallo\Item;
use WEEEOpen\Tarallo\SSRv1\Summary\RamSummarizer;
use WEEEOpen\TaralloTest\SSRv1\Summary\SummarizerTestCase;

/**
 * @covers \WEEEOpen\Tarallo\SSRv1\Summary\RamSummarizer
 */
class RamSummarizerTest extends SummarizerTestCase
{
	public function testRam()
	{
		$item = new Item('R123');
		$item->addFeature(new Feature('brand', 'Kingston'))
			->addFeature(new Feature('capacity-byte', 2147483648))
			->addFeature(new Feature('color', 'green'))
			->addFeature(new Feature('frequency-hertz', 1066000000))
			->addFeature(new Feature('model', 'ACR256X64D3S13C9G'))
			->addFeature(new Feature('owner', 'Area IT'))
			->addFeature(new Feature('ram-ecc', 'no'))
			->addFeature(new Feature('ram-form-factor', 'sodimm'))
			->addFeature(new Feature('ram-type', 'ddr3'))
			->addFeature(new Feature('sn', 'E516B7B6'))
			->addFeature(new Feature('type', 'ram'))
			->addFeature(new Feature('working', 'yes'));

		$summary = RamSummarizer::summarize($item);
		$this->assertArrayEquals(["RAM DDR3 SODIMM 2 GiB 1.066 GHz", "Kingston ACR256X64D3S13C9G"], $summary);

		return $summary;
	}

	public function testRamNothing()
	{
		$item = new Item('R123');
		$item->addFeature(new Feature('type', 'ram'));

		$summary = RamSummarizer::summarize($item);
		$this->assertArrayEquals(["RAM"], $summary);

		return $summary;
	}

	public function testRamMissingFreq()
	{
		$item = new Item('R123');
		$item->addFeature(new Feature('brand', 'Kingston'))
			->addFeature(new Feature('capacity-byte', 2147483648))
			->addFeature(new Feature('color', 'green'))
			->addFeature(new Feature('model', 'ACR256X64D3S13C9G'))
			->addFeature(new Feature('owner', 'Area IT'))
			->addFeature(new Feature('ram-ecc', 'no'))
			->addFeature(new Feature('ram-form-factor', 'sodimm'))
			->addFeature(new Feature('ram-type', 'ddr3'))
			->addFeature(new Feature('sn', 'E516B7B6'))
			->addFeature(new Feature('type', 'ram'))
			->addFeature(new Feature('working', 'yes'));

		$summary = RamSummarizer::summarize($item);
		$this->assertArrayEquals(["RAM DDR3 SODIMM 2 GiB", "Kingston ACR256X64D3S13C9G"], $summary);

		return $summary;
	}

	public function testRamMissingSize()
	{
		$item = new Item('R123');
		$item->addFeature(new Feature('brand', 'Kingston'))
			->addFeature(new Feature('color', 'green'))
			->addFeature(new Feature('frequency-hertz', 1066000000))
			->addFeature(new Feature('model', 'ACR256X64D3S13C9G'))
			->addFeature(new Feature('owner', 'Area IT'))
			->addFeature(new Feature('ram-ecc', 'no'))
			->addFeature(new Feature('ram-form-factor', 'sodimm'))
			->addFeature(new Feature('ram-type', 'ddr3'))
			->addFeature(new Feature('sn', 'E516B7B6'))
			->addFeature(new Feature('type', 'ram'))
			->addFeature(new Feature('working', 'yes'));

		$summary = RamSummarizer::summarize($item);
		$this->assertArrayEquals(["RAM DDR3 SODIMM 1.066 GHz", "Kingston ACR256X64D3S13C9G"], $summary);

		return $summary;
	}

	public function testRamMissingSizeAndFreq()
	{
		$item = new Item('R123');
		$item->addFeature(new Feature('brand', 'Kingston'))
			->addFeature(new Feature('color', 'green'))
			->addFeature(new Feature('model', 'ACR256X64D3S13C9G'))
			->addFeature(new Feature('owner', 'Area IT'))
			->addFeature(new Feature('ram-ecc', 'no'))
			->addFeature(new Feature('ram-form-factor', 'sodimm'))
			->addFeature(new Feature('ram-type', 'ddr3'))
			->addFeature(new Feature('sn', 'E516B7B6'))
			->addFeature(new Feature('type', 'ram'))
			->addFeature(new Feature('working', 'yes'));

		$summary = RamSummarizer::summarize($item);
		$this->assertArrayEquals(["RAM DDR3 SODIMM", "Kingston ACR256X64D3S13C9G"], $summary);

		return $summary;
	}

	public function testRamMissingType()
	{
		$item = new Item('R123');
		$item->addFeature(new Feature('brand', 'Kingston'))
			->addFeature(new Feature('capacity-byte', 2147483648))
			->addFeature(new Feature('color', 'green'))
			->addFeature(new Feature('frequency-hertz', 1066000000))
			->addFeature(new Feature('model', 'ACR256X64D3S13C9G'))
			->addFeature(new Feature('owner', 'Area IT'))
			->addFeature(new Feature('ram-ecc', 'no'))
			->addFeature(new Feature('ram-form-factor', 'sodimm'))
			->addFeature(new Feature('sn', 'E516B7B6'))
			->addFeature(new Feature('type', 'ram'))
			->addFeature(new Feature('working', 'yes'));

		$summary = RamSummarizer::summarize($item);
		$this->assertArrayEquals(["RAM SODIMM 2 GiB 1.066 GHz", "Kingston ACR256X64D3S13C9G"], $summary);

		return $summary;
	}

	public function testRamMissingFormFactor()
	{
		$item = new Item('R123');
		$item->addFeature(new Feature('brand', 'Kingston'))
			->addFeature(new Feature('capacity-byte', 2147483648))
			->addFeature(new Feature('color', 'green'))
			->addFeature(new Feature('frequency-hertz', 1066000000))
			->addFeature(new Feature('model', 'ACR256X64D3S13C9G'))
			->addFeature(new Feature('owner', 'Area IT'))
			->addFeature(new Feature('ram-ecc', 'no'))
			->addFeature(new Feature('ram-type', 'ddr3'))
			->addFeature(new Feature('sn', 'E516B7B6'))
			->addFeature(new Feature('type', 'ram'))
			->addFeature(new Feature('working', 'yes'));

		$summary = RamSummarizer::summarize($item);
		$this->assertArrayEquals(["RAM DDR3 2 GiB 1.066 GHz", "Kingston ACR256X64D3S13C9G"], $summary);

		return $summary;
	}

	public function testRamMissingLotsOfStuff()
	{
		$item = new Item('R123');
		$item->addFeature(new Feature('brand', 'Kingston'))
			->addFeature(new Feature('model', 'ACR256X64D3S13C9G'))
			->addFeature(new Feature('type', 'ram'));

		$summary = RamSummarizer::summarize($item);
		$this->assertArrayEquals(["RAM", "Kingston ACR256X64D3S13C9G"], $summary);

		return $summary;
	}

	public function testRamMissingModel()
	{
		$item = new Item('R123');
		$item->addFeature(new Feature('brand', 'Kingston'))
			->addFeature(new Feature('capacity-byte', 2147483648))
			->addFeature(new Feature('color', 'green'))
			->addFeature(new Feature('frequency-hertz', 1066000000))
			->addFeature(new Feature('owner', 'Area IT'))
			->addFeature(new Feature('ram-ecc', 'no'))
			->addFeature(new Feature('ram-form-factor', 'sodimm'))
			->addFeature(new Feature('ram-type', 'ddr3'))
			->addFeature(new Feature('sn', 'E516B7B6'))
			->addFeature(new Feature('type', 'ram'))
			->addFeature(new Feature('working', 'yes'));

		$summary = RamSummarizer::summarize($item);
		$this->assertArrayEquals(["RAM DDR3 SODIMM 2 GiB 1.066 GHz", "Kingston"], $summary);

		return $summary;
	}

	public function testRamMissingBrand()
	{
		$item = new Item('R123');
		$item->addFeature(new Feature('capacity-byte', 2147483648))
			->addFeature(new Feature('color', 'green'))
			->addFeature(new Feature('frequency-hertz', 1066000000))
			->addFeature(new Feature('model', 'ACR256X64D3S13C9G'))
			->addFeature(new Feature('owner', 'Area IT'))
			->addFeature(new Feature('ram-ecc', 'no'))
			->addFeature(new Feature('ram-form-factor', 'sodimm'))
			->addFeature(new Feature('ram-type', 'ddr3'))
			->addFeature(new Feature('sn', 'E516B7B6'))
			->addFeature(new Feature('type', 'ram'))
			->addFeature(new Feature('working', 'yes'));

		$summary = RamSummarizer::summarize($item);
		$this->assertArrayEquals(["RAM DDR3 SODIMM 2 GiB 1.066 GHz", "ACR256X64D3S13C9G"], $summary);

		return $summary;
	}

	public function testRamMissingBrandAndModel()
	{
		$item = new Item('R123');
		$item->addFeature(new Feature('capacity-byte', 2147483648))
			->addFeature(new Feature('color', 'green'))
			->addFeature(new Feature('frequency-hertz', 1066000000))
			->addFeature(new Feature('owner', 'Area IT'))
			->addFeature(new Feature('ram-ecc', 'no'))
			->addFeature(new Feature('ram-form-factor', 'sodimm'))
			->addFeature(new Feature('ram-type', 'ddr3'))
			->addFeature(new Feature('sn', 'E516B7B6'))
			->addFeature(new Feature('type', 'ram'))
			->addFeature(new Feature('working', 'yes'));

		$summary = RamSummarizer::summarize($item);
		$this->assertArrayEquals(["RAM DDR3 SODIMM 2 GiB 1.066 GHz"], $summary);

		return $summary;
	}

	public function testRamEcc()
	{
		$item = new Item('R123');
		$item->addFeature(new Feature('brand', 'Kingston'))
			->addFeature(new Feature('capacity-byte', 2147483648))
			->addFeature(new Feature('color', 'green'))
			->addFeature(new Feature('frequency-hertz', 1066000000))
			->addFeature(new Feature('model', 'ACR256X64D3S13C9G'))
			->addFeature(new Feature('owner', 'Area IT'))
			->addFeature(new Feature('ram-ecc', 'yes'))
			->addFeature(new Feature('ram-form-factor', 'sodimm'))
			->addFeature(new Feature('ram-type', 'ddr3'))
			->addFeature(new Feature('sn', 'E516B7B6'))
			->addFeature(new Feature('type', 'ram'))
			->addFeature(new Feature('working', 'yes'));

		$summary = RamSummarizer::summarize($item);
		$this->assertArrayEquals(["RAM ECC DDR3 SODIMM 2 GiB 1.066 GHz", "Kingston ACR256X64D3S13C9G"], $summary);

		return $summary;
	}

	public function testRamNoEcc()
	{
		$item = new Item('R123');
		$item->addFeature(new Feature('brand', 'Kingston'))
			->addFeature(new Feature('capacity-byte', 2147483648))
			->addFeature(new Feature('color', 'green'))
			->addFeature(new Feature('frequency-hertz', 1066000000))
			->addFeature(new Feature('model', 'ACR256X64D3S13C9G'))
			->addFeature(new Feature('owner', 'Area IT'))
			->addFeature(new Feature('ram-form-factor', 'sodimm'))
			->addFeature(new Feature('ram-type', 'ddr3'))
			->addFeature(new Feature('sn', 'E516B7B6'))
			->addFeature(new Feature('type', 'ram'))
			->addFeature(new Feature('working', 'yes'));

		$summary = RamSummarizer::summarize($item);
		$this->assertArrayEquals(["RAM (ECC?) DDR3 SODIMM 2 GiB 1.066 GHz", "Kingston ACR256X64D3S13C9G"], $summary);

		return $summary;
	}

	public function testRamEccMissingType()
	{
		$item = new Item('R123');
		$item->addFeature(new Feature('brand', 'Kingston'))
			->addFeature(new Feature('capacity-byte', 2147483648))
			->addFeature(new Feature('color', 'green'))
			->addFeature(new Feature('frequency-hertz', 1066000000))
			->addFeature(new Feature('model', 'ACR256X64D3S13C9G'))
			->addFeature(new Feature('owner', 'Area IT'))
			->addFeature(new Feature('ram-ecc', 'yes'))
			->addFeature(new Feature('ram-form-factor', 'sodimm'))
			->addFeature(new Feature('sn', 'E516B7B6'))
			->addFeature(new Feature('type', 'ram'))
			->addFeature(new Feature('working', 'yes'));

		$summary = RamSummarizer::summarize($item);
		$this->assertArrayEquals(["RAM ECC SODIMM 2 GiB 1.066 GHz", "Kingston ACR256X64D3S13C9G"], $summary);

		return $summary;
	}

	public function testRamEccMissingStuff()
	{
		$item = new Item('R123');
		$item->addFeature(new Feature('brand', 'Kingston'))
			->addFeature(new Feature('capacity-byte', 2147483648))
			->addFeature(new Feature('color', 'green'))
			->addFeature(new Feature('frequency-hertz', 1066000000))
			->addFeature(new Feature('model', 'ACR256X64D3S13C9G'))
			->addFeature(new Feature('owner', 'Area IT'))
			->addFeature(new Feature('ram-ecc', 'yes'))
			->addFeature(new Feature('sn', 'E516B7B6'))
			->addFeature(new Feature('type', 'ram'))
			->addFeature(new Feature('working', 'yes'));

		$summary = RamSummarizer::summarize($item);
		$this->assertArrayEquals(["RAM ECC 2 GiB 1.066 GHz", "Kingston ACR256X64D3S13C9G"], $summary);

		return $summary;
	}

	public function testRamEccMissingFirstPart()
	{
		$item = new Item('R123');
		$item->addFeature(new Feature('brand', 'Kingston'))
			->addFeature(new Feature('model', 'ACR256X64D3S13C9G'))
			->addFeature(new Feature('ram-ecc', 'yes'))
			->addFeature(new Feature('type', 'ram'));

		$summary = RamSummarizer::summarize($item);
		$this->assertArrayEquals(["RAM ECC", "Kingston ACR256X64D3S13C9G"], $summary);

		return $summary;
	}

	public function testRamEccMissingEverything()
	{
		$item = new Item('R123');
		$item->addFeature(new Feature('ram-ecc', 'yes'))
			->addFeature(new Feature('type', 'ram'));

		$summary = RamSummarizer::summarize($item);
		$this->assertArrayEquals(["RAM ECC"], $summary);

		return $summary;
	}

	public function testRamSimm()
	{
		$item = new Item('R123');
		$item
			->addFeature(new Feature('brand', 'PTC'))
			->addFeature(new Feature('capacity-byte', 8388608))
			->addFeature(new Feature('model', 'M1V-0 9638'))
			->addFeature(new Feature('owner', 'DISAT'))
			->addFeature(new Feature('ram-ecc', 'no'))
			->addFeature(new Feature('ram-form-factor', 'simm'))
			->addFeature(new Feature('ram-type', 'simm'))
			->addFeature(new Feature('type', 'ram'));

		$summary = RamSummarizer::summarize($item);
		$this->assertArrayEquals(["RAM SIMM 8 MiB", "PTC M1V-0 9638"], $summary);

		return $summary;
	}

	public function testRamRDIMM()
	{
		$item = new Item('R999');
		$item->addFeature(new Feature('brand', 'Samsung'))
			->addFeature(new Feature('model', 'M393A2K40CB2-CTD'))
			->addFeature(new Feature('capacity-byte', 17179869184))
			->addFeature(new Feature('frequency-hertz', 2400000000))
			->addFeature(new Feature('ram-ecc', 'yes'))
			->addFeature(new Feature('ram-form-factor', 'rdimm'))
			->addFeature(new Feature('ram-type', 'ddr4'))
			->addFeature(new Feature('type', 'ram'))
			->addFeature(new Feature('working', 'yes'));

		$summary = RamSummarizer::summarize($item);
		$this->assertArrayEquals(["RAM ECC DDR4 RDIMM 16 GiB 2.4 GHz", "Samsung M393A2K40CB2-CTD"], $summary);

		return $summary;
	}
}
