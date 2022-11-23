<?php

namespace WEEEOpen\Tarallo\SSRv1\Summary;

use WEEEOpen\Tarallo\ItemWithFeatures;
use WEEEOpen\Tarallo\SSRv1\FeaturePrinter;

class GraphicCardSummarizer implements Summarizer
{
	public static function summarize(ItemWithFeatures $item): array
	{
		$type = $item->getFeature('type');
		$capacity = $item->getFeature('capacity-byte');
		$color = $item->getFeature('color');

		$type = FeaturePrinter::printableValue($type);
		$socket = PartialSummaries::summarizeSockets($item, true);
		$type .= $socket ? " $socket" : '';
		$type .= $capacity ? ' ' . FeaturePrinter::printableValue($capacity) : '';

		$ports = PartialSummaries::summarizePorts($item, false, ', ');

		$color = $color ? FeaturePrinter::printableValue($color) : '';

		$commercial = PartialSummaries::summarizeCommercial($item);

		return array_filter([$type, $ports, $color, $commercial]);
	}
}
