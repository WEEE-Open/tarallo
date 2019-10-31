<?php


namespace WEEEOpen\Tarallo\SSRv1\Summary;


use WEEEOpen\Tarallo\Server\ItemWithFeatures;
use WEEEOpen\Tarallo\SSRv1\FeaturePrinter;

class MonitorSummarizer implements Summarizer {
	public static function summarize(ItemWithFeatures $item): string {
		//TODO: Diagonal-inch
		$type = $item->getFeature('type');
		$psuAmpere = $item->getFeature('psu-ampere');
		$psuVolt = $item->getFeature('psu-volt');

		$type = FeaturePrinter::printableValue($type);
		$type .= $psuAmpere ? ' ' . FeaturePrinter::printableValue($psuAmpere) : '';
		$type .= $psuVolt ? ' ' . FeaturePrinter::printableValue($psuVolt) : '';


		$commercial = PartialSummaries::summarizeCommercial($item);
		$commercial = $commercial ? ", $commercial" : '';

		$ports = PartialSummaries::summarizePorts($item, false, ' ');
		$ports = $ports ? ", $ports" : '';

		$power = PartialSummaries::summarizePowerconnectors($item, false, ' ');
		$power = $power ? ", $power" : '';

		$pretty = $type . $power .  $ports . $commercial;
		return $pretty;
	}
}