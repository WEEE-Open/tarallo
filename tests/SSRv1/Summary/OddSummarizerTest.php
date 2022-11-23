<?php

use WEEEOpen\Tarallo\Feature;
use WEEEOpen\Tarallo\Item;
use WEEEOpen\Tarallo\SSRv1\Summary\OddSummarizer;
use WEEEOpen\TaralloTest\SSRv1\Summary\SummarizerTestCase;

/**
 * @covers \WEEEOpen\Tarallo\SSRv1\Summary\OddSummarizer
 */
class OddSummarizerTest extends SummarizerTestCase
{
	public function testOdd()
	{
		$item = new Item('ODD200');
		$item
			->addFeature(new Feature('type', 'odd'))
			->addFeature(new Feature('brand', 'Toshiba'))
			->addFeature(new Feature('model', 'MK1234GSX'))
			->addFeature(new Feature('color', 'lightgrey'))
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('odd-type', 'dvd-rw'))
			->addFeature(new Feature('sata-ports-n', 1))
			->addFeature(new Feature('odd-form-factor', '5.25'));

		$summary = OddSummarizer::summarize($item);
		$this->assertArrayEquals(
			["ODD 5.25 in. DVD-RW", "SATA", "Light grey", "Toshiba MK1234GSX"],
			$summary
		);

		return $summary;
	}

	public function testOddNoOddType()
	{
		$item = new Item('ODD200');
		$item
			->addFeature(new Feature('type', 'odd'))
			->addFeature(new Feature('brand', 'Toshiba'))
			->addFeature(new Feature('model', 'MK1234GSX'))
			->addFeature(new Feature('color', 'lightgrey'))
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('sata-ports-n', 1))
			->addFeature(new Feature('odd-form-factor', '5.25'));

		$summary = OddSummarizer::summarize($item);
		$this->assertArrayEquals(
			["ODD 5.25 in.", "SATA", "Light grey", "Toshiba MK1234GSX"],
			$summary
		);

		return $summary;
	}

	public function testOddNoFormFactor()
	{
		$item = new Item('ODD200');
		$item
			->addFeature(new Feature('type', 'odd'))
			->addFeature(new Feature('brand', 'Toshiba'))
			->addFeature(new Feature('model', 'MK1234GSX'))
			->addFeature(new Feature('color', 'lightgrey'))
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('odd-type', 'dvd-rw'))
			->addFeature(new Feature('sata-ports-n', 1));

		$summary = OddSummarizer::summarize($item);
		$this->assertArrayEquals(
			["ODD DVD-RW", "SATA", "Light grey", "Toshiba MK1234GSX"],
			$summary
		);

		return $summary;
	}

	public function testOddNoFormFactorNoOddType()
	{
		$item = new Item('ODD200');
		$item
			->addFeature(new Feature('type', 'odd'))
			->addFeature(new Feature('brand', 'Toshiba'))
			->addFeature(new Feature('model', 'MK1234GSX'))
			->addFeature(new Feature('color', 'lightgrey'))
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('sata-ports-n', 1));

		$summary = OddSummarizer::summarize($item);
		$this->assertArrayEquals(
			["ODD", "SATA", "Light grey", "Toshiba MK1234GSX"],
			$summary
		);

		return $summary;
	}

	public function testOddNoPorts()
	{
		$item = new Item('ODD200');
		$item
			->addFeature(new Feature('type', 'odd'))
			->addFeature(new Feature('brand', 'Toshiba'))
			->addFeature(new Feature('model', 'MK1234GSX'))
			->addFeature(new Feature('color', 'lightgrey'))
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('odd-type', 'dvd-rw'))
			->addFeature(new Feature('odd-form-factor', '5.25'));

		$summary = OddSummarizer::summarize($item);
		$this->assertArrayEquals(
			["ODD 5.25 in. DVD-RW", "Light grey", "Toshiba MK1234GSX"],
			$summary
		);
	}

	public function testOddNoPortsNoCommercial()
	{
		$item = new Item('ODD200');
		$item
			->addFeature(new Feature('type', 'odd'))
			->addFeature(new Feature('color', 'lightgrey'))
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('odd-type', 'dvd-rw'))
			->addFeature(new Feature('odd-form-factor', '5.25'));

		$summary = OddSummarizer::summarize($item);
		$this->assertArrayEquals(
			["ODD 5.25 in. DVD-RW", "Light grey"],
			$summary
		);

		return $summary;
	}

	public function testOddNoPortsNoCommercialNoColor()
	{
		$item = new Item('ODD200');
		$item
			->addFeature(new Feature('type', 'odd'))
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('odd-type', 'dvd-rw'))
			->addFeature(new Feature('odd-form-factor', '5.25'));

		$summary = OddSummarizer::summarize($item);
		$this->assertArrayEquals(
			["ODD 5.25 in. DVD-RW"],
			$summary
		);

		return $summary;
	}

	public function testOddNothing()
	{
		$item = new Item('ODD200');
		$item
			->addFeature(new Feature('type', 'odd'))
			->addFeature(new Feature('working', 'yes'));

		$summary = OddSummarizer::summarize($item);
		$this->assertArrayEquals(
			["ODD"],
			$summary
		);

		return $summary;
	}

	public function testOddNoColor()
	{
		$item = new Item('ODD200');
		$item
			->addFeature(new Feature('type', 'odd'))
			->addFeature(new Feature('brand', 'Toshiba'))
			->addFeature(new Feature('model', 'MK1234GSX'))
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('odd-type', 'dvd-rw'))
			->addFeature(new Feature('sata-ports-n', 1))
			->addFeature(new Feature('odd-form-factor', '5.25'));

		$summary = OddSummarizer::summarize($item);
		$this->assertArrayEquals(
			["ODD 5.25 in. DVD-RW", "SATA", "Toshiba MK1234GSX"],
			$summary
		);

		return $summary;
	}

	public function testOddNoColorNoCommercial()
	{
		$item = new Item('ODD200');
		$item
			->addFeature(new Feature('type', 'odd'))
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('odd-type', 'dvd-rw'))
			->addFeature(new Feature('sata-ports-n', 1))
			->addFeature(new Feature('odd-form-factor', '5.25'));

		$summary = OddSummarizer::summarize($item);
		$this->assertArrayEquals(
			["ODD 5.25 in. DVD-RW", "SATA"],
			$summary
		);

		return $summary;
	}

	public function testOddNoBrand()
	{
		$item = new Item('ODD200');
		$item
			->addFeature(new Feature('type', 'odd'))
			->addFeature(new Feature('model', 'MK1234GSX'))
			->addFeature(new Feature('color', 'lightgrey'))
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('odd-type', 'dvd-rw'))
			->addFeature(new Feature('sata-ports-n', 1))
			->addFeature(new Feature('odd-form-factor', '5.25'));

		$summary = OddSummarizer::summarize($item);
		$this->assertArrayEquals(
			["ODD 5.25 in. DVD-RW", "SATA", "Light grey", "MK1234GSX"],
			$summary
		);

		return $summary;
	}

	public function testOddNoCommercial()
	{
		$item = new Item('ODD200');
		$item
			->addFeature(new Feature('type', 'odd'))
			->addFeature(new Feature('color', 'lightgrey'))
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('odd-type', 'dvd-rw'))
			->addFeature(new Feature('sata-ports-n', 1))
			->addFeature(new Feature('odd-form-factor', '5.25'));

		$summary = OddSummarizer::summarize($item);
		$this->assertArrayEquals(
			["ODD 5.25 in. DVD-RW", "SATA", "Light grey"],
			$summary
		);

		return $summary;
	}

	public function testOddNoModel()
	{
		$item = new Item('ODD200');
		$item
			->addFeature(new Feature('type', 'odd'))
			->addFeature(new Feature('brand', 'Toshiba'))
			->addFeature(new Feature('color', 'lightgrey'))
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('odd-type', 'dvd-rw'))
			->addFeature(new Feature('sata-ports-n', 1))
			->addFeature(new Feature('odd-form-factor', '5.25'));

		$summary = OddSummarizer::summarize($item);
		$this->assertArrayEquals(
			["ODD 5.25 in. DVD-RW", "SATA", "Light grey", "Toshiba"],
			$summary
		);

		return $summary;
	}
}
