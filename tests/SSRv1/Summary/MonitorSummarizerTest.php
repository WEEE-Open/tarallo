<?php

use PHPUnit\Framework\TestCase;
use WEEEOpen\Tarallo\Server\Feature;
use WEEEOpen\Tarallo\Server\Item;
use WEEEOpen\Tarallo\SSRv1\Summary\MonitorSummarizer;

/**
 * @covers \WEEEOpen\Tarallo\SSRv1\Summary\MonitorSummarizer
 */
class MonitorSummarizerTest extends TestCase {
	public function testOdd() {
		$item = new Item('V9');
		$item
			->addFeature(new Feature('brand', 'Dell'))
			->addFeature(new Feature('type', 'monitor'))
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('model', '1707FPt'))
			->addFeature(new Feature('dvi-ports-n', 1))
			->addFeature(new Feature('usb-ports-n', 2))
			->addFeature(new Feature('vga-ports-n', 1))
			->addFeature(new Feature('power-connector', 'c13'))
			->addFeature(new Feature('psu-ampere', (double) 4))
			->addFeature(new Feature('psu-volt', (double) 12));

		$summary = MonitorSummarizer::summarize($item);
		$this->assertEquals(
			'ODD 5.25 in. DVD-RW, SATA, Light grey, Toshiba MK1234GSX',
			$summary
		);

		return $summary;
	}

}
