<?php

use WEEEOpen\Tarallo\Feature;
use WEEEOpen\Tarallo\Item;
use WEEEOpen\Tarallo\SSRv1\Summary\SimpleDeviceSummarizer;
use WEEEOpen\TaralloTest\SSRv1\Summary\SummarizerTestCase;

/**
 * @covers \WEEEOpen\Tarallo\SSRv1\Summary\SimpleDeviceSummarizer
 */
class SimpleDeviceSummarizerTest extends SummarizerTestCase
{
	public function testStorageCard()
	{
		$item = new Item('Q1');
		$item
			->addFeature(new Feature('color', 'green'))
			->addFeature(new Feature('pci-low-profile', 'no'))
			->addFeature(new Feature('pcie-sockets-n', 1))
			->addFeature(new Feature('sas-sata-ports-n', 1))
			->addFeature(new Feature('type', 'storage-card'))
			->addFeature(new Feature('brand', 'Outtel'))
			->addFeature(new Feature('model', 'SRC123567'))
			->addFeature(new Feature('owner', 'ASD'))
			->addFeature(new Feature('sn', '99999999'))
			->addFeature(new Feature('variant', 'default'))
			->addFeature(new Feature('working', 'yes'));

		$summary = SimpleDeviceSummarizer::summarize($item);
		$this->assertArrayEquals(
			["Storage card", "1× SAS (SATA connector)", "Green", "Outtel SRC123567"],
			$summary
		);

		return $summary;
	}
}
