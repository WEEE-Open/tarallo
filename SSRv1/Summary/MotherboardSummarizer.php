<?php


namespace WEEEOpen\Tarallo\SSRv1\Summary;


use WEEEOpen\Tarallo\Server\ItemWithFeatures;
use WEEEOpen\Tarallo\SSRv1\FeaturePrinter;

class MotherboardSummarizer {
	public static function summarize(ItemWithFeatures $item): string {
		$type = $item->getFeature('type');
		$color = $item->getFeature('color');

		$ports = PartialSummaries::summarizePorts($item);
		$ports = $ports ? ", $ports" : '';

		$sockets = PartialSummaries::summarizeSockets($item);

		$color = $color = $color ? ', ' . FeaturePrinter::printableValue($color) : '';

		$commercial = PartialSummaries::summarizeCommercial($item);
		$commercial = $commercial ? ", $commercial" : '';

		$pretty =  $type . $ports . $sockets . $color . $commercial;
		return $pretty;
	}
}