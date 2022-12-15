<?php

namespace WEEEOpen\Tarallo\SSRv1\Summary;

use WEEEOpen\Tarallo\ItemWithFeatures;
use WEEEOpen\Tarallo\SSRv1\FeaturePrinter;

class MonitorSummarizer implements Summarizer
{
	public static function summarize(ItemWithFeatures $item): array
	{
		$type = $item->getFeature('type');
		$psuAmpere = $item->getFeature('psu-ampere');
		$psuVolt = $item->getFeature('psu-volt');
		$diagonalInch = $item->getFeature('diagonal-inch');

		$type = FeaturePrinter::printableValue($type);
		$type .= $diagonalInch ? ' ' . FeaturePrinter::printableValue($diagonalInch) : '';

		$commercial = PartialSummaries::summarizeCommercial($item);

		$ports = PartialSummaries::summarizePorts($item, false, ', ');

		$power = $psuAmpere ? FeaturePrinter::printableValue($psuAmpere) : '';
		$power .= $psuVolt ? ($power ? ' ' : '') . FeaturePrinter::printableValue($psuVolt) : '';
		$powerPorts = PartialSummaries::summarizePowerconnectors($item, false, ' ');
		$power .= $powerPorts ? " $powerPorts" : '';

		return array_filter([$type, $ports, $power, $commercial]);
	}
}
