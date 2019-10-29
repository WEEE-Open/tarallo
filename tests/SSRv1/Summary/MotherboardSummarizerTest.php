<?php

use PHPUnit\Framework\TestCase;
use WEEEOpen\Tarallo\Server\Feature;
use WEEEOpen\Tarallo\Server\Item;
use WEEEOpen\Tarallo\SSRv1\Summary\MotherboardSummarizer;

/**
 * @covers \WEEEOpen\Tarallo\SSRv1\Summary\MotherboardSummarizer
 */
class MotherboardSummarizerTest extends TestCase {
	public function testMotherboard() {
		$item = new Item('B55');
		$item
			->addFeature(new Feature('type', 'motherboard'))
			->addFeature(new Feature('brand', 'Compaq'))
			->addFeature(new Feature('model', 'D845GVFT'))
			->addFeature(new Feature('color', 'red'))
			->addFeature(new Feature('motherboard-form-factor', 'microatx'))
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('cpu-socket', 'socket478'))
			->addFeature(new Feature('usb-ports-n', 4))
			->addFeature(new Feature('vga-ports-n', 1))
			->addFeature(new Feature('ide-ports-n', 1))
			->addFeature(new Feature('pci-sockets-n', 1))
			->addFeature(new Feature('pcie-sockets-n', 3));

		$summary = MotherboardSummarizer::summarize($item);
		$this->assertEquals(
			'Motherboard , 2 Cores 2 Threads @ 2.13 GHz, Socket LGA775, Intel Core 2 Duo E6400',
			$summary
		);

		return $summary;
	}
}
