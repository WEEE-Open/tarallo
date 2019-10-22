<?php

use PHPUnit\Framework\TestCase;
use WEEEOpen\Tarallo\Feature;
use WEEEOpen\Tarallo\Item;
use WEEEOpen\Tarallo\SSRv1\Summary\CpuSummarizer;

/**
 * @covers \WEEEOpen\Tarallo\SSRv1\Summary\CpuSummarizer
 */
class CpuSummarizerTest extends TestCase {
	public function testCpu() {
		$item = new Item('C123');
		$item
			->addFeature(new Feature('brand', 'Intel'))
			->addFeature(new Feature('model', 'Core 2 Duo E6400'))
			->addFeature(new Feature('cpu-socket', 'lga775'))
			->addFeature(new Feature('core-n', 2))
			->addFeature(new Feature('thread-n', 2))
			->addFeature(new Feature('frequency-hertz',2130000000))
			->addFeature(new Feature('isa', 'x86-64'))
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('owner', 'Area IT'))
			->addFeature(new Feature('type', 'cpu'));

		$summary = CpuSummarizer::summarize($item);
		$this->assertEquals(
			'CPU x86 64 bit, 2 Cores 2 Threads @ 2.13 GHz, Socket LGA775, Intel Core 2 Duo E6400',
			$summary
		);

		return $summary;
	}

	public function testCpu2c4t() {
		$item = new Item('C123');
		$item
			->addFeature(new Feature('brand', 'Intel'))
			->addFeature(new Feature('model', 'Core 2 Duo E6400'))
			->addFeature(new Feature('cpu-socket', 'lga775'))
			->addFeature(new Feature('core-n', 2))
			->addFeature(new Feature('thread-n', 4))
			->addFeature(new Feature('frequency-hertz',2130000000))
			->addFeature(new Feature('isa', 'x86-64'))
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('owner', 'Area IT'))
			->addFeature(new Feature('type', 'cpu'));

		$summary = CpuSummarizer::summarize($item);
		$this->assertEquals(
			'CPU x86 64 bit, 2 Cores 4 Threads @ 2.13 GHz, Socket LGA775, Intel Core 2 Duo E6400',
			$summary
		);

		return $summary;
	}

	public function testCpuNoFreq() {
		$item = new Item('C123');
		$item
			->addFeature(new Feature('brand', 'Intel'))
			->addFeature(new Feature('model', 'Core 2 Duo E6400'))
			->addFeature(new Feature('cpu-socket', 'lga775'))
			->addFeature(new Feature('core-n', 2))
			->addFeature(new Feature('thread-n', 2))
			->addFeature(new Feature('isa', 'x86-64'))
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('owner', 'Area IT'))
			->addFeature(new Feature('type', 'cpu'));

		$summary = CpuSummarizer::summarize($item);
		$this->assertEquals('CPU x86 64 bit, 2 Cores 2 Threads, Socket LGA775, Intel Core 2 Duo E6400', $summary);

		return $summary;
	}

	public function testCpuNoThreads() {
		$item = new Item('C123');
		$item
			->addFeature(new Feature('brand', 'Intel'))
			->addFeature(new Feature('model', 'Core 2 Duo E6400'))
			->addFeature(new Feature('cpu-socket', 'lga775'))
			->addFeature(new Feature('core-n', 2))
			->addFeature(new Feature('frequency-hertz',2130000000))
			->addFeature(new Feature('isa', 'x86-64'))
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('owner', 'Area IT'))
			->addFeature(new Feature('type', 'cpu'));

		$summary = CpuSummarizer::summarize($item);
		$this->assertEquals(
			'CPU x86 64 bit, 2 Cores @ 2.13 GHz, Socket LGA775, Intel Core 2 Duo E6400',
			$summary
		);

		return $summary;
	}

	public function testCpuNoCores() {
		$item = new Item('C123');
		$item
			->addFeature(new Feature('brand', 'Intel'))
			->addFeature(new Feature('model', 'Core 2 Duo E6400'))
			->addFeature(new Feature('cpu-socket', 'lga775'))
			->addFeature(new Feature('thread-n', 2))
			->addFeature(new Feature('frequency-hertz',2130000000))
			->addFeature(new Feature('isa', 'x86-64'))
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('owner', 'Area IT'))
			->addFeature(new Feature('type', 'cpu'));

		$summary = CpuSummarizer::summarize($item);
		$this->assertEquals(
			'CPU x86 64 bit, 2 Threads @ 2.13 GHz, Socket LGA775, Intel Core 2 Duo E6400',
			$summary
		);

		return $summary;
	}

	public function testCpuNoCoresNoThreads() {
		$item = new Item('C123');
		$item
			->addFeature(new Feature('brand', 'Intel'))
			->addFeature(new Feature('model', 'Core 2 Duo E6400'))
			->addFeature(new Feature('cpu-socket', 'lga775'))
			->addFeature(new Feature('frequency-hertz',2130000000))
			->addFeature(new Feature('isa', 'x86-64'))
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('owner', 'Area IT'))
			->addFeature(new Feature('type', 'cpu'));

		$summary = CpuSummarizer::summarize($item);
		$this->assertEquals('CPU x86 64 bit, 2.13 GHz, Socket LGA775, Intel Core 2 Duo E6400', $summary);

		return $summary;
	}

	public function testCpuNoCoresNoThreadsNoFreq() {
		$item = new Item('C123');
		$item
			->addFeature(new Feature('brand', 'Intel'))
			->addFeature(new Feature('model', 'Core 2 Duo E6400'))
			->addFeature(new Feature('cpu-socket', 'lga775'))
			->addFeature(new Feature('isa', 'x86-64'))
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('owner', 'Area IT'))
			->addFeature(new Feature('type', 'cpu'));

		$summary = CpuSummarizer::summarize($item);
		$this->assertEquals('CPU x86 64 bit, Socket LGA775, Intel Core 2 Duo E6400', $summary);

		return $summary;
	}

	public function testCpuNoModel() {
		$item = new Item('C123');
		$item
			->addFeature(new Feature('brand', 'Intel'))
			->addFeature(new Feature('cpu-socket', 'lga775'))
			->addFeature(new Feature('core-n', 2))
			->addFeature(new Feature('thread-n', 2))
			->addFeature(new Feature('frequency-hertz',2130000000))
			->addFeature(new Feature('isa', 'x86-64'))
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('owner', 'Area IT'))
			->addFeature(new Feature('type', 'cpu'));

		$summary = CpuSummarizer::summarize($item);
		$this->assertEquals('CPU x86 64 bit, 2 Cores 2 Threads @ 2.13 GHz, Socket LGA775, Intel', $summary);

		return $summary;
	}

	public function testCpuNoBrand() {
		$item = new Item('C123');
		$item
			->addFeature(new Feature('model', 'Core 2 Duo E6400'))
			->addFeature(new Feature('cpu-socket', 'lga775'))
			->addFeature(new Feature('core-n', 2))
			->addFeature(new Feature('thread-n', 2))
			->addFeature(new Feature('frequency-hertz',2130000000))
			->addFeature(new Feature('isa', 'x86-64'))
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('owner', 'Area IT'))
			->addFeature(new Feature('type', 'cpu'));

		$summary = CpuSummarizer::summarize($item);
		$this->assertEquals(
			'CPU x86 64 bit, 2 Cores 2 Threads @ 2.13 GHz, Socket LGA775, Core 2 Duo E6400',
			$summary
		);

		return $summary;
	}

	public function testCpuNoCommercial() {
		$item = new Item('C123');
		$item
			->addFeature(new Feature('cpu-socket', 'lga775'))
			->addFeature(new Feature('core-n', 2))
			->addFeature(new Feature('thread-n', 2))
			->addFeature(new Feature('frequency-hertz',2130000000))
			->addFeature(new Feature('isa', 'x86-64'))
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('owner', 'Area IT'))
			->addFeature(new Feature('type', 'cpu'));

		$summary = CpuSummarizer::summarize($item);
		$this->assertEquals('CPU x86 64 bit, 2 Cores 2 Threads @ 2.13 GHz, Socket LGA775', $summary);

		return $summary;
	}

	public function testCpuNoSocket() {
		$item = new Item('C123');
		$item
			->addFeature(new Feature('brand', 'Intel'))
			->addFeature(new Feature('model', 'Core 2 Duo E6400'))
			->addFeature(new Feature('core-n', 2))
			->addFeature(new Feature('thread-n', 2))
			->addFeature(new Feature('frequency-hertz',2130000000))
			->addFeature(new Feature('isa', 'x86-64'))
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('owner', 'Area IT'))
			->addFeature(new Feature('type', 'cpu'));

		$summary = CpuSummarizer::summarize($item);
		$this->assertEquals('CPU x86 64 bit, 2 Cores 2 Threads @ 2.13 GHz, Intel Core 2 Duo E6400', $summary);

		return $summary;
	}

	public function testCpuNoSocketNoCommercial() {
		$item = new Item('C123');
		$item
			->addFeature(new Feature('core-n', 2))
			->addFeature(new Feature('thread-n', 2))
			->addFeature(new Feature('frequency-hertz',2130000000))
			->addFeature(new Feature('isa', 'x86-64'))
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('owner', 'Area IT'))
			->addFeature(new Feature('type', 'cpu'));

		$summary = CpuSummarizer::summarize($item);
		$this->assertEquals('CPU x86 64 bit, 2 Cores 2 Threads @ 2.13 GHz', $summary);

		return $summary;
	}

	public function testCpuNoTechnical() {
		$item = new Item('C123');
		$item
			->addFeature(new Feature('brand', 'Intel'))
			->addFeature(new Feature('model', 'Core 2 Duo E6400'))
			->addFeature(new Feature('cpu-socket', 'lga775'))
			->addFeature(new Feature('isa', 'x86-64'))
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('owner', 'Area IT'))
			->addFeature(new Feature('type', 'cpu'));

		$summary = CpuSummarizer::summarize($item);
		$this->assertEquals('CPU x86 64 bit, Socket LGA775, Intel Core 2 Duo E6400', $summary);

		return $summary;
	}

	public function testCpuNoTechnicalNoSocket() {
		$item = new Item('C123');
		$item
			->addFeature(new Feature('brand', 'Intel'))
			->addFeature(new Feature('model', 'Core 2 Duo E6400'))
			->addFeature(new Feature('isa', 'x86-64'))
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('owner', 'Area IT'))
			->addFeature(new Feature('type', 'cpu'));

		$summary = CpuSummarizer::summarize($item);
		$this->assertEquals('CPU x86 64 bit, Intel Core 2 Duo E6400', $summary);

		return $summary;
	}

	public function testCpuNoArchitecture() {
		$item = new Item('C123');
		$item
			->addFeature(new Feature('brand', 'Intel'))
			->addFeature(new Feature('model', 'Core 2 Duo E6400'))
			->addFeature(new Feature('cpu-socket', 'lga775'))
			->addFeature(new Feature('core-n', 2))
			->addFeature(new Feature('thread-n', 2))
			->addFeature(new Feature('frequency-hertz',2130000000))
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('owner', 'Area IT'))
			->addFeature(new Feature('type', 'cpu'));

		$summary = CpuSummarizer::summarize($item);
		$this->assertEquals(
			'CPU (Architecture?), 2 Cores 2 Threads @ 2.13 GHz, Socket LGA775, Intel Core 2 Duo E6400',
			$summary
		);

		return $summary;
	}

	public function testCpuAlmostUnknown() {
		$item = new Item('C123');
		$item
			->addFeature(new Feature('brand', 'Intel'))
			->addFeature(new Feature('model', 'Pentium III'))
			->addFeature(new Feature('type', 'cpu'));

		$summary = CpuSummarizer::summarize($item);
		$this->assertEquals('CPU (Architecture?), Intel Pentium III', $summary);

		return $summary;
	}

	public function testCpuNoNothing() {
		$item = new Item('C123');
		$item
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('owner', 'Area IT'))
			->addFeature(new Feature('type', 'cpu'));

		$summary = CpuSummarizer::summarize($item);
		$this->assertEquals('CPU', $summary);

		return $summary;
	}

	public function testCpuWeirdBehavior() {
		$item = new Item('C140');
		$item
			->addFeature(new Feature('type', 'cpu'))
			->addFeature(new Feature('brand', 'Intel'))
			->addFeature(new Feature('cpu-socket', 'slot1'))
			->addFeature(new Feature('frequency-hertz',700000000))
			->addFeature(new Feature('isa', 'x86-32'))
			->addFeature(new Feature('model', 'Pentium 3'))
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('owner', 'l\'asd'));

		// Incorrect output:
		// "CPU x86 32 bit, 700 MHz, Intel Pentium 3, Socket Slot"
		$summary = CpuSummarizer::summarize($item);
		$this->assertEquals('CPU x86 32 bit, 700 MHz, Socket Slot 1, Intel Pentium 3', $summary);

		return $summary;
	}
}
