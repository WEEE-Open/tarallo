<?php

use PHPUnit\Framework\TestCase;
use WEEEOpen\Tarallo\Server\Item;
use WEEEOpen\Tarallo\SSRv1\Summary\RamSummarizer;

/**
 * @covers \WEEEOpen\Tarallo\SSRv1\Summary\RamSummarizer
 */
class RamSummarizerTest extends TestCase {
	public function testValidUserNullPasswordAndHash() {
		$item = new Item('R123');
		$item->addFeature(new WEEEOpen\Tarallo\Server\Feature('brand', 'Kingston'))
			->addFeature(new WEEEOpen\Tarallo\Server\Feature('capacity-byte', 2147483648))
			->addFeature(new WEEEOpen\Tarallo\Server\Feature('color', 'green'))
			->addFeature(new WEEEOpen\Tarallo\Server\Feature('frequency-hertz', 1066000000))
			->addFeature(new WEEEOpen\Tarallo\Server\Feature('model', 'ACR256X64D3S13C9G'))
			->addFeature(new WEEEOpen\Tarallo\Server\Feature('owner', 'Area IT'))
			->addFeature(new WEEEOpen\Tarallo\Server\Feature('ram-ecc', 'no'))
			->addFeature(new WEEEOpen\Tarallo\Server\Feature('ram-form-factor', 'sodimm'))
			->addFeature(new WEEEOpen\Tarallo\Server\Feature('ram-type', 'ddr3'))
			->addFeature(new WEEEOpen\Tarallo\Server\Feature('sn', 'E516B7B6'))
			->addFeature(new WEEEOpen\Tarallo\Server\Feature('type', 'ram'))
			->addFeature(new WEEEOpen\Tarallo\Server\Feature('working', 'yes'));

		$summary = RamSummarizer::summarize($item);
		$this->assertEquals('RAM DDR3 SODIMM 2 GiB 1.066 GHz, Kingston ACR256X64D3S13C9G', $summary);

		return $summary;
	}
}
