<?php

use WEEEOpen\Tarallo\Feature;
use WEEEOpen\Tarallo\Item;
use WEEEOpen\Tarallo\SSRv1\Summary\GraphicCardSummarizer;
use WEEEOpen\TaralloTest\SSRv1\Summary\SummarizerTestCase;

/**
 * @covers \WEEEOpen\Tarallo\SSRv1\Summary\GraphicCardSummarizer
 */
class GraphicCardSummarizerTest extends SummarizerTestCase
{
	public function testGraphicCard()
	{
		$item = new Item('SG69');
		$item
			->addFeature(new Feature('brand', 'Nvidia'))
			->addFeature(new Feature('model', 'GeForce4 MX 440'))
			->addFeature(new Feature('color', 'green'))
			->addFeature(new Feature('owner', 'DISAT'))
			->addFeature(new Feature('s-video-ports-n', 1))
			->addFeature(new Feature('dvi-ports-n', 2))
			->addFeature(new Feature('hdmi-ports-n', 1))
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('pci-low-profile', 'no'))
			->addFeature(new Feature('sn', '314159265358'))
			->addFeature(new Feature('type', 'graphics-card'))
			->addFeature(new Feature('pcie-sockets-n', 1))
			->addFeature(new Feature('capacity-byte', 134217728));

		$summary = GraphicCardSummarizer::summarize($item);
		$this->assertArrayEquals(
			["Graphics card PCI Express 128 MiB", "2× DVI, 1× HDMI, 1× S-Video", "Green", "Nvidia GeForce4 MX 440"],
			$summary
		);
	}

	public function testGraphicCardNoColor()
	{
		$item = new Item('SG69');
		$item
			->addFeature(new Feature('brand', 'Nvidia'))
			->addFeature(new Feature('model', 'GeForce4 MX 440'))
			->addFeature(new Feature('owner', 'DISAT'))
			->addFeature(new Feature('s-video-ports-n', 1))
			->addFeature(new Feature('dvi-ports-n', 2))
			->addFeature(new Feature('hdmi-ports-n', 1))
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('pci-low-profile', 'no'))
			->addFeature(new Feature('sn', '314159265358'))
			->addFeature(new Feature('type', 'graphics-card'))
			->addFeature(new Feature('pcie-sockets-n', 1))
			->addFeature(new Feature('capacity-byte', 134217728));

		$summary = GraphicCardSummarizer::summarize($item);
		$this->assertArrayEquals(
			["Graphics card PCI Express 128 MiB", "2× DVI, 1× HDMI, 1× S-Video", "Nvidia GeForce4 MX 440"],
			$summary
		);
	}

	public function testGraphicCardNoBrand()
	{
		$item = new Item('SG69');
		$item
			->addFeature(new Feature('model', 'GeForce4 MX 440'))
			->addFeature(new Feature('color', 'green'))
			->addFeature(new Feature('owner', 'DISAT'))
			->addFeature(new Feature('s-video-ports-n', 1))
			->addFeature(new Feature('dvi-ports-n', 2))
			->addFeature(new Feature('hdmi-ports-n', 1))
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('pci-low-profile', 'no'))
			->addFeature(new Feature('sn', '314159265358'))
			->addFeature(new Feature('type', 'graphics-card'))
			->addFeature(new Feature('pcie-sockets-n', 1))
			->addFeature(new Feature('capacity-byte', 134217728));

		$summary = GraphicCardSummarizer::summarize($item);
		$this->assertArrayEquals(
			["Graphics card PCI Express 128 MiB", "2× DVI, 1× HDMI, 1× S-Video", "Green", "GeForce4 MX 440"],
			$summary
		);
	}

	public function testGraphicCardNoModel()
	{
		$item = new Item('SG69');
		$item
			->addFeature(new Feature('brand', 'Nvidia'))
			->addFeature(new Feature('color', 'green'))
			->addFeature(new Feature('owner', 'DISAT'))
			->addFeature(new Feature('s-video-ports-n', 1))
			->addFeature(new Feature('dvi-ports-n', 2))
			->addFeature(new Feature('hdmi-ports-n', 1))
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('pci-low-profile', 'no'))
			->addFeature(new Feature('sn', '314159265358'))
			->addFeature(new Feature('type', 'graphics-card'))
			->addFeature(new Feature('pcie-sockets-n', 1))
			->addFeature(new Feature('capacity-byte', 134217728));

		$summary = GraphicCardSummarizer::summarize($item);
		$this->assertArrayEquals(
			["Graphics card PCI Express 128 MiB", "2× DVI, 1× HDMI, 1× S-Video", "Green", "Nvidia"],
			$summary
		);
	}

	public function testGraphicCardNoCommercial()
	{
		$item = new Item('SG69');
		$item
			->addFeature(new Feature('color', 'green'))
			->addFeature(new Feature('owner', 'DISAT'))
			->addFeature(new Feature('s-video-ports-n', 1))
			->addFeature(new Feature('dvi-ports-n', 2))
			->addFeature(new Feature('hdmi-ports-n', 1))
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('pci-low-profile', 'no'))
			->addFeature(new Feature('sn', '314159265358'))
			->addFeature(new Feature('type', 'graphics-card'))
			->addFeature(new Feature('pcie-sockets-n', 1))
			->addFeature(new Feature('capacity-byte', 134217728));

		$summary = GraphicCardSummarizer::summarize($item);
		$this->assertArrayEquals(
			["Graphics card PCI Express 128 MiB", "2× DVI, 1× HDMI, 1× S-Video", "Green"],
			$summary
		);
	}

	public function testGraphicCardNoCommercialNoColor()
	{
		$item = new Item('SG69');
		$item
			->addFeature(new Feature('owner', 'DISAT'))
			->addFeature(new Feature('s-video-ports-n', 1))
			->addFeature(new Feature('dvi-ports-n', 2))
			->addFeature(new Feature('hdmi-ports-n', 1))
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('pci-low-profile', 'no'))
			->addFeature(new Feature('sn', '314159265358'))
			->addFeature(new Feature('type', 'graphics-card'))
			->addFeature(new Feature('pcie-sockets-n', 1))
			->addFeature(new Feature('capacity-byte', 134217728));

		$summary = GraphicCardSummarizer::summarize($item);
		$this->assertArrayEquals(
			["Graphics card PCI Express 128 MiB", "2× DVI, 1× HDMI, 1× S-Video"],
			$summary
		);
	}

	public function testGraphicCardNoPorts()
	{
		$item = new Item('SG69');
		$item
			->addFeature(new Feature('brand', 'Nvidia'))
			->addFeature(new Feature('model', 'GeForce4 MX 440'))
			->addFeature(new Feature('color', 'green'))
			->addFeature(new Feature('owner', 'DISAT'))
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('pci-low-profile', 'no'))
			->addFeature(new Feature('sn', '314159265358'))
			->addFeature(new Feature('type', 'graphics-card'))
			->addFeature(new Feature('pcie-sockets-n', 1))
			->addFeature(new Feature('capacity-byte', 134217728));

		$summary = GraphicCardSummarizer::summarize($item);
		$this->assertArrayEquals(
			["Graphics card PCI Express 128 MiB", "Green", "Nvidia GeForce4 MX 440"],
			$summary
		);
	}

	public function testGraphicCardNoPortsNoColor()
	{
		$item = new Item('SG69');
		$item
			->addFeature(new Feature('brand', 'Nvidia'))
			->addFeature(new Feature('model', 'GeForce4 MX 440'))
			->addFeature(new Feature('owner', 'DISAT'))
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('pci-low-profile', 'no'))
			->addFeature(new Feature('sn', '314159265358'))
			->addFeature(new Feature('type', 'graphics-card'))
			->addFeature(new Feature('pcie-sockets-n', 1))
			->addFeature(new Feature('capacity-byte', 134217728));

		$summary = GraphicCardSummarizer::summarize($item);
		$this->assertArrayEquals(
			["Graphics card PCI Express 128 MiB", "Nvidia GeForce4 MX 440"],
			$summary
		);
	}

	public function testGraphicCardNoCapacity()
	{
		$item = new Item('SG69');
		$item
			->addFeature(new Feature('brand', 'Nvidia'))
			->addFeature(new Feature('model', 'GeForce4 MX 440'))
			->addFeature(new Feature('color', 'green'))
			->addFeature(new Feature('owner', 'DISAT'))
			->addFeature(new Feature('s-video-ports-n', 1))
			->addFeature(new Feature('dvi-ports-n', 2))
			->addFeature(new Feature('hdmi-ports-n', 1))
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('pci-low-profile', 'no'))
			->addFeature(new Feature('sn', '314159265358'))
			->addFeature(new Feature('type', 'graphics-card'))
			->addFeature(new Feature('pcie-sockets-n', 1));

		$summary = GraphicCardSummarizer::summarize($item);
		$this->assertArrayEquals(
			["Graphics card PCI Express", "2× DVI, 1× HDMI, 1× S-Video", "Green", "Nvidia GeForce4 MX 440"],
			$summary
		);
	}

	public function testGraphicCardNoSocket()
	{
		$item = new Item('SG69');
		$item
			->addFeature(new Feature('brand', 'Nvidia'))
			->addFeature(new Feature('model', 'GeForce4 MX 440'))
			->addFeature(new Feature('color', 'green'))
			->addFeature(new Feature('owner', 'DISAT'))
			->addFeature(new Feature('s-video-ports-n', 1))
			->addFeature(new Feature('dvi-ports-n', 2))
			->addFeature(new Feature('hdmi-ports-n', 1))
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('pci-low-profile', 'no'))
			->addFeature(new Feature('sn', '314159265358'))
			->addFeature(new Feature('type', 'graphics-card'))
			->addFeature(new Feature('capacity-byte', 134217728));

		$summary = GraphicCardSummarizer::summarize($item);
		$this->assertArrayEquals(
			["Graphics card 128 MiB", "2× DVI, 1× HDMI, 1× S-Video", "Green", "Nvidia GeForce4 MX 440"],
			$summary
		);
	}

	public function testGraphicCardNoSocketNoCapacity()
	{
		$item = new Item('SG69');
		$item
			->addFeature(new Feature('brand', 'Nvidia'))
			->addFeature(new Feature('model', 'GeForce4 MX 440'))
			->addFeature(new Feature('color', 'green'))
			->addFeature(new Feature('owner', 'DISAT'))
			->addFeature(new Feature('s-video-ports-n', 1))
			->addFeature(new Feature('dvi-ports-n', 2))
			->addFeature(new Feature('hdmi-ports-n', 1))
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('pci-low-profile', 'no'))
			->addFeature(new Feature('sn', '314159265358'))
			->addFeature(new Feature('type', 'graphics-card'));

		$summary = GraphicCardSummarizer::summarize($item);
		$this->assertArrayEquals(
			["Graphics card", "2× DVI, 1× HDMI, 1× S-Video", "Green", "Nvidia GeForce4 MX 440"],
			$summary
		);
	}

	public function testGraphicCardNoCommercialNoPorts()
	{
		$item = new Item('SG69');
		$item
			->addFeature(new Feature('color', 'green'))
			->addFeature(new Feature('owner', 'DISAT'))
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('pci-low-profile', 'no'))
			->addFeature(new Feature('sn', '314159265358'))
			->addFeature(new Feature('type', 'graphics-card'))
			->addFeature(new Feature('pcie-sockets-n', 1))
			->addFeature(new Feature('capacity-byte', 134217728));

		$summary = GraphicCardSummarizer::summarize($item);
		$this->assertArrayEquals(
			["Graphics card PCI Express 128 MiB", "Green"],
			$summary
		);
	}

	public function testGraphicCardNoCommercialNoCapacityNoSocket()
	{
		$item = new Item('SG69');
		$item
			->addFeature(new Feature('color', 'green'))
			->addFeature(new Feature('owner', 'DISAT'))
			->addFeature(new Feature('s-video-ports-n', 1))
			->addFeature(new Feature('dvi-ports-n', 2))
			->addFeature(new Feature('hdmi-ports-n', 1))
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('pci-low-profile', 'no'))
			->addFeature(new Feature('sn', '314159265358'))
			->addFeature(new Feature('type', 'graphics-card'));

		$summary = GraphicCardSummarizer::summarize($item);
		$this->assertArrayEquals(
			["Graphics card", "2× DVI, 1× HDMI, 1× S-Video", "Green"],
			$summary
		);
	}

	public function testGraphicCardNoCommercialNoCapacityNoSocketNoPorts()
	{
		$item = new Item('SG69');
		$item
			->addFeature(new Feature('color', 'green'))
			->addFeature(new Feature('owner', 'DISAT'))
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('pci-low-profile', 'no'))
			->addFeature(new Feature('sn', '314159265358'))
			->addFeature(new Feature('type', 'graphics-card'));

		$summary = GraphicCardSummarizer::summarize($item);
		$this->assertArrayEquals(
			["Graphics card", "Green"],
			$summary
		);
	}

	public function testGraphicCardNothing()
	{
		$item = new Item('SG69');
		$item
			->addFeature(new Feature('working', 'yes'))
			->addFeature(new Feature('type', 'graphics-card'));

		$summary = GraphicCardSummarizer::summarize($item);
		$this->assertArrayEquals(
			["Graphics card"],
			$summary
		);
	}
}
