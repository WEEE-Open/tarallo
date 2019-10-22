<?php

use PHPUnit\Framework\TestCase;
use WEEEOpen\Tarallo\Feature;
use WEEEOpen\Tarallo\Item;
use WEEEOpen\Tarallo\SSRv1\Summary\PsuSummarizer;

/**
 * @covers \WEEEOpen\Tarallo\SSRv1\Summary\PsuSummarizer
 */
class PsuSummarizerTest extends TestCase {
	public function testPsu() {
		$item = new Item('A99');
		$item
			->addFeature(new Feature('brand', 'Corsair'))
			->addFeature(new Feature('model', 'CX500W'))
			->addFeature(new Feature('color', 'black'))
			->addFeature(new Feature('pcie-power-pin-n', 14))
			->addFeature(new Feature('power-connector', 'c13'))
			->addFeature(new Feature('power-rated-watt', 500))
			->addFeature(new Feature('psu-connector-cpu', '8pin'))
			->addFeature(new Feature('psu-connector-motherboard', 'atx-24pin'))
			->addFeature(new Feature('psu-form-factor', 'atx'))
			->addFeature(new Feature('sata-power-n', 4))
			->addFeature(new Feature('type', 'psu'))
			->addFeature(new Feature('working', 'yes'));

		$summary = PsuSummarizer::summarize($item);
		$this->assertEquals(
			'PSU ATX 500 W (C13/C14, ATX 24 pin Mobo, 8 pin CPU, 14× PCI Express power pins, 4× SATA power), Black, Corsair CX500W',
			$summary
		);
	}
	
	public function testPsuNoColor() {
		$item = new Item('A99');
		$item
			->addFeature(new Feature('brand', 'Corsair'))
			->addFeature(new Feature('model', 'CX500W'))
			->addFeature(new Feature('pcie-power-pin-n', 14))
			->addFeature(new Feature('power-connector', 'c13'))
			->addFeature(new Feature('power-rated-watt', 500))
			->addFeature(new Feature('psu-connector-cpu', '8pin'))
			->addFeature(new Feature('psu-connector-motherboard', 'atx-24pin'))
			->addFeature(new Feature('psu-form-factor', 'atx'))
			->addFeature(new Feature('sata-power-n', 4))
			->addFeature(new Feature('type', 'psu'))
			->addFeature(new Feature('working', 'yes'));

		$summary = PsuSummarizer::summarize($item);
		$this->assertEquals(
			'PSU ATX 500 W (C13/C14, ATX 24 pin Mobo, 8 pin CPU, 14× PCI Express power pins, 4× SATA power), Corsair CX500W',
			$summary
		);
	}
	public function testPsuNoBrand() {
		$item = new Item('A99');
		$item
			->addFeature(new Feature('model', 'CX500W'))
			->addFeature(new Feature('color', 'black'))
			->addFeature(new Feature('pcie-power-pin-n', 14))
			->addFeature(new Feature('power-connector', 'c13'))
			->addFeature(new Feature('power-rated-watt', 500))
			->addFeature(new Feature('psu-connector-cpu', '8pin'))
			->addFeature(new Feature('psu-connector-motherboard', 'atx-24pin'))
			->addFeature(new Feature('psu-form-factor', 'atx'))
			->addFeature(new Feature('sata-power-n', 4))
			->addFeature(new Feature('type', 'psu'))
			->addFeature(new Feature('working', 'yes'));

		$summary = PsuSummarizer::summarize($item);
		$this->assertEquals(
			'PSU ATX 500 W (C13/C14, ATX 24 pin Mobo, 8 pin CPU, 14× PCI Express power pins, 4× SATA power), Black, CX500W',
			$summary
		);
	}
	public function testPsuNoModel() {
		$item = new Item('A99');
		$item
			->addFeature(new Feature('brand', 'Corsair'))
			->addFeature(new Feature('color', 'black'))
			->addFeature(new Feature('pcie-power-pin-n', 14))
			->addFeature(new Feature('power-connector', 'c13'))
			->addFeature(new Feature('power-rated-watt', 500))
			->addFeature(new Feature('psu-connector-cpu', '8pin'))
			->addFeature(new Feature('psu-connector-motherboard', 'atx-24pin'))
			->addFeature(new Feature('psu-form-factor', 'atx'))
			->addFeature(new Feature('sata-power-n', 4))
			->addFeature(new Feature('type', 'psu'))
			->addFeature(new Feature('working', 'yes'));

		$summary = PsuSummarizer::summarize($item);
		$this->assertEquals(
			'PSU ATX 500 W (C13/C14, ATX 24 pin Mobo, 8 pin CPU, 14× PCI Express power pins, 4× SATA power), Black, Corsair',
			$summary
		);
	}
	public function testPsuNoCommercial() {
		$item = new Item('A99');
		$item
			->addFeature(new Feature('color', 'black'))
			->addFeature(new Feature('pcie-power-pin-n', 14))
			->addFeature(new Feature('power-connector', 'c13'))
			->addFeature(new Feature('power-rated-watt', 500))
			->addFeature(new Feature('psu-connector-cpu', '8pin'))
			->addFeature(new Feature('psu-connector-motherboard', 'atx-24pin'))
			->addFeature(new Feature('psu-form-factor', 'atx'))
			->addFeature(new Feature('sata-power-n', 4))
			->addFeature(new Feature('type', 'psu'))
			->addFeature(new Feature('working', 'yes'));

		$summary = PsuSummarizer::summarize($item);
		$this->assertEquals(
			'PSU ATX 500 W (C13/C14, ATX 24 pin Mobo, 8 pin CPU, 14× PCI Express power pins, 4× SATA power), Black',
			$summary
		);
	}
	public function testPsuNoCommercialNoColor() {
		$item = new Item('A99');
		$item
			->addFeature(new Feature('pcie-power-pin-n', 14))
			->addFeature(new Feature('power-connector', 'c13'))
			->addFeature(new Feature('power-rated-watt', 500))
			->addFeature(new Feature('psu-connector-cpu', '8pin'))
			->addFeature(new Feature('psu-connector-motherboard', 'atx-24pin'))
			->addFeature(new Feature('psu-form-factor', 'atx'))
			->addFeature(new Feature('sata-power-n', 4))
			->addFeature(new Feature('type', 'psu'))
			->addFeature(new Feature('working', 'yes'));

		$summary = PsuSummarizer::summarize($item);
		$this->assertEquals(
			'PSU ATX 500 W (C13/C14, ATX 24 pin Mobo, 8 pin CPU, 14× PCI Express power pins, 4× SATA power)',
			$summary
		);
	}
	
	public function testPsuNoConnectors() {
		$item = new Item('A99');
		$item
			->addFeature(new Feature('brand', 'Corsair'))
			->addFeature(new Feature('power-rated-watt', 500))
			->addFeature(new Feature('model', 'CX500W'))
			->addFeature(new Feature('color', 'black'))
			->addFeature(new Feature('psu-form-factor', 'atx'))
			->addFeature(new Feature('type', 'psu'))
			->addFeature(new Feature('working', 'yes'));

		$summary = PsuSummarizer::summarize($item);
		$this->assertEquals(
			'PSU ATX 500 W, Black, Corsair CX500W',
			$summary
		);
	}
	
	public function testPsuNoExternal() {
		$item = new Item('A99');
		$item
			->addFeature(new Feature('brand', 'Corsair'))
			->addFeature(new Feature('model', 'CX500W'))
			->addFeature(new Feature('color', 'black'))
			->addFeature(new Feature('pcie-power-pin-n', 14))
			->addFeature(new Feature('power-rated-watt', 500))
			->addFeature(new Feature('psu-connector-cpu', '8pin'))
			->addFeature(new Feature('psu-connector-motherboard', 'atx-24pin'))
			->addFeature(new Feature('psu-form-factor', 'atx'))
			->addFeature(new Feature('sata-power-n', 4))
			->addFeature(new Feature('type', 'psu'))
			->addFeature(new Feature('working', 'yes'));

		$summary = PsuSummarizer::summarize($item);
		$this->assertEquals(
			'PSU ATX 500 W (ATX 24 pin Mobo, 8 pin CPU, 14× PCI Express power pins, 4× SATA power), Black, Corsair CX500W',
			$summary
		);
	}
	
	public function testPsuNoInternal() {
		$item = new Item('A99');
		$item
			->addFeature(new Feature('brand', 'Corsair'))
			->addFeature(new Feature('model', 'CX500W'))
			->addFeature(new Feature('color', 'black'))
			->addFeature(new Feature('power-connector', 'c13'))
			->addFeature(new Feature('power-rated-watt', 500))
			->addFeature(new Feature('psu-form-factor', 'atx'))
			->addFeature(new Feature('type', 'psu'))
			->addFeature(new Feature('working', 'yes'));

		$summary = PsuSummarizer::summarize($item);
		$this->assertEquals(
			'PSU ATX 500 W (C13/C14), Black, Corsair CX500W',
			$summary
		);
	}
	
	public function testPsuMoboOnly() {
		$item = new Item('A99');
		$item
			->addFeature(new Feature('brand', 'Corsair'))
			->addFeature(new Feature('model', 'CX500W'))
			->addFeature(new Feature('color', 'black'))
			->addFeature(new Feature('power-rated-watt', 500))
			->addFeature(new Feature('psu-connector-motherboard', 'atx-24pin'))
			->addFeature(new Feature('psu-form-factor', 'atx'))
			->addFeature(new Feature('type', 'psu'))
			->addFeature(new Feature('working', 'yes'));

		$summary = PsuSummarizer::summarize($item);
		$this->assertEquals(
			'PSU ATX 500 W (ATX 24 pin Mobo), Black, Corsair CX500W',
			$summary
		);
	}
	
	public function testPsuNoWatt() {
		$item = new Item('A99');
		$item
			->addFeature(new Feature('brand', 'Corsair'))
			->addFeature(new Feature('model', 'CX500W'))
			->addFeature(new Feature('color', 'black'))
			->addFeature(new Feature('pcie-power-pin-n', 14))
			->addFeature(new Feature('power-connector', 'c13'))
			->addFeature(new Feature('psu-connector-cpu', '8pin'))
			->addFeature(new Feature('psu-connector-motherboard', 'atx-24pin'))
			->addFeature(new Feature('psu-form-factor', 'atx'))
			->addFeature(new Feature('sata-power-n', 4))
			->addFeature(new Feature('type', 'psu'))
			->addFeature(new Feature('working', 'yes'));

		$summary = PsuSummarizer::summarize($item);
		$this->assertEquals(
			'PSU ATX (C13/C14, ATX 24 pin Mobo, 8 pin CPU, 14× PCI Express power pins, 4× SATA power), Black, Corsair CX500W',
			$summary
		);
	}
	
	public function testPsuNoFF() {
		$item = new Item('A99');
		$item
			->addFeature(new Feature('brand', 'Corsair'))
			->addFeature(new Feature('model', 'CX500W'))
			->addFeature(new Feature('color', 'black'))
			->addFeature(new Feature('pcie-power-pin-n', 14))
			->addFeature(new Feature('power-connector', 'c13'))
			->addFeature(new Feature('power-rated-watt', 500))
			->addFeature(new Feature('psu-connector-cpu', '8pin'))
			->addFeature(new Feature('psu-connector-motherboard', 'atx-24pin'))
			->addFeature(new Feature('sata-power-n', 4))
			->addFeature(new Feature('type', 'psu'))
			->addFeature(new Feature('working', 'yes'));

		$summary = PsuSummarizer::summarize($item);
		$this->assertEquals(
			'PSU 500 W (C13/C14, ATX 24 pin Mobo, 8 pin CPU, 14× PCI Express power pins, 4× SATA power), Black, Corsair CX500W',
			$summary
		);
	}

	public function testPsuNoFFNoWattNoCommercial() {
		$item = new Item('A99');
		$item
			->addFeature(new Feature('pcie-power-pin-n', 14))
			->addFeature(new Feature('power-connector', 'c13'))
			->addFeature(new Feature('psu-connector-cpu', '8pin'))
			->addFeature(new Feature('psu-connector-motherboard', 'atx-24pin'))
			->addFeature(new Feature('sata-power-n', 4))
			->addFeature(new Feature('type', 'psu'))
			->addFeature(new Feature('working', 'yes'));

		$summary = PsuSummarizer::summarize($item);
		$this->assertEquals(
			'PSU (C13/C14, ATX 24 pin Mobo, 8 pin CPU, 14× PCI Express power pins, 4× SATA power)',
			$summary
		);
	}

	public function testPsuNoWattNoFFNoPorts() {
		$item = new Item('A99');
		$item
			->addFeature(new Feature('brand', 'Corsair'))
			->addFeature(new Feature('model', 'CX500W'))
			->addFeature(new Feature('color', 'black'))
			->addFeature(new Feature('type', 'psu'))
			->addFeature(new Feature('working', 'yes'));

		$summary = PsuSummarizer::summarize($item);
		$this->assertEquals(
			'PSU, Black, Corsair CX500W',
			$summary
		);
	}
	public function testPsuNoWattNoFFNoPortsNoColor() {
		$item = new Item('A99');
		$item
			->addFeature(new Feature('brand', 'Corsair'))
			->addFeature(new Feature('model', 'CX500W'))
			->addFeature(new Feature('type', 'psu'))
			->addFeature(new Feature('working', 'yes'));

		$summary = PsuSummarizer::summarize($item);
		$this->assertEquals(
			'PSU, Corsair CX500W',
			$summary
		);
	}
	public function testPsuNoWattNoFFNoPortsNoCommercial() {
		$item = new Item('A99');
		$item
			->addFeature(new Feature('color', 'black'))
			->addFeature(new Feature('type', 'psu'))
			->addFeature(new Feature('working', 'yes'));

		$summary = PsuSummarizer::summarize($item);
		$this->assertEquals(
			'PSU, Black',
			$summary
		);
	}
	public function testPsuNothing() {
		$item = new Item('A99');
		$item
			->addFeature(new Feature('type', 'psu'))
			->addFeature(new Feature('working', 'no'));

		$summary = PsuSummarizer::summarize($item);
		$this->assertEquals(
			'PSU',
			$summary
		);
	}

	public function testPsuManufacturer() {
		$item = new Item('A420');
		$item
			->addFeature(new Feature('brand', 'HP'))
			->addFeature(new Feature('brand-manufacturer', 'Delta Eletronics'))
			->addFeature(new Feature('model', 'DPS-100DB A'))
			->addFeature(new Feature('color', 'green'))
			->addFeature(new Feature('power-connector', 'c13'))
			->addFeature(new Feature('power-rated-watt', 100))
			->addFeature(new Feature('psu-connector-cpu', '8pin'))
			->addFeature(new Feature('psu-connector-motherboard', 'atx-24pin'))
			->addFeature(new Feature('psu-form-factor', 'proprietary'))
			->addFeature(new Feature('sata-power-n', 99))
			->addFeature(new Feature('type', 'psu'))
			->addFeature(new Feature('working', 'yes'));

		$summary = PsuSummarizer::summarize($item);
		$this->assertEquals(
			'PSU Proprietary 100 W (C13/C14, ATX 24 pin Mobo, 8 pin CPU, 99× SATA power), Green, HP (Delta Eletronics) DPS-100DB A',
			$summary
		);
	}

	public function testPsuManufacturerAndInternalName() {
		$item = new Item('A420');
		$item
			->addFeature(new Feature('brand', 'HP'))
			->addFeature(new Feature('brand-manufacturer', 'Delta Eletronics'))
			->addFeature(new Feature('model', 'DPS-100DB A'))
			->addFeature(new Feature('internal-name', 'F00B4R'))
			->addFeature(new Feature('color', 'green'))
			->addFeature(new Feature('power-connector', 'c13'))
			->addFeature(new Feature('power-rated-watt', 100))
			->addFeature(new Feature('psu-connector-cpu', '8pin'))
			->addFeature(new Feature('psu-connector-motherboard', 'atx-24pin'))
			->addFeature(new Feature('psu-form-factor', 'proprietary'))
			->addFeature(new Feature('sata-power-n', 99))
			->addFeature(new Feature('type', 'psu'))
			->addFeature(new Feature('working', 'yes'));

		$summary = PsuSummarizer::summarize($item);
		$this->assertEquals(
			'PSU Proprietary 100 W (C13/C14, ATX 24 pin Mobo, 8 pin CPU, 99× SATA power), Green, HP (Delta Eletronics) DPS-100DB A (F00B4R)',
			$summary
		);
	}

	public function testPsuInternalName() {
		$item = new Item('A420');
		$item
			->addFeature(new Feature('brand', 'HP'))
			->addFeature(new Feature('model', 'DPS-100DB A'))
			->addFeature(new Feature('internal-name', 'F00B4R'))
			->addFeature(new Feature('color', 'green'))
			->addFeature(new Feature('power-connector', 'c13'))
			->addFeature(new Feature('power-rated-watt', 100))
			->addFeature(new Feature('psu-connector-cpu', '8pin'))
			->addFeature(new Feature('psu-connector-motherboard', 'atx-24pin'))
			->addFeature(new Feature('psu-form-factor', 'proprietary'))
			->addFeature(new Feature('sata-power-n', 99))
			->addFeature(new Feature('type', 'psu'))
			->addFeature(new Feature('working', 'yes'));

		$summary = PsuSummarizer::summarize($item);
		$this->assertEquals(
			'PSU Proprietary 100 W (C13/C14, ATX 24 pin Mobo, 8 pin CPU, 99× SATA power), Green, HP DPS-100DB A (F00B4R)',
			$summary
		);
	}

	public function testPsuManufacturerAndInternalNameNoModel() {
		$item = new Item('A420');
		$item
			->addFeature(new Feature('brand', 'HP'))
			->addFeature(new Feature('brand-manufacturer', 'Delta Eletronics'))
			->addFeature(new Feature('internal-name', 'F00B4R'))
			->addFeature(new Feature('color', 'green'))
			->addFeature(new Feature('power-connector', 'c13'))
			->addFeature(new Feature('power-rated-watt', 100))
			->addFeature(new Feature('psu-connector-cpu', '8pin'))
			->addFeature(new Feature('psu-connector-motherboard', 'atx-24pin'))
			->addFeature(new Feature('psu-form-factor', 'proprietary'))
			->addFeature(new Feature('sata-power-n', 99))
			->addFeature(new Feature('type', 'psu'))
			->addFeature(new Feature('working', 'yes'));

		$summary = PsuSummarizer::summarize($item);
		$this->assertEquals(
			'PSU Proprietary 100 W (C13/C14, ATX 24 pin Mobo, 8 pin CPU, 99× SATA power), Green, HP (Delta Eletronics F00B4R)',
			$summary
		);
	}

	public function testPsuManufacturerAndInternalNameNoBrand() {
		$item = new Item('A420');
		$item
			->addFeature(new Feature('brand-manufacturer', 'Delta Eletronics'))
			->addFeature(new Feature('model', 'DPS-100DB A'))
			->addFeature(new Feature('internal-name', 'F00B4R'))
			->addFeature(new Feature('color', 'green'))
			->addFeature(new Feature('power-connector', 'c13'))
			->addFeature(new Feature('power-rated-watt', 100))
			->addFeature(new Feature('psu-connector-cpu', '8pin'))
			->addFeature(new Feature('psu-connector-motherboard', 'atx-24pin'))
			->addFeature(new Feature('psu-form-factor', 'proprietary'))
			->addFeature(new Feature('sata-power-n', 99))
			->addFeature(new Feature('type', 'psu'))
			->addFeature(new Feature('working', 'yes'));

		$summary = PsuSummarizer::summarize($item);
		$this->assertEquals(
			'PSU Proprietary 100 W (C13/C14, ATX 24 pin Mobo, 8 pin CPU, 99× SATA power), Green, (Delta Eletronics) DPS-100DB A (F00B4R)',
			$summary
		);
	}

	public function testPsuManufacturerAndInternalNameNoBrandNoModel() {
		$item = new Item('A420');
		$item
			->addFeature(new Feature('brand-manufacturer', 'Delta Eletronics'))
			->addFeature(new Feature('internal-name', 'F00B4R'))
			->addFeature(new Feature('color', 'green'))
			->addFeature(new Feature('power-connector', 'c13'))
			->addFeature(new Feature('power-rated-watt', 100))
			->addFeature(new Feature('psu-connector-cpu', '8pin'))
			->addFeature(new Feature('psu-connector-motherboard', 'atx-24pin'))
			->addFeature(new Feature('psu-form-factor', 'proprietary'))
			->addFeature(new Feature('sata-power-n', 99))
			->addFeature(new Feature('type', 'psu'))
			->addFeature(new Feature('working', 'yes'));

		$summary = PsuSummarizer::summarize($item);
		$this->assertEquals(
			'PSU Proprietary 100 W (C13/C14, ATX 24 pin Mobo, 8 pin CPU, 99× SATA power), Green, (Delta Eletronics F00B4R)',
			$summary
		);
	}
}
