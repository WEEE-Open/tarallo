<?php

use PHPUnit\Framework\TestCase;
use WEEEOpen\Tarallo\Feature;
use WEEEOpen\Tarallo\Item;
use WEEEOpen\Tarallo\SSRv1\Summary\MonitorSummarizer;

/**
 * @covers \WEEEOpen\Tarallo\SSRv1\Summary\MonitorSummarizer
 */
class MonitorSummarizerTest extends TestCase {
	public function testMonitor() {
		$item = new Item('V9');
		$item
			->addFeature(new Feature('brand', 'Dell'))
			->addFeature(new Feature('type', 'monitor'))
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('model', '1707FPt'))
			->addFeature(new Feature('diagonal-inch', (double)14))
			->addFeature(new Feature('dvi-ports-n', 1))
			->addFeature(new Feature('usb-ports-n', 2))
			->addFeature(new Feature('vga-ports-n', 1))
			->addFeature(new Feature('power-connector', 'c13'))
			->addFeature(new Feature('psu-ampere', (double) 4))
			->addFeature(new Feature('psu-volt', (double) 12));

		$summary = MonitorSummarizer::summarize($item);
		$this->assertEquals(
			'Monitor 14 in., 1× DVI 2× USB 1× VGA, 4 A 12 V C13/C14, Dell 1707FPt',
			$summary
		);

		return $summary;
	}

	public function testMonitorNoInch() {
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
			'Monitor, 1× DVI 2× USB 1× VGA, 4 A 12 V C13/C14, Dell 1707FPt',
			$summary
		);

		return $summary;
	}

	public function testMonitorNoVGA() {
		$item = new Item('V9');
		$item
			->addFeature(new Feature('brand', 'Dell'))
			->addFeature(new Feature('type', 'monitor'))
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('model', '1707FPt'))
			->addFeature(new Feature('diagonal-inch', (double)14))
			->addFeature(new Feature('dvi-ports-n', 1))
			->addFeature(new Feature('usb-ports-n', 2))
			->addFeature(new Feature('power-connector', 'c13'))
			->addFeature(new Feature('psu-ampere', (double) 4))
			->addFeature(new Feature('psu-volt', (double) 12));

		$summary = MonitorSummarizer::summarize($item);
		$this->assertEquals(
			'Monitor 14 in., 1× DVI 2× USB, 4 A 12 V C13/C14, Dell 1707FPt',
			$summary
		);

		return $summary;
	}

	public function testMonitorNoDVI() {
		$item = new Item('V9');
		$item
			->addFeature(new Feature('brand', 'Dell'))
			->addFeature(new Feature('type', 'monitor'))
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('model', '1707FPt'))
			->addFeature(new Feature('diagonal-inch', (double)14))
			->addFeature(new Feature('usb-ports-n', 2))
			->addFeature(new Feature('vga-ports-n', 1))
			->addFeature(new Feature('power-connector', 'c13'))
			->addFeature(new Feature('psu-ampere', (double) 4))
			->addFeature(new Feature('psu-volt', (double) 12));

		$summary = MonitorSummarizer::summarize($item);
		$this->assertEquals(
			'Monitor 14 in., 2× USB 1× VGA, 4 A 12 V C13/C14, Dell 1707FPt',
			$summary
		);

		return $summary;
	}

	public function testMonitorNoUSB() {
		$item = new Item('V9');
		$item
			->addFeature(new Feature('brand', 'Dell'))
			->addFeature(new Feature('type', 'monitor'))
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('model', '1707FPt'))
			->addFeature(new Feature('diagonal-inch', (double)14))
			->addFeature(new Feature('dvi-ports-n', 1))
			->addFeature(new Feature('vga-ports-n', 1))
			->addFeature(new Feature('power-connector', 'c13'))
			->addFeature(new Feature('psu-ampere', (double) 4))
			->addFeature(new Feature('psu-volt', (double) 12));

		$summary = MonitorSummarizer::summarize($item);
		$this->assertEquals(
			'Monitor 14 in., 1× DVI 1× VGA, 4 A 12 V C13/C14, Dell 1707FPt',
			$summary
		);

		return $summary;
	}

	public function testMonitorNoPorts() {
		$item = new Item('V9');
		$item
			->addFeature(new Feature('brand', 'Dell'))
			->addFeature(new Feature('type', 'monitor'))
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('model', '1707FPt'))
			->addFeature(new Feature('diagonal-inch', (double)14))
			->addFeature(new Feature('power-connector', 'c13'))
			->addFeature(new Feature('psu-ampere', (double) 4))
			->addFeature(new Feature('psu-volt', (double) 12));

		$summary = MonitorSummarizer::summarize($item);
		$this->assertEquals(
			'Monitor 14 in., 4 A 12 V C13/C14, Dell 1707FPt',
			$summary
		);

		return $summary;
	}

	public function testMonitorNoAmpere() {
		$item = new Item('V9');
		$item
			->addFeature(new Feature('brand', 'Dell'))
			->addFeature(new Feature('type', 'monitor'))
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('model', '1707FPt'))
			->addFeature(new Feature('diagonal-inch', (double)14))
			->addFeature(new Feature('dvi-ports-n', 1))
			->addFeature(new Feature('usb-ports-n', 2))
			->addFeature(new Feature('vga-ports-n', 1))
			->addFeature(new Feature('power-connector', 'c13'))
			->addFeature(new Feature('psu-volt', (double) 12));

		$summary = MonitorSummarizer::summarize($item);
		$this->assertEquals(
			'Monitor 14 in., 1× DVI 2× USB 1× VGA, 12 V C13/C14, Dell 1707FPt',
			$summary
		);

		return $summary;
	}	public function testMonitorNoVolt() {
		$item = new Item('V9');
		$item
			->addFeature(new Feature('brand', 'Dell'))
			->addFeature(new Feature('type', 'monitor'))
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('model', '1707FPt'))
			->addFeature(new Feature('diagonal-inch', (double)14))
			->addFeature(new Feature('dvi-ports-n', 1))
			->addFeature(new Feature('usb-ports-n', 2))
			->addFeature(new Feature('vga-ports-n', 1))
			->addFeature(new Feature('power-connector', 'c13'))
			->addFeature(new Feature('psu-ampere', (double) 4));

		$summary = MonitorSummarizer::summarize($item);
		$this->assertEquals(
			'Monitor 14 in., 1× DVI 2× USB 1× VGA, 4 A C13/C14, Dell 1707FPt',
			$summary
		);

		return $summary;
	}	public function testMonitorNoConnector() {
		$item = new Item('V9');
		$item
			->addFeature(new Feature('brand', 'Dell'))
			->addFeature(new Feature('type', 'monitor'))
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('model', '1707FPt'))
			->addFeature(new Feature('diagonal-inch', (double)14))
			->addFeature(new Feature('dvi-ports-n', 1))
			->addFeature(new Feature('usb-ports-n', 2))
			->addFeature(new Feature('vga-ports-n', 1))
			->addFeature(new Feature('psu-ampere', (double) 4))
			->addFeature(new Feature('psu-volt', (double) 12));

		$summary = MonitorSummarizer::summarize($item);
		$this->assertEquals(
			'Monitor 14 in., 1× DVI 2× USB 1× VGA, 4 A 12 V, Dell 1707FPt',
			$summary
		);

		return $summary;
	}

	public function testMonitorNoPower() {
		$item = new Item('V9');
		$item
			->addFeature(new Feature('brand', 'Dell'))
			->addFeature(new Feature('type', 'monitor'))
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('model', '1707FPt'))
			->addFeature(new Feature('diagonal-inch', (double)14))
			->addFeature(new Feature('dvi-ports-n', 1))
			->addFeature(new Feature('usb-ports-n', 2))
			->addFeature(new Feature('vga-ports-n', 1));

		$summary = MonitorSummarizer::summarize($item);
		$this->assertEquals(
			'Monitor 14 in., 1× DVI 2× USB 1× VGA, Dell 1707FPt',
			$summary
		);

		return $summary;
	}

	public function testMonitorNoBrand() {
		$item = new Item('V9');
		$item
			->addFeature(new Feature('type', 'monitor'))
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('model', '1707FPt'))
			->addFeature(new Feature('diagonal-inch', (double)14))
			->addFeature(new Feature('dvi-ports-n', 1))
			->addFeature(new Feature('usb-ports-n', 2))
			->addFeature(new Feature('vga-ports-n', 1))
			->addFeature(new Feature('power-connector', 'c13'))
			->addFeature(new Feature('psu-ampere', (double) 4))
			->addFeature(new Feature('psu-volt', (double) 12));

		$summary = MonitorSummarizer::summarize($item);
		$this->assertEquals(
			'Monitor 14 in., 1× DVI 2× USB 1× VGA, 4 A 12 V C13/C14, 1707FPt',
			$summary
		);

		return $summary;
	}

	public function testMonitorNoModel() {
		$item = new Item('V9');
		$item
			->addFeature(new Feature('brand', 'Dell'))
			->addFeature(new Feature('type', 'monitor'))
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('diagonal-inch', (double)14))
			->addFeature(new Feature('dvi-ports-n', 1))
			->addFeature(new Feature('usb-ports-n', 2))
			->addFeature(new Feature('vga-ports-n', 1))
			->addFeature(new Feature('power-connector', 'c13'))
			->addFeature(new Feature('psu-ampere', (double) 4))
			->addFeature(new Feature('psu-volt', (double) 12));

		$summary = MonitorSummarizer::summarize($item);
		$this->assertEquals(
			'Monitor 14 in., 1× DVI 2× USB 1× VGA, 4 A 12 V C13/C14, Dell',
			$summary
		);

		return $summary;
	}

	public function testMonitorNoCommercial() {
		$item = new Item('V9');
		$item
			->addFeature(new Feature('type', 'monitor'))
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('diagonal-inch', (double)14))
			->addFeature(new Feature('dvi-ports-n', 1))
			->addFeature(new Feature('usb-ports-n', 2))
			->addFeature(new Feature('vga-ports-n', 1))
			->addFeature(new Feature('power-connector', 'c13'))
			->addFeature(new Feature('psu-ampere', (double) 4))
			->addFeature(new Feature('psu-volt', (double) 12));

		$summary = MonitorSummarizer::summarize($item);
		$this->assertEquals(
			'Monitor 14 in., 1× DVI 2× USB 1× VGA, 4 A 12 V C13/C14',
			$summary
		);

		return $summary;
	}
	public function testMonitorNoCommercialNoPower() {
		$item = new Item('V9');
		$item
			->addFeature(new Feature('type', 'monitor'))
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('diagonal-inch', (double)14))
			->addFeature(new Feature('dvi-ports-n', 1))
			->addFeature(new Feature('usb-ports-n', 2))
			->addFeature(new Feature('vga-ports-n', 1));

		$summary = MonitorSummarizer::summarize($item);
		$this->assertEquals(
			'Monitor 14 in., 1× DVI 2× USB 1× VGA',
			$summary
		);

		return $summary;
	}

	public function testMonitorNoCommercialNoPowerNoPorts() {
		$item = new Item('V9');
		$item
			->addFeature(new Feature('type', 'monitor'))
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('diagonal-inch', (double)14));

		$summary = MonitorSummarizer::summarize($item);
		$this->assertEquals(
			'Monitor 14 in.',
			$summary
		);

		return $summary;
	}

	public function testMonitorNothing() {
		$item = new Item('V9');
		$item
			->addFeature(new Feature('type', 'monitor'))
			->addFeature(new Feature('working', 'yes'));

		$summary = MonitorSummarizer::summarize($item);
		$this->assertEquals(
			'Monitor',
			$summary
		);

		return $summary;
	}

}
