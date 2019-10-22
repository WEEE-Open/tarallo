<?php

namespace WEEEOpen\Tarallo\SSRv1\Summary;


use WEEEOpen\Tarallo\ItemWithFeatures;
use WEEEOpen\Tarallo\SSRv1\FeaturePrinter;

class GraphicCardSummarizer implements Summarizer {

	public static function summarize(ItemWithFeatures $item): string {
		$type = $item->getFeature('type');
		$capacity = $item->getFeature('capacity-byte');
		$color = $item->getFeature('color');


		$type = FeaturePrinter::printableValue($type);
		$socket = PartialSummaries::summarizeSockets($item, true);
		$type .= $socket ? " $socket" : '';
		$type .= $capacity ? ' ' . FeaturePrinter::printableValue($capacity) : '';

		$ports = PartialSummaries::summarizePorts($item, false, ' ');
		$ports = $ports ? ", $ports" : '';

		$color = $color ? ', ' . FeaturePrinter::printableValue($color) : '';

		$commercial = PartialSummaries::summarizeCommercial($item);
		$commercial = $commercial ? ", $commercial" : '';


		$pretty = $type . $ports . $color . $commercial;

		return $pretty;
	}
}
