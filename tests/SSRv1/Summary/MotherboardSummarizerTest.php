<?php

use PHPUnit\Framework\TestCase;
use WEEEOpen\Tarallo\Feature;
use WEEEOpen\Tarallo\Item;
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
			'Motherboard Micro ATX, 478 (desktop, mPGA478B) 1× PCI 3× PCI Express, 1× IDE/ATA 4× USB 1× VGA, Red, Compaq D845GVFT',
			$summary
		);

		return $summary;
	}

	public function testMotherboardNoSockets() {
		$item = new Item('B55');
		$item
			->addFeature(new Feature('type', 'motherboard'))
			->addFeature(new Feature('brand', 'Compaq'))
			->addFeature(new Feature('model', 'D845GVFT'))
			->addFeature(new Feature('color', 'red'))
			->addFeature(new Feature('motherboard-form-factor', 'microatx'))
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('usb-ports-n', 4))
			->addFeature(new Feature('vga-ports-n', 1))
			->addFeature(new Feature('ide-ports-n', 1));

		$summary = MotherboardSummarizer::summarize($item);
		$this->assertEquals(
			'Motherboard Micro ATX, 1× IDE/ATA 4× USB 1× VGA, Red, Compaq D845GVFT',
			$summary
		);

		return $summary;
	}

	public function testMotherboardNoPorts() {
		$item = new Item('B55');
		$item
			->addFeature(new Feature('type', 'motherboard'))
			->addFeature(new Feature('brand', 'Compaq'))
			->addFeature(new Feature('model', 'D845GVFT'))
			->addFeature(new Feature('color', 'red'))
			->addFeature(new Feature('motherboard-form-factor', 'microatx'))
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('cpu-socket', 'socket478'))
			->addFeature(new Feature('pci-sockets-n', 1))
			->addFeature(new Feature('pcie-sockets-n', 3));

		$summary = MotherboardSummarizer::summarize($item);
		$this->assertEquals(
			'Motherboard Micro ATX, 478 (desktop, mPGA478B) 1× PCI 3× PCI Express, Red, Compaq D845GVFT',
			$summary
		);

		return $summary;
	}

	public function testMotherboardNoColor() {
		$item = new Item('B55');
		$item
			->addFeature(new Feature('type', 'motherboard'))
			->addFeature(new Feature('brand', 'Compaq'))
			->addFeature(new Feature('model', 'D845GVFT'))
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
			'Motherboard Micro ATX, 478 (desktop, mPGA478B) 1× PCI 3× PCI Express, 1× IDE/ATA 4× USB 1× VGA, Compaq D845GVFT',
			$summary
		);

		return $summary;
	}

	public function testMotherboardNoColorNoCommercial() {
		$item = new Item('B55');
		$item
			->addFeature(new Feature('type', 'motherboard'))
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
			'Motherboard Micro ATX, 478 (desktop, mPGA478B) 1× PCI 3× PCI Express, 1× IDE/ATA 4× USB 1× VGA',
			$summary
		);

		return $summary;
	}

	public function testMotherboardNoFormFactor() {
		$item = new Item('B55');
		$item
			->addFeature(new Feature('type', 'motherboard'))
			->addFeature(new Feature('brand', 'Compaq'))
			->addFeature(new Feature('model', 'D845GVFT'))
			->addFeature(new Feature('color', 'red'))
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('cpu-socket', 'socket478'))
			->addFeature(new Feature('usb-ports-n', 4))
			->addFeature(new Feature('vga-ports-n', 1))
			->addFeature(new Feature('ide-ports-n', 1))
			->addFeature(new Feature('pci-sockets-n', 1))
			->addFeature(new Feature('pcie-sockets-n', 3));

		$summary = MotherboardSummarizer::summarize($item);
		$this->assertEquals(
			'Motherboard, 478 (desktop, mPGA478B) 1× PCI 3× PCI Express, 1× IDE/ATA 4× USB 1× VGA, Red, Compaq D845GVFT',
			$summary
		);

		return $summary;
	}

	public function testMotherboardNoBrand() {
		$item = new Item('B55');
		$item
			->addFeature(new Feature('type', 'motherboard'))
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
			'Motherboard Micro ATX, 478 (desktop, mPGA478B) 1× PCI 3× PCI Express, 1× IDE/ATA 4× USB 1× VGA, Red, D845GVFT',
			$summary
		);

		return $summary;
	}

	public function testMotherboardNoModel() {
		$item = new Item('B55');
		$item
			->addFeature(new Feature('type', 'motherboard'))
			->addFeature(new Feature('brand', 'Compaq'))
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
			'Motherboard Micro ATX, 478 (desktop, mPGA478B) 1× PCI 3× PCI Express, 1× IDE/ATA 4× USB 1× VGA, Red, Compaq',
			$summary
		);

		return $summary;
	}

	public function testMotherboardNoCommercial() {
		$item = new Item('B55');
		$item
			->addFeature(new Feature('type', 'motherboard'))
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
			'Motherboard Micro ATX, 478 (desktop, mPGA478B) 1× PCI 3× PCI Express, 1× IDE/ATA 4× USB 1× VGA, Red',
			$summary
		);

		return $summary;
	}

	public function testMotherboardNoCommercialNoPortsNoColor() {
		$item = new Item('B55');
		$item
			->addFeature(new Feature('type', 'motherboard'))
			->addFeature(new Feature('motherboard-form-factor', 'microatx'))
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('cpu-socket', 'socket478'))
			->addFeature(new Feature('pci-sockets-n', 1))
			->addFeature(new Feature('pcie-sockets-n', 3));

		$summary = MotherboardSummarizer::summarize($item);
		$this->assertEquals(
			'Motherboard Micro ATX, 478 (desktop, mPGA478B) 1× PCI 3× PCI Express',
			$summary
		);

		return $summary;
	}

	public function testMotherboardNoCommercialNoPortsNoColorNoPorts() {
		$item = new Item('B55');
		$item
			->addFeature(new Feature('type', 'motherboard'))
			->addFeature(new Feature('motherboard-form-factor', 'microatx'))
			->addFeature(new Feature('working', 'yes'));

		$summary = MotherboardSummarizer::summarize($item);
		$this->assertEquals(
			'Motherboard Micro ATX',
			$summary
		);

		return $summary;
	}

	public function testMotherboardNothing() {
		$item = new Item('B55');
		$item
			->addFeature(new Feature('type', 'motherboard'))
			->addFeature(new Feature('working', 'yes'));

		$summary = MotherboardSummarizer::summarize($item);
		$this->assertEquals(
			'Motherboard',
			$summary
		);

		return $summary;
	}

}
