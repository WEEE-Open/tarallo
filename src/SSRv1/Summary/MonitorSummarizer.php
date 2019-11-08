<?php


namespace WEEEOpen\Tarallo\SSRv1\Summary;


use WEEEOpen\Tarallo\ItemWithFeatures;
use WEEEOpen\Tarallo\SSRv1\FeaturePrinter;

class MonitorSummarizer implements Summarizer {
	public static function summarize(ItemWithFeatures $item): string {
		$type = $item->getFeature('type');
		$psuAmpere = $item->getFeature('psu-ampere');
		$psuVolt = $item->getFeature('psu-volt');
		$diagonalInch = $item->getFeature('diagonal-inch');

		$type = FeaturePrinter::printableValue($type);
		$type .= $diagonalInch ? ' ' . FeaturePrinter::printableValue($diagonalInch) : '';


		$commercial = PartialSummaries::summarizeCommercial($item);
		$commercial = $commercial ? ", $commercial" : '';

		$ports = PartialSummaries::summarizePorts($item, false, ' ');
		$ports = $ports ? ", $ports" : '';

		$power = $psuAmpere ? ' ' . FeaturePrinter::printableValue($psuAmpere) : '';
		$power .= $psuVolt ? ' ' . FeaturePrinter::printableValue($psuVolt) : '';
		$powerPorts = PartialSummaries::summarizePowerconnectors($item, false, ' ');
		$power .= $powerPorts ? " $powerPorts" : '';
		$power = $power ? ",$power" : '';

		$pretty = $type . $ports . $power . $commercial;
		return $pretty;
	}
}