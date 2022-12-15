<?php

namespace WEEEOpen\Tarallo\SSRv1\Summary;

use WEEEOpen\Tarallo\ItemWithFeatures;
use WEEEOpen\Tarallo\SSRv1\FeaturePrinter;

class SimpleDeviceSummarizer implements Summarizer
{
	public static function summarize(ItemWithFeatures $item): array
	{
		$type = FeaturePrinter::printableValue($item->getFeature('type'));
		$ports = PartialSummaries::summarizePorts($item, false, ' ');
//		$sockets = PartialSummaries::summarizeSockets($item, true, ' ');
		$commercial = PartialSummaries::summarizeCommercial($item);
		$color = $item->getFeature('color');

		return array_filter([$type, $ports, $color ? FeaturePrinter::printableValue($color) : '', $commercial ]);
	}
}
